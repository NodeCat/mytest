<?php

namespace Tms\Logic;

class ListLogic{

    public function storge(){
        $storge=M('Warehouse');
        $storge=$storge->field('name')->select();
        return $storge;
    }

    /*
     *功   能：根据配送单号和sku号获得最久远的批次
     *输入参数：$dist_code配送单号;$sku_number,SKU号
     *@return: 最久远的批次
    */
    public function get_long_batch($dist_code,$sku_number){
        unset($map);
        $map = array('refer_code' => $dist_code, 'pro_code' => $sku_number);
        $m = M('stock_bill_out_container');
        $batch = $m->distinct(true)->field('batch')->where($map)->order('batch asc')->find();
        return $batch['batch'];
    }

    /*
     *功   能：根据配送单号和sku号获得最近的批次
     *输入参数：$dist_code配送单号;$sku_number,SKU号
     *@return: 最近的批次
    */
    public function get_lasted_batch($dist_code,$sku_number){
        unset($map);
        $map = array('refer_code' => $dist_code, 'pro_code' => $sku_number);
        $m = M('stock_bill_out_container');
        $batch = $m->distinct(true)->field('batch')->where($map)->order('batch desc')->find();
        return $batch['batch'];
    }

    /**
     * [deliveryStatis 签到列表配送统计]
     * @param  array  $dist_ids [配送单ID数组]
     * @return [type]           [description]
     */
    public function deliveryStatis($dist_ids = array())
    {
        $wA = A('Wms/Distribution', 'Logic');
        //未删除配送单列表
        $dist = $wA->distList($dist_ids);
        $dist_ids = array_column($dist, 'id');
        //配送单详情
        $details = $wA->getDistDetailsByPid($dist_ids);
        $statis = array(
            'sign_orders'   => 0,
            'unsign_orders' => 0,
            'sign_finished' => 0,
            'delivering'    => 0,
        );
        foreach ($details as $value) {
            switch ($value['status']) {
                case '1'://配送中
                    $statis['delivering'] ++;
                    break;
                case '2'://已签收
                    $statis['sign_orders'] ++;
                    break;
                case '3'://已拒收
                    $statis['unsign_orders'] ++;
                    break;
                case '4'://已完成
                    $statis['sign_finished'] ++;
                    break;
            }
        }
        return $statis;
    }

    /**
     * 统计配送单的订单状态
     * @param  string  $dist_id  配送单id
     * @return array   $data     返回统计信息
     * @author   jt
     */
    public function deliveryCount($dist_id = '') {
        if($dist_id == '') {
            return FALSE;
        }
            $map['dist_id'] = $dist_id;
            $A = A('Tms/Dist','Logic');
            $bills = $A->billOut($map);
            $orders = $bills['orders'];
            $all_orders     = 0;  //总订单统计
            $sign_orders    = 0;  //签收统计
            $unsign_orders  = 0;  //退货统计
            $sign_finished  = 0;  //已完成
            $sum_deal_price = 0;  //司机回款统计
            $back_lists     = array(); //退货清单
            foreach ($orders as $value) {
                $value = $value['order_info'];
                $all_orders++;
                // 统计实收货款和签收未收订单 
                switch($value['status_cn']){
                    case '已签收':
                        $sign_orders++;
                        if($value['pay_status']=='1') {
                            $value['deal_price'] = 0;
                        }
                        $sum_deal_price += $value['deal_price'];//统计回款数
                        foreach ($value['detail'] as $val) {
                            $back_quantity = $val['quantity']-$val['actual_quantity'];
                            if ($back_quantity != 0) {
                                if(isset($back_lists[$val['sku_number']])){
                                    $back_lists[$val['sku_number']]['quantity'] += $back_quantity;
                                } else {
                                $back_lists[$val['sku_number']]['quantity'] = $back_quantity;
                                }   
                                $back_lists[$val['sku_number']]['unit_id'] = $val['unit_id'];
                                $back_lists[$val['sku_number']]['name']    = $val['name'];
                            }
                        }
                        break;
                    case '已退货':
                        $unsign_orders++;
                        foreach ($value['detail'] as $val) {
                            if(isset($back_lists[$val['sku_number']])){
                                $back_lists[$val['sku_number']]['quantity'] += (int) $val['quantity'];
                            }else{
                                $back_lists[$val['sku_number']]['quantity'] = (int) $val['quantity'];
                            }
                            $back_lists[$val['sku_number']]['unit_id'] = $val['unit_id'];
                            $back_lists[$val['sku_number']]['name']    = $val['name'];
                        }
                        break;
                    case '已完成':
                        $sign_finished++;
                } 
                
            }
            $list['dist_id']        = $dist_id;
            $list['sum_deal_price'] = $sum_deal_price; // 回款数
            $list['sign_orders']    = $sign_orders;    // 已签收
            $list['unsign_orders']  = $unsign_orders;  // 未签收
            $list['sign_finished']  = $sign_finished;  // 已完成
            $list['delivering']     = $all_orders - $sign_orders - $unsign_orders - $sign_finished;//派送中
            // $this->list       = $list;
            // $this->back_lists = $back_lists;

            $data['delivery_count'] = $list;//配送状态统计
            $data['back_lists'] = $back_lists;//退货清单统计
            return $data;
    }
    
