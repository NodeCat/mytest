<?php
namespace Wms\Logic;

class StockLogic{
	/**
	 * 一键出库，按照先进先出原则 减少库存 如果库存不够 则返回失败
	 * @param 
	 * $wh_id 仓库id
	 * $pro_code sku编号
	 * $pro_qty 产品数量
	 * $refer_code 出库单号
	 * )
	 */
	public function outStockBySkuFIFO($params = array()){
		if(empty($params['wh_id']) || empty($params['pro_code']) || empty($params['pro_qty'])){
			return array('status'=>0,'msg'=>'参数有误！');
		}

		$diff_qty = $params['stock_qty'];

		//根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
		$map['pro_code'] = $params['pro_code'];
		$map['wh_id'] = $params['wh_id'];
		$stock_list = M('Stock')->where($map)->order('batch')->select();
		unset($map);

		//检查所有的 库存量 是否满足 出库量
		foreach($stock_list as $stock){
			$stock_total += $stock['stock_qty'] - $stock['assign_qty'];
		}

		if($stock_total < $params['pro_qty']){
			return array('status'=>0,'msg'=>'库存总量不足！');
		}

		//按照现进先出原则 减去最早的批次量
		foreach($stock_list as $stock){
			if($diff_qty > 0){
				//如果库存量小于等于差异量 则删除该条库存记录 然后减去差异量diff_qty
				if($stock['stock_qty'] <= $diff_qty){
				$map['id'] = $stock['id'];
				M('Stock')->where($map)->delete();
				unset($map);

				$diff_qty = $diff_qty - $stock['stock_qty'];
				$log_qty = $stock['stock_qty'];
				$log_old_qty = $stock['stock_qty'];
				$log_new_qty = 0;
			}else{
				//根据id 更新库存表
				$map['id'] = $stock['id'];
				$log_qty = $diff_qty;
				$log_old_qty = $stock['stock_qty'];
				$data['stock_qty'] = $stock['stock_qty'] - $diff_qty;
				$log_new_qty = $data['stock_qty'];
				M('stock')->where($map)->data($data)->save();
				unset($map);
				unset($data);
			}

			//写入库存交易日志
			$stock_move_data = array(
				'wh_id' => session('user.wh_id'),
				'location_id' => $stock['location_id'],
				'pro_code' => $stock['pro_code'],
				'type' => 'move',
				'refer_code' => $params['refer_code'],
				'direction' => 'OUT',
				'move_qty' => $log_qty,
				'old_qty' => $log_old_qty,
				'new_qty' => $log_new_qty,
				'batch' => $stock['batch'],
				'status' => $stock['status'],
				);
			$stock_move = D('StockMoveDetail');
			$stock_move_data = $stock_move->create($stock_move_data);
			$stock_move->data($stock_move_data)->add();
			unset($log_qty);
			unset($log_old_qty);
			unset($log_new_qty);
			unset($stock_move_data);
			}
		}
		
		return array('status'=>1);
	}

	/**
	 * 入库收货时，库存表变化，调整库存量
	 * @param 
	 * $wh_id 仓库id
	 * $refer_code 关联单号
	 * $pro_code sku编号
	 * $pro_qty 产品数量
	 * $pro_uom 产品计量单位
	 * $status 库存状态
	 * )
	 */

	public function adjustStockByPrepare($wh_id,$refer_code,$pro_code,$pro_qty,$pro_uom,$status){
		
		//写库存
		$row['wh_id'] = $wh_id;
		$row['location_id'] = 0;
		$row['pro_code'] = $pro_code;
		$row['batch'] = $refer_code;
		$row['status'] =$status;
		$stock = D('stock');
		$res = $stock->where($row)->find();
		if(empty($res)) {
			$row['prepare_qty'] = $pro_qty;
			$row['stock_qty'] = 0;
			$row['assign_qty'] = 0;	
			
			$data = $stock->create($row);
			$res = $stock->add($data);
		}
		else{
			$map['id'] = $res['id'];
			$data['prepare_qty'] = $res['prepare_qty'] + $pro_qty;
			$data = $stock->create($data, 2);
			$res = $stock->where($map)->save($data);
		}

		if($res == false) {
			return false;
		}
		unset($row);

		//写库存移动记录
		/*$M = D('StockMove');
		$row['refer_code'] = $refer_code;
		$row['type'] = 'in';
		$row['pro_code'] = $pro_code;
		$row['pro_uom'] = $pro_uom;
		$row['move_qty'] = $pro_qty;
		$row['src_wh_id'] = 0;
		$row['src_location_id'] = 0;
		$row['dest_wh_id'] = $wh_id;
		$row['dest_location_id'] = 0;
		$row['status'] = '0';
		$row['is_deleted'] = '0';
		$data = $M->create($row);
		$res = $M->add($data);*/
		return true;
	}

