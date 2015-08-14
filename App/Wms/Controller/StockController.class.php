<?php
namespace Wms\Controller;
use Think\Controller;
class StockController extends CommonController {
	protected $columns = array('id' => '',
			'wh_name' => '仓库',
            'area_name' => '区域名称',
            'pro_code' => '货品号',
            'pro_name' => '货品名称',
            'uom_name' => '计量单位',
            'guarantee_period' => '保质期',
            'product_date' => '生产日期',
            'location_code' => '库位',
            'batch' => '批次',
            'stock_qty' => '在库数量',
            'prepare_qty' => '待上架量', 
            'assign_qty' => '分配数量',
            'available_qty' => '可用数量',
            'status' => '库存状态',
            );
	protected $query   = array (
        'stock.area' => array(
            'title' => '区域',
            'query_type' => 'eq',
            'control_type' => 'select',
            'value' => '',
        ),
        'stock.status' => array(
            'title' => '库存状态',
            'query_type' => 'eq',
            'control_type' => 'select',
            'value' => array(
                'qualified' => '合格',
                'unqualified' => '残次',
                'freeze' => '冻结',
            ),
        ),
        'stock.location_code'=>array(
            'title' => '库位',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock.pro_name'=>array(
            'title' => '产品名称',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
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
		'stock.wh_id' => array (
		    'title' => '仓库',
		    'query_type' => 'eq',
		    'control_type' => 'getField',
		    'value' => 'warehouse.id,name',
		),
	);
	//页面展示数据映射关系 例如取出数据是qualified 显示为合格
	protected $filter = array(
			'status' => array('qualified' => '合格','unqualified' => '残次','freeze' => '冻结'),
		);
	//设置列表页选项
	protected function before_index() {
        $this->table = array(
            'toolbar'   => false,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
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
			$data[$key]['area_name'] = $location_info['name'];
			unset($location_info);
			//库位
			$data[$key]['location_code'] = $data_detail['location_code'];
		}

		//添加pro_name字段
        $data = A('Pms','Logic')->add_fields($data,'pro_name');

		//查询所有库位信息
		$map['type'] = 1;
		$map['is_deleted'] = 0;
		$map['wh_id'] = session('user.wh_id');
		$location_info = M('Location')->where($map)->getField('id,name,code');
		unset($map);
		$this->area_info = $location_info;

        //模板赋值
        foreach($location_info as $locationVal){
            $this->query['stock.area']['value'][$locationVal['name']] =  $locationVal['name'];
        }
        $this->assign('query', $this->query);

		//如果包含空库位 查询location表
		if($this->in_empty_location){
			$map['type'] = 2;
			$map['is_deleted'] = 0;
			$map['wh_id'] = session('user.wh_id');
			$location_list = M('Location')->where($map)->select();
			unset($map);
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
			if(!empty($map['stock.area'])){
				$map_tmp['name'] = $map['stock.area'][1];
				$map_tmp['wh_id'] = session('user.wh_id');
				$location_id_by_area = M('Location')->where($map_tmp)->getField('id');
				unset($map_tmp);
				//根据pid（区域id）查找对应的库位id
				$map_tmp['pid'] = $location_id_by_area;
				$location_ids_by_location_name = M('Location')->where($map_tmp)->getField('id',true);
				unset($map_tmp);
                unset($map['stock.area']);
			}
			//根据库位code location.code 查询对应库位id location.id
			if(!empty($map['stock.location_code'])){
				//根据location.code 查询对应的库位id
				$location_map['code'] = array($map['stock.location_code'][0], $map['stock.location_code'][1]);
				$location_map['wh_id'] = session('user.wh_id');
				$location_ids_by_code = M('Location')->where($location_map)->getField('id',true);
				if(empty($location_ids_by_code)){
					$location_ids_by_code = array(-1);
				}
                unset($map['stock.location_code']);
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

			//根据pro_name 查询对应的pro_code
			if(!empty($map['stock.pro_name']) && empty($map['stock.pro_code'])){
				$SKUs = A('Pms','Logic')->get_SKU_by_pro_name($map['stock.pro_name'][1]);
				foreach($SKUs['list'] as $SKU){
					$pro_codes[] = $SKU['sku_number'];
				}
				if(empty($pro_codes)){
					$pro_codes = array(0);
				}
				$map['stock.pro_code'] = array('in',$pro_codes);
                unset($map['stock.pro_name']);
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
            if ($is_stock_move == 'move') {
                $ids = I('id');
                if (!$ids) {
                    $this->msgReturn(0, '没有得到你的请求数据！');
                }
                $where_stock = array();
                $where_stock['stock.id'] = array('in', $ids);
                $where_stock['stock.is_deleted'] = 0;
                $stock_m = M('stock');
                $join = array(' inner join location on stock.location_id=location.id');
                $result = $stock_m->field('stock.*,location.code as location_code')->join($join)->where($where_stock)->order('stock.updated_time DESC')->select();
                foreach ($result as $key => $value) {
                    $pro_name = M('stock_bill_in_detail')->where(array('pro_code'=>$value['pro_code']))->getField('pro_name');
                    $result[$key]['pro_name'] = $pro_name;
                    $result[$key]['avaliable_qty'] = bcsub($value['stock_qty'], $value['assign_qty'], 2);
                }
                if (!$result) {
                    $this->msgReturn(0,'没有找到该记录，请检查表关联或者纪录状态');
                }
                $this->assign('data',$result);
                $this->display('editStockMoveMore');
                exit;

            } else {
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
            

		}
		//view edit 展示
		$data['status_name'] = en_to_cn($data['status']);

		if(ACTION_NAME == 'view'){
			//根据pro_code 查询对应的pro_name
			$pro_codes = array($data['pro_code']);
			$SKUs = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
			$data['pro_name'] = $SKUs[$data['pro_code']]['wms_name'];

			//区域标识
			$location_info = A('Location','Logic')->getParentById($data['location_id']);
			$data['area_name'] = $location_info['name'];

		}
		
	}


	//重写save方法
	protected function save() {
		if(I('editStatus')){
			$params['wh_id'] = I('wh_id');
			$params['location_id'] = I('location_id');
			$params['pro_code'] = I('pro_code');
			$params['batch'] = I('batch');
			$params['origin_status'] = I('origin_status');
			$params['new_status'] = I('status');
			$res = A('Stock','Logic')->adjustStockStatus($params);

			if($res['status'] == 0){
				$this->msgReturn($res['status'],$res['msg']);
			}
		}

		if(I('editStockMove')){

            //判断参数是否合法性
            $result = $this->judgeQualified();
            if (!$result) {
                $this->msgReturn(0,'移库失败。');
            }
            $errorArr = array();
            foreach ($result as $key => $value) {
                $mesg = $this->moveStockController($value);
                if ($mesg) {
                   array_push($errorArr, $mesg);
                }
            }

            if ($errorArr) {
                $errorString = implode(',', $errorArr);
                $this->msgReturn(0,'移库失败。'.$errorString);
            }

            $this->msgReturn(1);

        }
    }

    //批次移动库存判断
    public function judgeQualified(){
        $params = array();
        $result = array();
        $params = I();
        if (!$params) {
            $mes = '请正常操作';
            $this->msgReturn(0,$mes);
        }
        if (!$params['dest_location_id']) {
            $this->msgReturn(0,'目标库位不能为空');
        }
        $dest_location_id = $params['dest_location_id'];
        //查询对应的location_id
        $map['code'] = $dest_location_id;
        $map['wh_id'] = session('user.wh_id');
        $dest_location_code = M('location')->where($map)->find();

        if(empty($dest_location_code)){
            $this->msgReturn(0,'目标库位不存在');
        }

        $dest_location_id = $dest_location_code['id'];
        foreach ($params['stock_id'] as $key => $value) {
            $stock_key_arr       = $params['stock_key'];
            $stock_id_arr        = $params['stock_id'];
            $stock_qty_arr       = $params['stock_qty'];
            $origin_stock_qty_arr= $params['origin_stock_qty'];
            $assign_qty_arr      = $params['assign_qty'];
            $avaliable_qty_arr   = $params['avaliable_qty'];
            $wh_id_arr           = $params['wh_id'];
            $src_location_id_arr = $params['src_location_id'];
            $pro_code_arr        = $params['pro_code'];
            $batch_arr           = $params['batch'];
            $status_arr          = $params['status'];
            if (intval($stock_qty_arr[$key]*1000)<=0) {
                $mes = '序列'.$stock_key_arr[$key].'移动库存量不能小于零的数';
                $this->msgReturn(0,$mes);
            }
            if (strlen(formatMoney($stock_qty_arr[$key], 2, 1))>2) {
                $mes = '序列'.$stock_key_arr[$key].'移动库存量只能精确到两位小数点';
                $this->msgReturn(0,$mes);
            }

            if (intval($assign_qty_arr[$key]*1000)!==0) {
                $mes = '序列'.$stock_key_arr[$key].'分配量必须为0';
                $this->msgReturn(0,$mes);
            }
            if(bccomp($stock_qty_arr[$key], $avaliable_qty_arr[$key],2) == 1){
                $mes = '序列'.$stock_key_arr[$key].'移动量不可大于可用量';
                $this->msgReturn(0,$mes);
            }

            if(bccomp($stock_qty_arr[$key], $origin_stock_qty_arr[$key],2) == 1){
                $mes = '序列'.$stock_key_arr[$key].'移动量不能大于原库存量';
                $this->msgReturn(0,$mes);
            }
    
            $src_location_id = $src_location_id_arr[$key];
            
            if($src_location_id === $dest_location_id){
                $this->msgReturn(0,'请修改库位信息');
            }
            $where = array();
            $where['src_location_id'] = $src_location_id;
            $where['dest_location_id'] = $dest_location_id;
            $where['wh_id'] = $wh_id_arr[$key];
            //$params['status'] = I('status');
            $where['pro_code'] = $pro_code_arr[$key];
            //判断目标库位是否可以 混货 混批次
            $res = A('Stock','Logic')->checkLocationMixedProOrBatch($where);

            if($res['status'] == 0){
                $this->msgReturn(0,'序列'.$stock_key_arr[$key].'移库失败。'.$res['msg']);
            }

            $tmpArr                     = array();
            $tmpArr['xid']              = $stock_key_arr[$key];
            $tmpArr['variable_qty']     = $stock_qty_arr[$key];
            $tmpArr['wh_id']            = $wh_id_arr[$key];
            $tmpArr['src_location_id']  = $src_location_id;
            $tmpArr['dest_location_id'] = $dest_location_id;
            $tmpArr['pro_code']         = $pro_code_arr[$key];
            $tmpArr['batch']            = $batch_arr[$key];
            $tmpArr['status']           = $status_arr[$key];

            array_push($result, $tmpArr);

        }

        return $result;
    }

    //移库操作
    public function moveStockController($movearr = array()){
        //库存移动
        $mes = '';
        $variable_qty = formatMoney($movearr['variable_qty'], 2);
        $params['variable_qty'] = $variable_qty;
        $params['wh_id'] = $movearr['wh_id'];
        $params['src_location_id'] = $movearr['src_location_id'];
        $params['dest_location_id'] = $movearr['dest_location_id'];
        $params['pro_code'] = $movearr['pro_code'];
        $params['batch'] = $movearr['batch'];
        $params['status'] = $movearr['status'];
        $res = A('Stock','Logic')->adjustStockByMove($params);

        if($res['status'] == 0){
            $mes = '序列'.$movearr['xid']. $res['msg'];
            //$this->msgReturn(0,'移库失败。'.$res['msg']);
        }

        return $mes;
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
			//ena13 to pro_code
			$codeLogic = A('Code','Logic');
			$params['pro_code'] = $codeLogic->getProCodeByEna13code($params['pro_code']);
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
		//ena13 to pro_code
		$codeLogic = A('Code','Logic');
		$params['pro_code'] = $codeLogic->getProCodeByEna13code($params['pro_code']);
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