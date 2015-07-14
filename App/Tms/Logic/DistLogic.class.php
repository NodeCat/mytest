<?php

namespace Tms\Logic;

class DistLogic{

	protected $wms_api_path = 'Wms/dist/';
	protected $server;
	protected $request;

    public function __construct() {
    	$this->server  = C('HOP_API_PATH');
		import("Common.Lib.HttpCurl");
		$this->request = new \HttpCurl();
    }

    //订单详情--WMS
	public function distInfo($id) {
		$action = 'distInfo';
		$res = R($this->wms_api_path . $action, array($id),'Api');
		return $res;
	}


	//出库单列表--WMS
	public function billOut($map = array()){
		$action = 'lists';
		//wms API 获取出库单列表
		$res = R($this->wms_api_path . $action, array($map),'Api');
		//配送单关联订单信息
		if($res) {
			$order_ids = array();
			foreach ($res as $re) {
				$order_ids[] = $re['refer_code'];
			}
			$map['order_ids'] = $order_ids;
			$map['itemsPerPage'] = count($res);
			unset($map['dist_id']);
			$cA = A('Common/Order','Logic');
			$orders = $cA->order($map);
			//配送单关联订单信息
			foreach ($res as &$bill) {
				foreach ($orders as $value) {
					if($bill['refer_code'] == $value['id']) {
						$bill['order_info'] = $value;
					}
				}
			}
			$res = array(
				'orders'     => $res,
				'orderCount' => count($orders),
			);
		}
		return $res;
	}

    //使用curl post 来获取一个接口数据
	public function get($url,$map='') {
		$url = $this->server . $url;
		$map = json_encode($map);
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}

	/**
	 * [set_dist_detail_status 修改配送单详情状态]
	 * @param array $map [bill_out_id,status]
	 */
	public function set_dist_detail_status($params = array()) {
		if(!empty($params['bill_out_id']) && !empty($params['status'])) {
			$M = M('stock_wave_distribution_detail');
			$map['bill_out_id'] = $params['bill_out_id'];
			$map['is_deleted']  = 0;
			//配送单详情
			$bill_out = $M->field('id,pid,status')->where($map)->find();
			if($bill_out['status'] == $params['status']) {
				$res = array(
					'code' => 0,
					'msg'  => '状态已更新'
				);
			}
			else {
				//更新配送单详情状态
				$s = $M->where(array('bill_out_id' => $map['bill_out_id']))
				     ->save(array('status' => $map['status']));
				if($s) {
					$res = array(
						'code' => 0,
						'msg'  => '配送单详情状态更新成功'
					);
				}
				else {
					$res = array(
						'code' => -1,
						'msg'  => '配送单详情状态更新失败'
					);
					return $res;
				}
			}
			$dmap['dist_id'] = $bill_out['pid'];
			$dmap['status']  = $params['status'];
			$ds = $this->set_dist_status($dmap);
			if($ds['status'] === -1){
				$res = array(
					'code' => -1,
					'msg'  => '配送单详情状态更新成功，配送单主表状态更新失败'
				);
				return $res;
			}
		}
		else {
			$res = array(
				'code' => -1,
				'msg'  => '出库单ID或状态不能为空'
			);
		}
		
		return $res;
	}

	/**
	 * [set_dist_status 更改配送单状态]
	 * @param array $map [dist_id,status]
	 */
	public function set_dist_status($params = array()) {
		if(!empty($params['dist_id'] && !empty($params['status']))) {
			$map['dist_id'] = $params['dist_id'];
			$map['status']  = $params['status'];
			$M = M('stock_wave_distribution');
			//配送单是否已为需要更新的状态
			$dist = $M->field('id')->where($map)->find();
			if($dist) {
				$res = array(
					'code' => 0,
					'msg'  => '状态已更新'
				);
				return $res;
			} 
			else {
				//该配送单所有配送单详情状态
				$detail_status = $M->table('stock_wave_distribution')
				    ->field('status')
				    ->where(array('pid' => $params['dist_id']))
				    ->select();
				//所有配送单详情均为该状态时，修改配送单主表状态
				$flag = 1;
				foreach ($detail_status as $value) {
					if($value['status'] != $params['status']) {
						$flag = 0;
						break;
					}
				}
				//更新配送单状态
				if($flag) {
					unset($map);
					$map['id'] = $params['dist_id'];
					switch($params['status']) {
						case '1'://已装车对应配送单状态2:已发运
						    $status = 2;
							break;
						case '2'://已签收对应配送单状态3:已配送
							$status = 3;
							break;
						case '3'://已完成对应配送单状态4:已结算
							$status = 4;
							break;
					}
					$data['status'] = $status;
					$s = $M->where($map)->save($data);
					//成功的返回结果
					if($s) {
						$res = array(
							'code' => 0,
							'msg'  => '配送单状态更新成功'
						);
					}
					//失败的返回结果
					else {
						$res = array(
							'code' => -1,
							'msg'  => '配送单状态更新失败'
						);
						return $res;
					}
				}
				else {
					$res = array(
						'code' => 0,
						'msg'  => '当前状态无需更新'
					);
				}
			}
		}
		else {
			$res = array(
				'code' => -1,
				'msg'  => '配送单ID或状态不能为空'
			);
		}
		return $res;
	}

}