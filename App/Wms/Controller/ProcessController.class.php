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
	protected $face = array(
	    1 => '待审核',
	    2 => '已生效',
	    3 => '已加工',
	    4 => '已驳回',
	    5 => '已作废',
	);
	protected $columns = array (
		'id' => '',
	    'code' => '加工单号',
		'type' => '加工类型',
		'wh_id' => '仓库',
		'task' => '总任务数',
	    'over_task' => '完成任务数',
		'status' => '状态',
		'remark' => '备注',
	);
	protected $query   = array (
        'erp_process.code' => array(
                'title' => '加工单号',
                'query_type' => 'eq',
                'control_type' => 'text',
                'value' => 'code',
        ),
	    'erp_process.p_pro_code' => array(
		       'title' => '父SKU编号',
	           'query_type' => 'eq',
	           'control_type' => 'text',
	           'value' => 'p_pro_code',
	    ),
        'erp_process.status' => array(
                'title' => '状态',
                'query_type' => 'eq',
                'control_type' => 'select',
                'value' => array(
        	            1 => '待审核',
			        2 => '已生效',
                    3 => '已完成',
			        4 => '已驳回',
			        5 => '已作废',
		            
                ),
        ),
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
            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"1,4"), 
            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"1"),
            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"1"),
            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"1,2,4")
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
        $code = array();
        
        //格式化状态
        $new_data = array();
        foreach ($data as $key => &$value) {
            foreach ($this->face as $k => $val) {
                if ($k == $value['status']) {
                    $value['status'] = $val;
                }
            }
            $value['type'] = en_to_cn($value['type']);
        }
    }
    
    /**
     * 所有物料清单中存在的父sku信息(新建加工单时js请求接口)
     */
    public function match_code() {
        $code=I('q');
        
        $D = D('Process', 'Logic');
        //默认取十条
        $result = $D->get_ration_by_pms(intval($code), 10);
        if(empty($result)) {
            $result['']='';
        }
        echo json_encode($result);
    }

    /**
     * 加工单新建(编辑)
     * @see 
     */
    public function save(){
        	if(!IS_POST){
        	    $this->msgReturn(false, '未知错误');
        	}
    	    $post = I('post.');
    	    
    	    if (empty($post)) {
    	        $this->msgReturn(false, '参数有误');
    	    }
    	    $pros = $post['pros'];
    	    unset($pros[0]); //删除默认提交数据
    	    if (empty($pros)) {
    	        $this->msgReturn(false, '请填写sku信息');
    	    }
    	    if (empty($post['type'])) {
    	        $this->msgReturn(false, '请选择类型');
    	    }
    	    if (empty($post['wh_id'])) {
    	        $this->msgReturn(false, '请选择所属仓库');
    	    }
    	    $process = D('Process' , 'Logic');
    	    $data = array(); //加工单数据
    	    $param = array(); //记录SKU
    	    foreach ($pros as $key => $value) {
    	        //判断是否有sku信息未填写
    	        if (empty($value['pro_code'])) {
    	            $this->msgReturn(false, '请选择sku');
    	        }
    	        if (empty($value['pro_qty']) || intval($value['pro_qty']) != $value['pro_qty'] || intval($value['pro_qty']) <= 0) {
    	            $this->msgReturn(false, '请填写大于0的整型数量');
    	        }
    	        $result = $process->get_ratio_by_pro_code($value['pro_code']);
    	        if (empty($result)) {
    	            $this->msgReturn(false, '你添加的父sku中含有不存在物料清单的');
    	        }
    	        $data['detail'][$key] = $value; //加工详情数据
    	        $data['detail'][$key]['real_qty'] = 0; //实际加工量 默认0
    	        $param[] = $value['pro_code'];
    	    }
    	    if (count($param) > count(array_unique($param))) {
    	        //发现重复的SKU
    	        $this->msgReturn(false, '请叠加相同的SKU数量');
    	    }
    		$data['type'] = $post['type']; //加工类型
    		$data['remark'] = !empty($post['remark']) ? $post['remark'] : ''; //备注
    		//创建加工单
    		if (ACTION_NAME == 'add') {
    		    //添加操作
    		    $back = $process->create_process($data);
    		    if(!$back){
    		        $this->msgReturn(false,'创建失败');
    		    }
    		} elseif (ACTION_NAME == 'edit') {
    		    //编辑操作
    		}

    		if(!$back){
    		    $this->msgReturn(false,'创建失败');
    		}
    		$this->msgReturn(true,'创建成功','','/Process/view/id/'.$back);
    }

    //重写view
    public function view() {
        $this->_before_index();
        $this->edit();
    }

    protected function before_edit(&$data){
        //加工单详情数据处理
        D('Process', 'Logic')->get_process_all_sku_detail($data);
    }
    
    protected function after_save($pid){
        	if(ACTION_NAME == 'edit'){
        	    //更新状态
        	    $M = M('erp_process');
        	    $map['id'] = $pid;
        	    $result = $M->where($map)->find();
        	    if (!empty($result)) {
        	        if ($result['status'] != 'confirm') {
        	            $data['status'] = 'confirm';
        	            if ($M->create($data)) {
        	                $M->where($map)->save();
        	            }
        	        }
        	    }
        		$this->msgReturn(1,'','',U('view','id='.$pid));
        	}
    }

    /**
     * 批准加工单操作
     */
    public function pass(){
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $id = I('get.id'); //加工单id
        //获取加工单数据
        $map['id'] = I('id');
        $process =  M('erp_process')->where($map)->find();
        unset($map);
        
        if (empty($process)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        //是否已经批准
        if ($process['status'] != 1) { //1 待审核状态
            $this->msgReturn(false, '非新建加工单');
        }
        
        $Logic = D('Process', 'Logic');
        
        //获取详情
        $Logic->get_process_all_sku_detail($process);
        
        //获取格式化后的父SKU信息
        $format_p = $Logic->format_process_sku($process, 'p');
        
        //获取格式化后的子SKU信息
        $format_c = $Logic->format_process_sku($process, 'c');
        
        if($process['type'] == 'unite'){
            /**
             * 组合状态下 分别在erp wms上创建单据
             * 父SKU创建入库及入库详情单
             * 子SKU创建出库及出库详情单
             */
            
            //创建wms下的父SKU入库单(返回入库单号)
            $in_code = $Logic->make_process_in_stock_wms($format_p);
            if (empty($in_code)) {
                $this->msgReturn(false, '生成加工入库单失败');
            }
            
            //创建wms下的子SKU出库单（返回出库单号）
            $out_code = $Logic->make_process_out_stock_wms($format_c);
            if (empty($out_code)) {
                $this->msgReturn(false, '生成加工出库单失败');
            }
            
            //创建erp下的父SKU入库单(返回出库单号)
            $in_pid = $Logic->make_process_in_stock_erp($format_p, $in_code);
            if (empty($in_pid)) {
                $this->msgReturn(false, '生成加工入库单失败');
            }
            
            //创建erp下的子SKU出库单（返回出库单号）
            $out_pid = $Logic->make_process_out_stock_erp($format_c, $out_code);
            if (empty($out_pid)) {
                $this->msgReturn(false, '生成加工出库单失败');
            }
            
        } else {
            //拆分
            /**
             * 拆分状态下分别在 erp wms上创建单据
             * 父SKU创建出库及出库详情单
             * 子SKU创建入库及入库详情单
             */
            //创建wms下的父SKU出库单(返回出库单号)
            $out_code = $Logic->make_process_out_stock_wms($format_p);
            if (empty($out_code)) {
                $this->msgReturn(false, '生成加工出库单失败');
            }
            
            //创建wms下的子SKU入库单（返回入库单号）
            $in_code = $Logic->make_process_in_stock_wms($format_c);
            if (empty($in_code)) {
                $this->msgReturn(false, '生成加工入库单失败');
            }
            
            //创建erp下的父SKU出库单(返回出库单号)
            $out_pid = $Logic->make_process_out_stock_erp($format_p, $out_code);
            if (empty($out_pid)) {
                $this->msgReturn(false, '生成加工出库单失败');
            }
            
            //创建erp下的子SKU入库单（返回出库单号）
            $in_pid = $Logic->make_process_in_stock_erp($format_c, $in_code);
            if (empty($in_pid)) {
                $this->msgReturn(false, '生成加工入库单失败');
            }
        }
        
        //更新状态
        $map['id'] = I('id');
        $data['status'] = 2; //批准
        M('erp_process')->where($map)->save($data);
        $this->msgReturn(true, '已生效');
    }
    
    /**
     * 加工单驳回
     */
    public function reject(){
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        	$id = I('get.id');
        	if (empty($id)) {
        	    $this->msgReturn(false, '参数有误');
        	}
        	$map['id'] = $id;
        	$res = M('erp_process')->where($map)->find();
        	if (empty($res)) {
        	    $this->msgReturn(false, '不存在的加工单');
        	}
        	if ($res['status'] != 1) {
        	    //状态为1 待审核
        	    $this->msgReturn(false, '非新建加工单不能驳回');
        	}
        	$data['status'] = 4; //4驳回
        	$res = M('erp_process')->where($map)->save($data);
        	if (!$res) {
        	    $this->msgReturn(false, '驳回失败');
        	}
    
        	$this->msgReturn(true, '已驳回');
    }
    
    /**
     * 加工单作废
     */
    public function close(){
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $id = I('get.id');
        if (empty($id)) {
            $this->msgReturn(false, '参数有误');
        }
        $map['id'] = $id;
        $res = M('erp_process')->where($map)->find();
        if (empty($res)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        if ($res['status'] == 3) {
            //状态为3 已完成
            $this->msgReturn(false, '已完成的加工单不能作废');
        }
        if ($res['status'] == 5) {
            //已作废
            $this->msgReturn(false, '此加工单已经作废，无需重复操作');
        }
        if ($res['status'] == 2) {
            //已生效
            $D = D('Process', 'Logic');
            $detail = $D->get_process_detail($id);
            foreach ($detail as $value) {
                if ($value['real_qty'] > 0) {
                    $this->msgReturn(false, '已开始加工的加工单不可作废');
                }
            }
        }
        
        //获取详情
        	$data['status'] = 5; //5作废
        	$back = M('erp_process')->where($map)->save($data);
        	if (!$back) {
        	    $this->msgReturn(false, '作废失败');
        	}
        	
        	//作废关联的出入库单
        	unset($map);
        	$map['refer_code'] = $res['code'];
        	$wms_update['is_deleted'] = 1;
        	M('stock_bill_out')->where($map)->save($wms_update);
        	M('stock_bill_in')->where($map)->save($wms_update);
        	
        	$erp_update['status'] = 3; //已作废
        M('erp_process_out')->where($map)->save($erp_update);
        M('erp_process_in')->where($map)->save($erp_update);
        
        	$this->msgReturn(true, '已作废');
    }
    
    /**
     * 加工单验证操作
     */
    public function order() {
        if (IS_POST) {
            $post = I('post.process_code');
            if (empty($post)) {
                $this->msgReturn(false, '请输入加工单号');
                return;
            }
            
            //查询加工单是否存在
            $map['code'] = $post;
            $process = M('erp_process')->where($map)->find();
            if (empty($process)) {
                //不存在
                $this->msgReturn(false, '不存在的加工单');
            }
            if ($process['status'] == 3) {
                //生产完成
                $this->msgReturn(false, '已生产完成');
            } elseif ($process['status'] != 2) {
                //未审核加工单
                $this->msgReturn(false, '未审核的加工单');
            }
            unset($map);
            $param = array(
                'process_id' => $process['id'], //加工单ID
            );
            $this->msgReturn(true, '', '', U('confirm', $param));
        } else {
            $this->title = '扫描加工单号';
            C('LAYOUT_NAME','pda');
            $this->display();
        }
    }
    
    /**
     * 父SKU确认
     */
    public function confirm() {
        if (IS_GET) {
            $process_id = I('get.process_id');
            if (empty($process_id)) {
                $this->msgReturn(false, '参数有误');
            }
            //加载模板
            $this->title = '请扫描父SKU货号';
            C('LAYOUT_NAME','pda');
            $this->assign('process_id', $process_id);
            $this->display();
            return;
        }
        
        if (!IS_POST) {
            $this->msgReturn(false, '未知错误');
        }
        $process_id = I('post.process_id');
        $sku_code = I('post.sku_code');
        if (empty($process_id) || empty($sku_code)) {
            $this->msgReturn(false, '参数有误');
        }
        /** 确认父SKU */
        //判断加工单
        $map['id'] = $process_id;
        $res = M('erp_process')->where($map)->find();
        if (empty($res)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        unset($map);
        //加工单详情
        $map['pid'] = $process_id;
        $result = M('erp_process_detail')->where($map)->select();
        if (empty($result)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        $break = false;
        foreach ($result as $value) {
            if ($value['p_pro_code'] == $sku_code) {
                switch ($res['type']) {
                	    case 'unite':
                	        $res['type'] = '组合';
                	        break;
                	    case 'split':
                	        $res['type'] = '拆分';
                	        break;
                }
                unset($value['id']);
                $res = array_merge($res, $value);
                $break = true;
                break;
            }
            
        }
        if ($break == false) {
            $this->msgReturn(false, '该加工单中不存在此SKU');
        }
        C('LAYOUT_NAME','pda');
        $this->assign('data', $res);
        $this->display('process');
    }
    
    /**
     * 加工区操作
     */
    public function process() {
        
        if (!IS_POST) {
            $this->msgReturn(false, '未知错误');
        }
        //实际加工数量
        $real_qty = I('post.real_qty');
        //加工单ID
        $process_id = I('post.process_id');
        //预加工SKU编号
        $sku_code = I('post.sku_code');
        
        if (empty($process_id) || empty($sku_code)) {
            $this->msgReturn(false, '参数有误');
        }
        if (empty($real_qty)) {
            $this->msgReturn(false, '请输入加工数量');
        }
        //实际生产量是否合法
        if ($real_qty <= 0) {
            $this->msgReturn(false, '请输入大于0的整数');
        }
        $real_qty = intval($real_qty);
        if ($real_qty != I('post.real_qty')) {
            //非整型
            $this->msgReturn(false, '请输入整型数字');
        }
        //获取加工单关于此SKU的所有信息
        $Logic = D('Process', 'Logic');
        $process = $Logic->get_process_and_detail_unite($process_id, $sku_code, $real_qty);
        if (empty($process)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        if (($process['main_status'] != 2 && $process['main'] != 3) || $process['over_task'] >= $process['task']) {
            //状态2 为已生效 3已加工
            $this->msgReturn(false, '此加工单已经生产完成');
        }
        if ($process['real_qty'] >= $process['plan_qty']) {
            $this->msgReturn(false, '此SKU已经加工完成');
        }
        if ($real_qty + $process['real_qty'] > $process['plan_qty']) {
            $this->msgReturn(false, '加工数量不可大于待入库数量');
        }
        
        /**   开始加工  */
        if ($process['type'] == 'unite') {
            /**
             * 组合状态下 扣减子SKU库存 入库父SKU
             * 更新ERP WSM 出库单 入库单的信息
             */
            
            /** 1---判断库存是否充足 如果充足 扣减子SKU库存 并且记录出库SKU单价 */
            
            //格式化出库数据(子SKU出库)
            $out_data = $Logic->format_move_stock($process, 'c', 'out');
            //检查库存 （子SKU 可能多个 需要循环）
            foreach ($out_data as $value) {
                $check = $Logic->checkout_stock($value);
                if ($check == false) {
                    $this->msgReturn(false, '库存不足');
                }
            }
            //出库 （子SKU 循环出库）
            $price = 0; //总价格(更新ERP入库单用)
            foreach ($out_data as $value) {
                $out_back = $Logic->move_stock($value);
                if ($out_back['status'] == false) {
                    $this->msgReturn(false, $out_back['msg']);
                }
                $price += $out_back['price'];
            }
            /** 2---父SKU入库 */
            
            //格式化入库数据（父SKU入库）
            $in_data = $Logic->format_move_stock($process, 'p', 'in');
            //入库 （父SKU 只有一个 无需循环）
            $in_back = $Logic->in_stock($in_data);
            if (!$out_back) {
                $this->msgReturn(false, '入库失败');
            }
            /** 3---获取WMS ERP 出 入库单的ID */
            
            $wms_in_id = $Logic->get_code_by_process($process['code'], 'wms_in');
            $wms_out_id = $Logic->get_code_by_process($process['code'], 'wms_out');
            $erp_in_id = $Logic->get_code_by_process($process['code'], 'erp_in');
            $erp_out_id = $Logic->get_code_by_process($process['code'], 'erp_out');
             
            /** 4---更新WMS端出库单 入库单 数据 */
            
            //更新入库单(针对父SKU 无需循环 只有一个)
            $wmsorder_back_in = $Logic->update_in_stock_wms($process, $wms_in_id);
            if (!$wmsorder_back_in) {
                $this->msgReturn(false, '更新入库单失败');
            }
            //更新出库单(针对子SKU 需要循环 可能有多个)
            foreach ($process['ratio'] as $value) {
                $wmsorder_back_out = $Logic->update_out_stock_wms($value, $wms_out_id);
                if (!$wmsorder_back_out) {
                    $this->msgReturn(false, '更新出库单失败');
                }
            }
            
            /** 5---更新ERP端出库单 入库单 数据 */
            
            //更新入库单(针对父SKU 无需循环 只有一个)
            $erporder_back_in = $Logic->update_in_stock_erp($process, $erp_in_id, $price);
            if (!$erporder_back_in) {
                $this->msgReturn(false, '更新入库单失败');
            }
            //更新出库单(针对子SKU 需要循环 可能有多个)
            foreach ($process['ratio'] as $val) {
                $erporder_back_out = $Logic->update_out_stock_erp($val, $erp_out_id);
                if (!$erporder_back_out) {
                    $this->msgReturn(false, '更新出库单失败');
                }
            }
            
        } else {
            /**
             * 拆分状态下 扣减父SKU库存 入库子SKU
             * 更新ERP WMS 出库单 入库单信息
             */
            /** 1---判断库存是否充足 如果充足 扣减父SKU库存 并且记录出库SKU单价 */
            
            //格式化出库数据（父SKU 出库 只有一个 无需循环）
            $out_data = $Logic->format_move_stock($process, 'p', 'out');
            //检查库存 （父SKU）
            $check = $Logic->checkout_stock($out_data);
            if ($check == false) {
                $this->msgReturn(false, '库存不足');
            }
            //出库(父SKU)
            $price = 0; //总价格(更新ERP入库单用)
            $out_back = $Logic->move_stock($out_data);
            if ($out_back['status'] == false) {
                $this->msgReturn(false, $out_back['msg']);
            }
            $price = $out_back['price'];
            
            /** 2---子SKU入库 */
            
            //格式化入库数据（子SKU入库 可能有多个 需要循环）
            $in_data = $Logic->format_move_stock($process, 'c', 'in');
            //入库 （子SKU）
            foreach ($in_data as $value) {
                $in_back = $Logic->in_stock($value);
                if (!$out_back) {
                    $this->msgReturn(false, '入库失败');
                }
            }
            
            /** 3---获取WMS ERP 出 入库单的ID */
            
            $wms_in_id = $Logic->get_code_by_process($process['code'], 'wms_in');
            $wms_out_id = $Logic->get_code_by_process($process['code'], 'wms_out');
            $erp_in_id = $Logic->get_code_by_process($process['code'], 'erp_in');
            $erp_out_id = $Logic->get_code_by_process($process['code'], 'erp_out');
             
            /** 4---更新WMS端出库单 入库单 数据 */
            
            //更新出库单(针对父SKU 无需循环 只有一个)
            $wmsorder_back_out = $Logic->update_out_stock_wms($process, $wms_out_id);
            if (!$wmsorder_back_out) {
                $this->msgReturn(false, '更新出库单失败');
            }
            //更新入库单(针对子SKU 需要循环 可能有多个)
            foreach ($process['ratio'] as $value) {
                $wmsorder_back_in = $Logic->update_out_stock_wms($value, $wms_in_id);
                if (!$wmsorder_back_in) {
                    $this->msgReturn(false, '更新入库单失败');
                }
            }
            
            /** 5---更新ERP端出库单 入库单 数据 */
            
            //更新出库单(针对父SKU 无需循环 只有一个)
            $erporder_back_out = $Logic->update_out_stock_erp($process, $erp_out_id);
            if (!$erporder_back_out) {
                $this->msgReturn(false, '更新出库单失败');
            }
            //更新入库单(针对子SKU 需要循环 可能有多个)
            foreach ($process['ratio'] as $val) {
                $erporder_back_in = $Logic->update_in_stock_erp($val, $erp_in_id, $price);
                if (!$erporder_back_in) {
                    $this->msgReturn(false, '更新入库单失败');
                }
            }
        }
        /** 更新加工单状态位已生产 加工数量 */
        
        $process_data = array();
        if ($process['status'] != 3) {
            $process_data['status'] = 3; //已生产
        }
        $process_data['real_qty'] = $real_qty;
        $process_data['p_pro_code'] = $process['p_pro_code'];
        $process_back = $Logic->update_process_info($process_data, $process['id']);
        if (!$process_back) {
            $this->msgReturn(false, '更新加工单失败');
        }
        $this->msgReturn(true, '已完成', '', U('Process/confirm/process_id/' . $process['id']));
    }
    
    /**
     * 批量添加加工任务预览
     */
    public function preview() {
        $pro_infos = I('post.pro_infos');
        if(empty($pro_infos)){
            $this->msgReturn(0,'请提交批量处理的信息');
        }
        $pro_infos_list = explode("\n", $pro_infos);
        
        $pro_codes = array();
        $purchase_infos = array();
        foreach($pro_infos_list as $pro_info){
            $pro_info_arr = explode("\t", $pro_info);
            $pro_codes[] = $pro_info_arr[0];
            $purchase_infos[$pro_info_arr[0]]['pro_qty'] = $pro_info_arr[1];
            $purchase_infos[$pro_info_arr[0]]['price_unit'] = $pro_info_arr[2];
        }
        
        $sku_list = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        
        //拼接模板
        foreach($pro_codes as $pro_code){
            $result .= '<tr class="tr-cur">
			    <td style="width:50%;">
			    	<input type="hidden" value="'.$pro_code.'" name="pros[pro_code][]" class="pro_code form-control input-sm"><input type="hidden" value="'.$sku_list[$pro_code]['name'].'" name="pros[pro_name][]" class="pro_name form-control input-sm"><input type="hidden" value="'.$sku_list[$pro_code]['pro_attrs_str'].'" name="pros[pro_attrs][]" class="pro_attrs form-control input-sm">
			    	<input type="text" value="'.'['.$pro_code.'] '.$sku_list[$pro_code]['wms_name'].'" class="pro_names typeahead form-control input-sm" autocomplete="off">
			    </td>
			    <td style="width:10%;">
			        <input type="text" id="pro_qty" name="pros[pro_qty][]" placeholder="数量" value="'.$purchase_infos[$pro_code]['pro_qty'].'" class="pro_qty form-control input-sm text-left p_qty" autocomplete="off">
			    </td>
			    <td style="width:10%;">
			        <select name="pros[pro_uom][]" class="form-control input-sm">
			            <!--<option value="箱">箱</option>-->
			            <option value="件">件</option>
			        </select>
			    </td>
			    <td style="width:10%;">
			        <input type="text" id="price_unit" name="pros[price_unit][]" placeholder="单价" value="'.$purchase_infos[$pro_code]['price_unit'].'" class="form-control input-sm text-left p_price">
			    </td>
        
			    <td style="width:10%;">
			        <label type="text" class="text-left p_res">'.$purchase_infos[$pro_code]['price_unit'] * $purchase_infos[$pro_code]['pro_qty'].'</label>
			    </td>
        
			    <td style="width:10%;" class="text-center">
			        <a data-href="/Category/delete.htm" data-value="67" class="btn btn-xs btn-delete" data-title="删除" rel="tooltip" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-original-title="" title=""><i class="glyphicon glyphicon-trash"></i> </a>
			    </td>
			</tr>';
        }
         
        $this->msgReturn(1,'',array('html'=>$result));
    }
}