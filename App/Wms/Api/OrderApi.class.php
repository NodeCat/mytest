<?php
/**
* 前端商城订单 进入到wms 转换成出库单
* @author liang 2015-6-12
*/
namespace Wms\Api;
use Think\Controller;
class OrderApi extends Controller{
	//根据订单 创建出库单
	public function addBillOut(){
		$order_id = I('orderId');
		if(empty($order_id)){
			$return = array('error_code' => '101', 'error_message' => 'param is empty' );
			$this->ajaxReturn($return);
		}

		$order_info = A('Order','Logic')->getOrderInfoByOrderId($order_id);
		var_dump($order_info);exit;

		//写入出库单
	}

	//取消订单
}