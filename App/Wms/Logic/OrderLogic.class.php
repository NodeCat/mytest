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
}