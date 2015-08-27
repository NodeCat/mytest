<?php
namespace Fms\Controller;
use Think\Controller;

class RefundController extends \Common\Controller\CommonController {

	protected $columns = array (
		'created_time'    => '生成时间',
		'type'            => '退款单类型',
		'status'          => '处理状态',
		'area'            => '地区',
		'order_id'        => '母订单id',
		'suborder_id'     => '子订单id',
		'shop_name'       => '店铺名称',
		'customer_name'   => '客户姓名',
		'customer_mobile' => '客户电话',
		'reject_reason'   => '拒收原因',
		'sum_reject_price'=> '退款金额',
	);
    protected $filter = array (
        'expire_status' => array(
            '0' => '',
            '1' => '逾期未付'
        )
    );
    protected $query = array (
    	'refund.area' => array(
        		'title' => '地区',
	            'query_type' => 'eq',
	            'control_type' => 'select',
	            'value' => '',
            ),
        'refund.type' => array(
        		'title' => '退款单类型',
	            'query_type' => 'eq',
	            'control_type' => 'select',
	            'value' => '',
            ),
        'start_time' =>    array (    
			'title' => '生成时间',     
			'query_type' => 'between',     
			'control_type' => 'datetime',     
			'value' => '',   
		  ),
        
    );

	public function before_index() {

        $this->table = array(
                'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
                'searchbar' => true, //是否显示搜索栏
                'checkbox'  => false, //是否显示表格中的浮选款
                'status'    => false,
                'toolbar_tr'=> true,
                'statusbar' => true
        );
        //$this->search_addon = true;
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
        );

        $this->pill = array(
			'status'=> array(
				'0'=>array('value'=>'0','title'=>'未处理','class'=>'warning'),
				'1'=> array('value'=>'1','title'=>'已处理','class'=>'success'),//已审核
				'2'=> array('value'=>'2','title'=>'已关闭','class'=>'danger'),
			)
		);
        $this->query['refund.area'] = 
    }
	protected function before_edit(&$data) {

        $detail = M('refund_detail');
        $map['pid']        = $data['id'];
        $map['is_deleted'] = 0;
        $data['detail'] = $detail->where($map)->select();

    }
}
