<?php
namespace Wms\Logic;

class StockLogic{
	/**
	 * 入库上架时，库存表变化，调整库存量
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
			$data = $stock->create($data);
			$res = $stock->where($map)->save($data);
		}

		if($res == false) {
			return false;
		}
		unset($row);

		//写库存移动记录
		$M = D('StockMove');
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
		$res = $M->add($data);
		return $res;
	}

	/**
	 * 入库收货时，库存表变化，调整库存量
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
		$map['wh_id'] = $wh_id;
		$map['location_id'] = '0';
		$map['pro_code'] = $pro_code;
		$map['batch'] = $refer_code;
		$map['status'] = 'unknown';
		$res = $stock->field('id,prepare_qty')->where($map)->find();
		
		if(empty($res)) {
			return false;
		}
		unset($map);
		$map['id'] = $res['id'];
		$data['prepare_qty'] = $resp['prepare_qty'] - $pro_qty;
		$data = $stock->create($data);
		$res = $stock->where($map)->save($data);
		if($res == false) {
			return false;
		}
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
		}
		else{
			$map['id'] = $res['id'];
			$data['stock_qty'] = $res['stock_qty'] + $pro_qty;
			$data = $stock->create($data);
			$res = $stock->where($map)->save($data);
		}
		if($res == false) {
			return false;
		}
		unset($row);
		unset($data);
		
		//写库存移动记录
		$M = D('StockMove');
		$row['refer_code'] = $refer_code;
		$row['type'] = 'in';
		$row['pro_code'] = $pro_code;
		$row['pro_uom'] = $pro_uom;
		$row['move_qty'] = $pro_qty;
		$row['src_wh_id'] = 0;
		$row['src_location_id'] = 0;
		$row['dest_wh_id'] = $wh_id;
		$row['dest_location_id'] = $location_id;
		$row['status'] = '0';
		$row['is_deleted'] = '0';
		$data = $M->create($row);
		$res = $M->add($data);
		return $res;
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
		$result = array();
		foreach($params as $param){
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
}