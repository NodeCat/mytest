<?php
namespace Wms\Controller;
use Think\Controller;
class StockMoveDetailController extends CommonController {
	//页面展示数据映射关系 例如取出数据是qualified 显示为合格
	protected $filter = array(
			'type' => array('in' => '收货','on' => '上架','move_location' => '库存移动'),
		);

    protected $columns = array('id' => '',
            'refer_code' => '关联单据',
            'type' => '类型',
            'batch' => '批次',
            'pro_code' => '产品编号',
            'pro_uom' => '计量单位',
            'move_qty' => '移动量',
            'price_unit' => '单价',
            'src_wh_name' => '原仓库',
            'src_location_name' => '原库位',
            'dest_wh_name' => '目标仓库',
            'dest_location_name' => '目标库位',
            );

    protected $query   = array (
        'stock_move.refer_code' => array (
            'title' => '关联单据',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_move.type' => array (
            'title' => '盘点类型',
            'query_type' => 'eq',
            'control_type' => 'select',
            'value' => array('in' => '收货','on' => '上架','move_location' => '库存移动'),
        ),
        'stock_move.pro_code' => array (
            'title' => '产品编号',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
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
            array('name'=>'add', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    //在search方法执行后，执行该方法
    /*protected function after_search(&$map){
        //替换调整单type查询条件
        if($map['stock_move.type'][1]){
            $map['stock_move.type'][1] = cn_to_en($map['stock_move.type'][1]);
        }
    }*/

}