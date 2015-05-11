<?php
/**
* @author liang
* @example
* $SKU = A('Product','Logic')->get_SKU_by_pro_code('1000123');
* $SKU = A('Product','Logic')->get_SKU_by_pro_name('桃');
*
*/
namespace Wms\Logic;

class ProductLogic{
	//根据pro_code 查询对应的SKU
	public function get_SKU_by_pro_code($pro_code){
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => 15,
			'where' => array('sku_number'=>$pro_code),
			);
		/*$url = 'http://s.test.dachuwang.com/sku/manage';
		$jsonStr = json_encode($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json; charset=utf-8',
		    'Content-Length: ' . strlen($jsonStr)
		   )
		);
		$response = curl_exec($ch);
		var_dump($response);exit;*/
		$url = 'http://s.test.dachuwang.com/sku/manage';
		$json_data = json_encode($data);
		$result = $request->post($url,$json_data);
		return json_decode($result,true);
	}

	//根据pro_name 模糊查询对应的SKU
	public function get_SKU_by_pro_name($pro_name){
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => 15,
			'where' => array('like'=>array('name'=>$pro_name)),
			);
		$url = 'http://s.test.dachuwang.com/sku/manage';
		$json_data = json_encode($data);
		$result = $request->post($url,$json_data);
		return json_decode($result,true);
	}
}