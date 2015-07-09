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
            $map['stock_wave_distribution_detail.pid'] = $dist_id;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $bill_out_id = M('stock_wave_distribution_detail')->field('bill_out_id')->where($map)->select();
            //若查出的出库单id非空
            if(!empty($bill_out_id)){   
                $bill_out_id = array_column($bill_out_id,'bill_out_id');
                unset($map);
                $map['refer_code'] = array('in',$bill_out_id); 
                $back_in = M('stock_bill_in')->where($map)->select();
                if(!empty($back_in)){
                    $status = true;
                }else{      //如果没有查到相应的客退入库单，直接返回FALSE
                    $status = false;
                }
            }  
        }
        return $status;
    }

    /**
     * 统计配送单的订单状态
     * @param  string  $dist_id  配送单id
     * @return array   $data     返回统计信息
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
            $map['itemsPerPage'] = $res['order_count'];//传递页数
            $map['order_by'] = array('user_id' => 'ASC','created_time' => 'DESC');
            $A = A('Common/Order','Logic');
            $orders = $A->order($map);
            $this->data = $orders;
            $all_orders     = 0;  //总订单统计
            $sign_orders    = 0;  //签收统计
            $unsign_orders  = 0;  //退货统计
            $sign_finished  = 0;  //已完成
            $sum_deal_price = 0;  //司机回款统计
            $back_lists     = array(); //退货清单
            foreach ($orders as $key => $value) {
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

}