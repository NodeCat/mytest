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
        
        //$map['company_id'] = $search['company_id'];
        $map['wh_id'] = $session('user.wh_id');
        $map['line_id'] = $search['line'];
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
        $map['delivery_date'] = $search['date'];
        $map['process_type'] = $search['type'];
        $map['status'] = 5; //状态 分拣完成
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['msg'] = '没有符合符号条件的订单';
            return $return;
        }
        $data = array(); //订单筛选条件
        foreach ($result as $value) {
            $data[] = $value['refer_code'];
        }
        //获取订单详情
        $order = D('Order', 'Logic');
        $order_info = $order->getOrderInfoByOrderIdArr($data);
        if ($order_info['status'] == false) {
            $return['msg'] = $order_info['msg'];
            return $return;
        }
        $list = array();
        $this->format_data($order_info['list']);
        $return['msg'] = '成功';
        $return['status'] = true;
        $return['list'] = $order_info['list'];
        return $return;
    }
    
    /**
     * 订单筛选字段验证
     * @param array $post 筛选条件
     * @return array
     */
    public function order_lists($post) {
        $return = array('status' => false, 'msg' => '');

        if (empty($post['company_id'])) {
            $return['msg'] = '请选择系统';
            return $return;
        }
        /*if (empty($post['wh_id'])) {
            $return['msg'] = '请选择仓库';
            return $return;
        }*/
        if (empty($post['type'])) {
            $return['msg'] = '请选择订单类型';
            return $return;
        }
        if (empty($post['line'])) {
            $return['msg'] = '请选择线路';
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
    public function format_data(&$data) {
        foreach ($data as $key => &$value) {
            $value['order_id'] = $value['id'];
            $value['line_total'] = count($value['detail']);
             foreach ($value['detail'] as $k => &$v) {
                $value['sku_total'] += $v['quantity'];
                $v['attrs'] = '';
                foreach ($v['spec'] as $spec) {
                    $v['attrs'] .= $spec['name'] . ':' . $spec['val'] . ',';
                }
            }
        }
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
        $map['pid'] = array('in', $dis_id);
        $result = $M->field('bill_out_id')->where($map)->select();
        if (empty($result)) {
            return $return;
        }
        foreach ($result as $value) {
            //格式化数据
            $return[] = $value['bill_out_id'];
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
        $D = D('Order', 'Logic');
        $dis = D('Distribution'); 
        $det = M('stock_wave_distribution_detail');
        $M = M('stock_bill_out');
        foreach ($nid as $value) {
            //判断是否已创建
            foreach ($value as $id) {
                if (empty($id)) {
                    //没有选择订单
                    $return['msg'] = '请选择订单';
                    return $return;
                }
                $map['refer_code'] = $id;
                $sta = $M->field('status')->where($map)->find();
                if ($sta['status'] != 5) { //5 检货完成 只有状态5才可以加入配送单
                    $return['msg'] = '订单已经加入了配送单';
                    return $return;
                }
            }
            //获取订单详细信息
            $result = $D->getOrderInfoByOrderIdArr($value);
            if ($result['status'] == false) {
                $return['msg'] = $result['msg'];
                return $return;
            }
            $result = $result['list'];
            //创建配送单
            $data = array();
            $data['dist_code'] = get_sn('dis'); //配送单号
            $data['total_price'] = 0; //应收金额
            $data['company_id'] = 1;
            $data['order_count'] = count($value); //订单数
            $data['status'] = 1; //状态 未发运
            $data['is_printed'] = 0; //未打印
            $i = 0;
            foreach ($result as $val) {
                $data['total_price'] += $val['total_price']; //总价格
                $data['line_count'] += count($val['detail']); //总种类
                foreach ($val['detail'] as $v) {
                    $data['sku_count'] += $v['quantity']; //sku总数量
                }
                if ($i < 1) {
                    //重复数据  取一次即可
                    $data['line_id'] = $val['line_id']; //路线
                    $data['deliver_date'] = $val['deliver_date']; //配送日期
                    $data['deliver_time'] = $val['deliver_time']; //配送时段
                    $data['wh_id'] = intval($val['warehouse_id']); //所属仓库
                }
                $i ++;
            }
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
            //更新出库单状态
            
            $map['refer_code'] = array('in', $value);
            $data['status'] = 6;
            if ($M->create($data)) {
                $M->where($map)->save();
            }
        }
       
        $return['status'] = true;
        $reurn['msg'] = '成功';
        return $return;
    }
    
    /**
     * 获取所有已分拣订单 并按线路ID统计数量
     * @return array
     */
    public function get_all_orders() {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out');
        $map['type'] = 1; //类型 1销售出库
        $map['status'] = 5; //状态 检货完成
        //$map['wh_id'] = $session('user.wh_id');
        
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['status'] = true;
            $return['msg'] = '没有待发运的订单';
            $return['list'] = array();
            return $return;
        }
        $list = array();
        foreach ($result as $value) {
            if (!isset($list[$value['line_id']])) {
                $list[$value['line_id']] = 1;
            } else {
                $list[$value['line_id']] += 1;
            }
        }
        $list['sum'] = array_sum($list); //总计
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 根据pid获取出库单详情 可批量获取
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
        $result = $M->where($map)->select();
        if (!empty($result)) {
            $return['msg'] = '成功';
            $return['status'] = true;
            $return['list'] = $result;
        }
        
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
            $val['single_price'] . '元',
            $val['close_unit'] == 0 ? '/' . $this->_unit_dict[1] : '/' . $this->_unit_dict[$val['close_unit']],
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
        $line_need_pay = ['应付总价', $item['final_price']];
        if($item['mobile'] == '15084783678' || $item['mobile'] == '18618142363' || $item['mobile'] == '18612118635' || $item['mobile'] == '13520205658') {
            $line_need_pay = ['应付总价', $item['final_price'], 'ka客户月结，司机不用收款'];
        }
    
        $tail_arr = [
        ['订单备注', $item['remarks']],
        ["－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－－"],
        ['订单总价', $item['total_price'], '', '', '', '', '', '', '',  '实收总金额'],
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
            ['预估总价', $item['total_price'], '', '', '', '', '', '', '',  '实收总金额'],
            ['活动优惠', '- ' . $item['minus_amount']],
            ['微信支付优惠', '-' . $item['pay_reduce']],
            ['运费', '+' . $item['deliver_fee']],
            ['应付总价', $item['final_price'], '', '', '', '', '', '', '', '以实际称重为准'],
            ['支付状态：' . $item['pay_status'] . ', 支付方式：' . $item['pay_type']],
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
        $wh = M('warehouse');
        $map['id'] = array('in', $ids);
        $dist_list = $M->where($map)->select();
        //获取所有订单id
        $orderids = $this->get_order_ids_by_dis_id($ids);
        //获取所有订单
        $D = D('Order', 'Logic');
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
            $dist_arr = [];
            $dist_arr[] = array('配送线路单号:' . $dist['dist_code'], '', '', '', '', '', '仓库:' . $dist['warehouse_name'], '', '', '', '', '', '', '');
            $dist_arr[] = array('线路（片区）:' . $dist['line_name'], '', '', '', '', '', '发车时间:' . $dist['deliver_date'] . ($dist['deliver_time'] == 1 ? '上午' : '下午'), '', '', '', '', '', '', '');
            $dist_arr[] = array('订单数:' . count($dist['orders']), '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('订单明细', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = $title_arr;
            foreach ($dist['orders'] as $order) {
                foreach ($order['detail'] as $detail) {
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