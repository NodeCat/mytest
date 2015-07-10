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
        //$map['company_id'] = $search['company_id'];
        $map['order_type'] = $search['type']; //订单类型
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
        $map['delivery_date'] = date('Y-m-d H:i:s', strtotime($search['date']));
        $map['status'] = 1; //状态 1带生产
        //获取出库单
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['msg'] = '没有符合符号条件的订单';
            return $return;
        }
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
                }
            }
                
        }
        //result 中为全部复合搜索条件的出库单
        //取出库单ID获取详情信息
        foreach ($result as $index => &$out) {
            //没有线路的出库单去除
            if ($out['line_id'] <= 0) {
                unset($result[$index]);
            } else {
                //获取出库单详情
                $out['detail'] = $this->get_out_detail($out['id']);
            }
        }
        //整理前端数据
        $list = $this->format_data($result);
        $return['msg'] = '成功';
        $return['status'] = true;
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 替换订单sku信息
     */
    public function replace_sku_info($data = array(), $dis = 0) {
        $return = array();
        
        if (empty($data) || empty($dis)) {
            return $return;
        }
        $M = M('stock_bill_out');
        $det = M('stock_bill_out_detail');
        $distri = M('stock_wave_distribution_detail');
        $map['pid'] = $dis;
        $detail = $distri->where($map)->select();
        foreach ($detail as $val) {
            $detail_ids[] = $val['bill_out_id'];
        }
        unset($map);
        foreach ($data as &$value) {
            $map['refer_code'] = $value['id'];
            $info = $M->where($map)->select();
            foreach ($info as $key => $v) {
                if (!in_array($v['id'], $detail_ids)) {
                    unset($info[$key]);
                }
            }
            
            $info = array_shift($info);
            unset($map);
            $map['pid'] = $info['id'];
            $result = $det->where($map)->select();
            $result = A('Pms','Logic')->add_fields($result,'pro_name');
            $value['detail'] = $result;
            $value['stock_bill_out_code'] = $info['code'];
        }
        $return = $data;
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
        $map['is_deleted'] = array('eq', 0);
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
     * 订单筛选字段验证
     * @param array $post 筛选条件
     * @return array
     */
    public function order_lists($post) {
        $return = array('status' => false, 'msg' => '');

        if (empty($post['type'])) {
            $return['msg'] = '请选择订单类型';
            return $return;
        }
        if (empty($post['time'])) {
            $return['msg'] = '请选择时段';
            return $return;
        }
        if (empty($post['date'])) {
            $return['msg'] = '请选择日期';
            return $return;
        }
        //时段是否区分
        if ($post['time'] == 3) {
            unset($post['time']);
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
     * 根据线路id获取线路名称
     * @param number $line_id
     * @return string|Ambigous <string, unknown>
     */
    public function format_line($line_id = 0) {
        $return = '';
        
        //获取线路
        if (empty($this->line)) {
            $lines = D('Wave', 'Logic');
            $result = $lines->line();
            $this->line = $result;
        }
        
        if (empty($line_id)) {
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
     * 根据配送单id获取订单
     * @param int $dis_id 订单ID
     */
    public function get_order_ids_by_dis_id($dis_id = array()) {
        $return = array();
        
        if (empty($dis_id)) {
            return $return;
        }
        $M = M('stock_wave_distribution_detail');
        $bill_out = M('stock_bill_out');
        $map['pid'] = array('in', $dis_id);
        $map['is_deleted'] = 0;
        $result = $M->field('bill_out_id')->where($map)->select();
        if (empty($result)) {
            return $return;
        }
        $out = array();
        foreach ($result as $value) {
            //格式化数据
            $out[] = $value['bill_out_id'];
        }
        unset($map);
        $map['id'] = array('in', $out);
        $res = $bill_out->where($map)->select();
        if (empty($res)) {
            return $return;
        }
        foreach ($res as $val) {
            $return[$val['id']] = $val['refer_code'];
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
            //判断是否已创建
            foreach ($value as $id) {
                if (empty($id)) {
                    //没有选择订单
                    $return['msg'] = '请选择出库单';
                    return $return;
                }
                $map['id'] = $id;
                $sta = $M->where($map)->find();
                if ($sta['dis_mark'] == 1 || $sta['status'] != 1) {
                    //1 已分拨 不可再次加入配送单 状态必须为 1带生产
                    $return['msg'] = '此出库单已经加入了配送单';
                    return $return;
                }
            }
            unset($map);
            $map['id'] = array('in', $value);
            $stock_out = $M->where($map)->select();
            $send_date = array();
            foreach ($stock_out as &$con) {
                //获取出库单详情
                $info = $this->get_out_detail_by_pids($con['id']);
                $con['detail'] = $info['list'];
                //获取发送日期 判断选择日期是否为同一天用
                $send_date[] = $con['delivery_date'];
            }
            $prm_date = array_unique($send_date);
            if (count($prm_date) > 1) {
                //选择的不是同一天
                $return['msg'] = '请选择相同发运日期的订单';
                return $return;
            }     
            //创建配送单
            $data = array();
            $data['dist_code'] = get_sn('dis'); //配送单号
            $data['company_id'] = 1;
            $data['order_count'] = count($value); //订单数
            $data['status'] = 1; //状态 未发运
            $data['is_printed'] = 0; //未打印
            $i = 0;
            foreach ($stock_out as $val) {
                $data['line_count'] += count($val['detail']); //总种类
                $data['line_id'] .= $val['line_id'] . ','; //路线
                foreach ($val['detail'] as $v) {
                    $data['sku_count'] += $v['order_qty']; //sku总数量
                    $data['total_price'] += $v['price'] * $v['order_qty']; //总价格
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
        }
       
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 获取所有可加入配送单的出库单 并按线路ID统计数量
     * @return array
     */
    public function get_all_orders() {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out');
        //$map['type'] = 1; //类型 1销售出库
        $map['status'] = 1; //状态 1带生产
        $map['dis_mark'] = 0; //配送标示 0未分拨
        $map['wh_id'] = session('user.wh_id');
        $map['line_id'] = array('gt', 0); //线路ID > 0
        
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
     * 根据出库单id获取出库单详情 可批量获取
     * @param array or int $pid 父id
     */
    public function get_out_detail_by_pids($pid) {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out_detail');
        if (empty($pid)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        if (is_array($pid)) {
            $map['pid'] = array('in', $pid);
        } else {
            $map['pid'] = $pid;
        }
        $map['is_deleted'] = array('eq', 0);
        $result = $M->where($map)->select();
        if (!empty($result)) {
            $return['msg'] = '成功';
            $return['status'] = true;
            if (is_array($pid)) {
                foreach ($result as $value) {
                    $return['list'][$value['pid']][] = $value;
                }
            } else {
                foreach ($result as $value) {
                    $return['list'][] = $value;
                }
            }
        }
        
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
            if (!pid) {
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
        ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
        ['货号', '产品名称', '', '', '', '订货数量', '订货单位', '结算单价', '结算单位', '实收数量', '实收金额'],
        ];
    
        //产品列表部分
        $details = [];
        foreach($item['detail'] as $key => $val) {
            $spec_str = $this->format_spec($val['spec']);
            $detail   = [
            $val['sku_number'],
            $val['name'],
            '',
            '',
            '',
            $val['quantity'],
            //$val['unit_id'],
            $val['unit_id'] == 0 ? $this->_unit_dict[1] : $this->_unit_dict[$val['unit_id']],
            sprintf("%.2f", $val['single_price']) . '元',
            $val['close_unit'],
            '',
            ''
                    ];
    
            $details[] = $detail;
        }
        //为了让尾部内容可以吸底，需要补充一些空行
        $detail_cnt = count($details);
        while($detail_cnt < 12) {
            $details[] = [];
            $detail_cnt ++;
        }
    
        //合并表头和列表
        $csv_data = array_merge($csv_data, $details);
    
    
        //尾部内容
        //湖南大厦ka客户的临时需求
        $line_need_pay = ['应付总价', sprintf("%.2f", $item['final_price'])];
        if($item['mobile'] == '15084783678' || $item['mobile'] == '18618142363' || $item['mobile'] == '18612118635' || $item['mobile'] == '13520205658') {
            $line_need_pay = ['应付总价', sprintf("%.2f", $item['final_price']), 'ka客户月结，司机不用收款'];
        }
    
        $tail_arr = [
        ['订单备注', $item['remarks']],
        ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
        ['订单总价', sprintf("%.2f", $item['total_price']), '', '', '', '', '', '', '',  '实收总金额'],
        ['活动优惠', '-' . $item['minus_amount']],
        ['微信支付优惠', '-' . $item['pay_reduce']],
        ['运费', '+' . $item['deliver_fee']],
        $line_need_pay,
        ['支付状态：' . $item['pay_status_cn'] . ', 支付方式：' . $item['pay_type_cn']],
        ['客户签字'],
        [],
        ['客户(白联) 存根(粉联)', '', '', '', '', '', '', '', '', '售后电话', 'tel:400-8199-491']
        ];
    
        //大果定制需求
        if($item['site_name'] == '大果') {
            $tail_arr = [
            ['订单备注', $item['remarks']],
            ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
            ['预估总价', sprintf("%.2f", $item['total_price']), '', '', '', '', '', '', '',  '实收总金额'],
            ['活动优惠', '- ' . $item['minus_amount']],
            ['微信支付优惠', '-' . $item['pay_reduce']],
            ['运费', '+' . $item['deliver_fee']],
            ['应付总价', sprintf("%.2f", $item['final_price']), '', '', '', '', '', '', '', '以实际称重为准'],
            ['支付状态：' . $item['pay_status_cn'] . ', 支付方式：' . $item['pay_type_cn']],
            ['客户签字'],
            [],
            ['客户(白联) 存根(粉联)', '', '', '', '', '', '', '', '', '售后电话', 'tel:400-8199-491']
            ];
        }
    
        $csv_data = array_merge($csv_data, $tail_arr);
        return $csv_data;
    }
    
    /**
     * 三联单数据格式化
     * @param unknown $spec
     * @return string
     */
    public function format_spec($spec = array()) {
        $spec_str = '';
        if(empty($spec)) {
            return $spec_str;
        }
        foreach($spec as $item) {
            if(!empty($item['name']) && $item['name'] != '描述' && !empty($item['val'])) {
                $spec_str .= $item['name'] . ':' . $item['val'] . ';';
            }
        }
        return $spec_str;
    }
    
    /**
     * 配送单
     */
    public function format_distribution($ids = array()) {
        $return = array();
        
        if (empty($ids)) {
            return $return;
        }
        //获取要导出的配送单
        $M = M('stock_wave_distribution');
        $det = M('stock_wave_distribution_detail');
        $wh = M('warehouse');
        $map['id'] = array('in', $ids);
        $dist_list = $M->where($map)->select();
        //获取所有订单id
        $orderids = $this->get_order_ids_by_dis_id($ids);
        //获取所有订单
        $D = D('Common/Order', 'Logic');
        $order_info = $D->getOrderInfoByOrderIdArr($orderids);
        if ($order_info['status'] == false) {
            //获取失败
            return $return;
        }
        $order_info = $order_info['list'];
        unset($map);
        foreach ($dist_list as &$dist) {
            //格式化仓库名
            $map['id'] = $dist['wh_id'];
            $warehouse = $wh->field('name')->where($map)->find();
            $dist['warehouse_name'] = $warehouse['name'];
            
            //获取此配送单下的订单id
            $out_ids = $this->get_order_ids_by_dis_id(array($dist['id']));
            //筛选此配送单下的所有订单
            $dist['orders'] = array();
            foreach ($order_info as $val) {
                if (in_array($val['id'], $out_ids)) {
                    $dist['orders'][] = $val;
                }
            }
            //组合线路片区
            $dist['line_id'] = explode(',', $dist['line_id']);
            foreach ($dis['line_id'] as $line_id) {
                $dist['line_name'] .= $D->format_line($line_id) . '/';
            }
            $dist['line_name'] = rtrim($dist['line_name'], '/');   
                     
            $dist_arr = [];
            $dist_arr[] = array('配送线路单号:' . $dist['dist_code'], '', '', '', '', '', '仓库:' . $dist['warehouse_name'], '', '', '', '', '', '', '');
            $dist_arr[] = array('线路（片区）:' . $dist['line_name'], '', '', '', '', '', '发车时间:' . $dist['deliver_date'] . ($dist['deliver_time'] == 1 ? '上午' : '下午'), '', '', '', '', '', '', '');
            $dist_arr[] = array('订单数:' . count($dist['orders']), '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('订单明细', '', '', '', '', '', '', '', '', '', '', '', '', '');
            //$dist_arr[] = $title_arr;
            foreach ($dist['orders'] as $order) {
                foreach ($orderids as $k => $order_id) {
                    //获取次订单所属出库单ID
                    if ($order['id'] == $order_id) {
                        $outid = $k;
                        break;
                    }
                }
                //获取出库单下sku信息
                $info = $this->get_out_detail_by_pids($outid);
                $info = $info['list'];
                $sku_code = array();
                foreach ($info as $sku_num) {
                    $sku_code[] = $sku_num['pro_code'];
                }
                foreach ($order['detail'] as $detail) {
                    if (!in_array($detail['sku_number'], $sku_code)) {
                        //非出库单中sku则跳过
                        continue;
                    }
                    $specs = '';
                    foreach ($detail['spec'] as $spec) {
                        if($spec['name'] != '描述') {
                            $specs .= $spec['name'] . ':' . $spec['val'];
                        }
                    }
                    $dist_arr[] = array(
                            $order['id'],
                            $order['order_number'],
                            $order['shop_name'],
                            $order['deliver_addr'],
                            $order['mobile'],
                            $order['remarks'],
                            $detail['sku_number'],
                            $detail['name'],
                            $specs,
                            $detail['single_price'],
                            $detail['close_unit'],
                            $detail['quantity'],
                            $detail['unit_id'],
                            '',
                    );
                }
        
            }
        
            $dist_arr[] = array('汇总', '', '', '', '', '', '', '', '', '', '', $dist['sku_count'], '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('货品汇总', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('货品号', '产品名称', '订货数量', '', '', '', '', '', '', '', '', '', '', '');
            foreach ($dist['sku_list'] as $sku) {
                $dist_arr[] = array(
                        $sku['sku_number'],
                        $sku['name'],
                        $sku['quantity'],
                );
            }
            $dist_arr[] = array('汇总', '', $dist['sku_count'], '', '', '', '', '', '', '', '', '', '', '');
        
            $xls_list[] = $dist_arr;
            $sheet_titles[] = $dist['dist_number'];
        }
        
        $return['xls_list'] = $xls_list;
        $return['sheet_titles'] = $sheet_titles;
        return $return;
    }
}
