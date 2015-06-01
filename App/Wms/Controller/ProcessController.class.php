<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessController extends CommonController {
	protected $filter = array(
		'type' =>  array(
			'unite' => '组合',
			'split' => '拆分',
		),
		'status' => array(
			'confirm' => '待确认',
			'pass' => '已生效',
			'reject' => '已驳回',
			'close' => '已作废', 
		),
	);
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
            array('name'=>'edit', 'show' => false,'new'=>'true'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
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
            'view'=>array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            'edit'=>array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'true','domain'=>"draft,confirm"), 
            'pass'=>array('name'=>'pass' ,'show' => !isset($auth['audit']),'new'=>'true','domain'=>"draft,confirm"),
            'reject'=>array('name'=>'reject' ,'show' => !isset($auth['audit']),'new'=>'true','domain'=>"draft,confirm"),
            'close'=>array('name'=>'close' ,'show' => !isset($auth['close']),'new'=>'true','domain'=>"draft,confirm,pass,reject")
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => !isset($auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => !isset($auth['resume']))
            ),
        );
    }

    //重写add方法
    public function add(){
    	if(IS_POST){
    		$wh_id = I('wh_id');
    		$plan_qty = I('plan_qty');
    		$type = I('type');
    		$process_pro_code = I('process_pro_code');
    		$remark = I('remark');
    		if(empty($wh_id) || empty($plan_qty) || empty($type) || empty($process_pro_code) ){
				$this->msgReturn(0,'参数错误，请填写类型，仓库，计划数量，加工SKU');
    		}
    		//创建加工单
    		$data['code'] = get_sn('erp_pro_'.$type);
    		$data['wh_id'] = $wh_id;
    		$data['type'] = $type;
    		$data['plan_qty'] = $plan_qty;
            $data['real_qty'] = $plan_qty;
    		$data['p_pro_code'] = $process_pro_code;
    		$data['status'] = 'confirm';
    		$data['remark'] = $remark;
    		$process = D('Process');
    		$data = $process->create($data);
    		$res = $process->data($data)->add();

    		if($res){
    			$this->msgReturn(1,'创建成功','','/Process/view/id/'.$res);
    		}

    		$this->msgReturn(0,'创建失败');
    	}

    	if(I('get.process_pro_code')){
    		//获得加工SKU
    		$process_pro_code = I('process_pro_code');

    		//根据父SKU 查询加工关系
    		$map['p_pro_code'] = $process_pro_code;
    		$process_relation = M('erp_process_sku_relation')->where($map)->select();
    		unset($map);

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

    		//添加stock_qty字段
			$sku = A('Stock','Logic')->add_fields($sku,'stock_qty');

    		//父SKU信息
    		$p_sku_info = $sku[$process_pro_code];
    		//子SKU信息
    		unset($sku[$process_pro_code]);
    		$c_sku_info = $sku;

    		$this->p_sku_info = $p_sku_info;
    		$this->c_sku_info = $c_sku_info;
    		$this->ratio = $ratio;
    		$this->process_pro_code = $process_pro_code;
    	}

    	$this->display();
    }

    //重写view
    public function view() {
        $this->_before_index();
        $this->edit();
    }

    //在edit方法执行之前执行该方法
    protected function before_edit(){
    	$M = D('Process');
		$id = I($M->getPk());
		$map['id'] = $id;
		$process_info = M('erp_process')->where($map)->find();
		unset($map);

		$process_pro_code = $process_info['p_pro_code'];
		//根据父SKU 查询加工关系
		$map['p_pro_code'] = $process_pro_code;
		$process_relation = M('erp_process_sku_relation')->where($map)->select();
		unset($map);

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

		//添加stock_qty字段
		$sku = A('Stock','Logic')->add_fields($sku,'stock_qty');

		//父SKU信息
		$p_sku_info = $sku[$process_pro_code];
		//子SKU信息
		unset($sku[$process_pro_code]);
		$c_sku_info = $sku;

		$this->p_sku_info = $p_sku_info;
		$this->c_sku_info = $c_sku_info;
		$this->ratio = $ratio;
		$this->process_pro_code = $process_pro_code;

    }

    protected function after_save($pid){
    	if(ACTION_NAME == 'edit'){
    		$this->msgReturn(1,'','',U('view','id='.$pid));
    	}
    }

    //批准
    public function pass(){
        $map['id'] = I('id');
        $process = M('erp_process')->where($map)->find();
        unset($map);

        //获得物料清单
        $map['p_pro_code'] = $process['p_pro_code'];
        $process_relation = M('erp_process_sku_relation')->where($map)->select();
        unset($map);

        if($process['status'] != 'confirm'){
            $this->msgReturn(0,'加工单状态异常');
        }

        if(empty($process)){
            $this->msgReturn(0,'加工单未找到');
        }

        //如果是组合
        if($process['type'] == 'unite'){
            //写入加工入库单 父SKU
            $process_in = D('ProcessIn');
            $data['wh_id'] = $process['wh_id'];
            $data['code'] = get_sn('erp_pro_in');
            $data['refer_code'] = $process['code'];
            $data['process_type'] = $process['type'];
            $data['status'] = 'prepare';
            $data['remark'] = $process['remark'];
            $data = $process_in->create($data);

            //写入加工入库单详情 父SKU
            $process_in_detail = D('ProcessInDetail');
            $detail_data['pro_code'] = $process['p_pro_code'];
            $detail_data['batch'] = $process['code'];
            $detail_data['plan_qty'] = $process['plan_qty'];
            $detail_data['real_qty'] = $process['real_qty'];
            $detail_data['status'] = 'prepare';
            $detail_data = $process_in_detail->create($detail_data);
            $data['detail'][] = $detail_data;
            
            $process_in->relation(true)->add($data);
            unset($data);

            //写入加工出库单 子SKU
            $process_on = D('ProcessOn');
            $data['wh_id'] = $process['wh_id'];
            $data['code'] = get_sn('erp_pro_on');
            $data['refer_code'] = $process['code'];
            $data['process_type'] = $process['type'];
            $data['status'] = 'prepare';
            $data['remark'] = $process['remark'];
            $data = $process_on->create($data);

            foreach($process_relation as $k => $val){
                
            }


            var_dump($data);exit;
        }
        

        
        //$process_in = D('ProcessIn');
        //$data = $process_in->create($data);
        //$process_in->data($data)->save();


        var_dump($process,$data);exit;

    }

    //驳回
    public function reject(){
    	$map['id'] = I('id');
    	$data['status'] = 'reject';
    	$res = M('erp_process')->where($map)->save($data);

    	$this->msgReturn($res);
    }

    //作废
    public function close(){
    	$map['id'] = I('id');
    	$data['status'] = 'close';
    	$res = M('erp_process')->where($map)->save($data);

    	$this->msgReturn($res);
    }
}