<?php
namespace Wms\Logic;

class StockInLogic{
	
	public function getInQty($inId,$code) {
		if(empty($inId) || empty($code)) {
			return false;
		}
		$pro_code = $this->getCode($code);
		if(!empty($pro_code)) {
			$code = $pro_code;
		}

		$in = M('stock_bill_in')->field('id,code,type,refer_code,status')->find($inId);
		$detail = $this->getLine($inId,$code);
		if(empty($detail)) {
			return false;
		}

		$detail['id'] = $in['id'];
		$detail['code'] = $in['code'];
		$detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
		$detail['moved_qty'] = $detail['pro_qty'] - $this->getQtyForIn($inId,$code);
		return $detail;
	}
	public function getOnQty($inId,$code) {
		if(empty($inId) || empty($code)) {
			return false;
		}
		$pro_code = $this->getCode($code);
		if(!empty($pro_code)) {
			$code = $pro_code;
		}
		
		$in = M('stock_bill_in')->field('id,code,type,refer_code,status')->find($inId);
		$detail = $this->getLine($inId,$code);
		if(empty($detail)) {
			return false;
		}

		$detail['id'] = $in['id'];
		$detail['code'] = $in['code'];
		$detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
		$detail['moved_qty'] = $this->getQtyForOn($in['code'],$code);

		return $detail;
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
	public function on($inId,$code,$qty,$location_id,$status){
		if(empty($inId) || empty($code) ||  !is_numeric($qty) || empty($qty) || empty($location_id) || empty($status)) {
			return false;
		}
		//获取入库单信息
		$in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);
		if(empty($in)) {
			return false;
		}
		$qtyForOn = $this->getQtyForOn($in['code'],$code);
		if(empty($qtyForOn) || $qtyForOn < $qty) {
			return false;
		}

		//写库存
		$row['wh_id'] = $in['wh_id'];
		$row['location_id'] = 0;
		$row['pro_code'] = $code;
		$row['batch'] = $in['code'];
		$row['status'] = 'unknown';

		$stock = M('Stock');
		$prepare = $stock->field('id,prepare_qty')->where($row)->find();

		$row['location_id'] = $location_id;
		$row['status'] = $status;
		$res = $stock->where($row)->find();

		if(empty($res)) {
			$row['stock_qty'] = $qty;
			$row['prepare_qty'] = 0;
			$row['assign_qty'] = 0;	
			$data = $stock->create($row);
			$stock->add($data);
		}
		else {
			$data['stock_qty'] =$res['stock_qty'] + $qty;
			$map['id'] = $res['id'];
			$stock->where($map)->save($data);
		}

		unset($row);
		unset($map);
		unset($data);
		$map['id'] = $prepare['id'];
		$data['prepare_qty'] = $prepare['prepare_qty'] - $qty;
		$stock->where($map)->save($data);

		$detail = $this->getLine($inId,$code);

		//写库存移动记录
		$M = D('StockMove');
		$row['refer_code'] = $in['code'];
		$row['type'] = 'on';
		$row['pid'] = $in['id'];
		$row['pro_code'] = $detail['pro_code'];
		$row['pro_uom'] = $detail['pro_uom'];
		$row['move_qty'] = $qty;
		$row['src_wh_id'] = 0;
		$row['src_location_id'] = 0;
		$row['dest_wh_id'] = $in['wh_id'];
		$row['dest_location_id'] = $location_id;
		$row['status'] = '1';
		$row['is_deleted'] = '0';
		$data = $M->create($row);
		$res = $M->add($data);
		if($res == true){
			$res = '数量：<strong>'.$qty.'</strong> '.$detail['pro_uom'].'。名称：['.$detail['pro_code'] .'] '. $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
			return $res;
		}
		return false;

	}
	public function in($inId,$code,$qty) {
		if(empty($inId) || empty($code) ||  !is_numeric($qty) || empty($qty) || $qty < 0) {
			return false;
		}
		$qtyForIn = $this->getQtyForIn($inId,$code);
		
		if(empty($qtyForIn) || $qtyForIn < $qty) {
			return false;
		}
		$in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);
		//@todo 检查入库单状态
		
		//写库存
		$stock = M('stock');
		$row['wh_id'] = $in['wh_id'];
		$row['location_id'] = 0;
		$row['pro_code'] = $code;
		$row['batch'] = $in['code'];
		$row['status'] ='unknown';
		$res = $stock->where($row)->find();
		if(!empty($res)) {
			$map['id'] = $res['id'];
			$data['prepare_qty'] = $res['prepare_qty'] + $qty;
			$stock->where($map)->save($data);
		}
		else{
			$row['prepare_qty'] = $qty;
			$row['stock_qty'] = 0;
			$row['assign_qty'] = 0;	
			
			$data = $stock->create($row);
			$stock->add($data);
		}

		$detail = $this->getLine($inId,$code);

		unset($row);
		//写库存移动记录
		$M = D('StockMove');
		$row['refer_code'] = $in['code'];
		$row['type'] = 'in';
		$row['pid'] = $in['id'];
		$row['pro_code'] = $code;
		$row['pro_uom'] = $detail['pro_uom'];
		$row['move_qty'] = $qty;
		$row['src_wh_id'] = 0;
		$row['src_location_id'] = 0;
		$row['dest_wh_id'] = $in['wh_id'];
		$row['dest_location_id'] = 0;
		$row['status'] = '0';
		$row['is_deleted'] = '0';
		$data = $M->create($row);
		$res = $M->add($data);
		if($res == true){
			$res = '数量：<strong>'.$qty.'</strong> '.$detail['pro_uom'].'。名称：['.$detail['pro_code'] .'] '. $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
			return $res;
		}
		return false;
	}

	public  function checkIn($inId,$pro_code=''){
		$M = M('stock_bill_detail');
		$map['pid'] = $inId;
		if(!empty($pro_code)) {
			$map['pro_code'] = $pro_code;
		}
		$in = $M->group('pro_code')->where($map)->getField('pro_code,sum(pro_qty) as qty_total');
		$map['status'] = '0';
		$moved = M('stock_move')->where($map)->getField('pro_code,sum(move_qty) as qty_total');
		foreach ($in as $key => $val) {
			if(array_key_exists($key, $moved)) {
				if($val != $moved[$key]) {
					return false;
				}
			}
			else {
				return false;
			}
		}
		return true;
		
	}

	public function getQtyForIn($inId,$pro_code){
		$M = M('stock_bill_detail');
		$map['pid'] = $inId;
		$map['pro_code'] = $pro_code;
		//待入库量
		$in = $M->field('pro_code,sum(pro_qty) as qty_total')->group('pro_code')->where($map)->find();
		
		if(empty($in)) {
			return 0;
		}
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
		//$map['wh_id'] = $wh_id;
		$map['pro_code'] = $pro_code;
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