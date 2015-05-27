<?php
namespace Wms\Controller;
use Think\Controller;
class StockBillInTypeController extends CommonController {
	protected $columns = array (   
		'id' => '',   
		'type' => '单据类型',   
		'name' =>'类型名称',  
	);
    protected $query   = array (
        'stock_bill_in_type.type' => array (
            'title' => '单据类型',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'stock_bill_in_type.name' => array (
            'title' => '类型名称',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
    );
	public function _before_index() {
		$this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr =array( 
            'edit'=>array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['add']),'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
	}
}