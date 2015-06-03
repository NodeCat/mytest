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
            array('name'=>'add', 'show' => true,'new'=>'true'), 
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
        $data['real_qty'] = 0;
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
        $erp_process = M('erp_process');
        $process = $erp_process->where($map)->find();
        
        //是否已经批准
        if ($process['status'] != 'confirm') {
            $this->msgReturn(false, '非新建加工单');
        }
        
        //更新状态
        $data['status'] = 'pass'; //批准
        $erp_process->where($map)->save($data);
        unset($map);
        unset($data);
        
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
            /**
             * 组合状态下 分别在erp wms上创建单据
             * 父SKU创建入库及入库详情单
             * 子SKU创建出库及出库详情单
             */
            //-----erp start-----
            
            //写入加工入库单 父SKU
            $process_in = D('ProcessIn');
            $data['wh_id'] = $process['wh_id']; //所属仓库
            $data['code'] = get_sn('erp_pro_in'); //加工入库单号
            $data['refer_code'] = $process['code']; //关联加工单号
            $data['process_type'] = $process['type']; //类型 组合 or 拆分
            $data['status'] = 'prepare'; //状态 待入库
            $data['remark'] = $process['remark']; //备注

            //写入加工入库单详情 父SKU
            $detail_data['pro_code'] = $process['p_pro_code']; //sku编号
            $detail_data['batch'] = $process['code']; //批次 关联加工单号
            $detail_data['plan_qty'] = $process['plan_qty']; //计划量
            $detail_data['real_qty'] = $process['real_qty']; //实际量
            $detail_data['status'] = 'prepare'; //状态 
            //关联操作
            $data['detail'] = $detail_data;
            
            $process_in->relation('detail')->add($data);
            unset($data);
            unset($detail_data);
            
            //写入加工出库单 子SKU
            $process_out = D('ProcessOut');
            $data['wh_id'] = $process['wh_id']; //所属仓库
            $data['code'] = get_sn('erp_pro_out'); //出库单号
            $data['refer_code'] = $process['code']; //关联加工单号
            $data['process_type'] = $process['type']; //出库类型
            $data['status'] = 'prepare'; //状态
            $data['remark'] = $process['remark']; //备注
            
            //写入加工出库单详情 子SKU详情
            $company_id = 1; //所属系统 默认大厨网
            foreach($process_relation as $k => $val){
                $detail_data = array();
                $company_id = $val['company_id']; //所属系统
                $detail_data['pro_code'] = $val['c_pro_code']; //sku编号
                $detail_data['batch'] = $process['code']; //批次＝＝加工出库单号
                $detail_data['plan_qty'] = $process['plan_qty'] * $val['ratio']; //计划出库量 父sku * 比例
                $detail_data['real_qty'] = $process['real_qty'] * $val['ratio']; //实际出库量 子sku ＊ 比例
                $detail_data['status'] = 'prepare'; //壮态 待出库
                //关联操作
                $data['detail'][] = $detail_data; 
            }
            $process_out->relation('detail')->add($data);
            unset($data);
            unset($detail_data);
            //-----------erp end--------
            
            //-----------wms start------
            //写入wms入库单
            //获取入库类型id
            $name = 'wms_pro_in'; //入库类型名称
            $stock_type = D('stock_bill_in_type');
            $numbs = M('numbs');
            $map['name'] = $name;
            $type_info = $numbs->field('prefix')->where($map)->find();
            $id_info = $stock_type->field('id')->where(array('type' => $type_info['prefix']))->find();
            unset($map);
            //创建数据
            $data['code'] = get_sn($name); //入库单号
            $data['wh_id'] = $process['wh_id']; //仓库id
            $data['type'] = $id_info['id']; //入库类型ID
            $data['company_id'] = $company_id; //所属系统
            $data['refer_code'] = ''; //关联采购单号
            $data['pid'] = 0; //关联采购单号ID
            $data['batch_code'] = get_sn('batch'); //批次号
            $data['partner_id'] = 0; //供应商
            $data['remark'] = $process['remark']; //备注
            $data['status'] = 21; //状态 21待入库
            
            //创建wms入库详情单数据
            $detail_data['wh_id'] = $process['wh_id']; //所属仓库
            $detail_data['refer_code'] = $data['code']; //关联入库单号
            $detail_data['pro_code'] = $process['p_pro_code']; //SKU编号
            $detail_data['expetced_qty'] = $process['plan_qty']; //预计数量
            $detail_data['prepare_qty'] = 0; //待上架量
            $detail_data['done_qty'] = 0; //已上架量
            $detail_data['pro_uom'] = '件';
            $detail_data['remark'] = $process['remark'];
            
            //调用PMS接口根据编号查询SKU名称规格
            $pms = D('Pms', 'Logic');
            $sku_info = $pms->get_SKU_field_by_pro_codes($process['p_pro_code']);
            $detail_data['pro_name'] = $sku_info[$process['p_pro_code']]['name']; //SKU名称
            $detail_data['pro_attrs'] = $sku_info[$process['p_pro_code']]['pro_attrs_str']; //SKU规格
            
            //写入入库单
            $stock_in = D('StockIn');
            $data['detail'] = $detail_data;
            $stock_in->relation('detail')->add($data);
            unset($data);
            unset($detail_data);
            
            //创建出库数据
            $data['biz_type'] = $company_id;//所属系统
            //查询仓库code
            $wh = M('warehouse');
            $code_arr = $wh->field('code')->where(array('id', $process['wh_id']))->find();
            $data['picking_type_id'] = $code_arr['code']; //所属仓库
            $data['stock_out_type'] = 'MNO'; //出库类型 加工出库
            $data['return_type'] = true; //定义接口不输出数据
            foreach ($process_relation as $v) {
                //出库sku
                $data['product_list'][] = array(
                	    'product_code' => $v['c_pro_code'], //sku编号
                    'qty' => $v['ratio'] * $process['plan_qty'], //出库量
                );
            }
            //创建API对象
            $API = new \Wms\Api\StockOutApi();
            //写入出库单
            $_POST = array();
            $_POST = $data;
            //调用stockout方法自动生成出库单
            $API->stockout();
            //-----------wms end-------------
            $this->msgReturn(true, '已批准');
        } else {
            //拆分
            /**
             * 拆分状态下分别在 erp wms上创建单据
             * 父SKU创建出库及出库详情单
             * 子SKU创建入库及入库详情单
             */
            //--------------erp start-------
            //父SKU加工出库单
            $data['wh_id'] = $process['wh_id']; //所属仓库
            $data['code'] = get_sn('erp_pro_out'); //加工入库单号
            $data['refer_code'] = $process['code']; //关联加工单号
            $data['process_type'] = $process['type']; //类型 组合 or 拆分
            $data['status'] = 'prepare'; //状态 待入库
            $data['remark'] = $process['remark']; //备注
            
            //父SKU加工出库单详情
            $detail_data['pro_code'] = $process['p_pro_code']; //sku编号
            $detail_data['batch'] = $process['code']; //批次 关联加工单号
            $detail_data['plan_qty'] = $process['plan_qty']; //计划量
            $detail_data['real_qty'] = $process['real_qty']; //实际量
            $detail_data['status'] = 'prepare'; //状态
            //关联操作
            $data['netail'] = $detail_data;
            $process_out = D('ProcessOut');
            $process_out->relation('netail')->add($data);
            unset($data);
            unset($detail_data);
            
            //子SKU加工入库单
            $data['wh_id'] = $process['wh_id']; //所属仓库
            $data['code'] = get_sn('erp_pro_in'); //出库单号
            $data['refer_code'] = $process['code']; //关联加工单号
            $data['process_type'] = $process['type']; //出库类型
            $data['status'] = 'prepare'; //状态
            $data['remark'] = $process['remark']; //备注
            
            //子SKU加工入库单详情
            $company_id = 1; //所属系统 默认大厨网
            foreach($process_relation as $k => $val){
                $detail_data = array();
                $company_id = $val['company_id']; //所属系统
                $detail_data['pro_code'] = $val['c_pro_code']; //sku编号
                $detail_data['batch'] = $process['code']; //批次＝＝加工出库单号
                $detail_data['plan_qty'] = $process['plan_qty'] * $val['ratio']; //计划出库量 父sku * 比例
                $detail_data['real_qty'] = $process['real_qty'] * $val['ratio']; //实际出库量 子sku ＊ 比例
                $detail_data['status'] = 'prepare'; //壮态 待出库
                //关联操作
                $data['netail'][] = $detail_data;
            }
            $process_in = D('ProcessIn');
            $process_in->relation('netail')->add($data);
            unset($data);
            unset($detail_data);
            
            //-------------erp end---------
            //-------------wms start-------
            
            //wms父SKU入库单
            //获取入库类型id
            $name = 'wms_pro_in'; //入库类型名称
            $stock_type = D('stock_bill_in_type');
            $numbs = M('numbs');
            $map['name'] = $name;
            $type_info = $numbs->field('prefix')->where($map)->find();
            $id_info = $stock_type->field('id')->where(array('type' => $type_info['prefix']))->find();
            unset($map);
            $data['code'] = get_sn($name); //入库单号
            $data['wh_id'] = $process['wh_id']; //仓库id
            $data['type'] = $id_info['id']; //入库类型ID
            $data['company_id'] = $company_id; //所属系统
            $data['refer_code'] = ''; //关联采购单号
            $data['pid'] = 0; //关联采购单号ID
            $data['batch_code'] = get_sn('batch'); //批次号
            $data['partner_id'] = 0; //供应商
            $data['remark'] = $process['remark']; //备注
            $data['status'] = 21; //状态 21待入库
            
            //wms父SKU入库单详情
            foreach ($process_relation as $value) {
                $detail_data['wh_id'] = $process['wh_id']; //所属仓库
                $detail_data['refer_code'] = $data['code']; //关联入库单号
                $detail_data['pro_code'] = $value['c_pro_code']; //SKU编号
                $detail_data['expetced_qty'] = $process['plan_qty'] * $value['ratio']; //预计数量
                $detail_data['prepare_qty'] = 0; //待上架量
                $detail_data['done_qty'] = 0; //已上架量
                $detail_data['pro_uom'] = '件';
                $detail_data['remark'] = $process['remark'];
                //关联操作
                $data['netail'][] = $detail_data;
            }
            //写入操作
            $stock_in = D('StockIn');
            $stock_in->relation('netail')->add($data);
            unset($data);
            unset($detail_data);
            
            //wms出库单
            $data['biz_type'] = $company_id;//所属系统
            //查询仓库code
            $wh = M('warehouse');
            $code_arr = $wh->field('code')->where(array('id', $process['wh_id']))->find();
            $data['picking_type_id'] = $code_arr['code']; //所属仓库
            $data['stock_out_type'] = 'MNO'; //出库类型 加工出库
            $data['return_type'] = true; //定义此接口不输出数据
            //出库sku
            $data['product_list'][] = array(
            	    'product_code' => $process['p_pro_code'], //sku编号
                'qty' => $process['plan_qty'], //出库量
            );
            //创建API对象
            $API = new \Wms\Api\StockOutApi();
            //写入出库单
            $_POST = array();
            $_POST = $data;
            //调用stockout方法自动生成出库单
            $API->stockout();
            
            //-----------wms end-------------
            $this->msgReturn(true, '已批准');
        }
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