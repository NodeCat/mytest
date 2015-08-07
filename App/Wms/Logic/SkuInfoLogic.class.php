<?php
namespace Wms\Logic;

/**
 * SKU信息操作逻辑封装类
 */
class SkuInfoLogic 
{
    protected $server = '';
    protected $request ;
    
    public function __construct(){
        import("Common.Lib.HttpCurl");
        
        $this->server = C('TMS_API_PATH');
        $this->request = new \HttpCurl();
    }
    
    /**
     * 根据SKU编号计算SKU平均采购价格
     * @param string $skuCodeArr SKU编号
     * @param int $warehouseId 仓库ID
     * @param int stime 开始时间
     * @param int etime 结束时间
     * @param return array
     */
    public function calculatePrice($skuCodeArr = array(), $warehouseId = 0, $stime = 0, $etime = 0) 
    {
        $return = array();
        
        if (empty($skuCodeArr)) {
            return $return;
        }
        dump($skuCodeArr);
        //查询SKU采购信息
        $map['stock_purchase_detail.pro_code']   = array('in', $skuCodeArr);
        $map['stock_purchase_detail.is_deleted'] = 0;
        $map['stock_purchase.expecting_date']    = array('between', array(date('Y-m-d H:i:s', $stime), date('Y-m-d H:i:s', $etime)));
        if ($warehouseId > 0) {
            $map['stock_purchase.wh_id'] = $warehouseId;
        }
        $purchaseDetail = M('stock_purchase_detail')
                              ->field('pro_code,pro_qty,price_unit')
                              ->join('stock_purchase ON stock_purchase.id=stock_purchase_detail.pid')
                              ->where($map)->select();
        unset($map);
        //查询加工信息
        $map['erp_process_in_detail.pro_code']   = array('in', $skuCodeArr);
        $map['erp_process_in_detail.is_deleted'] = 0;
        $map['erp_process_in.created_time']      = array('between', array(date('Y-m-d H:i:s', $stime), date('Y-m-d H:i:s', $etime)));
        if ($warehouseId > 0) {
            $map['erp_process_in.wh_id'] = $warehouseId;
        }
        $erpProcessInDetail = M('erp_process_in_detail')
                              ->field('pro_code,real_qty as pro_qty,price as price_unit')
                              ->join('erp_process_in ON erp_process_in.id=erp_process_in_detail.pid')
                              ->where($map)->select();
        unset($map);
        
        if (empty($erpProcessInDetail) && empty($purchaseDetail)) {
            return $return;
        }
        $mergeDetail = array_merge($erpProcessInDetail, $purchaseDetail);
        //按照SKU将数据分类
        foreach ($mergeDetail as $key => $detail) {
            $mergeDetail[$detail['pro_code']][] = $detail;
            unset($mergeDetail[$key]);
        }
        
        foreach ($mergeDetail as $k => $value) {
            $index = strval($k) . '#';
            //BCMATH高精度运算
            $total_num   = 0; //总数量
            $total_price = 0; //总价格
            $return[$index]  = 0; //平均价格
            foreach ($value as $val) {
                $total_num = bcadd($total_num, $val['pro_qty'], 2);
                $total_price = bcadd($total_price, bcmul($val['pro_qty'], $val['price_unit'], 2), 2);
            }
            if ($total_num > 0 && $total_price > 0) {
                $return[$index] = bcdiv($total_price, $total_num, 2);
            }
        }
        dump($return);
        return $return;
    }
    
    /**
     * 根据SKU编号计算规定时间内出库总数
     * @param string $skuCode SKU编号
     * @param int $warehouse 仓库ID
     * @param int $stime 开始时间
     * @param int $etime 结束时间
     * @return 
     */
    public function stockSellQty($skuCodeArr = array(), $warehouseId = 0, $stime = 0, $etime = 0)
    {
        $return = array();
        
        if (empty($skuCodeArr)) {
            return $return;
        }
        
        //查询出库详情获取出库单ID
        $map['stock_bill_out_detail.pro_code']   = array('in', $skuCodeArr);
        $map['stock_bill_out_detail.is_deleted'] = 0;
        if ($warehouseId > 0) {
            $map['stock_bill_out.wh_id'] = $warehouseId;
        }
        $map['stock_bill_out.type']  = 1; //销售类型
        $map['stock_bill_out.op_date'] = array('between', array(date('Y-m-d H:i:s', $stime), date('Y-m-d H:i:s', $etime)));
        $stockOutDetail = M('stock_bill_out_detail')
                              ->field('pro_code,SUM(delivery_qty) as qty')
                              ->join('stock_bill_out ON stock_bill_out.id=stock_bill_out_detail.pid')
                              ->group('pro_code')
                              ->where($map)->select();
        if (empty($stockOutDetail)) {
            return $return;
        }
        //按照SKU将数据分类
        foreach ($stockOutDetail as $key => $detail) {
            $index = strval($detail['pro_code']) . '#';
            $return[$index] = $detail['qty'];
        }
        dump($return);exit;
        return $return;
    }
    
    /**
     * 调用TMS接口获取 拒收SKU数量 实际销售额及实际销售件数
     * @param number $stime
     * @param number $etime
     * @param number $warehouseId
     * @return multitype:|multitype:unknown
     */
    public function getTmsInfo($stime = 0, $etime = 0, $warehouseId = 0) {
        $return = array();
        
        if (empty($stime) || empty($etime)) {
            return $return;
        }
        $param = array(
        	    'start_time'   => $stime,
            'end_time'     => $etime,
            'warehouse_id' => $warehouseId
        );
        $url = $this->server . '/SignIn/skuStatis';
        $result = $this->request->post($url, json_encode($param, true));
        $result = json_decode($result, true);
        if ($result['status'] == -1) {
            return $return;
        }
        if ($result['status'] == 0) {
            foreach ($result['list'] as $value) {
                $index = $value['sku_number'];
                unset($value['sku_number']);
                $return[$index] = $value;
            }
        }
        
        return $return;
    }
    
    /**
     * 根据仓库ID查询SKU上架记录
     */
    public function getSkuVisitRecord($warehouseId = 0) {
        $return = array();
        
        $map = array();
        if ($warehouseId > 0) {
            $map['wh_id'] = $warehouseId;
        }
        $map['done_qty'] = array('gt', 0);
        $result = M('stock_bill_in_detail')->field('pro_code')->where($map)->group('pro_code')->select();
        if (!empty($result)) {
            foreach ($result as $value) {
                $return[] = $value['pro_code'];
            }
        }
        return $return;
    }
}