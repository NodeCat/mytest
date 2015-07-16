<?php
namespace Wms\Logic;
/**
 * 配送路线逻辑封装
 * @author zhangchaoge
 *
 */
class DistributionLogic {
    
    public static $line = array(); //线路 （缓存线路）
    
    
    /**
     * 订单筛选字段验证
     * @param array $post 筛选条件
     * @return array
     */
    public function order_lists($post) {
        $return = array('status' => false, 'msg' => '');
    
        if (empty($post['stype'])) {
            $return['msg'] = '请选择出库单类型';
            return $return;
        }
        if ($post['stype'] != 1 && $post['stype'] != 4 && $post['stype'] != 5 && $post['stype'] != 3) {
            //1 销售出库 5调拨出库
            $return['msg'] = '目前只能选择销售出库,调拨出库,领用出库,采购正品出库';
            return $return;
        }
        if ($post['stype'] == 1) {
            if (empty($post['type'])) {
                $return['msg'] = '请选择订单类型';
                return $return;
            }
            if (empty($post['date'])) {
                $return['msg'] = '请选择配送时间';
                return $return;
            }
            if (empty($post['time'])) {
                $return['msg'] = '请选择时段';
                return $return;
            }
    
            //时段是否区分
            if ($post['time'] == 3) {
                unset($post['time']);
            }
        }
    
        //获取搜索结果
        $seach_info = $this->search($post);
        if ($seach_info['status'] == false) {
            //搜索失败
            $return['msg'] = $seach_info['msg'];
            return $return;
        }
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['list'] = $seach_info['list'];
        return $return;
    }
    /**
     * 搜索订单
     */
    public function search($search = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($search)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        $M = M('stock_bill_out');
        
        $map['dis_mark'] = 0; //未加入分配单的
        $map['wh_id'] = session('user.wh_id');
        $map['status'] = 1; //状态 1带生产
        $map['type'] = $search['stype']; //出库单类型
        $map['is_deleted'] = 0;
        if (!empty($search['type'])) {
            $map['order_type'] = $search['type']; //订单类型
        }
        if (!empty($search['line'])) {
            $map['line_id'] = $search['line'];
        }
        if (isset($search['time'])) {
            switch ($search['time']) {
                	case 1:
                	    $search['time'] = 'am';
                	    break;
                	case 2:
                	    $search['time'] = 'pm';
                	    break;
            }
            $map['delivery_ampm'] = $search['time'];
        }
        if (!empty($search['date'])) {
            //日期
            $map['delivery_date'] = date('Y-m-d H:i:s', strtotime($search['date']));
        }
        //获取出库单
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['msg'] = '没有符合符号条件的订单';
            return $return;
        }
        if ($search['stype'] == 1) { //销售出库单
            $order_ids = array(); //订单ids
            foreach ($result as $value) {
                $order_ids[] = $value['refer_code'];
            }
            
            //获取订单详情
            $order = D('Common/Order', 'Logic');
            $order_info = $order->getOrderInfoByOrderIdArr($order_ids);
            if ($order_info['status'] == false) {
                $return['msg'] = $order_info['msg'];
                return $return;
            }
            foreach ($order_info['list'] as $key => $val) {
                //增加客服id 创建配送单使用
                foreach ($result as $k => $v) {
                    if ($v['refer_code'] == $val['id']) {
                        $result[$k]['user_id'] = $val['user_id'];
                        break;
                    }
                }
            }
        }
        //result 中为全部复合搜索条件的出库单
        //取出库单ID获取详情信息
        foreach ($result as $index => &$out) {
            //获取出库单详情
            $out['detail'] = $this->get_out_detail($out['id']);
        }
        //整理前端数据
        $list = $this->format_data($result);
        $return['msg'] = '成功';
        $return['status'] = true;
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 订单数据处理
     * @param unknown $data
     * @return multitype:
     */
    public function format_data($out = array()) {
        $return = array();
    
        if (empty($out)) {
            return $return;
        }
    
        foreach ($out as $key => $value) {
            $return[$key]['id'] = $value['id'];
            $return[$key]['out_id'] = $value['id']; //出库单id
            $return[$key]['code'] = $value['code']; //出库单号
            $return[$key]['user_id'] = $value['user_id']; //客服名称
            $return[$key]['order_id'] = $value['refer_code'];//订单id
            $return[$key]['line'] = $this->format_line($value['line_id']); //线路名称
            $return[$key]['address'] = $value['delivery_address']; //地址
            $return[$key]['deliver_date'] = $value['delivery_date'] . ' ' . $value['delivery_time'];//配送时间
            $return[$key]['line_total'] = count($value['detail']); //sku总数
            foreach ($value['detail'] as $k => $val) {
                $return[$key]['sku_total'] += $val['order_qty']; //sku总数
                $return[$key]['detail'][$k]['name'] = $val['pro_name']; //名称
                $return[$key]['detail'][$k]['attrs'] = $val['pro_attrs']; //规格
                $return[$key]['detail'][$k]['quantity'] = $val['order_qty']; //数量
            }
        }
    
        return $return;
    }
    
