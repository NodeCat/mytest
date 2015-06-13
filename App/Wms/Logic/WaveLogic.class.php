<?php
namespace Wms\Logic;

class WaveLogic{

	/**
	 * 根据出库单格式化出库单数据 
	 *  
	 * @param String $ids 出库单id
	 * @param Int $site_url 来自哪里：1大厨2大果
	 * @return Array $data;
	 * 
	 */
	public function getWaveDate($ids, $site_src = 1){

		if(!$ids) return FALSE;

		$idsArr = explode(',', $ids);

		$data = array();

		$m = M('stock_wave');

		$sumResult = $this->sumStockBillOut($idsArr);

		$data['wave_type']   = 2;

		$data['order_count'] = count($idsArr); //订单数

		$data['line_count']  = $sumResult['skuCount'];//sku码

		$data['total_count'] = $sumResult['totalCount'];//商品总数

		$data['company_id']    = $site_src;//pm和王爽说大厨与大果是不会在同一个仓库

		return $data;


	}

	public function sumStockBillOut($idsArr){

		$m = M('stock_bill_out_detail');

		$map['pid']  = array('in',$idsArr);

		$skuCount   =  count($m->field('count(id) as num')->where($map)->group('pro_code')->select());

		$totalCount = $m->where($map)->sum('order_qty');//预计出库量

		$data       = array();

		$data['skuCount']   = $skuCount?$skuCount:0;

		$data['totalCount'] = $totalCount?$totalCount:0;

		return $data;

	}

	public function addWaveDetail($ids, $wave_id){

		if(!$ids) return FALSE;

		$idsArr = explode(',', $ids);

		$WaveDetailArr = array();

		$M      = M('stock_wave_detail');

		foreach ($idsArr as $key => $value) {
			
			$WaveDetailArr[$key]['bill_out_id'] = $value;

			$WaveDetailArr[$key]['pid'] = $wave_id;

			$WaveDetailArr[$key]['created_time'] = get_time();

			$WaveDetailArr[$key]['created_user'] = session('user.username');

			$WaveDetailArr[$key]['updated_user'] = session('user.username');

			$WaveDetailArr[$key]['updated_time'] = get_time();

		}

		$result = $M->addAll($WaveDetailArr)?TRUE:FALSE;

		return $result;

	}

	public function updateBillOutStatus($ids){

		if(!$ids) return FALSE;

		$idsArr     = explode(',', $ids);

		$map        = array();

		$map['id']  = array('in',$idsArr);

		$Model      = M('stock_bill_out');

		$data['status'] = '3';

		$result      = $Model->data($data)->where($map)->save()?TRUE:FALSE;

		return $result;

	}
}
