<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessRatioController extends CommonController {
	protected $columns = array (
          //'id' => '',
          'p_pro_code' => '父SKU',
          'c_pro_code' => '子SKU',
          'company_id' => '所属系统',
          'ratio' => '比例',
          'created_user' => '创建人',
          'created_time' => '创建时间',
          'updated_user' => '修改人',
          'updated_time' => '修改时间',
          'is_deleted' => '是否生效',
    );
	protected $query   = array (
	       'erp_process_sku_relation.p_pro_code' => array(
		       'title' => '父SKU',
	           'query_type' => 'eq',
	           'control_type' => 'text',
	           'value' => 'p_pro_code',
	        ),
	        'erp_process_sku_relation.c_pro_code' => array(
	                'title' => '子SKU',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => 'c_pro_code',
	        ),
	        'erp_process_sku_relation.company_id' => array(
	                'title' => '所属系统',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => 'company_id',
	        ),
    );
	protected function before_index() {
	    $this->table = array(
	            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
	            'searchbar' => true, //是否显示搜索栏
	            'checkbox'  => true, //是否显示表格中的浮选款
	            'status'    => false, //是否显示列表栏状态
	            'toolbar_tr'=> true, //是否显示列表栏操作
	            'statusbar' => false, //是否显示状态栏
	    );
	    $this->toolbar =array(
	            array('name'=>'add', 'show' => true,'new'=>'true'),
	            array('name'=>'edit', 'show' => false,'new'=>'false'),
	            array('name'=>'delete' ,'show' => false,'new'=>'false'),
	            array('name'=>'import' ,'show' => false,'new'=>'false'),
	            array('name'=>'export' ,'show' => false,'new'=>'false'),
	            array('name'=>'print' ,'show' => false,'new'=>'false'),
	            array('name'=>'setting' ,'show' => false,'new'=>'false'),
	    );
	    $this->status =array(
	            array(
	                    array('name'=>'forbid', 'title'=>'禁用', 'show' => !isset($auth['forbid'])),
	                    array('name'=>'resume', 'title'=>'启用', 'show' => !isset($auth['resume']))
	            ),
	    );
	}
}