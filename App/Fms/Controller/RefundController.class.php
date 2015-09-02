<?php
namespace Fms\Controller;
use Think\Controller;

class RefundController extends \Common\Controller\CommonController {

	protected $columns = array (
		'created_time'    => '生成时间',
		'type'            => '退款单类型',
		'status'          => '处理状态',
		'city_name'       => '地区',
		'order_id'        => '母订单id',
		'suborder_id'     => '子订单id',
		'shop_name'       => '店铺名称',
		'customer_name'   => '客户姓名',
		'customer_mobile' => '客户电话',
		'sum_reject_price'=> '退款金额',
	);
    protected $filter = array (
        'status' => array(
            '0' => '未处理',
            '1' => '已处理',
            '2' => '已关闭',
        ),
        'type'   => array(
            '0' => '拒收退款单',
            '1' => '缺货退款单',
        ),
    );
    protected $query = array (
    
        'fms_refund.type'        => array(
        		'title'        => '退款单类型',
	            'query_type'   => 'eq',
	            'control_type' => 'select',
	            'value' => array(
                        '0' => '拒收退款单',
                        '1' => '缺货退款单',
                    ),
            ),
        'fms_refund.order_id' => array(
                'title'        => '母订单id',
                'query_type'   => 'eq',
                'control_type' => 'text',
                'value'        => '',
            ),
        'fms_refund.suborder_id' => array(
                'title'        => '子订单id',
                'query_type'   => 'eq',
                'control_type' => 'text',
                'value'        => '',
            ),
        'fms_refund.shop_name' => array(
                'title'        => '店铺名称',
                'query_type'   => 'eq',
                'control_type' => 'text',
                'value'        => '',
            ),
        'fms_refund.customer_name' => array(
                'title'        => '客户姓名',
                'query_type'   => 'eq',
                'control_type' => 'text',
                'value'        => '',
            ),
        'fms_refund.customer_mobile' => array(
                'title'        => '客户电话',
                'query_type'   => 'eq',
                'control_type' => 'text',
                'value'        => '',
            ),

        'fms_refund.created_time' =>    array (    
                'title'        => '生成时间',     
                'query_type'   => 'between',     
                'control_type' => 'datetime',     
                'value' => '',   
          ),
        'fms_refund.city_id' => array(
            'title'        => '地区',
            'query_type'   => 'eq',
            'control_type' => 'select',
            'value'        => '',
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
            array('name'=>'pass','link'=>'handle','icon'=>'ok','title'=>'处理', 'show'=>true,'new'=>'true','domain'=>'0'),
        );

        $pill = array(
			'status'=> array(
				'0'=>array('value'=>'0','title'=>'未处理','class'=>'warning'),
				'1'=> array('value'=>'1','title'=>'已处理','class'=>'success'),//已审核
				'2'=> array('value'=>'2','title'=>'已关闭','class'=>'danger'),
			)
		);

        $M_refund = M('fms_refund');
        $map['is_deleted'] = 0;
        //$map['wh_id'] = session('user.wh_id');
        
        $res = $M_refund->field('status,count(status) as qty')->where($map)->group('status')->select();

        foreach ($res as $key => $val) {
            if(array_key_exists($val['status'], $pill['status'])){
                $pill['status'][$val['status']]['count'] = $val['qty'];
                $pill['status']['total'] += $val['qty'];
            }
        }

        foreach($pill['status'] as $k => $val){
            if(empty($val['count'])){
                $pill['status'][$k]['count'] = 0;
            }
        }

        $this->pill = $pill;
        
        $A = A('Common/Order','Logic');
        $this->query['fms_refund.city_id']['value'] = $A->city();
    }

	protected function before_edit(&$data) {

        $detail = M('fms_refund_detail');
        $map['pid']        = $data['id'];
        $map['is_deleted'] = 0;
        $data['detail'] = $detail->where($map)->select();
        $logs = getlogs('fms_refund',$data['id']);
        $data['log'] = $logs;
    }

    public function addRemark()
    {
        $id   = I('id');
        $mark = I('remark');
    
        if (!$id) {
            $this->msgReturn(0,'退款单id不合法。');
        }
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $data['remark']    = $mark;
        $res = M('fms_refund')->where($map)->save($data);
        if ($res) {
            logs($id,$mark,'fms_refund');
            $this->msgReturn(1,'添加备注成功！');
        } else {
            $this->msgReturn(0,'添加备注失败！');
        }
    }

    public function handle()
    {
        $id    = I('id');
        if (!$id) {
            $this->msgReturn(0,'退款单id不合法。');
        }
        //设置状态为已处理
        $res = $this->setRefundStatus($id,1);
        if ($res) {
            logs($id,'已处理','fms_refund');
            $this->msgReturn(1,'处理成功！');
        } else {
            $this->msgReturn(0,'处理失败！');
        }
    }

    public function cancel()
    {
        $id    = I('id');
        $reason = I('reason');
        if (!$id) {
            $this->msgReturn(0,'退款单id不合法。');
        }
        //设置状态为已关闭
        $res = $this->setRefundStatus($id,2);
        if ($res) {
            logs($id,'已关闭，'.$reason,'fms_refund');
            $this->msgReturn(1,'关闭成功！');
        } else {
            $this->msgReturn(0,'关闭失败！');
        }
    }

    public function setRefundStatus($id=0,$status=0)
    {
        if (!$id) {
            return false;
        }   
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $data['status']    = $status;
        $res = M('fms_refund')->where($map)->save($data);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
}
