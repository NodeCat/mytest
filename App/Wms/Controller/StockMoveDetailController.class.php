<?php
namespace Wms\Controller;
use Think\Controller;
class StockMoveDetailController extends CommonController {
	//页面展示数据映射关系 例如取出数据是qualified 显示为合格
	protected $filter = array(
			'type' => array('in' => '收货','on' => '上架','move_location' => '库存移动'),
		);

    protected $columns = array('id' => '',
            'wh_code' => '仓库',
            'created_time' => '操作时间',
            'user_nickname' => '操作人',
            'pro_code' => '货品号',
            'pro_name' => '货品名',
            'location_code' => '库位',
            'refer_code' => '关联单据',
            'type' => '类型',
            'direction' => '方向',
            'move_qty' => '变化数量',
            'batch' => '批次',
            );

    protected $query   = array (
        'stock_move.location_code'=>array(
            'title' => '库位',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.refer_code' => array (
            'title' => '关联单据',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.pro_code' => array (
            'title' => '货品号',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.type' => array (
            'title' => '类型',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.direction' => array (
            'title' => '方向',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.batch' => array (
            'title' => '批次',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.created_time' =>    array (    
            'title' => '操作时间',     
            'query_type' => 'between',     
            'control_type' => 'datetime',     
            'value' => '',   
        ), 
    );
	//设置列表页选项
	protected function before_index() {
        $this->table = array(
            'toolbar'   => true,
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
            array('name'=>'export' ,'show' => true,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    //lists方法执行后，执行该方法
    protected function after_lists(&$data){
        //添加pro_name字段
        $data = A('Pms','Logic')->add_fields($data,'pro_name');
    }

    //edit方法执行前，执行该方法
    protected function before_edit(&$data){
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

    public function export(){
        $query = I('query');
        //必须选择条件才能导出
        $start_time = $query['stock_move.created_time'];
        $end_time = $query['stock_move.created_time_1'];
        if(!$start_time || !$end_time) {
            $this->msgReturn(false,'选择时间范围才能导出数据');
        }
        parent::export();
    }

    //serach方法执行后，执行该方法
    protected function after_search(&$map){
        if(IS_AJAX){
            //根据库位code location.code 查询对应库位id location.id
            if(!empty($map['stock_move.location_code'])){
                //根据location.code 查询对应的库位id
                $location_map['code'] = array($map['stock_move.location_code'][0], $map['stock_move.location_code'][1]);
                $location_ids_by_code = M('Location')->where($location_map)->getField('id',true);
                if(empty($location_ids_by_code)){
                    $location_ids_by_code = array(-1);
                }
                unset($map['stock_move.location_code']);
            }

            //添加map
            if(!empty($location_ids_by_code)){
                $map['stock_move.location_id'] = array('in',$location_ids_by_code);
            }
        }
    }
}