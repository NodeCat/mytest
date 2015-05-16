<?php
namespace Wms\Logic;

class StockInLogic{
	
	public function getInQty($inId,$code) {
		if(empty($inId) || empty($code)) {
			return array('res'=>false,'msg'=>'必填字段不能为空。');
		}
		$pro_code = $this->getCode($code);
		if(!empty($pro_code)) {
			$code = $pro_code;
		}

		$in = M('stock_bill_in')->field('id,code,type,refer_code,status')->find($inId);
		$detail = $this->getLine($inId,$code);
		if(empty($detail)) {
			return array('res'=>false,'msg'=>'单据中未找到该货品。');
		}

		$detail['id'] = $in['id'];
		$detail['code'] = $in['code'];
		$detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
		$detail['moved_qty'] = $detail['pro_qty'] - $this->getQtyForIn($inId,$code);
		return array('res'=>true,'data'=>$detail);
	}

	public function getOnQty($inId,$code) {
		if(empty($inId) || empty($code)) {
			return array('res'=>false,'msg'=>'必填字段不能为空。');
		}
		$pro_code = $this->getCode($code);
		if(!empty($pro_code)) {
			$code = $pro_code;
		}
		
		$detail = $this->getLine($inId,$code);
		if(empty($detail)) {
			return array('res'=>false,'msg'=>'单据中未找到该货品。');
		}

		$in = M('stock_bill_in')->field('id,code,type,refer_code,status')->find($inId);

		$qtyForOn = $this->getQtyForOn($in['code'],$code);
		if(empty($qtyForOn)) {
			return array('res'=>false,'msg'=>'该货品没有待上架量。');
		}
		

		$detail['id'] = $in['id'];
		$detail['code'] = $in['code'];
		$detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
		$detail['moved_qty'] = $qtyForOn;
		return array('res'=>true,'data'=>$detail);
	}

	public function getCode($barcode){
		$map['barcode'] = $barcode;
		$map['is_deleted'] = 0;
		$res = M('product_barcode')->field('pro_code')->where($map)->find();
		if(empty($res)) {
			return $res;
		}
		else {
			return $res['pro_code'];
		}
	}

	public function on($inId,$code,$qty,$location_code,$status){
		if(empty($inId) || empty($code)  || $location_code == '' || empty($status)) {
			return array('res'=>false,'msg'=>'必填字段不能为空。');
		}
		if(!is_numeric($qty)|| empty($qty)) {
			return array('res'=>false,'msg'=>'上架数量有误。');
		}
		//获取入库单信息
		$in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);

		if(empty($in)) {
			return array('res'=>false,'msg'=>'未找到该入库单。');
		}
		$qtyForOn = $this->getQtyForOn($in['code'],$code);
		if(empty($qtyForOn)) {
			return array('res'=>false,'msg'=>'该货品没有待上架量。');
		}
		if($qtyForOn < $qty) {
			return array('res'=>false,'msg'=>'上架数量不能大于该货品待上架数量');
		}

		//检查库位
		$map['wh_id'] = $in['wh_id'];
		$map['code'] = $location_code;
		$map['type'] = '2';
		$map['is_deleted'] = 0;
		$res = M('location')->field('id')->where($map)->find();

		if(empty($res)) {
			return array('res'=>false,'msg'=>'库位不存在。');
		}
		else {
			$location_id = $res['id'];
			unset($map);
			$map['location_id'] = $location_id;
			$location = M('location_detail')->field('is_mixed_pro,is_mixed_batch')->where($map)->find();
		}
		
		if($location['is_mixed_pro'] ==2 || $location['is_mixed_batch'] == 2) {
			//检查库位上的货品
			unset($map);
			$map['location_id'] = $location_id;
			$map['wh_id'] = $in['wh_id'];
			$map['status'] = $status;
			$map['stock_qty'] = array('neq','0');
			$map['is_deleted'] = 0;
			$res = M('stock')->field('pro_code,batch,status')->group('pro_code,status')->where($map)->select();
			
			if(!empty($res)) {
				if($location['is_mixed_pro'] == 2) {
					foreach ($res as $key => $val) {
						if($val['pro_code'] != $code) {
							return array('res'=>false,'msg'=>'该库位不允许混放货品。');
						}
					}
				}
				if($location['is_mixed_batch'] == 2) {
					foreach ($res as $key => $val) {
						if($val['batch'] != $in['code']) {
							return array('res'=>false,'msg'=>'该库位不允许混放批次。');
						}
					}
				}
			}
		}
		
		//写库存
		$line = $this->getLine($inId,$code);
		$pro_code = $line['pro_code'];
		$pro_uom = $line['pro_uom'];
		$pro_qty = $qty;
		$refer_code = $in['code'];
		$wh_id = $in['wh_id'];
		$batch   = $in['code'];
		$res = A('Stock','Logic')->adjustStockByShelves($wh_id,$location_id,$refer_code,$batch,$pro_code,$pro_qty,$pro_uom,$status);
		
