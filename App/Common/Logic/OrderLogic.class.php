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
    //获取订单列表
	public function order($map=''){
		$url = '/suborder/lists';
		$res = $this->get($url,$map);
		return $res['orderlist'];
	}
	//获取一条订单信息
	public function oneOrder($map=''){
		$url = '/suborder/info';
		$res = $this->get($url,$map);
		return $res['info'];
	}
	//根据客户id获取客户信息
	public function customer($map=''){
		$url = '/customer/view';
		$res = $this->get($url,$map);
		return $res['info'];
	}
	//设置订单的抹零和押金
	public function setDeposit($map=''){
		$url = '/suborder/set_deposit_and_neglect';
		$res = $this->get($url,$map);
		return $res;
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

	/**
	 * [saveSignature 将客户电子签名回调给订单]
	 * @param  array  $params [suborder_id,sign_img]
	 * @return [type]         [description]
	 */
	public function saveSignature($params = array())
	{
		$url = '/suborder/set_sign_img';
		if (!isset($params['suborder_id']) || !isset($params['sign_img'])) {
			$res = array(
				'status' => -1,
				'msg'    => '参数有误'
			);
			return $res;
		}
		$res = $this->get($url,$params);
		return $res;

	}
	
	/**
	 * [sendPushMsg 发送短信]
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public function sendPushMsg($params = array())
	{
		$url = '/sms/send_sms';
		if (empty($params['mobile']) || empty($params['content'])) {
			$res = array(
				'status' => -1,
				'msg'    => '参数有误'
			);
			return $res;
		}
		$res = $this->get($url,$params);
		return $res;
	}

	/**
	 * [sendPullMsg 撤回短信]
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public function sendPullMsg($params = array())
	{
		$url = '/sms/pull_sms_job';
		if (empty($params['job_id'])) {
			$res = array(
				'status' => -1,
				'msg'    => '参数有误'
			);
			return $res;
		}
		$res = $this->get($url,$params);
		return $res;
	}

	/**
	 * [getParentAccountByCoustomerId 根据子账户获ID取母账户信息]
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public function getParentAccountByCoustomerId($params = array())
	{
		$url = '/customer/get_parent_info';
		if (empty($params['customer_id'])) {
			$res = array(
				'status' => -1,
				'msg'    => '参数有误'
			);
			return $res;
		}
		$res = $this->get($url,$params);
		return $res;
	}

	public function get($url,$map='') {
		$url = $this->server . $url;
		$map = json_encode($map);
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}
}