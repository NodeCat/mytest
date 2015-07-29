<?php

namespace Tms\Logic;

class ListLogic{

	public function storge(){
		$storge=M('Warehouse');
		$storge=$storge->field('name')->select();

		return $storge;
	}
	/*
     *功   能：根据配送单id查询客退入库单状态
     *输入参数：$dist_id,配送单id
    */
    public function view_return_goods_status($dist_id){
        $status = false;
        if(!empty($dist_id)) {
            unset($map);
            //查询条件为配送单id
            $map['pid'] = $dist_id;
            $map['is_deleted'] = 0;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $bill_out_ids = M('stock_wave_distribution_detail')->field('bill_out_id')->where($map)->select();
            //若查出的出库单id非空
            if(!empty($bill_out_ids)){   
                $bill_out_ids = array_column($bill_out_ids,'bill_out_id');
                unset($map);
                $map['id'] = array('in',$bill_out_ids);
                $map['is_deleted'] = 0;
                $codes = M('stock_bill_out')->field('code')->where($map)->select();
                if(!empty($codes)) {
                    $codes = array_column($codes,'code');
                    unset($map);
                    $map['refer_code'] = array('in',$codes); 
                    $map['is_deleted'] = 0;
                    $back_in = M('stock_bill_in')->where($map)->select();
                    if(!empty($back_in)) {
                        $status = true;
                    }else{      //如果没有查到相应的拒收入库单，直接返回FALSE
                        $status = false;
                    }
                }
            }  
        }
        return $status;
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
     * 统计配送单的订单状态
     * @param  string  $dist_id  配送单id
     * @return array   $data     返回统计信息
     * @author   jt
     */
	public function deliveryCount($dist_id = '') {
		if($dist_id == '') {
			return FALSE;
		}
            $M = M('tms_delivery');
			$map['dist_id'] = $dist_id;
            $start_date = date('Y-m-d',NOW_TIME);
            $end_date = date('Y-m-d',strtotime('+1 Days'));
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $res = $M->where($map)->find();
            unset($map['created_time']);
            if (defined('VERSION')) {
                $map['order_by'] = array('created_time' => 'DESC');
                $A = A('Tms/Dist','Logic');
                $bills = $A->billOut($map);
                $orders = $bills['orders'];
            } else {
                $A = A('Common/Order','Logic');
                $map['itemsPerPage'] = $res['order_count'];//传递页数
                $map['order_by'] = array('user_id' => 'ASC','created_time' => 'DESC');
                $orders = $A->order($map);
            }
            //$this->data = $orders;
            $all_orders     = 0;  //总订单统计
            $sign_orders    = 0;  //签收统计
            $unsign_orders  = 0;  //退货统计
            $sign_finished  = 0;  //已完成
            $sum_deal_price = 0;  //司机回款统计
            $back_lists     = array(); //退货清单
            foreach ($orders as $key => $value) {
                if (defined('VERSION')) {
                    $value = $value['order_info'];
                }
                $all_orders++;
                // 统计实收货款和签收未收订单 
                switch($value['status_cn']){
                    case '已签收':
                        $sign_orders++;
                        if($value['pay_status']=='1') {
                            $value['deal_price'] = 0;
                        }
                        $sum_deal_price += $value['deal_price'];//统计回款数
                        foreach ($value['detail'] as $key1 => $value1) {
                            $back_quantity = $value1['quantity']-$value1['actual_quantity'];
                            if ($back_quantity != 0) {
                                if(array_key_exists($value1['sku_number'],$back_lists)){
                                    $back_lists[$value1['sku_number']]['quantity'] += $back_quantity;
                                } else {
                                $back_lists[$value1['sku_number']]['quantity'] = $back_quantity;
                                }   
                                $back_lists[$value1['sku_number']]['unit_id'] = $value1['unit_id'];
                                $back_lists[$value1['sku_number']]['name']    = $value1['name'];
                            }
                        }
                        break;
                    case '已退货':
                        $unsign_orders++;
                        foreach ($value['detail'] as $key1 => $value1) {
                            if(array_key_exists($value1['sku_number'],$back_lists)){
                                $back_lists[$value1['sku_number']]['quantity'] += (int) $value1['quantity'];
                            }else{
                                $back_lists[$value1['sku_number']]['quantity'] = (int) $value1['quantity'];
                            }
                            $back_lists[$value1['sku_number']]['unit_id'] = $value1['unit_id'];
                            $back_lists[$value1['sku_number']]['name']    = $value1['name'];
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
        $data = M('tms_delivery')->where($map)->select();
        unset($map);
        $A = A('Common/Order','Logic');
        $geo_array = array();
        $customer  = array();
        foreach ($data as $key => $value) {
            // dump($value['dist_id']);
            $map['dist_id'] = $value['dist_id'];
            if (defined('VERSION')) {
                $map['order_by'] = array('created_time' => 'DESC');
                $A = A('Tms/Dist','Logic');
                $bills  = $A->billOut($map);
                $orders = $bills['orders'];
            } else { 
                $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
                $map['itemsPerPage'] = $value['order_count'];
                $orders = $A->order($map);
            }
            foreach ($orders as $keys => $values) {
                if (defined('VERSION')) {
                    $values = $values['order_info'];
                }
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
                // 只要有一单还没送完颜色就是0
                if($values['status_cn']=='已签收' || $values['status_cn']=='已退货' || $values['status_cn']=='已完成' ) {
                    if($geo_array[$values['user_id']]['color_type'] == NULL || $geo_array[$values['user_id']]['color_type'] != 0 ) {
                        $geo['color_type'] = 3;
                    }
                    else{
                        $geo['color_type'] = 0;
                    }      
                }
                else{
                    $geo['color_type'] = 0;
                }   
                $geo_array[$values['user_id']] = $geo;//把地图位置和信息按用户id存储，重复的覆盖               
            }            
        }
        $customer_count = count($customer);
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

}