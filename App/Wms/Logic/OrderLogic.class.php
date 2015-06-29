<?php
namespace Wms\Logic;

class OrderLogic{
	protected $server = '';
	protected $request ;
    public function __construct(){
    	$this->server = C('HOP_API_PATH');
		import("Common.Lib.HttpCurl");
		$this->request = new \HttpCurl();
    }
    public function operate($map='') {
    	$url = '/wave/create_wave2';
		$res = $this->get($url,$map);
		return $res;
    }

	public function order($map=''){
		$url = '/order/lists';
		$res = $this->get($url,$map);
		return $res['orderlist'];
	}
	public function sign($map='') {
    	$url = '/order/set_status';
		$res = $this->get($url,$map);
		return $res;
    }
	public function line($map='') {
		$url = '/line/lists';
		$res = $this->get($url,$map);
		return $res['list'];
	}
	public function weight_sku($map='') {
		$url = '/order/weight_sku';
		$res = $this->get($url,$map);
		return $res;
	}
	public function get_details_by_wave_and_sku($map='') {
		$url = '/order/get_details_by_wave_and_sku';
		$res = $this->get($url,$map);
		return $res;
	}

	public function city() {
		$url = '/location/get_child';
		$res = $this->get($url);
		foreach ($res['list'] as $key => $val) {
			unset($res['list'][$key]);
			$res['list'][$val['id']] = $val['name'];
		}
		return $res['list'];
	}
	public function distInfo($map='') {
		$url = '/distribution/view';
		$res = $this->get($url,$map);
		return $res['info'];
	}
	public function get($url,$map='') {
		$url = $this->server . $url;
		$map = json_encode($map);
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}
	//根据order_id 或者 order_number 查询订单信息
	public function getOrderInfoByOrderId($orderId){
		if(empty($orderId)){
			return false;
		}
		$url = $this->server . '/order/info';
		$map = json_encode(array('order_id'=>$orderId));
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}
	
	/**
	 * 根据订单ID批量获取订单
	 * @param array ids 订单id数组
	 * @return array
	 */
	public function getOrderInfoByOrderIdArr($ids = array()) {
	    $return = array('status' => false, 'msg' => '');
	    
	    if (empty($ids)) {
	        $return['msg'] = '参数有误';
	    }
	    $url = $this->server . '/order/lists';
	    $map = json_encode(array('order_ids' => $ids, 'itemsPerPage' => count($ids)));
	    $res = $this->request->post($url, $map);
	    $res = json_decode($res, true);
	     
	    if ($res['status'] == 0) {
	        $return['status'] = true;
	        $return['msg'] = '成功';
	        $return['list'] = $res['orderlist'];
	    } else {
	        $return['msg'] = '没有符合条件的订单';
	    }
	    return $return;
	}
}