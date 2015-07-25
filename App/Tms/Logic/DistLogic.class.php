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
                break;
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

    /*根据配送单id或者配送单号dist_code获得配送单信息
    *@param array(id,dist_code)
    *@return $dist结果集
    */
    public function distInfo($map){
        if(empty($map)){
            return null;
        }
        $map['is_deleted'] = 0;
        $dist = M('stock_wave_distribution')->where($map)->find();
        unset($map);
        //查询条件为配送单id
        $map['pid'] = $dist['id'];
        $map['is_deleted'] = 0;
        //根据配送单id查配送详情单里与出库单相关联的出库单id
        $dist_detail = M('stock_wave_distribution_detail')->where($map)->select();
        $dist['detail'] = $dist_detail;
        return $dist;
    }
    /*根据出库单id获得出库单信息
    *@param id出库单id
    *@return $info结果集
    */
    public function bill_out_Info($id){
        if(empty($id)){
            return null;
        }
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $m = M('stock_bill_out');
        $bill_out = $m->where($map)->find();
        if (!empty($bill_out)) {
            unset($map);
            //查询条件为出库单id
            $map['pid'] = $id;
            $map['is_deleted'] = 0;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $bill_out_detail = M('stock_bill_out_detail')->where($map)->select();
            $bill_out['detail'] = $bill_out_detail;
        }
        return $bill_out;
    }
}