    /**
     * 替换订单sku信息
     * @param int $dis 配送单ID
     */
    public function replace_sku_info($dis = 0) {
        $return = array();
        
        if (empty($dis)) {
            return $return;
        }
        $M = M('stock_bill_out');
        $det = M('stock_bill_out_detail');
        $distri = M('stock_wave_distribution_detail');
        $map['pid'] = $dis;
        $map['is_deleted'] = 0;
        $detail = $distri->where($map)->select();
        unset($map);
        $result = array();
        $order_ids = array();
        foreach ($detail as $key => $value) {
            $map['id'] = $value['bill_out_id'];
            $map['is_deleted'] = 0;
            $result[$key] = $M->where($map)->find();
            unset($result[$key]['id']);
            $result[$key]['detail'] = $this->get_out_detail($value['bill_out_id']);
            //获取所有关联单号 如果是订单ID 下面会用到
            $order_ids[] = $result[$key]['refer_code'];
            foreach ($result[$key]['detail'] as $sku) {
                //获取所有sku编号 下面获取sku计量单位会用到
                $bill_out_pro_codes[] = $sku['pro_code'];
            }
        }
        
        //获取计量单位
        $bill_out_pro_codes = array_unique($bill_out_pro_codes);
        //这里一次获取所有sku的计量单位信息
        $uom = A('Pms', 'Logic')->get_SKU_field_by_pro_codes($bill_out_pro_codes, count($bill_out_pro_codes));
        
        //给每个sku添加计量单位
        foreach ($result as &$stock_out) {
            //循环出库单
            foreach ($stock_out['detail'] as $k => $stock_out_detail) {
                //循环详情 获取sku编号
                foreach ($uom as $unit) {
                    //循环获取的sku信息
                    if ($stock_out_detail['pro_code'] == $unit['pro_code']) {
                        //匹配到相同时 就赋值给出库单 并跳出循环 进行下一次
                        $stock_out['detail'][$k]['uom_name'] = $unit['uom_name'];
                        break;
                    }
                }
            }
        }
        $Order = A('Common/Order', 'Logic');
        $res = $Order->getOrderInfoByOrderIdArr($order_ids);
        $res = $res['list'];
        $merge = array();
        
        $return = $result;
        if (!empty($res)) {
            //此配送单下单的出库单为订单
            foreach ($res as $val) {
                //循环订单
                foreach ($result as $v) {
                    //循环出库单
                    foreach ($v['detail'] as $index => $stock_detail) {
                        //循环订单详情
                        foreach ($val['detail'] as $order_detail) {
                            //循环出库单详情 发现sku一致则获取结算单位和订货单位
                            if ($order_detail['sku_number'] == $stock_detail['pro_code']) {
                                $v['detail'][$index]['close_unit'] = $order_detail['close_unit'];
                                $v['detail'][$index]['unit_id'] = $order_detail['unit_id'];
                            }
                        }
                    }
                    //合并出库单与订单 此处用出库单中的sku信息替换了订单中的sku 信息
                    if ($val['id'] == $v['refer_code']) {
                       unset($val['detail']);
                       $merge[] = array_merge($v, $val); 
                    }
                }
            }
            $return = $merge;
        }
        return $return;
    }
    
    
    /**
     * 根据出库单ID获取详情信息
     * @param unknown $ids
     */
    public function get_out_detail($ids = array()) {
        $return = array();
        
        if (empty($ids)) {
            return $return;
        }
        $M = M('stock_bill_out_detail');
        $map['pid'] = array('in', $ids);
        $map['is_deleted'] = 0;
        $result = $M->where($map)->select();
        if (empty($result)) {
            return $return;
        }
        foreach ($result as $value) {
            $return[] = $value;
        }
        
        return $return;
    }
    
