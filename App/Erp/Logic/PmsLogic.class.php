<?php
/**
* @author liang
* @example
* $SKU = A('Product','Logic')->get_SKU_by_pro_codes(array('1000123','1000315'));
* $SKU = A('Product','Logic')->get_SKU_by_pro_name('桃');
*
*/
namespace Erp\Logic;

class PmsLogic{
	//查询SKU所有分类信息
	public function get_SKU_category(){
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$url = C('PMS_API').'/category/lists';
		$result = $request->get($url);
		return json_decode($result,true);
	}

	//无条件 查询对应的SKU
	public function get_SKU_by_all($page = 1,$count = 10){
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => $page,
			'itemsPerPage' => $count,
			);
		$url = C('PMS_API').'/sku/manage';
		$json_data = json_encode($data);
		$result = $request->post($url,$json_data);
		return json_decode($result,true);
	}

	//根据category_id 查询对应的SKU
	public function get_SKU_by_category_id($category_ids = array(), $currentPage = 1, $itemsPerPage){
		if(empty($category_ids)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$itemsPerPage = $itemsPerPage?$itemsPerPage:C('PAGE_SIZE');
		$data = array(
			'currentPage' => $currentPage,
			'itemsPerPage' => $itemsPerPage,
			'where' => array('in'=>array('category_id'=>$category_ids)),
			);
		$url = C('PMS_API').'/sku/manage';
		$json_data = json_encode($data);
		$result = $request->post($url,$json_data);
		return json_decode($result,true);
	}

	//根据pro_code 查询对应的SKU
	public function get_SKU_by_pro_codes($pro_codes = array()){
		if(empty($pro_codes)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => C('PAGE_SIZE'),
			'where' => array('in'=>array('sku_number'=>$pro_codes)),
			);
		$url = C('PMS_API').'/sku/manage';
		$json_data = json_encode($data);
		$result = $request->post($url,$json_data);
		return json_decode($result,true);
	}

	//根据ena13码 查询对应的SKU
    public function get_SKU_by_ena_code($nea13_codes = array()){
        if(empty($nea13_codes)){
            return false;
        }
        import("Common.Lib.HttpCurl");
        $request = new \HttpCurl();
        $data = array(
            'currentPage' => 1,
            'itemsPerPage' => C('PAGE_SIZE'),
            'where' => array('in'=>array('code'=>$nea13_codes)),
            );
        $url = C('PMS_API').'/sku/manage';
        $json_data = json_encode($data);
        $result = $request->post($url,$json_data);
        //var_dump($url,$data,$result);die;
        return json_decode($result,true);
    }

	//根据pro_code 模糊查询对应的SKU
	public function get_SKU_by_pro_codes_fuzzy($pro_code, $page = 1, $count = 10){
		if(empty($pro_code)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => $page,
			'itemsPerPage' => $count,
			'where' => array('like'=>array('sku_number'=>$pro_code)),
			);
		$url = C('PMS_API').'/sku/manage';
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
			'itemsPerPage' => C('PAGE_SIZE'),
			'where' => array('like'=>array('name'=>$pro_name)),
			);
		$url = C('PMS_API').'/sku/manage';
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
			foreach($data as $key => $value){
				$prepare_data[$key] = $value;
				$pro_codes[] = $value['pro_code'];
			}

			//一次最大数量
			$max = C('PAGE_SIZE');
			$buffer = array();
			foreach($pro_codes as $pro_code){
				$buffer_codes[] = $pro_code;

				if($max == count($buffer_codes)){
					//根据pro_code 接口查询SKU
					$SKUs = $this->get_SKU_field_by_pro_codes($buffer_codes);

					foreach($prepare_data as $key => $value){
						//如果$SKUs['pro_code']结果存在
						if(isset($SKUs[$value['pro_code']])){
							$prepare_data[$key]['pro_name'] = $SKUs[$value['pro_code']]['wms_name'];
							$prepare_data[$key]['uom_name'] = $SKUs[$value['pro_code']]['uom_name'];
							$prepare_data[$key]['guarantee_period'] = $SKUs[$value['pro_code']]['guarantee_period'];
							$prepare_data[$key]['pro_attrs_str'] = $SKUs[$value['pro_code']]['pro_attrs_str'];
						}
					}

					unset($buffer_codes);
					unset($SKUs);
				}
			}

			if(!empty($buffer_codes)){
				//根据pro_code 接口查询SKU
				$SKUs = $this->get_SKU_field_by_pro_codes($buffer_codes);

				foreach($prepare_data as $key => $value){
					//如果$SKUs['pro_code']结果存在
					if(isset($SKUs[$value['pro_code']])){
						$prepare_data[$key]['pro_name'] = $SKUs[$value['pro_code']]['wms_name'];
						$prepare_data[$key]['uom_name'] = $SKUs[$value['pro_code']]['uom_name'];
						$prepare_data[$key]['guarantee_period'] = $SKUs[$value['pro_code']]['guarantee_period'];
						$prepare_data[$key]['pro_attrs_str'] = $SKUs[$value['pro_code']]['pro_attrs_str'];
					}
				}
			}

			return $prepare_data;
		}
		
		return $data;
	}

	//根据pro_code 查询对应的SKU信息，信息是经过整理后的
	public function get_SKU_field_by_pro_codes($pro_codes = array(), $pagesize = 0){
		if(empty($pro_codes)){
			return false;
		}
		import("Common.Lib.HttpCurl");
		$request = new \HttpCurl();
		$data = array(
			'currentPage' => 1,
			'itemsPerPage' => $pagesize > 0 ? $pagesize : C('PAGE_SIZE'),
			'where' => array('in'=>array('sku_number'=>$pro_codes)),
			);
		$url = C('PMS_API').'/sku/manage';
		$json_data = json_encode($data);
		$result = $request->post($url,$json_data);

		$result = json_decode($result,true);

		$return_data = array();
		//整理返回数据
		foreach($result['list'] as $value){
			$return_data[$value['sku_number']]['wms_name'] = $value['name'].'('.$value['spec'][0]['name'].':'.$value['spec'][0]['val'].','.$value['spec'][1]['name'].':'.$value['spec'][1]['val'].')';
			$return_data[$value['sku_number']]['name'] = $value['name'];
			$return_data[$value['sku_number']]['pro_code'] = $value['sku_number'];
			$return_data[$value['sku_number']]['pro_attrs_str'] = $value['spec'][0]['name'].':'.$value['spec'][0]['val'].','.$value['spec'][1]['name'].':'.$value['spec'][1]['val'];
			$return_data[$value['sku_number']]['pro_attrs'] = $value['spec'];
			$return_data[$value['sku_number']]['uom_name'] = $value['unit_name'];
			$return_data[$value['sku_number']]['guarantee_period'] = $value['guarantee_period'];
		}

		return $return_data;
	}

	//根据pro_code 模糊查询对应的SKU
	public function get_SKU_by_pro_codes_fuzzy_return_data($pro_code, $page = 1, $count = 10){
		if(empty($pro_code)){
			return false;
		}

		$res = $this->get_SKU_by_pro_codes_fuzzy($pro_code);
        if(!empty($res['list'])){
            $i = 0;
            foreach ($res['list'] as $key => $val) {
                $data[$i]['val']['code'] = $val['sku_number'];
                $data[$i]['val']['name'] = $val['name'];
                //加了计量单位 liuguangping
                $data[$i]['val']['unit_name'] = $val['unit_name'];
                    
                foreach ($val['description'] as $k => $v) {
                      $attrs[]= $v['name'].':'.$v['val'];
                }
                $data[$i]['val']['attrs'] = implode(',',$attrs);
                $data[$i]['name'] = '['.$val['sku_number'].'] '.$val['name'] .'（'. $data[$i]['val']['attrs'].'）';
                unset($attrs);
                $i++;
        	}
        }
		
		return $data;
	}
}