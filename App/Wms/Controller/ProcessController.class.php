<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessController extends CommonController {
	protected $columns = array (
		'id' => '',
		'type' => '加工类型',
		'wh_id' => '仓库',
		'plan_qty' => '计划加工数量',
		'real_qty' => '实际加工数量',
		'status' => '状态',
		'remark' => '备注',
	);
	protected $query   = array (
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
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false'),
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['add']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    //重写add方法
    public function add(){
    	if(IS_POST){
    		//获得加工SKU
    		$process_pro_code = I('process_pro_code');

    		//根据父SKU 查询加工关系
    		$map['p_pro_code'] = $process_pro_code;
    		$process_relation = M('erp_process_sku_relation')->where($map)->select();

    		//整理比率
    		foreach($process_relation as $relation){
    			$ratio[$relation['c_pro_code']] = $relation['ratio'];
    		}

    		if(empty($process_relation)){
    			$this->msgReturn(0,'加工SKU不存在任何加工关系');
    		}

    		//根据pro_code查询对应pro_name
    		$pro_codes[] = $process_pro_code;
    		foreach($process_relation as $relation){
    			$pro_codes[] = $relation['c_pro_code'];
    		}

    		$sku = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);

    		//父SKU信息
    		$p_sku_info = $sku[$process_pro_code];
    		//子SKU信息
    		unset($sku[$process_pro_code]);
    		$c_sku_info = $sku;

    		$this->p_sku_info = $p_sku_info;
    		$this->c_sku_info = $c_sku_info;
    		$this->ratio = $ratio;

    		$this->display();
    	}
    	$this->display();
    }
}