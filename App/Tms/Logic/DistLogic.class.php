<?php
namespace Tms\Logic;

class DistLogic {

    /**
     * [billOut 从WMS获取出库单列表并关联订单信息]
     * @param  array  $params [出库单ID或配送单ID，排序方式]
     * @return [type]         [description]
     */
    public function billOut($params = array()) {
        $res = A('Wms/StockOut', 'Logic')->bill_out_list($params);
        //配送单关联订单信息
        if($res['status'] === 0) {
            $bill_out_lists = $res['list'];
            $order_ids = array();
            foreach ($bill_out_lists as $bill) {
                $order_ids[] = $bill['refer_code'];
            }
            $map['order_ids'] = $order_ids;
            $map['itemsPerPage'] = count($order_ids);
            $cA = A('Common/Order','Logic');
            $orders = $cA->order($map);
            //配送单关联订单信息
            foreach ($bill_out_lists as &$bill) {
                foreach ($orders as $value) {
                    if($bill['refer_code'] == $value['id']) {
                        $bill['order_info'] = $value;
                    }
                }
            }
            $res = array(
                'orders'     => $bill_out_lists,
                'orderCount' => count($orders),
            );
            return $res;
        }
        return false;
    }

    /**
     * [formateSum 格式化应收金额]
     * @param  [type] $sum [应收]
     * @return [type]      [返回格式好的数据]
     */
    public function formateSum($sum)
    {
        $sum = sprintf('%.1f', $sum);
        $s = substr($sum, -1, 1);
        $s = intval($s);
        $s = ($s < 5) ? 0 : $s;
        $sum = ($s === 0) ? sprintf('%.2f', intval($sum)) : sprintf('%.2f', $sum);
        return $sum;
    }

    /**
     * [getPayStatusByCode 根据支付状态码获取中文状态]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    public function getPayStatusByCode($code)
    {
        switch ($code) {
            case -1:
                $s = '货到付款';
                break;
            case 0:
                $s = '货到付款';
                break;
            case 1:
                $s = '已付款';
            default:
                $s = '';
                break;
        }
        return $s;
    }
    
    /** 
 	** 抹零处理函数，用来得到抹零后的结果
 	** 说明：抹零规则，0.5以下抹去，0.5〜0.9保留，第二位小数四舍五入
 	** @param float $price
 	** @return float $price抹零处理的结果
 	**/
    public function wipeZero($price =0.0) 
    {
        if ($price + 0.5 < ceil($price)) {
            $price = floor($price);
        }
        $price = round($price, 1);
        return $price;
    }
}