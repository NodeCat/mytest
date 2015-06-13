<?php
/**
* 前端商城订单 进入到wms 转换成出库单
* @author liang 2015-6-12
*/
namespace Wms\Api;
use Think\Controller;
class OrderApi extends CommApi{
	//根据订单 创建出库单
	public function addBillOut(){
		$order_ids = I('orderIds');
		if(empty($order_ids)){
			$return = array('error_code' => '101', 'error_message' => 'param is empty' );
			$this->ajaxReturn($return);
		}

		$order_id_list = explode(',', $order_ids);

		foreach($order_id_list as $order_id){
			$order_info = A('Order','Logic')->getOrderInfoByOrderId($order_id);
			if(empty($order_info['info'])){
				$return = array('error_code' => '201', 'error_message' => 'order info is empty' );
				$this->ajaxReturn($return);
			}
			if(empty($order_info['info']['detail'])){
				$return = array('error_code' => '202', 'error_message' => 'detail is empty' );
				$this->ajaxReturn($return);
			}

			//写入出库单
			$params['wh_id'] = $order_info['info']['warehouse_id'];
			$params['type'] = 'SO';
			$params['line_id'] = $order_info['info']['line_id'];
			$params['refer_code'] = $order_info['info']['order_number'];
			foreach($order_info['info']['detail'] as $order_detail){
				$detail[] = array(
					'pro_code' => $order_detail['sku_number'],
					'order_qty' => $order_detail['quantity'],
					);
			}
			$params['detail'] = $detail;
			A('StockOut','Logic')->addStockOut($params);
			unset($params);
			unset($order_info);
			unset($detail);
		}
		

		$return = array('error_code' => '0', 'error_message' => 'succ' );
		$this->ajaxReturn($return);
	}

	//取消订单
}