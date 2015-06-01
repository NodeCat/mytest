<?php
namespace Wms\Logic;

class StockLogic{
	/**
	 * 检查是否可以一键出库，按照先进先出原则 
	 * @param 
	 * $wh_id 仓库id
	 * $pro_code sku编号
	 * $pro_qty 产品数量
	 * $refer_code 出库单号
	 * )
	 */
	public function outStockBySkuFIFOCheck($params = array()){
		if(empty($params['wh_id']) || empty($params['pro_code']) || empty($params['pro_qty'])){
			return array('status'=>0,'msg'=>'参数有误！');
		}

		$diff_qty = $params['pro_qty'];

		//根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
		$map['pro_code'] = $params['pro_code'];
		$map['wh_id'] = $params['wh_id'];
		$map['stock.status'] = 'qualified';
		$stock_list = M('Stock')->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')->where($map)->order('stock_batch.product_date')->field('stock.*,stock_batch.product_date')->select();
		unset($map);

		//检查所有的 库存量 是否满足 出库量
		foreach($stock_list as $stock){
			$stock_total += $stock['stock_qty'] - $stock['assign_qty'];
		}

		if($stock_total < intval($params['pro_qty'])){
			return array('status'=>0,'msg'=>'库存总量不足！');
		}

		return array('status'=>1);
	}
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

		$diff_qty = $params['pro_qty'];

		//根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
		$map['pro_code'] = $params['pro_code'];
		$map['wh_id'] = $params['wh_id'];
		//目前只出合格商品
		$map['stock.status'] = 'qualified';
		$stock_list = M('Stock')->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')->where($map)->order('stock_batch.product_date')->field('stock.*,stock_batch.product_date')->select();
		unset($map);

		//检查所有的 库存量 是否满足 出库量
		foreach($stock_list as $stock){
			$stock_total += $stock['stock_qty'] - $stock['assign_qty'];
		}

		if($stock_total < $params['pro_qty']){
			return array('status'=>0,'msg'=>'库存总量不足！');
		}

		$diff_qty = intval($diff_qty);

