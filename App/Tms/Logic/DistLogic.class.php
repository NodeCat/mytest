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
		$map['itemsPerPage'] = count($res);
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

	//签收后修改配送单详情－>配送单状态
	public function set_dist_status($map = array()) {
		if(!empty($map)) {
			$M = M('stock_wave_distribution_detail');
			$data['status'] = 1;//配送单详情状态：已签收
			//更新配送单详情状态
			$re = $M->where($map)->save($data);
			$code = $re ? 1 : -1;
			$pid = $map['pid'];
			unset($map);
			unset($data);
			$map['pid'] = $pid;
			//该配送单所有配送单详情状态
			$detail_status = $M->field('status')->where($map)->select();
			unset($map);
			$flag = 1;
			foreach ($detail_status as $value) {
				if($value['status'] != 1) {
					$flag = 0;
					break;
				}
			}
			if($flag) {
				//更新配送单状态
				$map['id'] = $pid;
				$data['status'] = 3;//配送单状态：已签收
				$re = $M->table('stock_wave_distribution')->where($map)->save($data);
				$code = $re ? 2 : $code;
			}
		}
		else {
			$code = -1;
		}

		return $code;
	}






}