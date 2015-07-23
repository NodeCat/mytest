<?php
namespace Tms\Api;
use Think\Controller;

/**
 * 出库单接口
 */
class BillOutApi extends CommApi
{
    public function skuStatis()
    {
        $start_time   = I('start_time/d', 0);
        $end_time     = I('end_time/d', 0);
        $warehouse_id = I('warehouse_id/d', 0);
        if (empty($start_time) || empty($end_time)) {
            $res = array(
                'status' => -1,
                'msg'    => '时间参数错误'
            );
            $this->ajaxReturn($res);
        }
        $start_date = date('Y-m-d H:i:s',$start_time);
        $end_date   = date('Y-m-d H:i:s',$end_time);
        $map['bo.delivery_date'] = array('between', array($start_date, $end_date));
        if ($warehouse_id) {
            $map['bo.wh_id'] = $warehouse_id;
        }
        $M = M('tms_sign_in_detail');
        $field = 'bod.pro_code,sum(CAST(bod.delivery_qty - sid.real_sign_qty as SIGNED)) as qty';
        $list = $M->alias('sid')->field($field)
            ->join('stock_bill_out_detail bod on bod.id = sid.bill_out_detail_id')
            ->join('join stock_bill_out bo on bo.id = bod.pid')
            ->where($map)
            ->group('bod.pro_code')
            ->having('qty !=0')
            ->select();
        $res = array(
            'status' => 0,
            'list'   => $list
        );
        $this->ajaxReturn($res);
    }
}
