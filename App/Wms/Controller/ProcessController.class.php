<?php
namespace Wms\Controller;
use Think\Controller;
use Wms\Model\Stock_bill_in_detailModel;
class ProcessController extends CommonController {
	protected $filter = array(
	/*	'type' =>  array(
			'unite' => '组合',
			'split' => '拆分',
		),
		'status' => array(
			'confirm' => '待审核',
			'pass' => '已生效',
			'reject' => '已驳回',
			'close' => '已作废',
		    'make' => '已生产', 
		),*/
	);
	protected $columns = array (
		'id' => '',
	    'code' => '加工单号',
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
            array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'true'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($this->auth['add']),'new'=>'true'), 
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
     * 列表字段处理
     * @param unknown $data
     */
    public function after_lists(&$data) {
        $warehouse = M('warehouse');
        $warehouse_info = $warehouse->select();
        //格式化状态
        foreach ($data as &$value) {
            $value['status'] = en_to_cn($value['status']);
            $value['type'] = en_to_cn($value['type']);
            foreach ($warehouse_info as $val) {
                if ($value['wh_id'] == $val['id']) {
                    $value['wh_id'] = $val['name'];
                }
            }
        }
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
        		$this->display('prolist-add');
        		return;
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

    /**
     * 批准加工单操作
     */
    public function pass(){
        $map['id'] = I('id');
        $erp_process = M('erp_process');
        $process = $erp_process->where($map)->find();
        
        //是否已经批准
        if ($process['status'] != 'confirm') {
            $this->msgReturn(false, '非新建加工单');
        }
        
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
        
        $Logic = D('Process', 'Logic');
        //如果是组合
        if($process['type'] == 'unite'){
            /**
             * 组合状态下 分别在erp wms上创建单据
             * 父SKU创建入库及入库详情单
             * 子SKU创建出库及出库详情单
             */
            //-----erp start-----
            
            /**
             *  写入加工入库单 父SKU
             */
            $process_in = D('ProcessIn');
            
            $data = $Logic->make_process_in_stock('parpare', $process);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($process_in->create($data)) {
                $pid = $process_in->add();
            }
            if (!$pid) {
                $this->msgReturn(false,'加工入库单生成失败');
            }
            unset($data);
            
            /**
             * 写入加工入库单详情 父SKU
             */
            $process_in_detail = M('erp_process_in_detail');
            $param = $process;
            $param['pid'] = $pid;
            $param['pro_code'] = $process['p_pro_code'];
            $data = $Logic->make_process_in_stock_detail('parpare', $param);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($process_in_detail->create($data)) {
                $affect = $process_in_detail->add();
            }
            if (!affect) {
                $this->msgReturn(false,'详情写入失败');
            }
            unset($pid); 
            unset($affect);
            unset($data);
            unset($param);
            
            //写入加工出库单 子SKU
            $process_out = D('ProcessOut');
            $data = $Logic->make_process_out_stock('parpare', $process);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($process_out->create($data)) {
                $pid = $process_out->add();
            }
            if (!$pid) {
                $this->msgReturn(false,'加工出库单写入失败');
            }
            unset($data);
            //写入加工出库单详情 子SKU详情
            $process_out_detail = M('erp_process_out_detail');
            $param = $process;
            $param['pid'] = $pid;
            $company_id = 1;
            foreach($process_relation as $k => $val){
                $company_id = $val['company_id'];
                $param['pro_code'] = $val['c_pro_code'];
                $param['plan_qty'] = $process['plan_qty'] * $val['ratio'];
                $data = $Logic->make_process_out_stock_detail('prepare', $param);
                if ($data['status'] == true) {
                    $data = $data['data'];
                }
                if ($process_out_detail->create($data)) {
                    $process_out_detail->add();
                }
            }
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            
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
            
            $stock_in = D('StockIn');
            $param = $process;
            $param['id'] = $id_info['id'];
            $param['name'] = $name;
            $param['company_id'] = $company_id;
            $data = $Logic->make_process_in_stock_wms(21, $param);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($stock_in->create($data)) {
                $pid = $stock_in->add();
            }
            if (!$pid) {
                $this->msgReturn(false,'wms入库单生成失败');
            }
            $refer_code = $data['refer_code'];
            unset($param);
            unset($data);
            
           //创建wms入库详情单数据
            $stock_in_detail = M('stock_bill_in_detail');
            $param = $process;
            $param['pid'] = $pid;
            $param['pro_code'] = $process['p_pro_code'];
            $param['code'] = $refer_code;
            $param['expected_qty'] = $process['plan_qty'];
            $data = $Logic->make_process_in_stock_detail('parpare', $param);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($stock_in_detail->create($data)) {
                $affect = $stock_in_detail->add();
            }
            if (!affect) {
                $this->msgReturn(false,'详情写入失败');
            }
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            
            //创建出库数据
            $data['biz_type'] = $company_id;//所属系统
            //查询仓库code
            $wh = M('warehouse');
            $code_arr = $wh->field('code')->where(array('id', $process['wh_id']))->find();
            $data['picking_type_id'] = $code_arr['code']; //所属仓库
            $data['stock_out_type'] = 'MNO'; //出库类型 加工出库
            $data['return_type'] = true; //定义接口不输出数据
            $data['refer_code'] = $process['code']; //关联单号 ＝＝加工单号
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
            
            
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            unset($map);
            //更新状态
            $map['id'] = I('id');
            $data['status'] = 'pass'; //批准
            $erp_process->where($map)->save($data);
            $this->msgReturn(true, '已批准');
        } else {
            //拆分
            /**
             * 拆分状态下分别在 erp wms上创建单据
             * 父SKU创建出库及出库详情单
             * 子SKU创建入库及入库详情单
             */
            //--------------erp start-------
            
            //写入加工出库单 父SKU
            $process_out = D('ProcessOut');
            $data = $Logic->make_process_out_stock('parpare', $process);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($process_out->create($data)) {
                $pid = $process_out->add();
            }
            if (!$pid) {
                $this->msgReturn(false,'加工出库单写入失败');
            }
            unset($data);
            //写入加工出库单详情 父SKU详情
            $process_out_detail = M('erp_process_out_detail');
            $param = $process;
            $param['pid'] = $pid;
            $param['pro_code'] = $process['p_pro_code'];
            $data = $Logic->make_process_out_stock_detail('prepare', $param);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($process_out_detail->create($data)) {
                $process_out_detail->add();
            }
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            
            //写入加工入库单 子SKU
            $process_in = D('ProcessIn');
            $Logic = D('Process', 'Logic');
            $data = $Logic->make_process_in_stock('parpare', $process);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($process_in->create($data)) {
                $pid = $process_in->add();
            }
            if (!$pid) {
                $this->msgReturn(false,'加工入库单生成失败');
            }
            unset($data);
            //写入加工入库单详情 子SKU
            $process_in_detail = M('erp_process_in_detail');
            
            $param = $process;
            $param['pid'] = $pid;
            foreach ($process_relation as $val) {
                $company_id = $val['company_id'];
                $param['pro_code'] = $val['c_pro_code'];
                $param['plan_qty'] = $process['plan_qty'] * $val['ratio'];
                $data = $Logic->make_process_in_stock_detail('parpare', $param);
                if ($data['status'] == true) {
                    $data = $data['data'];
                }
                if ($process_in_detail->create($data)) {
                    $affect = $process_in_detail->add();
                }
                if (!affect) {
                    $this->msgReturn(false,'详情写入失败');
                }
                
            }
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            
            //-------------erp end---------
            //-------------wms start-------
            
            //写入wms入库单
            //获取入库类型id
            $name = 'wms_pro_in'; //入库类型名称
            $stock_type = D('stock_bill_in_type');
            $numbs = M('numbs');
            $map['name'] = $name;
            $type_info = $numbs->field('prefix')->where($map)->find();
            $id_info = $stock_type->field('id')->where(array('type' => $type_info['prefix']))->find();
            unset($map);
            
            $stock_in = D('StockIn');
            $param = $process;
            $param['id'] = $id_info['id'];
            $param['name'] = $name;
            $param['company_id'] = $company_id;
            $data = $Logic->make_process_in_stock_wms(21, $param);
            if ($data['status'] == true) {
                $data = $data['data'];
            }
            if ($stock_in->create($data)) {
                $pid = $stock_in->add($data);
            }
            
            if (!$pid) {
                $this->msgReturn(false,'wms入库单生成失败');
            }
            $refer_code = $data['refer_code'];
            unset($param);
            unset($data);
            
            //创建wms入库详情单数据
            $stock_in_detail = M('stock_bill_in_detail');
            $param = $process;
            $param['pid'] = $pid;
            $param['code'] = $refer_code;
            foreach ($process_relation as $value) {
                $param['pro_code'] = $value['c_pro_code'];
                $param['expected_qty'] = $process['plan_qty'] * $value['ratio'];
                $data = $Logic->make_process_in_stock_detail('parpare', $param);
                if ($data['status'] == true) {
                    $data = $data['data'];
                }
                if ($stock_in_detail->create($data)) {
                    $affect = $stock_in_detail->add();
                }
                if (!affect) {
                    $this->msgReturn(false,'详情写入失败');
                }
            }
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            
            //wms出库单
            $data['biz_type'] = $company_id;//所属系统
            //查询仓库code
            $wh = M('warehouse');
            $code_arr = $wh->field('code')->where(array('id', $process['wh_id']))->find();
            $data['picking_type_id'] = $code_arr['code']; //所属仓库
            $data['stock_out_type'] = 'MNO'; //出库类型 加工出库
            $data['return_type'] = true; //定义此接口不输出数据
            $data['refer_code'] = $process['code']; //关联单号 ＝＝加工单号
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
            unset($pid);
            unset($affect);
            unset($data);
            unset($param);
            unset($map);
            //更新状态
            $map['id'] = I('id');
            $data['status'] = 'pass'; //批准
            $erp_process->where($map)->save($data);
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
    
    /**
     * 加工单验证操作
     */
    public function order() {
        if (IS_POST) {
            $post = I('post.');
            if (empty($post['process_code'])) {
                $this->msgReturn(false, '请输入加工单号');
                return;
            }
            
            //查询加工单是否存在
            $process = D('erp_process');
            $map['code'] = $post['process_code'];
            $process_info = $process->where($map)->find();
            if (empty($process_info)) {
                //不存在
                $this->msgReturn(false, '不存在的加工单');
            }
            if ($process_info['real_qty'] >= $process_info['plan_qty']) {
                //已经生产完成加工单
                $this->msgReturn(false, '已生产完成');
            } elseif ($process_info['status'] != 'pass' && $process_info['status'] != 'make') {
                //未审核加工单
                $this->msgReturn(false, '请先审核加工单');
            }
            unset($map);
            $param = array(
                'process_code' => $post['process_code'], //加工单号
            );
            $this->msgReturn(true, '', '', U('process', $param));
        } else {
            $this->title = '扫描加工单号';
            $this->display();
        }
    }
    
    /**
     * 加工区操作
     */
    public function process() {
        if (IS_POST) {
            $post = I('post.');
            $real_qty = $post['real_qty']; 
            //实际生产量是否合法
            if ($real_qty <= 0) {
                //非法
                $this->msgReturn(false, '此货品没有可入库数量');
            }
            
            //查询加工单
            $process = M('erp_process');
            $map['code'] = $post['process_code'];
            $process_info = $process->where($map)->find();
            unset($map);
            
            //查询加工比例
            $process_ratio = D('ProcessRatio');
            $map['p_pro_code'] = $process_info['p_pro_code'];
            $process_ratio_info = $process_ratio->where($map)->select();
            unset($map);
            
            if ($real_qty + $process_info['real_qty'] > $process_info['plan_qty']) {
                //非法
                $this->msgReturn(false, '加工数量不可大于待入库数量');
                return;
            }
            
            $stock_out = D('Process', 'Logic');
            $param = array(); //创建出库单更新数据
            if ($process_info['type'] == 'unite') {
                /**
                 * 组合状态下 扣减子sku库存
                 */
                foreach ($process_ratio_info as $value) {
                    $data['real_qty'] = $value['ratio'] * $real_qty; //出库数量
                    $data['wh_id'] = $process_info['wh_id']; //仓库ID
                    $data['refer_code'] = $post['out_code']; //关联单号＝＝出库单号
                    $data['pro_code'] = $value['c_pro_code']; //出库子sku编号
                    $suc = $stock_out->process_out_stock($data);
                    if ($suc['status'] == false) {
                        $this->msgreturn(false, $suc['msg']);
                        return;
                    }
                    $param[] = array(
                            'qty' => $data['real_qty'], 
                            'pro_code' => $value['c_pro_code'],
                            'wh_id' => $process_info['wh_id'],
                    );
                }
                unset($data);
                
                /**
                 * 更新出库单 实际出库数量
                 */
                $stock_out_code = M('stock_bill_out');
                $map['code'] = $post['out_code'];
                $id_out = $stock_out_code->field('id')->where($map)->find();
                $update_out = $stock_out->update_out_stock_detail($id_out['id'], $param);
                if (!$update_out) {
                    $this->msgReturn(false, '更新出库单失败');  
                }
                unset($map);
                unset($param);
                
                //入库操作
                $in = $stock_out->process_in_stock($process_info['p_pro_code'], $real_qty, $process_info['wh_id'], $post['in_code']);
                if ($in['status'] == false) {
                    $this->msgReturn(false, $in['msg']);
                }
                /**
                * 更新入库单
                */
                $stock_out_code = M('stock_bill_in');
                $map['code'] = $post['in_code'];
                $id_in = $stock_out_code->field('id')->where($map)->find();
                $param['qty'] = $real_qty;
                $param['pro_code'] = $process_info['p_pro_code'];
                $param['wh_id'] = $process_info['wh_id'];
                $update_in = $stock_out->update_in_stock_detail($id_in['id'], $param);
                if (!$update_in) {
                    $this->msgReturn(false, '更新入库单失败');
                }
                
            } else {
                /**
                 * 拆分状态下 扣减父SKU库存
                 */
                $data['real_qty'] = $real_qty; //出库数量
                $data['wh_id'] = $process_info['wh_id']; //仓库ID
                $data['refer_code'] = $post['out_code']; //关联单号＝＝出库单号
                $data['pro_code'] = $process_info['p_pro_code']; //出库子sku编号
                $suc = $stock_out->process_out_stock($data);
                if ($suc['status'] == false) {
                    $this->msgReturn(false, $suc['msg']);
                    return;
                }
                /**
                 * 更新出库单 实际出库数量
                 */
                $stock_out_code = M('stock_bill_out');
                $map['code'] = $post['out_code'];
                $id_out = $stock_out_code->field('id')->where($map)->find();
                $param = array();
                $param[] = array(
                        'qty' => $data['real_qty'], 
                        'pro_code' => $process_info['p_pro_code'],
                        'wh_id' => $process_info['wh_id'],
                );
                $update_out = $stock_out->update_out_stock_detail($id_out['id'], $param);
                if (!$update_out) {
                    $this->msgReturn(false, '更新出库单失败');
                }
                
                //入库操作
                foreach ($process_ratio_info as $value) {
                    $qty = $real_qty * $value['ratio'];
                    $in = $stock_out->process_in_stock($value['c_pro_code'], $qty, $process_info['wh_id'], $post['in_code']);
                    if ($in['status'] == false) {
                        $this->msgReturn(false, $in['msg']);
                    }
                }
                
                /**
                 * 更新入库单
                 */
                unset($param);
                $stock_out_code = M('stock_bill_in');
                $map['code'] = $post['in_code'];
                $id_in = $stock_out_code->field('id')->where($map)->find();
                foreach ($process_ratio_info as $value) {
                    $param = array();
                    $param[] = array(
                            'qty' => $real_qty * $value['ratio'],
                            'pro_code' => $process_info['p_pro_code'],
                            'wh_id' => $value['c_pro_code'],
                    );
                    $update_in = $stock_out->update_in_stock_detail($id_in['id'], $param);
                    if (!$update_in) {
                        $this->msgReturn(false, '更新入库单失败');
                    } 
                }
            }
            /**
             * 更新加工单状态位已生产 加工数量
             */
            unset($map);
            unset($data);
            if ($process_info['status'] != 'make') {
                $data['status'] = 'make'; //已生产
            }
            $map['code'] = $post['process_code'];
            $data['real_qty'] = $post['real_qty'] + $process_info['real_qty']; //实际数量
            if ($process->create($data)) {
                $process->where($map)->save();
            }
            $this->msgReturn(true, '已完成', '', U('index'));
        } else {
            $get = I('get.');
            if (empty($get)) {
                $this->msgReturn(false, '获取数据失败');
            }
            
            //查询加工单
            $process = M('erp_process');
            $map['code'] = $get['process_code'];
            $process_info = $process->where($map)->find();
            unset($map);
            //获取货品信息
            $pms = D('Pms', 'Logic');
            $pro_info = $pms->get_SKU_field_by_pro_codes(array($process_info['p_pro_code']));
            //查询加工入库单是否存在
            $in = D('StockIn');
            $in = M('stock_bill_in');
            $map['refer_code'] = $get['process_code'];
            $info_in = $in->where($map)->find();
            if (empty($info_in)) {
                $this->msg = '加工入库单不存在';
                $this->msgReturn(false, '加工入库单不存在', '', U('order'));
            }
            unset($map);
            
            //查询加工出库单是否存在
            $out = D('StockOut');
            $map['refer_code'] = $get['process_code'];
            $info_out = $out->where($map)->find();
            if (empty($info_out)) {
                $this->msg = '加工出库单不存在';
                $this->msgReturn(false, '加工出库单不存在', '', U('order'));
            }
            unset($map);
            
            $data['in_code'] = $info_in['code'];
            $data['out_code'] = $info_out['code'];
            $data['process_code'] = $get['process_code'];
            $data['pro_name'] = $pro_info[$process_info['p_pro_code']]['name'];
            switch ($process_info['type']) {
            	    case 'unite': //组合
            	        $data['type'] = '组合';
            	        break;
            	    case 'split': //拆分
            	        $data['type'] = '拆分';
            	        break;
            }
            $data['p_pro_code'] = $process_info['p_pro_code'];
            $this->data = $data;
            $this->display();
        }
    }
}