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
	 * 根据订单id,配送时间,时段,订单类型,批量获取订单(配送用)
	 * @param array $orderIds 订单id数组
	 * array(
	 *     order_ids => '订单id组'
	 *     deliver_date => 配送日期
	 *     deliver_time => 配送时段
	 *     order_type => 订单类型
	 * )
	 * @return multitype:boolean string |multitype:boolean string mixed
	 */
	public function getOrderInfoByOrderIds($data = array('orderIds' => array(), 'deliver_date' => 0, 'deliver_time' => 0, 'order_type' => 0)) {
	    $return = array('status' => false, 'msg' => '');
	    if (empty($data)) {
	        $return['msg'] = '参数有误';
	        return $return;
	    }
	    foreach ($data as $value) {
	        if (empty($value)) {
	            $return['msg'] = '参数有误';
	            return $return;
	        }
	    }
	    $url = $this->server . '/order/lists';
	    $map = json_encode($data);
	    $res = $this->request->post($url, $map);
	    $res = json_decode($res);
	    if ($res['status']) {
	        $return['status'] = true;
	        $return['msg'] = '成功';
	        $return['list'] = $res;
	    }
	    $return['msg'] = '没有符合条件的订单';
	    return $return;
	}
}