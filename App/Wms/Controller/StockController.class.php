<?php
namespace Wms\Controller;
use Think\Controller;
class StockController extends CommonController {
	//页面展示数据映射关系 例如取出数据是Qualified 显示为合格
	protected $filter = array(
			'status' => array('qualified' => '合格','unqualified' => '不合格'),
		);
	//设置列表页选项
	public function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['print']),'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
        $this->search_addon = true;
    }
	//lists方法执行前，执行该方法
	protected function before_lists(&$M){
		//整理显示项
		$columns['id'] = '';
		$columns['area'] = '区域标识';
		$columns['pro_code'] = '货品号';
		$columns['pro_name'] = '货品名称';
		$columns['location_code'] = '库位';
		foreach($this->columns as $key => $column){
			$columns[$key] = $column;
		}
		$columns['available_qty'] = '可用量';
		$columns['status'] = '库存状态';
		$this->columns = $columns;
	}

	//lists方法执行后，执行该方法
	protected function after_lists(&$data){
		//整理数据项
		foreach($data as $key => $data_detail){
			$data[$key] = $data_detail;
			//可用量=库存量-配送量
			$data[$key]['available_qty'] = $data_detail['stock_qty'] - $data_detail['assign_qty'];
			//区域标识
			$data[$key]['area'] = $data_detail['location_name'];
			//库位
			$data[$key]['location_code'] = $data_detail['location_code'];
			//转换库存状态显示
			/*if($data_detail['status'] == 'qualified'){
				$data[$key]['status'] = '合格';
			}else{
				$data[$key]['status'] = '不合格';
			}*/
		}

		//添加pro_name字段
        $data = A('Pms','Logic')->add_fields($data,'pro_name');

		//查询所有库位信息
		$location_info = M('Location')->where('type = 1')->getField('id,name,code');
		$this->area_info = $location_info;
	}

	//serach方法执行后，执行该方法
	protected function after_search(&$map){
		if(IS_AJAX){
			//用于重新整理查询条件
			//根据区域name location.name 查询对应库位id location.id
			$location_name = I('area');
			if(!empty($location_name)){
				$location_id_by_area = M('Location')->where('name = "'.$location_name.'"')->getField('id');
				//根据pid（区域id）查找对应的库位id
				$location_ids_by_location_name = M('Location')->where('pid = '.$location_id_by_area)->getField('id',true);
			}
			//根据库位code location.code 查询对应库位id location.id
			$location_code = I('location_code');
			if(!empty($location_code)){
				//根据location.code 查询对应的库位id
				$location_map['code'] = array('LIKE',$location_code.'%');
				$location_ids_by_code = M('Location')->where($location_map)->getField('id',true);
			}
			if(empty($location_ids_by_location_name)){
				$location_ids_by_location_name = $location_ids_by_code;
			}
			if(empty($location_ids_by_code)){
				$location_ids_by_code = $location_ids_by_location_name;
			}
			//取得交集
			$location_ids = array_intersect($location_ids_by_location_name,$location_ids_by_code);
			//添加map
			if(!empty($location_ids)){
				$map['stock.location_id'] = array('in',$location_ids);
			}//else{
				//$map['stock.location_id'] = array('eq',0);
			//}

			//根据stock.status 查询对应stock记录
			//添加map
			$stock_status = I('status');
			if($stock_status == 'qualified'){
				$map['stock.status'] = array('eq','qualified');
			}
			if($stock_status == 'unqualified'){
				$map['stock.status'] = array('eq','unqualified');
			}

			//根据pro_name 查询对应的pro_code
			$pro_name = I('pro_name');
			if(!empty($pro_name)){
				$SKUs = A('Pms','Logic')->get_SKU_by_pro_name($pro_name);
				foreach($SKUs['list'] as $SKU){
					$pro_codes[] = $SKU['sku_number'];
				}
				$map['stock.pro_code'] = array('in',$pro_codes);
			}
		}
	}

	//edit方法执行前，执行该方法
	protected function before_edit(&$data){
		if(IS_AJAX){
			$is_stock_move = I('is_stock_move');
			//替换edit显示数据
			//根据warehouse.id 查询仓库name
			$warehouse_name = M('Warehouse')->where('id = '.$data['wh_id'])->getField('name');
			$data['wh_name'] = $warehouse_name;

			//根据location.id 查询库位code
			$location_code = M('Location')->where('id = '.$data['location_id'])->getField('code');
			$data['location_name'] = $location_code;
		}
		//view edit 展示
		switch($data['status']){
			case 'unqualified':
				$data['status_name'] = '不合格';
				break;
			case 'qualified':
				$data['status_name'] = '合格';
				break;
			default:
				break;
		}

		//根据pro_code 查询对应的pro_name
		$pro_codes = array($data['pro_code']);

		$SKUs = A('Pms','Logic')->get_SKU_by_pro_codes($pro_codes);
		$data['pro_name'] = $SKUs['list'][0]['name'];
	}

	//save方法之前，执行该方法
	protected function before_save(&$M){
		if(IS_POST){
			//对比状态是否改变，如果没有改变，报错
			//根据stock.id 查询对应stock.status
			$data = $M->data();
			$old_stock_info = M('Stock')->where('id = '.$data['id'])->getField('id,status,location_id');
			
			if(I('editStatus')){
				if($old_stock_info[$data['id']]['status'] === $data['status']){
					$this->msgReturn(0,'请修改库存状态');
				}
			}

			if(I('editStockMove')){
				if($old_stock_info[$data['id']]['location_id'] === $data['location_id']){
					$this->msgReturn(0,'请修改库位信息');
				}
			}
		}
	}

	//save方法之后，执行该方法
	protected function after_save($res){
		if(IS_POST){
			//调整状态完成后触发的方法
			if(I('editStatus')){
				//创建库存调整单
				$adjustment_code = get_sn('adjust');
				$adjustment_data = array(
					'code' => $adjustment_code,
					'type' => 'move',
					);
				M('Stock_adjustment')->data($adjustment_data)->add();

				//创建库存调整单详情
				$adjustment_detail_data = array(
					'adjustment_code' => $adjustment_code,
					'pro_code' => I('pro_code'),
					'origin_qty' => I('stock_qty'),
					'adjusted_qty' => 0,
					'origin_status' => I('origin_status'),
					'adjust_status' => I('status'),
					);
				M('Stock_adjustment_detail')->data($adjustment_detail_data)->add();
			}

			//库存移动完成后触发的方法
			if(I('editStockMove')){
				//创建库存移动记录
				//根据pro_code 查询产品信息
				$SKUs = A('Pms','Logic')->get_SKU_by_pro_codes(array(I('pro_code')));
				$SKU = $SKUs['list'][0];
				$stock_move_data = array(
					'type' => 'move_location',
					'batch' => I('batch'),
					'pro_code' => I('pro_code'),
					'move_qty' => I('stock_qty'),
					'price_unit' => 0,
					'src_wh_id' => I('wh_id'),
					'dest_wh_id' => I('wh_id'),
					'src_location_id' => I('location_id'),
					'dest_location_id' => I('location_id'),
					);
				M('stock_move')->data($stock_move_data)->add();
			}
		}
	}

	//调整状态按钮触发的方法
	public function editStatus(){
		$this->editStatus = true;
		$this->edit();
	}

	//库存移动按钮触发的方法
	public function editStockMove(){
		$this->editStockMove = true;
		$this->edit();
	}
}