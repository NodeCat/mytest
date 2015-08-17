<?php
/**
 * Date: 15/7/29
 * Time: 下午6:15
 */

namespace Erp\Logic;

class ProcessLossLogic
{

    /**
     * 返回库存损耗明细
     * @param $code            SKU
     * @param $start_time      开始时间
     * @param $end_time        结束时间
     * @param $location_ids  加工损耗区ID
     * @return array
     */
    public function getStockLoss($code, $start_time, $end_time, $location_ids)
    {
        $model = M('stock');
        $where['stock.is_deleted']  = 0;
        $where['stock.location_id'] = array('in', $location_ids);      //加工损耗区标记
        $where['stock.pro_code']    = array('in', $code);
        if (!empty($start_time) && !empty($end_time)) {
            $where['DATE_FORMAT(stock.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        }
        $filed  = 'stock.pro_code, stock.batch, SUM(stock.stock_qty * erp_purchase_in_detail.price_unit) AS total_amount, SUM(stock.stock_qty) AS stock_qty';
        $join   = 'INNER JOIN erp_purchase_in_detail ON erp_purchase_in_detail.stock_in_code=stock.batch AND erp_purchase_in_detail.pro_code=stock.pro_code';
        $result = $model->join($join)->where($where)->group('stock.pro_code')->getField($filed);

        return $result;
    }
}