    /**
     * 根据出库单ID判断PACK区库存是否充足
     * @param int $id 出库单ID
     */
    public function checkout_stock_eg($id = 0) {
        $return = false;
        
        if (empty($id)) {
            return $return;
        }
        $detail = $this->get_out_detail(array($id));
        $area_name = array('PACK');
        $location_ids = A('Location','Logic')->getLocationIdByAreaName($area_name);
        foreach ($detail as $value) {
            $info = array();
            $info['wh_id'] = session('user.wh_id');
            $info['pro_code'] = $value['pro_code'];
            $info['pro_qty'] = $value['order_qty'];
            $info['location_ids'] = $location_ids;
            $result = A('Stock', 'Logic')->outStockBySkuFIFOCheck($info);
            if ($result['status'] <= 0) {
                return $return;
            }
        }
        $return = true;
        return $return;
    }
        
    /**
     * 根据线路id获取线路名称
     * @param number $line_id
     * @return string|Ambigous <string, unknown>
     */
    public function format_line($line_id = -1) {
        $return = '';
        
        if ($line_id == 0) {
            return $return;
        }
        //获取线路
        if (empty($this->line)) {
            $lines = D('Wave', 'Logic');
            $result = $lines->line();
            $this->line = $result;
        }
        
        if ($line_id == -1) {
            //不指定线路id则返回所有
            $return = array();
            $return = $this->line;
            return $return;
        }
        
        foreach ($this->line as $key => $value) {
            if ($key == $line_id) {
                $return = $value;
                break;
            }
        }
        
        return $return;
    }
    
