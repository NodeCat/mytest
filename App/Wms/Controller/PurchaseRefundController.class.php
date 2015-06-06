<?php
namespace Wms\Controller;
use Think\Controller;
class PurchaseRefundController extends CommonController {
	protected $filter = array(
		'invoice_method' =>  array(
			'0' => '预付款',
			'1' => '货到付款',
		),
		'status' => array(
			'norefund' => '未退款',
			'refund' => '已退款',
		),
	);
	
	protected $columns = array (   
		 'code' => '冲红单号',
		 'refer_code' => '采购单号',
		 'price_total' => '采购金额',
		 'refund_total' => '冲红金额',
		 'status' => '状态',
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
		    'value' => array('norefund'=>'未退款','refund'=>'已退款'),
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
		$map['pid'] = $id;
		$pros = M('erp_purchase_refund_detail')->where($map)->order('id desc')->select();
		$refund_total = 0;
		foreach ($pros as $key => $val) {
			$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		
			$refund_total += ($val['qualified_qty'] - $val['expected_qty']) * $val['price_unit'];
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
				foreach($purchase_refund_detail as $val){
					$refund_total += ($val['qualified_qty'] - $val['expected_qty']) * $val['price_unit'];
				}
				$data[$k]['refund_total'] = $refund_total;
			}
		}
	}

	public function refund(){
		$ids = I('ids');

		$map = array('id' => array('in',$ids));

		M('erp_purchase_refund')->where($map)->data(array('status'=>'refund'))->save();
		unset($map);

		$this->msgReturn(1);
	}
}