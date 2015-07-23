<?php
namespace Tms\Logic;
/**
 *
 *  签收逻辑
 *
 *  @author  pengyanlei@dachuwang.com
 */

class SignInLogic 
{
    /**
     * [skuStatis 统计SKU售卖信息]
     * @param [type] [发货时间段:start_time,endtime,仓库ID:warehouse_id]
     */
    public function skuStatis($params = array())
    {
        $start_time   = isset($params['start_time']) ? $params['start_time'] : 0;
        $end_time     = isset($params['end_time']) ? $params['end_time'] : 0;
        $warehouse_id = isset($params['warehouse_id']) ? $params['warehouse_id'] : 0;
        if (empty($start_time) || empty($end_time) || empty($warehouse_id)) {
            $res = array(
                'status' => -1,
                'msg'    => '参数错误'
            );
            return $res;
        }
        $start_date = date('Y-m-d H:i:s',$start_time);
        $end_date   = date('Y-m-d H:i:s',$end_time);
        $map['bo.delivery_date'] = array('between', array($start_date, $end_date));
        $map['bo.wh_id'] = $warehouse_id;
        $M = M('tms_sign_in_detail');
        //返回字段：SKU号，实际销售额，实际销售件数，拒收sku数
        $field = 'bod.pro_code as sku_number,';
        $field .= 'sum(sid.real_sign_qty * sid.price_unit) as actual_sale_amount,';
        $field .= 'sum(sid.real_sign_qty) as actual_sale_count,';
        $field .= 'sum(CAST(bod.delivery_qty - sid.real_sign_qty as SIGNED)) as reject_sku_counts';
        $list = $M->alias('sid')->field($field)
            ->join('stock_bill_out_detail bod on bod.id = sid.bill_out_detail_id')
            ->join('join stock_bill_out bo on bo.id = bod.pid')
            ->where($map)
            ->group('bod.pro_code')
            ->select();
        $res = array(
            'status' => 0,
            'list'   => $list
        );
        return $res;
    }
}