		if($res == true){
			return array('res'=>ture,'msg'=>'库位：'.$location_code.'。数量：<strong>'.$pro_qty.'</strong> '.$line['pro_uom'].'。名称：['.$line['pro_code'] .'] '. $line['pro_name'] .'（'. $line['pro_attrs'].'）');
		}
		return array('res'=>false,'msg'=>'添加上架记录失败。');

	}

	public function in($inId,$code,$qty) {
		if(empty($inId) || empty($code) || $qty =='') {
			return array('res'=>false,'msg'=>'必填字段不能为空。');
		}
		if(!is_numeric($qty) || empty($qty) || $qty < 0) {
			return array('res'=>false,'msg'=>'验收数量有误。');
		}
		$qtyForIn = $this->getQtyForIn($inId,$code);
		
		if(empty($qtyForIn) || $qtyForIn < $qty) {
			return array('res'=>false,'msg'=>'验收数量不能大于可验收数量。');
		}

		$line = $this->getLine($inId,$code);
		$pro_uom = $line['pro_uom'];

		$in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);
		$refer_code = $in['code'];
		$batch   = $in['code'];
		$status  = 'unknown';
		$wh_id = $in['wh_id'];
		$res = A('Stock','Logic')->adjustStockByPrepare($wh_id,$refer_code,$code,$qty,$pro_uom,$status);
		
		if($res == true){
			return array('res'=>true,'msg'=>'数量：<strong>'.$qty.'</strong> '.$line['pro_uom'].'。名称：['.$line['pro_code'] .'] '. $line['pro_name'] .'（'. $line['pro_attrs'].'）');
			
		}
		return array('res'=>false,'msg'=>'添加验收记录失败。');
	}

	public  function checkIn($inId,$pro_code=''){
		$M = M('stock_bill_detail');
		$map['pid'] = $inId;
		if(!empty($pro_code)) {
			$map['pro_code'] = $pro_code;
		}
		$in = $M->group('refer_code,pro_code')->where($map)->getField('refer_code,pro_code,sum(pro_qty) as qty_total');
		unset($map['pid']);
		$map['refer_code'] = $in['refer_code'];
		$map['status'] = '0';
		$moved = M('stock_move')->where($map)->field('pro_code,sum(move_qty) as qty_total')->find();
		if(empty($moved['pro_code'])) {
			return 0;
		}
		dump($moved);
		foreach ($in as $key => $val) {
			if(array_key_exists($key, $moved)) {
				if($val != $moved[$key]) {
					return 1;
				}
			}
			else {
				return 1;
			}
		}
		return 2;
		
	}

	public function getQtyForIn($inId,$pro_code){
		$M = M('stock_bill_detail');
		$map['pid'] = $inId;
		$map['pro_code'] = $pro_code;
		//待入库量
		$in = $M->field('refer_code,pro_code,sum(pro_qty) as qty_total')->group('pro_code')->where($map)->find();
		
		if(empty($in)) {
			return 0;
		}
		unset($map['pid']);
		$map['refer_code'] = $in['refer_code'];
		$map['type'] = 'in';
		$map['status'] = '0';
		$moved = M('stock_move')->field('pro_code,sum(move_qty) as qty_total')->group('pro_code')->where($map)->find();
		
		if(empty($moved)) {
			return $in['qty_total'];
		}
		else{
			return $in['qty_total'] - $moved['qty_total'];
		}
	}

	public function getQtyForOn($batch,$pro_code){
		$map['location_id'] = '0';
		$map['pro_code'] = $pro_code;
		$map['type'] = 'in';
		$map['status'] = 'unknown';
		$map['batch'] = $batch;
		$res = M('stock')->field('stock_qty,prepare_qty')->where($map)->find();
		if(empty($res)) {
			return 0;
		}
		else {
			return $res['prepare_qty'];
		}
	}
	public function getLine($inId,$code){
		$map['pid'] = $inId;
		$map['pro_code'] = $code;
		$map['is_deleted'] = '0';
		$detail = M('stock_bill_detail')
		->field('pro_code,pro_name,pro_attrs,pro_uom,sum(pro_qty) as pro_qty')
		->group('pro_code')->where($map)->find();
		return $detail;
	}
	public function finishByPurchase($purchaseId) {
		$map['id_deleted'] = 0;
		$map['id'] = $purchaseId;
		$data['status'] = '23'; //完成
		$M = M('stock_purchase');
		$M->where($map)->save($data);
		$purchase = $M->field('code')->find($purchaseId);
		unset($map['id']);
		$map['refer_code'] = $purchase['code'];

		$M = M('stock_bill_in');
		$M->where($map)->save($data);
		
		return true;
	}
}