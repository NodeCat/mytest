<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessRatioController extends CommonController {
    //列表显示定义
	protected $columns = array (
	      'company_id' => '所属系统',
          'p_pro_code' => '父SKU',
	      'p_pro_name' => '父产品名称',
	      'p_pro_norms' => '父产品规格',
          'c_pro_code' => '子SKU',
	      'c_pro_name' => '子产品名称',
	      'c_pro_norms' => '子产品规格',
          'ratio' => '比例',
    );
	//搜索字段定义
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
	
	/**
	 * 定义页面格局
	 */
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
	                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
	                array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
	            ),
	    );
	    $this->toolbar_tr =array(
	            array('name'=>'edit','link'=>'edit','title'=>'编辑', 'show' => true,'new'=>'false'),
	            array('name'=>'delete','link'=>'delete','title'=>'删除', 'show'=>true,'new'=>'true','target'=>'_blank'),
	    );
	}
	/**
	 * 完整添加数据
	 */
	protected function before_add(&$M) {
	    $M->create_user = session()['user']['uid']; //创建人
	    $M->update_user = session()['user']['uid']; //更新人
	}
	/**
	 * 添加完成页面跳转
	 */
	protected function after_add() {
	    $this->msgReturn(true, '', '', U('index'));
	}
	
	/**
	 * 列表信息处理
	 */
	protected function after_lists(&$data) {
	    if (empty($data)) {
	        return;
	    }
	    
	    $pms = D('Pms', 'Logic');
	    $code = array();
	    //获取所有sku编号
	    foreach ($data as $key => $value) {
            $code[] = $value['p_pro_code'];
            $code[] = $value['c_pro_code'];
	    }
	    //调用PMS接口获取产品信息
	    $code_info = $pms->get_SKU_field_by_pro_codes($code);
	    foreach ($data as &$val) {
	        foreach ($code_info as $k => $v) {
	            if ($val['p_pro_code'] == $k) {
	                $val['p_pro_name'] = $v['name'];
	                $val['p_pro_norms'] = $v['pro_attrs_str'];
	            } elseif($val['c_pro_code'] == $k) {
	                $val['c_pro_name'] = $v['name'];
	                $val['c_pro_norms'] = $v['pro_attrs_str'];
	            }
	        }
	    }
	}
}