	/**
	 * 入库上架时，库存表变化，调整库存量
	 * @param 
	 * $wh_id 仓库id
	 * $location_id 库位id
	 * $refer_code 关联单号
	 * $pro_code sku编号
	 * $pro_qty 产品数量
	 * $pro_uom 产品计量单位
	 * $status 库存状态
	 * )
	 */
	public function adjustStockByShelves($wh_id,$location_id,$refer_code,$batch,$pro_code,$pro_qty,$pro_uom,$status){
		$stock = D('stock');
		//减待上架库存
		/*$map['wh_id'] = $wh_id;
		$map['location_id'] = '0';
		$map['pro_code'] = $pro_code;
		$map['batch'] = $refer_code;
		$map['status'] = 'unknown';
		$res = $stock->field('id,prepare_qty')->where($map)->find();
		
		if(empty($res)) {
			return false;
		}
		unset($map);*/
		/*$map['id'] = $res['id'];
		$data['prepare_qty'] = $res['prepare_qty'] - $pro_qty;
		$data = $stock->create($data);
		$res = $stock->where($map)->save($data);
		if($res == false) {
			return false;
		}*/
		//增加库存
		$row['wh_id'] = $wh_id;
		$row['location_id'] = $location_id;
		$row['pro_code'] = $pro_code;
		$row['batch'] = $batch;
		$row['status'] =$status;
		
		$res = $stock->where($row)->find();
		
		if(empty($res)) {
			$row['prepare_qty'] = 0;
			$row['stock_qty'] = $pro_qty;
			$row['assign_qty'] = 0;	
			
			$data = $stock->create($row);

			$res = $stock->add($data);

			$log_old_qty = 0;
			$log_new_qty = $pro_qty;
		}
		else{
			$log_old_qty = $res['stock_qty'];
			$log_new_qty = $res['stock_qty'] + $pro_qty;

			$map['id'] = $res['id'];
			$data['stock_qty'] = $res['stock_qty'] + $pro_qty;
			$data = $stock->create($data,2);
			$res = $stock->where($map)->save($data);
			unset($map);
		}
		if($res == false) {
			return false;
		}
		unset($row);
		unset($data);

		//减待上架库存 增加已上量
		$map['refer_code'] = $refer_code;
		$map['pro_code'] = $pro_code;
		$map['pro_uom'] = $pro_uom;
		M('stock_bill_in_detail')->where($map)->setDec('prepare_qty',$pro_qty);
		M('stock_bill_in_detail')->where($map)->setInc('done_qty',$pro_qty);
		unset($map);
		
		//写入库存交易日志
		$stock_move_data = array(
			'wh_id' => $wh_id,
			'location_id' => $location_id,
			'pro_code' => $pro_code,
			'type' => 'move',
			'refer_code' => $refer_code,
			'direction' => 'IN',
			'move_qty' => $pro_qty,
			'old_qty' => $log_old_qty,
			'new_qty' => $log_new_qty,
			'batch' => $batch,
			'status' => $status,
			);
		$stock_move = D('StockMoveDetail');
		$stock_move_data = $stock_move->create($stock_move_data);
		$stock_move->data($stock_move_data)->add();

		return ture;
	}