		//按照现进先出原则 减去最早的批次量
		foreach($stock_list as $stock){
			if($diff_qty > 0){
				//如果库存量小于等于差异量 则删除该条库存记录 然后减去差异量diff_qty
				if($stock['stock_qty'] < $diff_qty){
					$map['id'] = $stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);

					$diff_qty = $diff_qty - $stock['stock_qty'];
					$log_qty = $stock['stock_qty'];
					$log_old_qty = $stock['stock_qty'];
					$log_new_qty = 0;

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

				}elseif($stock['stock_qty'] == $diff_qty){
					$map['id'] = $stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);

					$diff_qty = $diff_qty - $stock['stock_qty'];
					$log_qty = $stock['stock_qty'];
					$log_old_qty = $stock['stock_qty'];
					$log_new_qty = 0;

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

					break;
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

					break;
				}
			}
		}

		
		return array('status'=>1);
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
	* 移库操作 库存表变化，调整库存量 没有批次参数 按照批次先进先出
	* @param 
	* $params = array(
	* 	'variable_qty' => 80,
	* 	'wh_id'=>'xxx',
	*	'src_location_id'=>xxxx,
	*	'dest_location_id'=>xxxx,
	*	'pro_code'=>xxxxx,
	*	'dest_location_status'=>xxxx,
	* )
	*
	*/
	public function adjustStockByMoveNoBatchFIFO($param = array()){
		if($param['variable_qty'] == 0 || 
			empty($param['wh_id']) || 
			empty($param['src_location_id']) || 
			empty($param['dest_location_id']) || 
			empty($param['pro_code']) || 
			empty($param['dest_location_status']) ){

			//添加错误信息
			return array('status'=>0,'msg'=>'参数有误');
		}
		//查询目标库位上是否有商品
		$map['location_id'] = $param['dest_location_id'];
		$map['pro_code'] = $param['pro_code'];
		$map['wh_id'] = $param['wh_id'];
		$dest_stock_info = M('Stock')->where($map)->find();
		unset($map);

		//查询源库位上信息
		$map['location_id'] = $param['src_location_id'];
		$map['pro_code'] = $param['pro_code'];
		$map['wh_id'] = $param['wh_id'];
		$src_stock_list = M('Stock')->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')->where($map)->order('stock_batch.product_date')->field('stock.*,stock_batch.product_date')->group('batch')->select();
		unset($map);

		//检查变化量是否大于总库存量，如果大于则报错
		foreach($src_stock_list as $src_stock){
			$src_total_qty += $src_stock['stock_qty'];
		}


		if(intval($param['variable_qty']) > intval($src_total_qty)){
			return array('status'=>0,'msg'=>'移库量大于库存总量！');
		}

		//剩余移动量
		$diff_qty = intval($param['variable_qty']);

		//整理数据格式
		foreach($src_stock_list as $key => $value){
			$src_stock_list[$key]['stock_qty'] = intval($value['stock_qty']);
			$src_stock_list[$key]['assign_qty'] = intval($value['assign_qty']);
			$src_stock_list[$key]['prepare_qty'] = intval($value['prepare_qty']);
		}

		//按照现进先出原则 减去最早的批次量
		foreach($src_stock_list as $src_stock){
			if($diff_qty > 0){
				//库存量大于剩余移动量
				if($src_stock['stock_qty'] > $diff_qty){
					//增加目标库存量 减少原库存量
					$param['variable_qty'] = $diff_qty;
					$this->incDestStockDecSrcStock($src_stock,$dest_stock_info,$param);
				}

				//库存量等于剩余移动量
				if($src_stock['stock_qty'] == $diff_qty){
					//增加目标库存量 减少原库存量
					$param['variable_qty'] = $diff_qty;
					$this->incDestStockDecSrcStock($src_stock,$dest_stock_info,$param);

					//删除原库存记录
					$map['id'] = $src_stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);
				}

				//库存量小于剩余移动量
				if($src_stock['stock_qty'] < $diff_qty){
					//增加目标库存量 减少原库存量
					$param['variable_qty'] = $src_stock['stock_qty'];
					$this->incDestStockDecSrcStock($src_stock,$dest_stock_info,$param);

					//删除原库存记录
					$map['id'] = $src_stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);

					$diff_qty = $diff_qty - $src_stock['stock_qty'];
				}
			}else{
				break;
			}
		}


		return array('status'=>1);
	}

	/**
	* 	
	*/
	public function incDestStockDecSrcStock($src_stock,$dest_stock_info,$param){
		//如果没有记录，则新加一条记录
		if(empty($dest_stock_info)){
			$add_info['wh_id'] = $param['wh_id'];
			$add_info['location_id'] = $param['dest_location_id'];
			$add_info['pro_code'] = $param['pro_code'];
			$add_info['batch'] = $src_stock['batch'];
			$add_info['status'] = $src_stock['status'];
			$add_info['stock_qty'] = $param['variable_qty'];
			$add_info['assign_qty'] = 0;
			$add_info['prepare_qty'] = 0;

			try{
				//插入数据
				$stock = D('Stock');
				$add_info = $stock->create($add_info);
				$stock->data($add_info)->add();

				//写入库存交易日志
				$stock_move_data = array(
					'wh_id' => $param['wh_id'],
					'location_id' => $param['dest_location_id'],
					'pro_code' => $param['pro_code'],
					'type' => 'move',
					'direction' => 'IN',
					'move_qty' => $param['variable_qty'],
					'old_qty' => 0,
					'new_qty' => $param['variable_qty'],
					'batch' => $src_stock['batch'],
					'status' => $src_stock['status'],
					);
				$stock_move = D('StockMoveDetail');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();

				//减少原库存量 如果和原库存量相等，则直接删除库存记录
				if($param['variable_qty'] == $src_stock['stock_qty']){
					$map['id'] = $src_stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);
				}else{
					$map['location_id'] = $param['src_location_id'];
					$map['pro_code'] = $param['pro_code'];
					$map['batch'] = $src_stock['batch'];
					$map['status'] = $src_stock['status'];
				
					M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);	
				}

				//写入库存交易日志

				$stock_move_data['location_id'] = $param['src_location_id'];
				$stock_move_data['direction'] = 'OUT';
				$stock_move_data['old_qty'] = $src_stock['stock_qty'];
				$stock_move_data['new_qty'] = $src_stock['stock_qty'] - $param['variable_qty'];
				$stock_move = D('StockMoveDetail');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();
				unset($map);
			}catch(Exception $e){
				//添加错误信息
				return array('status'=>0,'msg'=>'添加库存记录错误');
			}
			
		}
		//如果有记录，则更新记录
		else{
			//如果变化量大于0 增加目标库存 减少原库存
			if($param['variable_qty'] > 0){
				try{
					//检查是否有库存记录 如果有 则增加目标库存 如果没有 则新建库存记录
					$map['wh_id'] = $param['wh_id'];
					$map['location_id'] = $param['dest_location_id'];
					$map['pro_code'] = $param['pro_code'];
					$map['batch'] = $src_stock['batch'];
					$map['status'] = $src_stock['status'];
					$stock_info = M('Stock')->where($map)->find();
					if(empty($stock_info)){
						//新增目标库存记录
						$stock_add_data = $map;
						$stock_add_data['stock_qty'] = $param['variable_qty'];
						$stock = D('Stock');
						$stock_add_data = $stock->create($stock_add_data);
						$stock->data($stock_add_data)->add();
					}else{
						//增加目标库存
						M('Stock')->where($map)->setInc('stock_qty',$param['variable_qty']);
					}

					//写入库存交易日志
					$stock_move_data = array(
						'wh_id' => $dest_stock_info['wh_id'],
						'location_id' => $param['dest_location_id'],
						'pro_code' => $param['pro_code'],
						'type' => 'move',
						'direction' => 'IN',
						'move_qty' => $param['variable_qty'],
						'old_qty' => $dest_stock_info['stock_qty'],
						'new_qty' => $dest_stock_info['stock_qty'] + $param['variable_qty'],
						'batch' => $src_stock['batch'],
						'status' => $dest_stock_info['status'],
						);
					$stock_move = D('StockMoveDetail');
					$stock_move_data = $stock_move->create($stock_move_data);
					$stock_move->data($stock_move_data)->add();

					//减少原库存
					$map['location_id'] = $param['src_location_id'];
					M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);

					//写入库存交易日志
					$stock_move_data['location_id'] = $param['src_location_id'];
					$stock_move_data['direction'] = 'OUT';
					$stock_move_data['old_qty'] = $src_stock['stock_qty'];
					$stock_move_data['new_qty'] = $src_stock['stock_qty'] - $param['variable_qty'];
					$stock_move = D('StockMoveDetail');
					$stock_move_data = $stock_move->create($stock_move_data);
					$stock_move->data($stock_move_data)->add();
					unset($map);
				}catch(Exception $e){
					//添加错误信息
					return array('status'=>0,'msg'=>'变更数量错误');
				}
			}
		}
		return true;
	}

	/**
	* 移库操作 库存表变化，调整库存量
	* @param 
	* $params = array(
	* 	'variable_qty' => 80,
	* 	'wh_id'=>'xxx',
	*	'src_location_id'=>xxxx,
	*	'dest_location_id'=>xxxx,
	*	'pro_code'=>xxxxx,
	*	'batch'=>xxxx,
	*	'status'=>xxxx,
	* )
	*
	*/
	public function adjustStockByMove($param = array()){
		if($param['variable_qty'] == 0 || 
			empty($param['wh_id']) || 
			empty($param['src_location_id']) || 
			empty($param['dest_location_id']) || 
			empty($param['pro_code']) || 
			empty($param['batch']) ||
			empty($param['status'])){

			//添加错误信息
			return array('status'=>0,'msg'=>'参数有误');
		}
		//判断目标库位上是否有商品
		$map['location_id'] = $param['dest_location_id'];
		$map['pro_code'] = $param['pro_code'];
		$map['batch'] = $param['batch'];
		$map['wh_id'] = $param['wh_id'];
		$dest_stock_info = M('Stock')->where($map)->find();

		//查询源库位上信息
		$map['location_id'] = $param['src_location_id'];
		$src_stock_info = M('Stock')->where($map)->find();
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
				//插入数据
				$stock = D('Stock');
				$add_info = $stock->create($add_info);
				$stock->data($add_info)->add();

				//写入库存交易日志
				$stock_move_data = array(
					'wh_id' => $param['wh_id'],
					'location_id' => $param['dest_location_id'],
					'pro_code' => $param['pro_code'],
					'type' => 'move',
					'direction' => 'IN',
					'move_qty' => $param['variable_qty'],
					'old_qty' => 0,
					'new_qty' => $param['variable_qty'],
					'batch' => $param['batch'],
					'status' => $param['status'],
					);
				$stock_move = D('StockMoveDetail');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();

				//减少原库存量
				$map['location_id'] = $param['src_location_id'];
				$map['pro_code'] = $param['pro_code'];
				$map['batch'] = $param['batch'];
				$map['status'] = $param['status'];
			
				//检查原库存 如果库存量与变化量相等 则删除数据 如果不等 则减掉库存量
				$stock = M('Stock')->where($map)->find();
				if($stock['stock_qty'] == $param['variable_qty']){
					//删除库存记录
					$map['id'] = $stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);
				}else{
					//减少原库存
					M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);
				}
				

				//写入库存交易日志

				$stock_move_data['location_id'] = $param['src_location_id'];
				$stock_move_data['direction'] = 'OUT';
				$stock_move_data['old_qty'] = $src_stock_info['stock_qty'];
				$stock_move_data['new_qty'] = $src_stock_info['stock_qty'] - $param['variable_qty'];
				$stock_move = D('StockMoveDetail');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();
				unset($map);
			}catch(Exception $e){
				//添加错误信息
				return array('status'=>0,'msg'=>'添加库存记录错误');
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

					//写入库存交易日志
					$stock_move_data = array(
						'wh_id' => $param['wh_id'],
						'location_id' => $param['dest_location_id'],
						'pro_code' => $param['pro_code'],
						'type' => 'move',
						'direction' => 'IN',
						'move_qty' => $param['variable_qty'],
						'old_qty' => $dest_stock_info['stock_qty'],
						'new_qty' => $dest_stock_info['stock_qty'] + $param['variable_qty'],
						'batch' => $param['batch'],
						'status' => $param['status'],
						);
					$stock_move = D('StockMoveDetail');
					$stock_move_data = $stock_move->create($stock_move_data);
					$stock_move->data($stock_move_data)->add();

					
					$map['location_id'] = $param['src_location_id'];

					//检查原库存 如果库存量与变化量相等 则删除数据 如果不等 则减掉库存量
					$stock = M('Stock')->where($map)->find();
					if($stock['stock_qty'] == $param['variable_qty']){
						//删除库存记录
						$map['id'] = $stock['id'];
						M('Stock')->where($map)->delete();
						unset($map);
					}else{
						//减少原库存
						M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);
					}

					

					//写入库存交易日志
					$stock_move_data['location_id'] = $param['src_location_id'];
					$stock_move_data['direction'] = 'OUT';
					$stock_move_data['old_qty'] = $src_stock_info['stock_qty'];
					$stock_move_data['new_qty'] = $src_stock_info['stock_qty'] - $param['variable_qty'];
					$stock_move = D('StockMoveDetail');
					$stock_move_data = $stock_move->create($stock_move_data);
					$stock_move->data($stock_move_data)->add();
					unset($map);
				}catch(Exception $e){
					//添加错误信息
					return array('status'=>0,'msg'=>'变更数量错误');
				}
			}
		}


		return array('status'=>1);
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

		if(!empty($stock_status)){
			$map['stock.status'] = array('eq',$stock_status);
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
	*	'src_location_id' => xxx,
	* 	'dest_location_id' => xxx,
	*	'wh_id' => xxxx,
	*	'status' => xxxx,
	*	'pro_code' => xxxx,
	*	'batch' => xxx
	* );
	*/
	public function checkLocationMixedProOrBatch($params = array()){
		if(empty($params) || empty($params['dest_location_id']) || empty($params['wh_id']) || empty($params['pro_code'])){
			return array('status'=>0,'msg'=>'参数有误');
		}

		//根据location_id 查询目标库位详情
		$map['location_id'] = $params['dest_location_id'];
		$location_detail = M('location_detail')->field('is_mixed_pro,is_mixed_batch')->where($map)->find();
		unset($map);

		if($location_detail['is_mixed_pro'] ==2){
			//检查目标库位上的货品
			$map['wh_id'] = $params['wh_id'];
			$map['location_id'] = $params['dest_location_id'];
			//$map['status'] = $params['status'];
			$map['stock_qty'] = array('neq','0');
			$map['is_deleted'] = 0;
			$dest_stock_info = M('stock')->field('pro_code,batch,status')->group('pro_code,status')->where($map)->select();
			unset($map);

			//如果有记录 则禁止混货
			if(!empty($dest_stock_info)) {
				if($location_detail['is_mixed_pro'] == 2) {
					foreach ($dest_stock_info as $key => $val) {
						if($val['pro_code'] != $params['pro_code']) {
							return array('status'=>0,'msg'=>'该库位不允许混放货品。');
						}
					}
				}
			}
		}

		//检查混批次
		if($location_detail['is_mixed_batch'] == 2) {
			//检查目标库位上的货品
			$map['wh_id'] = $params['wh_id'];
			$map['location_id'] = $params['dest_location_id'];
			//$map['status'] = $params['status'];
			$map['pro_code'] = $params['pro_code'];
			$map['stock_qty'] = array('neq','0');
			$map['is_deleted'] = 0;
			//由于已经是禁止混批次，所以理论上查询的结果只有一条记录
			$dest_stock_info = M('stock')->field('pro_code,batch,status')->group('pro_code,status')->where($map)->select();
			unset($map);

			//根据src_location_id 查询对应的原库位数据
			if(!empty($params['src_location_id'])){
				$map['location_id'] = $params['src_location_id'];
				$map['pro_code'] = $params['pro_code'];
				$map['wh_id'] = $params['wh_id'];
				$map['stock_qty'] = array('neq','0');
				$map['is_deleted'] = 0;
				$src_stock_info = M('Stock')->where($map)->select();

				//如果有不同的批次 则直接返回错误
				foreach($src_stock_info as $src_stock){
					$src_stock_batch[] = $src_stock['batch'];
				}

				if(count($src_stock_batch) > 1){
					return array('status'=>0,'msg'=>'该库位不允许混放批次。');
				}
				unset($map);
			}

			//禁止混批次
			if(!empty($dest_stock_info)) {
				if(!empty($src_stock_info)){
					foreach ($src_stock_info as $key => $val) {
						//由于已经是禁止混批次，所以理论上查询的结果只有一条记录
						if($val['batch'] != $dest_stock_info[0]['batch']) {
							return array('status'=>0,'msg'=>'该库位不允许混放批次。');
						}
					}
				}else{
					foreach($dest_stock_info as $key => $val){
						if($val['batch'] != $params['batch']){
							return array('status'=>0,'msg'=>'该库位不允许混放批次。');
						}
					}
				}
			}
		}
		return array('status'=>1);
	}

	/**
	* 调整已有库存记录的库存状态
	* @param
	* $params = array(
	*	'wh_id' => xxxx,
	* 	'location_id' => xxx,
	*	'pro_code' => xxx,
	*	'batch' => xxxx,
	*	'origin_status' => xxxx,
	*	'new_status' => xxxx,
	* );
	*/
	public function adjustStockStatus($params = array()){
		if(empty($params['wh_id']) || 
			empty($params['location_id']) || 
			empty($params['pro_code']) || 
			empty($params['batch']) || 
			empty($params['origin_status']) || 
			empty($params['new_status']) ){
			return array('status'=>0,'msg'=>'参数有误！');
		}

		//如果没有变更状态 则报错
		if($params['origin_status'] === $params['new_status']){
			return array('status'=>0,'msg'=>'请修改库存状态');
		}

		//根据 wh_id location_id pro_code batch origin_status 查询对应记录id
		$map['wh_id'] = $params['wh_id'];
		$map['location_id'] = $params['location_id'];
		$map['pro_code'] = $params['pro_code'];
		$map['batch'] = $params['batch'];
		$map['status'] = $params['origin_status'];
		$stock_info = M('Stock')->where($map)->find();
		unset($map);

		//变更状态
		//查询是否有变更状态后的记录
		$map['wh_id'] = $stock_info['wh_id'];
		$map['location_id'] = $stock_info['location_id'];
		$map['pro_code'] = $stock_info['pro_code'];
		$map['batch'] = $stock_info['batch'];
		$map['status'] = $params['new_status'];
		$dest_stock_info = M('Stock')->where($map)->find();
		unset($map);
		

		$map['id'] = $stock_info['id'];
		$save_data['status'] = $params['new_status'];
		$res = M('Stock')->where($map)->save($save_data);
		unset($map);

		//如果变更后的状态有记录 则需要合并记录
		if(!empty($dest_stock_info)){
			$this->mergeStockInfo(array('src_stock_id'=>$stock_info['id'],'dest_stock_id'=>$dest_stock_info['id']));
		}

		if($res){
			//写入库存交易日志
			$stock_move_data = array(
				'wh_id' => $params['wh_id'],
				'location_id' => $params['location_id'],
				'pro_code' => $params['pro_code'],
				'type' => 'status',
				'direction' => 'OUT',
				'move_qty' => 0,
				'old_qty' => $stock_info['stock_qty'],
				'new_qty' => $stock_info['stock_qty'],
				'batch' => $params['batch'],
				'status' => $params['origin_status'],
				);
			$stock_move = D('StockMoveDetail');
			$stock_move_data = $stock_move->create($stock_move_data);
			$stock_move->data($stock_move_data)->add();
			
			$stock_move_data['direction'] = 'IN';
			$stock_move_data['status'] = $params['new_status'];
			$stock_move->data($stock_move_data)->add();

			//创建库存调整单
			$adjustment_code = get_sn('adjust');
			$adjustment_data = array(
				'code' => $adjustment_code,
				'type' => 'change_status',
				'refer_code' => 'STOCK'.$stock_info['id'],
				);
			$stock_adjustment = D('Adjustment');
			$adjustment_data = $stock_adjustment->create($adjustment_data);
			$stock_adjustment->data($adjustment_data)->add();

			//创建库存调整单详情
			$adjustment_detail_data = array(
				'adjustment_code' => $adjustment_code,
				'pro_code' => $params['pro_code'],
				'origin_qty' => $stock_info['stock_qty'],
				'adjusted_qty' => 0,
				'origin_status' => $params['origin_status'],
				'adjust_status' => $params['new_status'],
				);
			$stock_adjustment_detail = D('AdjustmentDetail');
			$stock_adjustment_detail_data = $stock_adjustment_detail->create($adjustment_detail_data);
			$stock_adjustment_detail->data($stock_adjustment_detail_data)->add();
		}

		return array('status'=>1);
	}

	/**
	* 合并两条相同的记录 库存量相加
	* 条件：wh_id location_id pro_code batch status 全部相等
	* @param
	* $params = array(
	*	'src_stock_id' => xxxx,
	* 	'dest_stock_id' => xxxx,
	* );
	*/
	public function mergeStockInfo($params){
		if(empty($params['src_stock_id']) || empty($params['dest_stock_id'])){
			return false;
		}

		//根据 src_stock_id dest_stock_id 获得库存记录
		$map['id'] = $params['src_stock_id'];
		$src_stock_info = M('Stock')->where($map)->find();
		unset($map);

		$map['id'] = $params['dest_stock_id'];
		$dest_stock_info = M('Stock')->where($map)->find();
		unset($map);

		//判断wh_id location_id pro_code batch status 是否全部相等
		if($src_stock_info['wh_id'] != $dest_stock_info['wh_id'] || 
			$src_stock_info['location_id'] != $dest_stock_info['location_id'] || 
			$src_stock_info['pro_code'] != $dest_stock_info['pro_code'] || 
			$src_stock_info['batch'] != $dest_stock_info['batch'] || 
			$src_stock_info['status'] != $dest_stock_info['status']
			){
			return false;
		}

		//如果全部相等 则将src合并到dest里面 同时删除src记录
		$map['id'] = $dest_stock_info['id'];
		$data['stock_qty'] = $src_stock_info['stock_qty'] + $dest_stock_info['stock_qty'];
		M('Stock')->where($map)->save($data);
		unset($map);

		//删除src记录
		$map['id'] = $src_stock_info['id'];
		M('Stock')->where($map)->delete();
		unset($map);
		
		return true;
	}

	//为数组添加pro_name字段
	public function add_fields($data = array(),$add_field = ''){
		if(empty($data) || empty($add_field)){
			return $data;
		}

		if($add_field == 'stock_qty'){
			$prepare_data = array();
			//整理pro_codes
			foreach($data as $k => $val){
				$pro_codes[] = $val['pro_code'];
			}

			$map['pro_code'] = array('in',$pro_codes);
			$stock_infos = M('stock')->where($map)->field('sum(stock_qty) as stock_qty, pro_code')->group('pro_code')->select();

			foreach($data as $k => $val){
				$prepare_data[$k] = $val;
				foreach($stock_infos as $stock_info){
					if($val['pro_code'] == $stock_info['pro_code']){
						$prepare_data[$k]['stock_qty'] = $stock_info['stock_qty'];
						break;
					}
				}
			}

			return $prepare_data;
		}

		return $data;
	}
}
