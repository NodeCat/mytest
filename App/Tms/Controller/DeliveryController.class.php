<?php
namespace Tms\Controller;
use Think\Controller;
class DeliveryController extends \Common\Controller\CommonController
{
	protected $columns = array(
		'delivery_date' => '日期',
		'mobile' => '手机号',
		'username' => '司机',
		'dist_ids' => '提货码',
		'line_names' => '线路',
	);

	protected $query   = array (
        
        'dist.id' => array(
            'title' => '提货码',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
        'order_id' => array(
            'title' => '订单id',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
        'code' => array(
            'title' => '出库单号',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
        'd.mobile' => array(
            'title' => '司机手机号',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'dist.dist_code' => array(
            'title' => '配送单号',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
        'date(dist.deliver_date)' => array(
            'title' => '配送日期',
            'query_type' => 'eq',
            'control_type' => 'date',
            'value' => '',
        ),
    );

	public function after_search(&$map) {
		if(!empty($map['code'])) {
			$where['bo.code'] = $map['code'];
			unset($map['code']);
		}
		if(!empty($map['order_id'])) {
			$where['bo.refer_code'] = $map['order_id'];
			$where['bo.type']	= 1;
			unset($map['order_id']);
		}
		if(!empty($where)) {
			$where['bo.is_deleted'] = 0;
			$where['dd.is_deleted'] = 0;
			$res = M('stock_wave_distribution_detail dd')
				->join('stock_bill_out bo on bo.id = dd.bill_out_id')
				->field('dd.pid')->where($where)->find();
			$map['d.dist_id'] = $res['pid'];
		}
		elseif (empty($map['date(dist.deliver_date)'])) {
			$map['date(dist.deliver_date)'] = array('eq',today());
		}
        C('PAGE_SIZE',99);

	}

    public function after_lists(&$data){
        foreach ($data as &$value) {
            $val = explode(',', $value['line_names']);
            if(count($val) == 1) {
                continue;
            } else {
                $lines = array();
                foreach ($val as $v) {
                    $lines = array_merge($lines, explode('/', $v));
                }
                $value['line_names'] = array_unique($lines);
            }

        }
    }

}