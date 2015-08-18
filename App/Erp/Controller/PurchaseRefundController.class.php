<?php
namespace Erp\Controller;
use Think\Controller;
class PurchaseRefundController extends CommonController {
	protected $filter = array(
		'invoice_method' =>  array(
			'0' => '预付款',
			'1' => '货到付款',
		),
		'status' => array(
			'norefund' => '未收款',
			'refund' => '已收款',
			'cancel' => '已作废',
		),
	);
	
	protected $columns = array (   
		 'code' => '冲红单号',
		 'refer_code' => '采购单号',
		 'price_total' => '采购金额',
		 'refund_total' => '冲红金额',
		 'status' => '状态',
		 'created_user' => '创建人',
		 'created_time' => '创建时间',
	);

	protected $query = array (
		'erp_purchase_refund.code' => array (
			'title' => '冲红单号',
			'query_type' => 'like',
			'control_type' => 'text',
			'value' => '',
		),
		'erp_purchase_refund.refer_code' => array (
			'title' => '采购单号',
			'query_type' => 'like',
			'control_type' => 'text',
			'value' => '',
		),
		'erp_purchase_refund.status' => array (
			'title' => '状态',
			'query_type' => 'eq',
			'control_type' => 'select',
		    'value' => array('norefund'=>'未收款','refund'=>'已收款','cancel'=>'已作废'),
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
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
        $this->search_addon = true;
    }

    public function view() {
        $this->_before_index();
        $this->edit();
    }

    protected function before_edit() {
		$M = D('PurchaseRefund');
		$id = I($M->getPk());

		$map['id'] = $id;
		$purchase_refund_info = $M->where($map)->find();
		unset($map);
		$this->purchase_code = $purchase_refund_info['refer_code'];

		$map['pid'] = $id;
		$pros = M('erp_purchase_refund_detail')->where($map)->order('id desc')->select();
		$refund_total = 0;
		$tmp = 0;
		foreach ($pros as $key => $val) {
			$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		
			$tmp = bcmul(bcsub($val['qualified_qty'],$val['expected_qty'],2), $val['price_unit'], 2);
			$refund_total = bcadd($refund_total, $tmp, 2);
		}
		$this->pros = $pros;
		$this->refund_total = $refund_total;
	}

	protected function after_lists(&$data){
		foreach($data as $k => $val){
			//根据erp_purchase_refund id 查询对应的detail
			$map['pid'] = $val['id'];
			$purchase_refund_detail = M('erp_purchase_refund_detail')->where($map)->select();
			unset($map);

			if(!empty($purchase_refund_detail)){
				//计算冲红金额
				$refund_total = 0;
				$tmp = 0;
				foreach($purchase_refund_detail as $val){
					$tmp = bcmul(bcsub($val['qualified_qty'],$val['expected_qty'],2), $val['price_unit'], 2);
					$refund_total = bcadd($refund_total, $tmp, 2);
				}
				$data[$k]['refund_total'] = $refund_total;
			}
		}
	}

	//退款
	public function refund(){
		$ids = I('ids');

		if(empty($ids)){
            $data['status'] = 0;
            $data['msg'] = '请选择一个冲红单';
            $this->ajaxReturn($data);
        }

		$map = array('id' => array('in',$ids));

		M('erp_purchase_refund')->where($map)->data(array('status'=>'refund'))->save();
		unset($map);

		$this->msgReturn(1);
	}

	//作废
	public function cancel(){
		$ids = I('ids');

		if(empty($ids)){
            $data['status'] = 0;
            $data['msg'] = '请选择一个冲红单';
            $this->ajaxReturn($data);
        }

        $map = array('id' => array('in',$ids));
        $purchase_refund_list = M('erp_purchase_refund')->where($map)->field('status')->select();
        
        //检查是否有已退款的冲红单
        foreach($purchase_refund_list as $purchase_refund){
        	if($purchase_refund['status'] == 'refund'){
        		$data['status'] = 0;
            	$data['msg'] = '只能作废状态为未收款的冲红单';
            	$this->ajaxReturn($data);
        	}
        }

        //将冲红单的状态改为已作废
        M('erp_purchase_refund')->where($map)->data(array('status'=>'cancel'))->save();
		unset($map);

		$this->msgReturn(1);
	}
}