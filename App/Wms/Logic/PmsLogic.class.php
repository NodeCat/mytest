<?php
/**
* @author liang
* @example
* $SKU = A('Product','Logic')->get_SKU_by_pro_codes(array('1000123','1000315'));
* $SKU = A('Product','Logic')->get_SKU_by_pro_name('桃');
*
*/
namespace Wms\Logic;

class PmsLogic{
	//根据pro_code 查询对应的SKU
	public function get_SKU_by_pro_codes($pro_codes = array()){
		if(empty($pro_codes)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => 15,
			'where' => array('in'=>array('sku_number'=>$pro_codes)),
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

	//根据pro_code 模糊查询对应的SKU
	public function get_SKU_by_pro_codes_fuzzy($pro_code){
		if(empty($pro_codes)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => 10,
			'where' => array('like'=>array('sku_number'=>$pro_code)),
			);
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

	//为数组添加pro_name字段
	public function add_fields($data = array(),$add_field = ''){
		if(empty($data) || empty($add_field)){
			return $data;
		}

		if($add_field == 'pro_name'){
			//添加pro_name字段
			$prepare_data = array();
			$pro_codes = array();
			foreach($data as $value){
				$prepare_data[$value['pro_code']] = $value;
				$pro_codes[] = $value['pro_code'];
			}

			//根据pro_code 接口查询SKU
			$SKUs = $this->get_SKU_field_by_pro_codes($pro_codes);

			//整理数据
			foreach($SKUs as $pro_code => $SKU){
				$prepare_data[$pro_code]['pro_name'] = $SKU['wms_name'];
			}

			return $prepare_data;
		}
		
		return $data;
	}

	//根据pro_code 查询对应的SKU信息，信息是经过整理后的
	public function get_SKU_field_by_pro_codes($pro_codes = array()){
		if(empty($pro_codes)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => 15,
			'where' => array('in'=>array('sku_number'=>$pro_codes)),
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

		$result = json_decode($result,true);

		$return_data = array();
		//整理返回数据
		foreach($result['list'] as $value){
			$return_data[$value['sku_number']]['wms_name'] = $value['name'].'('.$value['spec'][0]['name'].':'.$value['spec'][0]['val'].','.$value['spec'][1]['name'].':'.$value['spec'][1]['val'].')';
			$return_data[$value['sku_number']]['name'] = $value['name'];
			$return_data[$value['sku_number']]['pro_code'] = $value['sku_number'];
			$return_data[$value['sku_number']]['pro_attrs'] = $value['spec'];
		}

		return $return_data;
	}
}