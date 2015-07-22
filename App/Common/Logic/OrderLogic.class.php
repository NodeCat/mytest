<?php
namespace Common\Logic;

class OrderLogic{
	protected $server = '';
	protected $request ;
    public function __construct(){
    	$this->server = C('HOP_API_PATH');
		import("Common.Lib.HttpCurl");
		$this->request = new \HttpCurl();
    }

    //账单查询条件
    public function billQuery()
    {
    	$url = '/billing/get_condition';
    	$res = $this->get($url,$map);
    	return $res;
    }

    //账单列表接口
    public function billList($map = '')
    {
    	$url = '/billing/lists';
    	$res = $this->get($url,$map);
    	return $res;
    }

    //账单详情
    public function billDetail($map = '')
    {
    	$url = '/billing/view';
    	$res = $this->get($url,$map);
    	return $res;
    }

    //账单详情中的订单列表
    public function billOrders($map = '')
    {
    	$url = '/billing/get_orders_of_billing';
    	$res = $this->get($url,$map);
    	return $res;
    }


    //添加账单备注
    public function billAddRemark($map = '')
    {
    	$url = '/billing/add_remark';
    	$res = $this->get($url,$map);
    	return $res;
    }

    //账单备注列表
    public function billRemarkList($map = '')
    {
    	$url = '/billing/get_billing_dynamic';
    	$res = $this->get($url,$map);
    	return $res;
    }


    //账单结算
    public function billPay($map = '')
    {
    	$url = '/billing/one_key_pay';
    	$res = $this->get($url,$map);
    	return $res;
    }

    //获取订单列表
	public function order($map=''){
		$url = '/suborder/lists';
		$res = $this->get($url,$map);
		return $res['orderlist'];
	}
	//根据客户id获取客户信息
	public function customer($map=''){
		$url = '/customer/view';
		$res = $this->get($url,$map);
		return $res['info'];
	}
	//修改订单状态
	public function set_status($map='') {
		switch ($map['status']) {
			case '1':
				$func = 'set_status_success';
				break;
			//已发货
			case '5':
				$func = 'set_status_delivering';
				break;			
			case '6':
				$func = 'set_status_signed';
				break;			
			case '8':
				$func = 'set_status_loading';
				break;			
			case '7':
				$func = 'set_status_rejected';
				break;
			//波次中
			case '11':
				$func = 'set_status_wave_executed';
				break;
			default:
				# code...
				break;
		}
    	$url = '/suborder/'.$func;
		$res = $this->get($url,$map);
		return $res;
    }
    //查询线路列表
	public function line($map='') {
		$url = '/line/lists';
		$res = $this->get($url,$map);
		return $res['list'];
	}
	//查询城市列表
	public function city() {
		$url = '/location/get_child';
		$res = $this->get($url);
		foreach ($res['list'] as $key => $val) {
			unset($res['list'][$key]);
			$res['list'][$val['id']] = $val['name'];
		}
		return $res['list'];
	}
	//查询配送单详情
	public function distInfo($map='') {
		$url = '/distribution/view';
		$res = $this->get($url,$map);
		return $res['info'];
	}
	//根据order_id 或者 order_number 查询订单信息
	public function getOrderInfoByOrderId($orderId){
		if(empty($orderId)){
			return false;
		}
		$url = '/suborder/info';
		$map = array('suborder_id'=>$orderId);
		$res = $this->get($url,$map);
		return $res;
	}
	
	/**
	 * 根据订单ID批量获取订单
	 * @param array ids 订单id数组
	 * @param unknown $ids
	 */
	public function getOrderInfoByOrderIdArr($ids = array()) {
	    $return = array('status' => false, 'msg' => '');
	    
	    if (empty($ids)) {
	        $return['msg'] = '参数有误';
	    }
	    $url = '/suborder/lists';
	    $map = array('order_ids' => $ids, 'itemsPerPage' => count($ids));
	    $res = $this->get($url,$map);
	     
	    if ($res['status'] == 0) {
	        $return['status'] = true;
	        $return['msg'] = '成功';
	        $return['list'] = $res['orderlist'];
	    } else {
	        $return['msg'] = '没有符合条件的订单';
	        $return['list'] = array();
	    }
	    return $return;
	}
	public function get($url,$map='') {
		$url = $this->server . $url;
		$map = json_encode($map);
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}
}