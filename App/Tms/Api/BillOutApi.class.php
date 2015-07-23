<?php
namespace Tms\Api;
use Think\Controller;

/**
 * 出库单接口
 */
class BillOutApi extends CommApi
{
    /**
     * [skuStatis 统计SKU售卖信息]
     * @param [type] [发货时间段:start_time,endtime,可选参数:warehouse_id]
     */
    public function skuStatis()
    {
        $param = I('json.');
        $start_time   = isset($param['start_time']) ? $param['start_time'] : 0;
        $end_time     = isset($param['end_time']) ? $param['end_time'] : 0;
        $warehouse_id = isset($param['warehouse_id']) ? $param['warehouse_id'] : 0;
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
