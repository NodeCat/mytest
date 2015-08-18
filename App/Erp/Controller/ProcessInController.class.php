<?php
namespace Erp\Controller;
use Think\Controller;
class ProcessInController extends CommonController {
    //列表显示定义
	protected $columns = array (
	      'wh_id' => '所属仓库',
	      'code' => '入库单号',
	      'refer_code' => '加工单号',
          'status' => '状态',
	      'created_time' => '创建时间',
    );
	//搜索字段定义
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
	        'erp_process_in.wh_id' => array(
	                'title' => '所属仓库',
	                'query_type' => 'eq',
	                'control_type' => 'getField',
	                'value' => 'warehouse.id,name',
	        ),
	         
	        'erp_process_in.status' => array(
	                'title' => '状态',
	                'query_type' => 'eq',
	                'control_type' => 'select',
	                'value' => array(
	        	            	1 => '待入库',
			            2 => '已入库',
			            3 => '已作废',
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
	            'toolbar'   => false,
	            'searchbar' => true,
	            'checkbox'  => true,
	            'status'    => false,
	            'toolbar_tr'=> true,
	    );
	    $this->toolbar_tr =array(
	            array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
	    );
	}
	
	/**
	 * 列表信息处理
	 * @param unknown $data
	 */
	public function after_lists(&$data) {
	    $in_detail = D('ProcessIn');
	    $code = array();
	    foreach ($data as &$value) {
	        $sql = "select *,company.name,f.p_pro_code from erp_process_in e
	                inner join erp_process d on d.code=e.refer_code
	                inner join erp_process_detail f on f.pid=d.id
	                inner join erp_process_sku_relation r on f.p_pro_code=r.p_pro_code
	                inner join company on r.company_id=company.id
	                where e.code=" . "'".$value['code'] . "' limit 1";
	        $result = $in_detail->query($sql);
	        $value['company_id'] = $result[0]['name'];
	        
	        //格式化仓库
	        $warehouse = M('warehouse');
	        $map['id'] = $value['wh_id'];
	        $result_warehouse = $warehouse->field('name')->where($map)->find();
	        $value['wh_id'] = $result_warehouse['name'];
	        
	        //格式化状态
	        switch ($value['status']) {
	        	    case 1:
	        	        $value['status'] = '待入库';
	        	        break;
	        	    case 2:
	        	        $value['status'] = '已上架';
	        	        break;
	        	    case 3:
	        	        $value['status'] = '已作废';
	        	        break;
	        }
	    }
	}
	
	/**
	 * 搜索条件处理
	 * @param unknown $map
	 */
	public function after_search(&$map) {
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
	       if (!empty($p_sku_arr)) {
        	       $where['p_pro_code'] = array('in', $p_sku_arr);
        	       $process_ids = $process->field('code')->where($where)->select();
        	       $process_ids_arr = array();
        	       foreach ($process_ids as $v) {
        	           $process_ids_arr[] = $v['code'];
        	       }
        	       if (!array_key_exists($map['erp_process_in.refer_code'])) {
        	           $map['erp_process_in.refer_code'] = array('in', $process_ids_arr);
        	       }
	       } else {
	           if (!array_key_exists($map['erp_process_in.refer_code'])) {
	               $map['erp_process_in.refer_code'] = array('eq', null);
	           }
	       }
	       unset($map['erp_process_sku_relation.company_id']);
	       
	   }
	}
    public function before_edit(&$data) {
        if (empty($data)) {
            return;
        }
        $map['pid'] = $data['id'];
        $M = M('erp_process_in_detail');
        $result = $M->where($map)->select();
        if (!empty($result)) {
            $data['pros'] = $result;
            $code = array();
            $num = 0;
            foreach ($data['pros'] as $value) {
                $num += $value['plan_qty'];
                $code[] = $value['pro_code'];
            }
            $code = array_unique($code);
            
            //调用pms接口
            $pms = D('Pms', 'Logic');
            $p_info = $pms->get_SKU_field_by_pro_codes($code);
            if (!empty($p_info)) {
                foreach ($data['pros'] as &$val) {
                    foreach ($p_info as $key => $v) {
                        if ($val['pro_code'] == $key) {
                            $val['pro_name'] = $v['name'];
                            $val['pro_attrs'] = $v['pro_attrs_str'];
                            $val['pro_uom'] = $v['uom_name'];
                            $val['prepare_qty'] = 0; //带上架量
                            break;
                        }
                    }
                }
            }
        }
        unset($map);
        //格式化仓库
        $warehouse = M('warehouse');
        $map['id'] = $data['wh_id'];
        $result_warehouse = $warehouse->field('name')->where($map)->find();
        $data['wh_id'] = $result_warehouse['name'];
         
        //格式化状态
        switch ($data['status']) {
            	case 1:
            	    $data['status'] = '待出库';
            	    break;
            	case 2:
            	    $data['status'] = '已出库';
            	    break;
            	case 3:
            	    $data['status'] = '已作废';
        }
        unset($map);
        
        //创建人
        $map['id'] = $data['created_user'];
        $user = M('user');
        $username = $user->field('nickname')->where($map)->find();
        $data['created_user'] = $username['nickname'];
        $data['total_qty'] = $num;
        $data['cat_total'] = count($data['pros']);
    }
}