<?php
/**
 * Date: 15/7/29
 * Time: 下午6:15
 */

namespace Erp\Logic;

class ProcessLossLogic
{

    public function getStockLoss($code, $start_time, $end_time)
    {
        $model = M('stock');
        $where['stock.is_deleted']  = 0;
        $where['stock.location_id'] ='96';
        $where['stock.pro_code']    = array('in', $code);
        if (!empty($start_time) && !empty($end_time)) {
            $where['DATE_FORMAT(stock.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        }
        $filed  = 'stock.pro_code, stock.batch, SUM(stock.stock_qty * erp_purchase_in_detail.price_unit) AS total_amount, SUM(stock.stock_qty) AS stock_qty';
        $join   = 'INNER JOIN erp_purchase_in_detail ON erp_purchase_in_detail.stock_in_code=stock.batch AND erp_purchase_in_detail.pro_code=stock.pro_code';
        $result = $model->field($filed)->join($join)->where($where)->group('stock.pro_code')->select();
        $array  = array();
        foreach ($result as $val) {
            $array[$val['pro_code']] = $val;
        }
        unset($result);
        return $array;
    }
}