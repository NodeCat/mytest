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
	public function adjust_stock_by_shelves($params){
		$stock_model = D('Stock');
		foreach($params as $param){
			//查看是否有记录
			$where_str = '';
			$where_str = where_array_to_str($param['where']);
			$stock_info = $stock_model->where($where_str)->find();
			
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

				$where_str = where_array_to_str($param['where']);
				
				//待上量减少
				M('Stock')->where($where)->setDec('assign_qty',$param['variable_qty']);
				//库存量增加
				M('Stock')->where($where)->setInc('stock_qty',$param['variable_qty']);
			}
			//添加库存移动记录
			//to do .....
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