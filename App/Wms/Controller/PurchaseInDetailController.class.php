<?php
namespace Wms\Controller;
use Think\Controller;
class PurchaseInDetailController extends CommonController {
	protected $filter = array(
			'status' => array('paid' => '已支付', 'nopaid' => '未支付',),
		);
	protected $columns = array(
		'purchase_code' => '采购单号',
		'stock_in_code' => '到货单号',
		'pro_code' => '货品号',
		'pro_qty' => '上架数量',
		'price_unit' => '单价',
		'price_subtotal' => '小计',
		'status' => '状态'
    );
    protected $query   = array (
		'erp_purchase_in_detail.purchase_code' => array (
		    'title' => '采购单号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
		'erp_purchase_in_detail.stock_in_code' => array (
		    'title' => '到货单号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
		'erp_purchase_in_detail.pro_code' => array (
		    'title' => '货品号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
	);

    //设置列表页选项
	protected function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => true,'new'=>'false'), 
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

    //支付
    public function pay(){
    	$ids = I('ids');
    	//根据ids 查询采购入库单信息
    	$map['id'] = array('in',$ids);
    	$purchase_in_details = M('erp_purchase_in_detail')->where($map)->select();
    	unset($map);

    	foreach($purchase_in_details as $purchase_in_detail){
    		if($purchase_in_detail['status'] == 'paid'){
    			$data['status'] = 0;
    			$data['msg'] = '所选单据中有已支付状态的单据，请选择未支付的单据';
    			$this->ajaxReturn($data);
    		}
    	}

    	$data['status'] = 1;

		$this->ajaxReturn($data);
    }
}