    /**
     * 获取司机配送客户的地址详情
     * @param  string $mobile     司机电话号码
     * @param  string $id         签到id（车次id）
     * @return array  $data       返回用户店铺位置信息和客户数量
     * @author   jt
     */
    public function getCustomerAddress($mobile,$id) {
        //只显示当次配送的记录
        $map['id']     = $id;
        $sign_msg = M('tms_sign_list')->where($map)->find();
        unset($map);
        $map['mobile'] = $mobile;
        $map['created_time'] = array('between',$sign_msg['created_time'].','.$sign_msg['delivery_time']);
        $map['status'] = '1';
        //$map['type']   = '0';
        $data = M('tms_delivery')->where($map)->select();
        unset($map);
        $geo_array = array();
        $customer  = array();
        foreach ($data as $key => $value) {
            if ($value['type'] == '0') {
            // dump($value['dist_id']);
                $map['dist_id'] = $value['dist_id'];
                $map['order_by'] = array('created_time' => 'DESC');
                $A = A('Tms/Dist','Logic');
                $bills  = $A->billOut($map);
                $orders = $bills['orders'];
                foreach ($orders as $keys => $values) {
                    $values = $values['order_info'];
                    $values['geo'] = json_decode($values['geo'],TRUE);
                    $customer[$values['user_id']] = 1;//统计商家数量
                    //如果地址为空的话跳过
                    if($values['geo']['lng'] == '' || $values['geo']['lat'] == '' ) {
                        continue;
                    }

                    $geo = $values['geo'];
                    $geo['order_id'] = $value['id'];
                    $geo['user_id']  = $values['user_id'];
                    $geo['address']  = '['.$values['shop_name'].']'.$values['deliver_addr'];
                    $geo['sign_time']= $this->getSignTime($values['user_id'],$value['dist_id']);
                    // 只要有一单还没送完颜色就是0
                    if($values['status_cn']=='已签收' || $values['status_cn']=='已退货' || $values['status_cn']=='已完成' ) {
                        if($geo_array[$values['user_id']]['color_type'] == NULL || $geo_array[$values['user_id']]['color_type'] != 0 ) {
                            $geo['color_type'] = 3;
                        }
                        else{
                            $geo['color_type'] = 0;
                        }      
                    } else {
                        $geo['color_type'] = 0;
                    }
                    unset($map);
                    $map['is_deleted']  = '0';
                    $map['customer_id'] = $values['user_id'];
                    $res = M('tms_report_error')->field('id')->where($map)->find();
                    unset($map);
                    if ($res) {
                        $geo['color_type'] = 2;
                    }
                    $geo_array[$values['user_id']] = $geo;//把地图位置和信息按用户id存储，重复的覆盖               
                }            
            } else {
                $nodes = M('tms_task_node')->where(array('pid' => $value['dist_id']))->select();
                $cus_count += count($nodes);
                foreach ($nodes as &$value) {
                    if ($value['status'] == '2' || $value['status'] == '3' ) {
                        $value['color_type'] = 3;
                    } else {
                        $value['color_type'] = 0;
                    }
                    $value['geo']     = isset($value['geo']) ? json_decode($value['geo'],true) : '';
                    //$value['geo_new'] = isset($value['geo']) ? json_decode($value['geo_new'],true) : '';
                    if ($value['geo'] != '') {
                        $geo = $value['geo'];
                        $geo['address'] = $value['name'];
                        $geo['color_type'] = $value['color_type'];
                        $geo['sign_time']  = $value['sign_time'];
                        $geo_array[]   = $geo;
                    }
                }
            }
        }
        $customer_count = count($customer) + $cus_count;
        $geo_arrays     = array_values($geo_array);
        unset($data);
        $data['customer_count'] = $customer_count;
        $data['geo_arrays']     = $geo_arrays;
        return $data;
    }
  
  /**
     * 计算两个时间的时间差
     * @param  string $begin_time 开始时间
     * @param  string $end_time   结束时间
     * @return array  $res        返回时间差
     * @author   jt
     */
    public function timediff($begin_time,$end_time) {
        $begin_time = strtotime($begin_time);
        $end_time   = strtotime($end_time);
        if($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime   = $end_time;
        }
        else {
        $starttime = $begin_time;
        $endtime   = $begin_time;
        }
        $timediff = $endtime-$starttime;
        $days = intval($timediff/86400);
        $remain = $timediff%86400;
        $hours = intval($remain/3600);
        $remain = $remain%3600;
        $mins = intval($remain/60);
        $secs = $remain%60;
        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
        return $res;
    }

    /**
     * 获取配送单司机签收时间
     * @param  string $customer_id  用户id
     * @param  string $dist_id      配送单id
     * @return string $sign_time    返回签收时间
     * @author   jt
     */
    public function getSignTime($customer_id,$dist_id)
    {   
        if(empty($customer_id) || empty($dist_id)) {
            return '';
        }
            $res = M('stock_bill_out')
                ->alias('b')
                ->field('d.sign_time')
                ->join('stock_wave_distribution_detail d ON b.id = d.bill_out_id')
                ->where(array('b.customer_id' => $customer_id,'d.pid' => $dist_id))
                ->order('d.sign_time DESC')
                ->find();
                
            return $res['sign_time'];
    }

}