    /**
     * 创建新配送单
     * @param array $ids 订单id组
     */
    public function add_distributioin($ids = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($ids['ids'])) {
            $return['msg'] = '没有选择订单';
            return $return;
        }
        $nid = array();
        //字符串处理
        foreach ($ids['ids'] as $values) {
            $nid[] = explode(',', $values);
        }
        $D = D('Common/Order', 'Logic');
        $dis = D('Distribution'); 
        $det = M('stock_wave_distribution_detail');
        $M = M('stock_bill_out');
        foreach ($nid as $value) {
            $map = array();
            $map['id'] = array('in', $value);
            $map['is_deleted'] = 0;
            $stock_out = $M->where($map)->select();
            if (empty($stock_out)) {
                $return['msg'] = '不存在的出库单';
                return $return;
            }
            $send_date = array();
            $send_type = array();
            $break = false;
            foreach ($stock_out as &$con) {
                if ($con['dis_mark'] == 1 || $con['status'] != 1) {
                    //1 已分拨 不可再次加入配送单 状态必须为 1带生产
                    $return['msg'] = '此出库单已经加入了配送单';
                    return $return;
                }
                //获取出库单详情
                $con['detail'] = $this->get_out_detail(array($con['id']));
                //获取发送日期 判断选择日期是否为同一天用
                if ($con['type'] == 1) {
                    //销售出库单
                    $send_date[] = $con['delivery_date'];
                }
                //获取类型 判断类型是否为统一
                $send_type[] = $con['type'];
            }
            $prm_type = array_unique($send_type);
            if (count($prm_type) > 1) {
                //选择的不是统一类型
                $return['msg'] = '请选择相同类型的出库单';
                return $return;
            }
            if (!empty($send_date)) {
                $prm_date = array_unique($send_date);
                if (count($prm_date) > 1) {
                    //选择的不是同一天
                    $return['msg'] = '请选择相同发运日期的订单';
                    return $return;
                }
            }  
            //创建配送单
            $data = array();
            $data['dist_code'] = get_sn('dis'); //配送单号
            $data['company_id'] = 1;
            $data['order_count'] = count($value); //订单数
            $data['status'] = 1; //状态 未发运
            $data['is_printed'] = 0; //未打印
            $data['line_count'] = 0; //总种类
            $data['line_id'] = ''; //路线
            $data['sku_count'] = 0; //sku总数量
            $data['total_price'] = 0;  //总价格
            $i = 0;
            foreach ($stock_out as $val) {
                $data['line_count'] += count($val['detail']); //总种类
                $data['line_id'] .= $val['line_id'] . ','; //路线
                $data['total_price'] += $val['total_amount']; //总价格
                foreach ($val['detail'] as $v) {
                    $data['sku_count'] += $v['order_qty']; //sku总数量
                } 
                if ($i < 1) {
                    //重复数据  取一次即可
                    $data['deliver_date'] = $val['delivery_date']; //配送日期
                    $data['deliver_time'] = $val['delivery_ampm'] == 'am' ? 1 : 2; //配送时段
                    $data['wh_id'] = $val['wh_id']; //所属仓库
                }
                $i ++;
            }
            $data['line_id'] = rtrim($data['line_id'], ',');
            if ($dis->create($data)) {
                //写入操作
                $pid = $dis->add();
            }
            if (!$pid) {
                $return['msg'] = '写入失败';
                return $return;
            }
            
            //创建配送单详情
            $detail = array();
            $detail['created_user'] = session()['user']['uid'];
            $detail['created_time'] = get_time();
            $detail['updated_user'] = session()['user']['uid'];
            $detail['updated_time'] = get_time();
            $detail['is_deleted'] = 0;
            foreach ($value as $vv) {
                $detail['bill_out_id'] = $vv;
                $detail['pid'] = $pid;
                if ($det->create($detail)) {
                    //写入操作
                    $det->add();
                }
            }
            unset($map);
            unset($data);
            //更新出库单配送标为1 已分拨
            
            $map['id'] = array('in', $value);
            $data['dis_mark'] = 1; //已分拨
            if ($M->create($data)) {
                $M->where($map)->save();
            }
            unset($map);
            unset($data);
        }
       
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 根据出库单类型判断库存
     * @param array $ids 加入波此的配送单ids
     */
    public function checkout_stock_by_type($ids = array()) {
        $return = array();
        
        if (empty($ids)) {
            return $return;
        }
        $wave_det = M('stock_wave_detail');
        $stockout_logic = A('StockOut', 'Logic');
        
        $bill_out_id = array(); //出库单id
        //获取详情
        $map['pid'] = array('in', $ids);
        $map['is_deleted'] = 0;
        $detail = M('stock_wave_distribution_detail')->where($map)->select();
        if (empty($detail)) {
            return $return;
        }
        unset($map);
        foreach ($detail as $value) {
            $map['bill_out_id'] = $value['bill_out_id'];
            $map['is_deleted'] = 0;
            $result = $wave_det->where($map)->find();
            if (empty($result)) {
                //只加入带生产状态的出库单（没有加入波次）
                $bill_out_id[] = $value['bill_out_id'];
            }
        }
        if (empty($bill_out_id)) {
            return $return;
        }
        unset($map);
        $map['id'] = array('in', $bill_out_id);
        $res = M('stock_bill_out')->where($map)->select();
        $merge = array();
        
        foreach ($res as $val) {
            //分离不同类型的出库单
            $merge[$val['type']][] = $val['id'];
        }

        $trueArr = array();
        $falseArr = array();
        foreach ($merge as $key => $v) {
            //用于多种类型出库单库存判断扩展
            $type = $this->get_stock_bill_out_type($key);
            if ($type[$key] == 'RTSG') {
                //采购正品退货单 可以不指定库位，指定批次出库 liuguangping
                $idsArr = array();
                $idsArr['tureResult'] = array();
                $idsArr['falseResult'] = array();
                foreach ($v as $outId) {
                    $batch_codeArr = M('stock_bill_out_detail')->field('batch_code')->where(array('pid'=>$outId))->find();
                    if($batch_codeArr){
                        $idsPurchaseArr = $stockout_logic->enoughaResult( $outId , null, $batch_codeArr['batch_code']);
                        if($idsPurchaseArr['tureResult']){
                            $idsArr['tureResult'] = array_merge($idsArr['tureResult'], explode(',', $idsPurchaseArr['tureResult']));
                        }
                        if($idsPurchaseArr['falseResult']){
                            $idsArr['falseResult'] = array_merge($idsArr['falseResult'],$idsPurchaseArr['falseResult']);
                        }
                    }
                    
                }
                if($idsArr['tureResult']){
                    $idsArr['tureResult'] = implode(',', $idsArr['tureResult']);
                }
            } else {
                $idsArr = $stockout_logic->enoughaResult(implode(',', $v));
            }

            $trueArr = array_merge($trueArr, explode(',', $idsArr['tureResult']));
            $falseArr = array_merge($falseArr, $idsArr['falseResult']);
        }
        $return['trueResult'] = $trueArr;
        $return['falseResult'] = $falseArr;

        return $return;
    }
    
