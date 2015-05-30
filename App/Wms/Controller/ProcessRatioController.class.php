<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessRatioController extends CommonController {
	protected $columns = array (
          'id' => '',
          'p_pro_code' => '',
          'c_pro_code' => '',
          'company_id' => '',
          'ratio' => '',
          'created_user' => '',
          'created_time' => '',
          'updated_user' => '',
          'updated_time' => '',
          'is_deleted' => '',
    );
	protected $query   = array (
	       'erp_process_sku_relation.p_pro_code' => array(
		       'title' => '父sku',
	           'query_type' => 'eq',
	           'control_type' => 'text',
	           'value' => 'p_pro_code',
	        ),
	        'erp_process_sku_relation.c_pro_code' => array(
	                'title' => '子sku',
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
	        'erp_process_sku_relation.ratio' => array(
	                'title' => '比例',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => 'ratio',
	        ),
	        
    );
	
}