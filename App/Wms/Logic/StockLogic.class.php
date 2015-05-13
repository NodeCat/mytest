<?php
namespace Develop\Logic;

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
}