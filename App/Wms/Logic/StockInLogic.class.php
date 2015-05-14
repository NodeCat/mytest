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
		$map['pid'] = $in['id'];
		$map['pro_code'] = $code;
		$map['is_deleted'] = '0';
		$detail = M('stock_bill_detail')
		->field('pro_code,pro_name,pro_attrs,pro_uom,sum(pro_qty) as pro_qty')
		->group('pro_code')->where($map)->find();
		
		if(empty($detail)) {
			return false;
		}

		$map['status'] = array('in',array('0','1'));
		$map['refer_code'] = $in['code'];

		$move = M('stock_move')->field('pro_code,sum(move_qty) as move_qty')->group('pro_code')->where($map)->find();
		
		$detail['id'] = $in['id'];
		$detail['code'] = $in['code'];
		$detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
		if(empty($move)) {
			$detail['moved_qty'] = 0;
		}
		else {
			$detail['moved_qty'] = $move['move_qty'];
		}
		return $detail;
	}
	public function getOnQty($inId,$code){

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
	public function in($inId,$code,$qty) {
		if(empty($inId) || empty($code) ||  !is_numeric($qty) || empty($qty) || $qty < 0) {
			return false;
		}
		$in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);
		//@todo 检查入库单状态
		$map['pid'] = $inId;
		$map['pro_code'] = $code;
		$map['is_deleted'] = '0';
		$detail = M('stock_bill_detail')->field('pro_code,pro_name,pro_attrs,pro_uom,sum(pro_qty) as pro_qty')->group('pro_code')->where($map)->find();
		
		if(empty($detail)) {
			return false;
		}

		$map['status'] = array('in',array('0','1'));
		$map['refer_code'] = $in['code'];
		$move = M('stock_move')->field('pro_code,move_qty')->where($map)->find();

		if(!empty($move)){
			if($detail['pro_qty'] < $qty + $move['move_qty']){
				return false;
			}
		}

		$M = D('StockMove');

		$row['refer_code'] = $in['code'];
		$row['type'] = 'in';
		$row['pid'] = $in['id'];
		$row['pro_code'] = $detail['pro_code'];
		$row['pro_uom'] = $detail['pro_uom'];
		$row['move_qty'] = $qty;
		$row['src_wh_id'] = 0;
		$row['dest_wh_id'] = $in['wh_id'];
		$row['status'] = '0';
		$data = $M->create($row);
		$res = $M->add();
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