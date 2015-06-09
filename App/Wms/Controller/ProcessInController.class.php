<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessInController extends CommonController {
    //列表显示定义  入库单
	protected $columns = array (
	      'company_id' => '所属系统',
	      'wh_id' => '所属仓库',
	      'code' => '入库单号',
	      'refer_code' => '加工单号',
          'p_pro_code' => '父SKU',
	      'p_pro_name' => '父产品名称',
	      'p_pro_norms' => '父产品规格',
          'status' => '状态',
	      'created_time' => '创建时间',
    );
	//搜索字段定义  入库单
	protected $query   = array (
	        'erp_process_in.code' => array(
	                'title' => ' 入库单号',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => 'code',
	        ),
	        'erp_process_in.refer_code' => array(
	                'title' => '加工单号',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => 'refer_code',
	        ),
	        'erp_process_in_detail.p_pro_code' => array(
	                'title' => '父SKU编号',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => '',
	        ),
	        'erp_process_in.wh_id' => array(
	                'title' => '所属仓库',
	                'query_type' => 'eq',
	                'control_type' => 'getField',
	                'value' => 'warehouse.id,name',
	        ),
	         
	       'erp_process_sku_relation.company_id' => array(
		       'title' => '所属系统',
	           'query_type' => 'eq',
	           'control_type' => 'getField',
	           'value' => 'company.id,name',
	        ),
	        'erp_process_in.status' => array(
	                'title' => '状态',
	                'query_type' => 'eq',
	                'control_type' => 'select',
	                'value' => array(
	        	            	'confirm' => '待审核',
			            'pass' => '已生效',
			            'reject' => '已驳回',
			            'close' => '已作废',
		                'make' => '已生产', 
	                ),
	        ),
	        'erp_process_in.created_time' => array(
	                'title' => '创建时间',
	                'query_type' => 'between',
	                'control_type' => 'datetime',
	                'value' => 'created_time',
	        ),
    );
	
	public function before_index() {
	    $this->table = array(
	            'toolbar'   => true,
	            'searchbar' => true,
	            'checkbox'  => true,
	            'status'    => false,
	            'toolbar_tr'=> true,
	    );
	    $this->toolbar_tr =array(
	            array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
	    );
	}
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
	            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
	            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"draft,confirm"),
	            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"draft,confirm"),
	            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"draft,confirm"),
	            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"draft,confirm,pass,reject")
	    );
	    $this->status =array(
	            array(
	                    array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
	                    array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
	            ),
	    );
	}
	
	
	
	/**
	 * 入库单
	 * 列表信息处理
	 * @param unknown $data
	 */
	public function after_lists(&$data) {
	    if (ACTION_NAME == 'index') {
        	    $in_detail = D('ProcessIn');
        	    $code = array();
        	    foreach ($data as &$value) {
        	        $sql = "select *,company.name from erp_process_in e
        	                inner join erp_process d on d.code=e.refer_code
        	                inner join erp_process_sku_relation r on d.p_pro_code=r.p_pro_code
        	                inner join company on r.company_id=company.id
        	                where e.code=" . "'".$value['code'] . "' limit 1";
        	        $result = $in_detail->query($sql);
        	        $value['company_id'] = $result[0]['name'];
        	        $value['p_pro_code'] = $result[0]['p_pro_code'];
        	        
        	        //格式化仓库
        	        $warehouse = M('warehouse');
        	        $map['id'] = $value['wh_id'];
        	        $result_warehouse = $warehouse->field('name')->where($map)->find();
        	        $value['wh_id'] = $result_warehouse['name'];
        	        
        	        //格式化状态
        	        switch ($value['status']) {
        	        	    case 'parpare':
        	        	        $value['status'] = '待入库';
        	        	        break;
        	        	    case 'on':
        	        	        $value['status'] = '已上架';
        	        	        break;
        	        }
        	        $code[] = $value['p_pro_code'];
        	    }
        	    //调用pms接口
        	    $pms = D('Pms', 'Logic');
        	    $p_info = $pms->get_SKU_field_by_pro_codes($code);
        	    if (!empty($p_info)) {
        	        foreach ($data as &$val) {
        	            foreach ($p_info as $key => $v) {
        	                if ($val['p_pro_code'] == $key) {
        	                    $val['p_pro_name'] = $v['name'];
        	                    $val['p_pro_norms'] = $v['pro_attrs_str'];
        	                    break;
        	                }
        	            }
        	        }
        	    }
	    }
	}
	
	/**
	 * 搜索条件处理  入库单
	 * @param unknown $map
	 */
	public function after_search(&$map) {
	   if (array_key_exists('erp_process_in_detail.p_pro_code', $map)) {
	       //根据父sku编号查询父id
	       $M = M('erp_process_in_detail');
	       $where['p_pro_code'] = $map['erp_process_in_detail.p_pro_code'][1];
	       $pids = $M->field('pid')->where($where)->select();
	       
	       foreach ($pids as $value) {
	           $pid_arr[] = $value['pid'];
	       }
	       $map['erp_process_in.id'] = array('in', $pid_arr);
	       unset($where);
	       unset($map['erp_process_in_detail.p_pro_code']);
	   }
	   if (array_key_exists('erp_process_sku_relation.company_id', $map)) {
	       //查询系统
	       $relation = M('erp_process_sku_relation');
	       $process = M('erp_process');
	       $where['company_id'] = $map['erp_process_sku_relation.company_id'][1];
	       $p_sku = $relation->field('p_pro_code')->where($where)->select();
	       $p_sku_arr = array();
	       foreach ($p_sku as $val) {
	           $p_sku_arr[] = $val['p_pro_code'];
	       }
	       $p_sku_arr = array_unique($p_sku_arr);
	       unset($where);
	       $where['p_pro_code'] = array('in', $p_sku_arr);
	       $process_ids = $process->field('code')->where($where)->select();
	       $process_ids_arr = array();
	       foreach ($process_ids as $v) {
	           $process_ids_arr[] = $v['code'];
	       }
	       if (!array_key_exists($map['erp_process_in.refer_code'])) {
	           $map['refer_code'] = array('in', $process_ids_arr);
	       }
	       
	       unset($map['erp_process_sku_relation.company_id']);
	   }
	}
	
	/**
	 * 出库单 
	 */
	public function pindex() {
	    
	}
}