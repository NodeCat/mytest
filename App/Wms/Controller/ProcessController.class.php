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
        'erp_process.created_user' => array(
                'title' => '创建人',
                'query_type' => 'eq',
                'control_type' => 'text',
                'value' => ''
        ),
	);
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
            if ($process['status'] != 2 && $process['status'] != 3) {
                //生产完成
                $this->msgReturn(false, '已生产完成');
            }
            if ($process['over_task'] >= $process['task']) {
                $this->msgReturn(false, '已生产完成');
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
        //ena13 to pro_code liuguangping
        $codeLogic = A('Code','Logic');
        $sku_code = $codeLogic->getProCodeByEna13code($sku_code);
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
                if ($value['real_qty'] >= $value['plan_qty']) {
                    $this->msgReturn(false, '此SKU已经加工完成');
                }
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
            $this->msgReturn(false, '请输入大于0的数');
        }

        if (strlen(formatMoney($real_qty, 2, 1))>2) {
            $mes = '本次加工量只能精确到两位小数点';
            $this->msgReturn(0,$mes);
        }
        $real_qty = formatMoney($real_qty, 2);

        //获取加工单关于此SKU的所有信息
        $Logic = D('Process', 'Logic');
        $process = $Logic->get_process_and_detail_unite($process_id, $sku_code, $real_qty);
        if (empty($process)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        if (($process['main_status'] != 2 && $process['main_status'] != 3) || $process['over_task'] >= $process['task']) {
            //状态2 为已生效 3已加工
            $this->msgReturn(false, '此加工单已经生产完成');
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
                $price = f_add($price, $out_back['price']);
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
            $price = formatMoney($out_back['price'], 2);
            
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

}