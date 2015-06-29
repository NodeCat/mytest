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
			if(empty($order_info['info']['warehouse_id'])){
				$return = array('error_code' => '203', 'error_message' => 'warehouse_id is empty' );
				$this->ajaxReturn($return);
			}
			//根据warehouse_id查询对应的仓库是否存在 如果不存在 不写入出库表
			$map['id'] = $order_info['info']['warehouse_id'];
			$warehouse = M('warehouse')->where($map)->find();
			if(empty($warehouse)){
				$return = array('error_code' => '204', 'error_message' => 'warehouse is not exsist' );
				$this->ajaxReturn($return);
			}
			
			//写入出库单
			$params['wh_id'] = $order_info['info']['warehouse_id'];
			$params['type'] = 'SO';
			$params['line_id'] = $order_info['info']['line_id'];
			$params['refer_code'] = $order_info['info']['id'];
			$params['delivery_date'] = str_replace('/', '-', $order_info['info']['deliver_date']);
			$params['delivery_time'] = $order_info['info']['deliver_time'];
			$params['delivery_ampm'] = 'am';
			$params['customer_realname'] = $order_info['info']['realname'];
			$params['delivery_address'] = $order_info['info']['deliver_addr'];
			$params['company_id'] = $order_info['info']['site_src'];

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
