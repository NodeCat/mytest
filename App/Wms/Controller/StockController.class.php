<?php
namespace Wms\Controller;
use Think\Controller;
class StockController extends CommonController {
	protected $columns = array('id' => '',
            'area' => '区域标识',
            'pro_code' => '货品号',
            'pro_name' => '货品名称',
            'location_code' => '库位',
            'batch' => '批次',
            'stock_qty' => '在库数量',
            'prepare_qty' => '待上架量', 
            'assign_qty' => '分配数量',
            'available_qty' => '可用数量',
            'status' => '库存状态',
            );
	protected $query   = array (
		'stock.pro_code' => array (
		    'title' => '货品号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
		'stock.batch' => array (
		    'title' => '批次',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
	);
	//页面展示数据映射关系 例如取出数据是qualified 显示为合格
	protected $filter = array(
			'status' => array('qualified' => '合格','unqualified' => '残次'),
		);
	//设置列表页选项
	public function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
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
	/*protected function before_lists(&$M){
		//如果包含空库位 查询location表
		if($this->in_empty_location){
			$M->union("select '','','','','','','','','','','','','','','',code as location_code,'','','' from location where type = 2");
		}	
	}*/

	//lists方法执行后，执行该方法
	protected function after_lists(&$data){
		//整理数据项
		foreach($data as $key => $data_detail){
			$data[$key] = $data_detail;
			//可用量=库存量-配送量
			$data[$key]['available_qty'] = $data_detail['stock_qty'] - $data_detail['assign_qty'];
			//区域标识
			$location_info = A('Location','Logic')->getParentById($data_detail['location_id']);
			$data[$key]['area'] = $location_info['code'];
			unset($location_info);
			//库位
			$data[$key]['location_code'] = $data_detail['location_code'];
		}

		//添加pro_name字段
        $data = A('Pms','Logic')->add_fields($data,'pro_name');

		//查询所有库位信息
		$location_info = M('Location')->where('type = 1')->getField('id,name,code');
		$this->area_info = $location_info;

		//如果包含空库位 查询location表
		if($this->in_empty_location){
			$location_list = M('Location')->where('type = 2')->select();
			foreach($location_list as $key => $location){
				$data_empty_location[$key]['location_code'] = $location['code'];

				//根据location_id 查询对应信息
				$area_info = A('Location','Logic')->getParentById($location['id']);
				$data_empty_location[$key]['area'] = $area_info['code'];
				$data_empty_location[$key]['status'] = $area_info['status'];
			}

			$data = array_merge($data,$data_empty_location);
		}
	}

	//serach方法执行后，执行该方法
	protected function after_search(&$map){
		if(IS_AJAX){
			//用于重新整理查询条件
			//根据区域name location.name 查询对应库位id location.id
			$location_name = I('area');
			if(!empty($location_name)){
				$map_tmp['name'] = $location_name;
				$location_id_by_area = M('Location')->where($map_tmp)->getField('id');
				unset($map_tmp);
				//根据pid（区域id）查找对应的库位id
				$map_tmp['pid'] = $location_id_by_area;
				$location_ids_by_location_name = M('Location')->where($map_tmp)->getField('id',true);
				unset($map_tmp);
			}
			//根据库位code location.code 查询对应库位id location.id
			$location_code = I('location_code');
			if(!empty($location_code)){
				//根据location.code 查询对应的库位id
				$location_map['code'] = array('LIKE',$location_code.'%');
				$location_ids_by_code = M('Location')->where($location_map)->getField('id',true);
				if(empty($location_ids_by_code)){
					$location_ids_by_code = array(-1);
				}
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
				//$map['stock.location_id'] = array('eq',-1);
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
			if(!empty($pro_name) && empty($map['stock.pro_code'])){
				$SKUs = A('Pms','Logic')->get_SKU_by_pro_name($pro_name);
				foreach($SKUs['list'] as $SKU){
					$pro_codes[] = $SKU['sku_number'];
				}
				if(empty($pro_codes)){
					$pro_codes = array(0);
				}
				$map['stock.pro_code'] = array('in',$pro_codes);
			}

			//是否包含空库位
			$in_empty_location = I('in_empty_location');
			if($in_empty_location == 'on'){
				$this->in_empty_location = true;
			}
		}
	}

	//edit方法执行前，执行该方法
	protected function before_edit(&$data){
		if(IS_AJAX){
			$is_stock_move = I('is_stock_move');
			//替换edit显示数据
			//根据warehouse.id 查询仓库name
			$map['id'] = $data['wh_id'];
			$warehouse_name = M('Warehouse')->where($map)->getField('name');
			unset($map);
			$data['wh_name'] = $warehouse_name;

			//根据location.id 查询库位code
			$map['id'] = $data['location_id'];
			$location_code = M('Location')->where($map)->getField('code');
			unset($map);
			$data['location_code'] = $location_code;
		}
		//view edit 展示
		switch($data['status']){
			case 'unqualified':
				$data['status_name'] = '残次';
				break;
			case 'qualified':
				$data['status_name'] = '合格';
				break;
			default:
				break;
		}

		if(ACTION_NAME == 'view'){
			//根据pro_code 查询对应的pro_name
			$pro_codes = array($data['pro_code']);
			$SKUs = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
			$data['pro_name'] = $SKUs[$data['pro_code']]['wms_name'];

			//区域标识
			$location_info = A('Location','Logic')->getParentById($data['location_id']);
			$data['area_name'] = $location_info['name'];

			//根据location.id 查询库位信息
			/*$map['id'] = $data['location_id'];
			$location_info = M('Location')->where($map)->find();
			$data['location_code'] = $location_info['code'];
			unset($map);

			//根据wh_id 查询仓库信息
			$map['id'] = $data['wh_id'];
			$wh_info = M('warehouse')->where($map)->find();
			$data['wh_code'] = $wh_info['code'];*/
		}
		
	}

	//save方法之前，执行该方法
	protected function before_save(&$M){
		if(IS_POST){
			//对比状态是否改变，如果没有改变，报错
			//根据stock.id 查询对应stock.status
			$data = $M->data();
			$map['id'] = $data['id'];
			$old_stock_info = M('Stock')->where($map)->getField('id,status,location_id');
			unset($map);

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
					'type' => 'change_status',
					'refer_code' => 'STOCK'.$res,
					);
				$stock_adjustment = D('Adjustment');
				$adjustment_data = $stock_adjustment->create($adjustment_data);
				$stock_adjustment->data($adjustment_data)->add();

				//创建库存调整单详情
				$adjustment_detail_data = array(
					'adjustment_code' => $adjustment_code,
					'pro_code' => I('pro_code'),
					'origin_qty' => I('stock_qty'),
					'adjusted_qty' => 0,
					'origin_status' => I('origin_status'),
					'adjust_status' => I('status'),
					);
				$stock_adjustment_detail = D('AdjustmentDetail');
				$stock_adjustment_detail_data = $stock_adjustment_detail->create($adjustment_detail_data);
				$stock_adjustment_detail->data($stock_adjustment_detail_data)->add();
			}

			//库存移动完成后触发的方法
			if(I('editStockMove')){
				//创建库存移动记录
				//根据pro_code 查询产品信息
				//$SKUs = A('Pms','Logic')->get_SKU_by_pro_codes(array(I('pro_code')));
				//$SKU = $SKUs['list'][0];
				/*$stock_move_data = array(
					'type' => 'move_location',
					'batch' => I('batch'),
					'pro_code' => I('pro_code'),
					'move_qty' => I('stock_qty'),
					'price_unit' => 0,
					'src_wh_id' => I('wh_id'),
					'dest_wh_id' => I('wh_id'),
					'src_location_id' => I('src_location_id'),
					'dest_location_id' => I('location_id'),
					);
				$stock_move = D('stock_move');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();
				*/
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


	//PDA 库存查询首页
	public function pdaStockIndex(){
		C('LAYOUT_NAME','pda');
		$this->display('Stock:'.'pdaStockIndex');
	}

	//PDA 库存查询接口
	public function pdaStockSearch(){
		if(IS_AJAX){
			$params['pro_code'] = I('pro_code');
			$params['pro_name'] = I('pro_name');
			$params['location_code'] = I('location_code');
			$params['stock_status'] = I('status');

			$stock_infos = A('Stock','Logic')->getStockInfosByCondition($params);
			$count = count($stock_infos);

			if(empty($stock_infos)){
				$data['status'] = 0;
				$data['msg'] = '没有找到任何数据';
			}else{
				$data['status'] = 1;
				$data['data']['redirect_url'] = "/stock/pdaStockShow?pro_name={$params['pro_name']}&pro_code={$params['pro_code']}&location_code={$params['location_code']}&status={$params['stock_status']}&count={$count}";
			}
			
			$this->ajaxReturn($data);
		}
	}

	//PDA 库存展示页面
	public function pdaStockShow(){
		$params['pro_code'] = I('pro_code');
		$params['pro_name'] = I('pro_name');
		$params['location_code'] = I('location_code');
		$params['stock_status'] = I('status');
		//总共多少条记录
		$count = I('count');
		//当前记录
		$cur_page = I('cur_page');
		$cur_page = ($cur_page) ? $cur_page : 1;


		if(empty($params['pro_code']) && empty($params['pro_name']) && empty($params['location_code']) && empty($params['stock_status'])){
			return false;
		}

		//查询库存信息
		$stock_infos = A('Stock','Logic')->getStockInfosByCondition($params);
		$stock_info = $stock_infos[$cur_page - 1];
		$stock_info['available_qty'] = $stock_info['stock_qty'] - $stock_info['assign_qty'];

		$SKUs = A('Pms','Logic')->get_SKU_field_by_pro_codes(array($stock_info['pro_code']));
		$stock_info['pro_name'] = $SKUs[$stock_info['pro_code']]['wms_name'];

		//查询库位code
		$map['id'] = $stock_info['location_id'];
		$location_info = M('Location')->where($map)->find();
		unset($map);
		$stock_info['location_code'] = $location_info['code'];

		//if($stock_info['status'])

		$data['count'] = $count;
		$data['cur_page'] = $cur_page;
		$data['stock_info'] = $stock_info;
		$pre_page = ($cur_page > 1) ? $cur_page - 1 : 1;
		$next_page = ($cur_page >= $count) ? 1 : $cur_page + 1;
		$data['pre_page_url'] = "/stock/pdaStockShow?pro_name={$params['pro_name']}&pro_code={$params['pro_code']}&location_code={$params['location_code']}&status={$params['stock_status']}&count={$count}&cur_page={$pre_page}";
		$data['next_page_url'] = "/stock/pdaStockShow?pro_name={$params['pro_name']}&pro_code={$params['pro_code']}&location_code={$params['location_code']}&status={$params['stock_status']}&count={$count}&cur_page={$next_page}";


		$this->assign($data);

		C('LAYOUT_NAME','pda');
		$this->display('Stock:'.'pdaStockShow');
	}
}