    /**
     * 获取出库但类型
     * @param int $id 类型ID
     */
    public function get_stock_bill_out_type($id = 0) {
        $return = array();
        
        if ($id > 0) {
            $map['id'] = $id;
        } else {
            $map['id'] = array('gt', 0);
        }
        $res = M('stock_bill_out_type')->where($map)->select();
        if (!empty($res)) {
            foreach ($res as $value) {
                $return[$value['id']] = $value['type'];
            }
        }
        return $return;
    }
    
    /**
     * 获取所有可加入配送单的出库单 并按线路ID统计数量
     * @return array
     */
    public function get_all_orders() {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out');
        $map['type'] = array('in', array(1, 3, 4, 5)); //类型 1销售出库 3 采购正品出库 4领用出库 5调拨出库
        $map['status'] = 1; //状态 1带生产
        $map['dis_mark'] = 0; //配送标示 0未分拨
        $map['wh_id'] = session('user.wh_id');
        //$map['line_id'] = array('gt', 0); //线路ID > 0
        
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['status'] = true;
            $return['msg'] = '没有待发运的订单';
            $return['list'] = array();
            return $return;
        }
        $list = array();
        //统计数量
        foreach ($result as $value) {
            if (!isset($list[$value['line_id']])) {
                $list[$value['line_id']] = 1;
            } else {
                $list[$value['line_id']] += 1;
            }
        }
        $list['sum'] = array_sum($list) > 0 ? array_sum($list) : 0; //总计
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 创建配送单波此
     * @param array $data 创建波此所需数据
     * $data = array(
     *     '' => 主表字段
     *     '' => 主表字段
     *     'detail' => array(
     *          '' => 辅表数据
     *     )
     * )
     */
    public function create_wave($data = array()) {
        $return = 0;
        
        if (empty($data)) {
            return $return;
        }
        $main = M('stock_wave');
        $assist = M('stock_wave_detail');
        //创建主表数据
        $param = array();
        $param['wh_id'] = $data['wh_id'];
        $param['status'] = 200; //待运行
        $param['wave_type'] = 2; //手动创建
        $param['order_count'] = $data['order_count'];
        $param['line_count'] = $data['line_count'];
        $param['total_count'] = $data['sku_count'];
        $param['company_id'] = $data['company_id'];
        $param['created_time'] = get_time();
        $param['created_user'] = session('user.uid');
        $param['updated_time'] = get_time();
        $param['updated_user'] = session('user.uid');
        $param['is_deleted'] = 0;
        if ($main->create($param)) {
            $pid = $main->add();
            if (!$pid) {
                return $return;
            }
        } 
        //创建辅表数据
        foreach ($data['detail'] as $value) {
            $detail = array();
            $detail['pid'] = $pid;
            $detail['bill_out_id'] = $value['bill_out_id'];
            $detail['refer_code'] = $value['refer_code'];
            $detail['created_time'] = get_time();
            $detail['created_user'] = session('user.uid');
            $detail['updated_time'] = get_time();
            $detail['updated_user'] = session('user.uid');
            $detail['is_deleted'] = 0;
            if ($assist->create($detail)) {
                $affected = $assist->add();
            }
        }
        
        $return = $pid;
        return $return;
    }
    
