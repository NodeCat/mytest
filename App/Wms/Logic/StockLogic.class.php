<?php
namespace Wms\Logic;

class StockLogic{
	/**
	 * 入库，上架时，库存表变化，调整库存量
	 * @param 
	 * $params = array(
	 * 		0 => array(
	 * 			'variable_qty' => 50,
	 * 			'where' => array('wh_id'=>xxxx,'location_id'=>xxxx,'pro_code'=>xxxxx,'batch'=>xxxx,'status'=>xxxxx),
	 * 			)
	 *		1 => array(
	 * 			'variable_qty' => 80,
	 * 			'where' => array('wh_id'=>xxxx,'location_id'=>xxxx,'pro_code'=>xxxxx,'batch'=>xxxx,'status'=>xxxxx),
	 * 			)
	 * )
	 */
	public function adjust_stock_by_shelves($params = array()){
		$stock_model = M('Stock');
		foreach($params as $param){
			//查看是否有记录
			$map['wh_id'] = $param['where']['wh_id'];
			$map['location_id'] = $param['where']['location_id'];
			$map['pro_code'] = $param['where']['pro_code'];
			$map['batch'] = $param['where']['batch'];
			$stock_info = $stock_model->where($map)->find();

			//如果没有记录，新建一条数据
			if(empty($stock_info)){
				$add_data['location_id'] = $param['where']['location_id'];
				$add_data['pro_id'] = $param['where']['pro_id'];
				$add_data['batch'] = $param['where']['batch'];
				$add_data['status'] = $param['where']['status'];
				$add_data['pro_name'] = $param['where']['pro_name'];
				$add_data['wh_id'] = $param['where']['wh_id'];
				$add_data['store_qty'] = $param['variable_qty'];
				$add_data['assign_qty'] = 0;
				$add_data['prepare_qty'] = 0;

				M('Stock')->data($add_data)->add();
			}else{
				if(empty($param['where'])){
					continue;
				}
				
				//待上量减少
				M('Stock')->where($map)->setDec('assign_qty',$param['variable_qty']);
				//库存量增加
				M('Stock')->where($map)->setInc('stock_qty',$param['variable_qty']);
			}
			//添加库存移动记录
			//to do .....
		}
		
		return true;
	}

	/**
	 * 入库，待上架时，库存表变化，调整库存量
	 * @param 
	 * $params = array(
	 * 		0 => array(
	 * 			'variable_qty' => 50,
	 * 			'where' => array('pro_code'=>xxxxx,'batch'=>xxxx),
	 * 			)
	 *		1 => array(
	 * 			'variable_qty' => 80,
	 * 			'where' => array('pro_code'=>xxxxx,'batch'=>xxxx),
	 * 			)
	 * )
	 */
	public function adjust_stock_by_prepare($params = array()){
		foreach($params as $param){
			//查看是否有记录
			$map['pro_code'] = $param['where']['pro_code'];
			$map['batch'] = $param['where']['batch'];
			$stock_info = M('Stock')->where($map)->find();

			//如果没有记录，新建一条数据
			if(empty($stock_info)){
				$add_data['wh_id'] = 0;
				$add_data['location_id'] = 0;
				$add_data['batch'] = $param['where']['batch'];
				$add_data['status'] = 'unknow';
				$add_data['pro_code'] = $param['where']['pro_code'];
				$add_data['store_qty'] = 0;
				$add_data['assign_qty'] = 0;
				$add_data['prepare_qty'] = $param['variable_qty'];

				M('Stock')->data($add_data)->add();
			}else{
				//待上量增加
				M('Stock')->where($map)->setInc('prepare_qty',$param['variable_qty']);
			}
			//添加库存移动记录
			//to do .....
		}
		
		return true;
	}

	/**
	* 移库操作 库存表变化，调整库存量
	* @param 
	* $params = array(
	* 		0 => array(
	* 			'variable_qty' => 50,
	* 			'where' => array('wh_id'=>'xxx','src_location_id'=>xxxx,'dest_location_id'=>xxxx,'pro_code'=>xxxxx,'batch'=>xxxx),
	* 			)
	*		1 => array(
	* 			'variable_qty' => 80,
	* 			'where' => array('wh_id'=>'xxx','src_location_id'=>xxxx,'dest_location_id'=>xxxx,'pro_code'=>xxxxx,'batch'=>xxxx),
	* 			)
	* )
	*/
	public function adjust_stock_by_move($params = array()){
		foreach($params as $param){
			//判断目标库位上是否有商品
			$map['location_id'] = $param['where']['dest_location_id'];
			$map['pro_code'] = $param['where']['pro_code'];
			$map['batch'] = $param['where']['batch'];
			$dest_stock_info = M('Stock')->where($map)->find();
			unset($map);

			//如果没有记录，则新加一条记录
			if(empty($dest_stock_info)){
				$add_info['wh_id'] = $param['where']['wh_id'];
				$add_info['location_id'] = $param['where']['dest_location_id'];
				$add_info['pro_code'] = $param['where']['pro_code'];
				$add_info['batch'] = $param['where']['batch'];
				$add_info['status'] = 'qualified';
				$add_info['stock_qty'] = $param['variable_qty'];
				$add_info['assign_qty'] = 0;
				$add_info['prepare_qty'] = 0;

				M('Stock')->data($add_info)->add();
			}
			//如果有记录，则更新记录
			else{
				//如果变化量大于0 增加目标库存 减少原库存
				if($param['variable_qty'] > 0){
					//增加目标库存
					$map['location_id'] = $param['where']['dest_location_id'];
					$map['pro_code'] = $param['where']['pro_code'];
					$map['batch'] = $param['where']['batch'];
					M('Stock')->where($map)->setInc('stock_qty',$param['variable_qty']);

					//减少原库存
					$map['location_id'] = $param['where']['src_location_id'];
					M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);
					unset($map);
				}
			}
		}
		
		return true;
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
	public function get_stock_infos_by_condition($params = array()){
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