	/**
	* 移库操作 库存表变化，调整库存量
	* @param 
	* $params = array(
	* 		0 => array(
	* 			'variable_qty' => 50,
	* 			'wh_id'=>'xxx',
	*			'src_location_id'=>xxxx,
	*			'dest_location_id'=>xxxx,
	*			'pro_code'=>xxxxx,
	*			'batch'=>xxxx,
	* 			)
	*		1 => array(
	* 			'variable_qty' => 80,
	* 			'wh_id'=>'xxx',
	*			'src_location_id'=>xxxx,
	*			'dest_location_id'=>xxxx,
	*			'pro_code'=>xxxxx,
	*			'batch'=>xxxx,
	* 			)
	* )
	*/
	public function adjustStockByMove($params = array()){
		//返回信息
		$result = array('status'=>0,'msg'=>'参数有误');
		foreach($params as $param){
			unset($result);
			if($param['variable_qty'] == 0 || 
				empty($param['wh_id']) || 
				empty($param['src_location_id']) || 
				empty($param['dest_location_id']) || 
				empty($param['pro_code']) || 
				empty($param['batch'])){

				//添加错误信息
				$result[] = array('status'=>0,'msg'=>'参数有误');
				continue;
			}
			//判断目标库位上是否有商品
			$map['location_id'] = $param['dest_location_id'];
			$map['pro_code'] = $param['pro_code'];
			$map['batch'] = $param['batch'];
			$dest_stock_info = M('Stock')->where($map)->find();
			unset($map);

			//如果没有记录，则新加一条记录
			if(empty($dest_stock_info)){
				$add_info['wh_id'] = $param['wh_id'];
				$add_info['location_id'] = $param['dest_location_id'];
				$add_info['pro_code'] = $param['pro_code'];
				$add_info['batch'] = $param['batch'];
				$add_info['status'] = $param['status'];
				$add_info['stock_qty'] = $param['variable_qty'];
				$add_info['assign_qty'] = 0;
				$add_info['prepare_qty'] = 0;

				try{
					M('Stock')->data($add_info)->add();

					//减少原库存量
					$map['location_id'] = $param['src_location_id'];
					$map['pro_code'] = $param['pro_code'];
					$map['batch'] = $param['batch'];
				
					M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);
					unset($map);
					$result[] = array('status'=>'succ');
				}catch(Exception $e){
					//添加错误信息
					$result[] = array('status'=>'err','msg'=>'添加库存记录错误');
				}
				
			}
			//如果有记录，则更新记录
			else{
				//如果变化量大于0 增加目标库存 减少原库存
				if($param['variable_qty'] > 0){
					try{
						//增加目标库存
						$map['location_id'] = $param['dest_location_id'];
						$map['pro_code'] = $param['pro_code'];
						$map['batch'] = $param['batch'];
						M('Stock')->where($map)->setInc('stock_qty',$param['variable_qty']);

						//减少原库存
						$map['location_id'] = $param['src_location_id'];
						M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);
						unset($map);
						$result[] = array('status'=>'succ');
					}catch(Exception $e){
						//添加错误信息
						$result[] = array('status'=>'err','msg'=>'变更数量错误');
					}
				}
			}
			//创建库存移动记录
			/*$stock_move_data = array
				'wh_id' => $param['wh_id'],
				'type' => 'move_location',
				'batch' => $param['batch'],
				'pro_code' => $param['pro_code'],
				'move_qty' => $param['variable_qty'],
				'price_unit' => 0,
				'src_location_id' => $param['src_location_id'],
				'dest_location_id' => $param['dest_location_id'],
				);
			$stock_move = D('stock_move');
			$stock_move_data = $stock_move->create($stock_move_data);
			$stock_move->data($stock_move_data)->add();
			unset($stock_move_data);
			unset($stock_move);
			*/

		}

		return $result;
	}


	/**
	* 根据货品号，货品名称，库位编号，库存状态，查询库存记录
	* $params = array(
	*	'pro_code' => 'xxxx',
	*	'pro_name' => 'xxxx',
	*	'location_code' => 'xxxx',
	*	'status' => 'xxxxx',
	* )
	* return array $stock_infos
	*/
	public function getStockInfosByCondition($params = array()){
		$pro_code = $params['pro_code'];
		$pro_name = $params['pro_name'];
		$location_code = $params['location_code'];
		$stock_status = $params['stock_status'];

		$map = array();
		//根据pro_code添加map
		if($pro_code){
			$map['stock.pro_code'] = array('LIKE','%'.$pro_code.'%');
		}
		//根据pro_name查询对应的pro_code
		if($pro_name){
			$SKUs = A('Pms','Logic')->get_SKU_by_pro_name($pro_name);
			$pro_codes = array();
			foreach($SKUs['list'] as $SKU){
				$pro_codes[] = $SKU['sku_number'];
			}
			$map['stock.pro_code'] = array('in',$pro_codes);
		}
		//根据库位编号 查询对应的location_id
		if($location_code){
			$location_map['code'] = array('LIKE','%'.$location_code.'%');
			$location_ids = M('Location')->where($location_map)->getField('id',true);
			if(empty($location_ids)){
				$location_ids = array(0);
			}

			$map['stock.location_id'] = array('in',$location_ids);
		}

		if($stock_status == 'qualified'){
			$map['stock.status'] = array('eq','qualified');
		}
		if($stock_status == 'unqualified'){
			$map['stock.status'] = array('eq','unqualified');
		}

		$stock_infos = M('Stock')->where($map)->select();

		return $stock_infos;
	}

	/**
	* 创建库存记录 并添加库存交易日志
	* $params = array(
	*	'wh_id' => xxx,
	*	'location_id' => xxx,
	*	'pro_code' => xxx,
	*	'batch' => xxxx,
	*	'status' => xxx,
	*	'stock_qty' => xxxx,
	*	'assgin_qty' => xxxx,
	*	'prepare_qty' => xxx,
	* )
	*/
	public function addStock($params = array()){
		if(!is_array($params)){
			return false;
		}

		if(empty($params['location_id']) || empty($params['pro_code']) || empty($params['batch']) || empty($params['stock_qty'])){
			return false;
		}

		$add_data = $params;

		//如果状态为空 则读取location对应的默认状态
		if(empty($params['status'])){
			$map['id'] = $params['location_id'];
			$location_info = M('Location')->where($map)->find();
			$add_data['wh_id'] = $location_info['wh_id'];
			$add_data['status'] = $location_info['status'];
			unset($map);
		}

		$add_data['stock_qty'] = (empty($params['stock_qty'])) ? 0 : $params['stock_qty'];
		$add_data['assgin_qty'] = (empty($params['assgin_qty'])) ? 0 : $params['assgin_qty'];
		$add_data['prepare_qty'] = (empty($params['prepare_qty'])) ? 0 : $params['prepare_qty'];

		//插入记录
		$stock = D('Stock');
		$add_data = $stock->create($add_data);
		$stock->data($add_data)->add();

		//写入库存交易记录
		$stock_move_data = array(
			'wh_id' => session('user.wh_id'),
			'location_id' => $params['location_id'],
			'pro_code' => $params['pro_code'],
			'type' => 'move',
			'refer_code' => $params['refer_code'],
			'direction' => 'IN',
			'move_qty' => $params['stock_qty'],
			'old_qty' => 0,
			'new_qty' => $params['stock_qty'],
			'batch' => $params['batch'],
			'status' => $add_data['status'],
			);
		$stock_move = D('StockMoveDetail');
		$stock_move_data = $stock_move->create($stock_move_data);
		$stock_move->data($stock_move_data)->add();

		return true;
	}

	/**
	* 插入库存记录时 检查目标库位是否允许 混货 混批次
	* @param
	* $params = array(
	* 	'location_id' => xxx,
	*	'wh_id' => xxxx,
	*	'status' => xxxx,
	*	'wh_id' => xxxx,
	* );
	*/
	public function checkLocationMixedProOrBatch($params = array()){
		if(empty($params) || empty($params['location_id']) || empty($params['wh_id'])){
			return array('res'=>false,'msg'=>'参数有误');
		}

		$map['location_id'] = $params['location_id'];
		$location_detail = M('location_detail')->field('is_mixed_pro,is_mixed_batch')->where($map)->find();
		unset($map);

		if($location_detail['is_mixed_pro'] ==2 || $location_detail['is_mixed_batch'] == 2) {
			//检查库位上的货品
			$map['location_id'] = $params['location_id'];
			$map['wh_id'] = $params['wh_id'];
			$map['status'] = $params['status'];
			$map['stock_qty'] = array('neq','0');
			$map['is_deleted'] = 0;
			$res = M('stock')->field('pro_code,batch,status')->group('pro_code,status')->where($map)->select();

			if(!empty($res)) {
				if($location_detail['is_mixed_pro'] == 2) {
					foreach ($res as $key => $val) {
						if($val['pro_code'] != $params['pro_code']) {
							return array('res'=>false,'msg'=>'该库位不允许混放货品。');
						}
					}
				}
				if($location_detail['is_mixed_batch'] == 2) {
					foreach ($res as $key => $val) {
						if($val['batch'] != $params['batch']) {
							return array('res'=>false,'msg'=>'该库位不允许混放批次。');
						}
					}
				}
			}
		}
		return array('res'=>true);
	}
}