    /**
     * 三联单
     * @param unknown $item
     * @return multitype:
     */
    public function format_export_data($item) {
        //抬头部分
        $csv_data = [
        ['id', $item['id'], '订单编号', "NO.{$item['order_number']}", '', '', '', '线路', $item['line'] ],
        [$item['city_name'], $item['address']],
        [$item['shop_name'], '', $item['realname'], "tel:{$item['mobile']}", '', '下单时间', $item['created_time']],
        ['销售', $item['bd']['name'], '销售电话', "tel:{$item['bd']['mobile']}", '',  '配送时间', "{$item['deliver_date']} {$item['deliver_time']}"],
        ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
        ['货号', '产品名称', '', '', '', '订货数量', '订货单位', '结算单价', '结算单位', '实收数量', '实收金额'],
        ];
    
        //产品列表部分
        $details = [];
        foreach($item['detail'] as $key => $val) {
            $detail   = [
            $val['pro_code'],
            $val['pro_name'],
            '',
            '',
            '',
            $val['order_qty'],
            !empty($val['unit_id']) ? $val['unit_id'] : $val['uom_name'],
            sprintf("%.2f", $val['price']) . '元',
            $val['close_unit'],
            '',
            ''
                    ];
    
            $details[] = $detail;
        }
        //为了让尾部内容可以吸底，需要补充一些空行
        $detail_cnt = count($details);
        while($detail_cnt < 8) {
            $details[] = [];
            $detail_cnt ++;
        }
    
        //合并表头和列表
        $csv_data = array_merge($csv_data, $details);
    
        $should_total_amount = 0.00;
        //应收总金额
        if($item['pay_status'] == 0){
            $should_total_amount = sprintf("%.2f",$item['total_price'] - $item['minus_amount'] - $item['pay_reduce'] + $item['deliver_fee']);
        }
        
        //订单备注
        $remarks = (empty($item['remarks'])) ? '' : substr($item['remarks'],0,120);
        //尾部内容
        $tail_arr = [
        ['订单备注', $remarks],
        ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
        ['订单总价', '',sprintf("%.2f", $item['total_price']), '', '', '', '', '',  '应收总金额' , '', $should_total_amount],
        ['活动优惠', '', '-' . $item['minus_amount'], '', '', '', '', '',  '实收总金额', ''],
        ['微信支付优惠', '', '-' . $item['pay_reduce']],
        ['运费', '', '+' . $item['deliver_fee']],
        $line_need_pay,
        ['支付状态：' . $item['pay_status_cn'] . ', 支付方式：' . $item['pay_type_cn']],
        ['客户签字'],
        [],
        ['客户(白联) 存根(粉联)', '', '', '', '', '', '', '', '售后电话', 'tel:400-8199-491'] ,''
        ];
    
        //大果定制需求
        if($item['site_name'] == '大果') {
            $should_total_amount = 0.00;
            //应收总金额
            if($item['pay_status'] == 0){
                $should_total_amount = sprintf("%.2f", $item['final_price']);
            }
            $tail_arr = [
            ['订单备注', $remarks],
            ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
            ['预估总价', '', sprintf("%.2f", $item['total_price']), '', '', '', '', '',  '实收总金额', ''],
            ['活动优惠', '', '-' . $item['minus_amount']],
            ['微信支付优惠', '', '-' . $item['pay_reduce']],
            ['运费', '', '+' . $item['deliver_fee']],
            ['应付总价', '', $should_total_amount, '', '', '', '', '', '以实际称重为准', ''],
            ['支付状态：' . $item['pay_status_cn'] . ', 支付方式：' . $item['pay_type_cn']],
            ['客户签字'],
            [],
            ['客户(白联) 存根(粉联)', '', '', '', '', '', '', '', '售后电话', 'tel:400-8199-491', '']
            ];
        }
    
        $csv_data = array_merge($csv_data, $tail_arr);
        return $csv_data;
    }
}