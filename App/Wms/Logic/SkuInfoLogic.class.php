<?php
namespace Wms\Logic;

/**
 * SKU信息操作逻辑封装类
 */
class SkuInfoLogic 
{
    /**
     * 根据SKU编号和仓库ID查询SKU实时在库量
     * @param string $skuCodeArr SKU编号
     * @param int $warehouseId 仓库ID
     * @return array
     */    
    public function getActualStockBySkuCode($skuCodeArr = array(), $warehouseId = 0) 
    {
        $return = array();
        
        if (empty($skuCodeArr) || empty($warehouseId)) {
            return $return;
        }
        //查询条件
        $map['pro_code'] = array('in', $skuCodeArr);
        $map['wh_id'] = $warehouseId;
        $map['status'] = 'qualified'; //合格
        $map['is_deleted'] = 0;
        $stockQty = M('stock')->field('pro_code,SUM(stock_qty) as qty')->where($map)->group('pro_code')->select();
        if (!empty($stockQty)) {
            foreach ($stockQty as $qty) {
                $index = strval($qty['pro_code']) . '#';
                $return[$index] = $qty['qty'];
            }
        }
        
        return $return;
    }
    
    /**
     * 根据SKU编号和仓库ID查询SKU实时可售量
     * @param string $skuCodeArr SKU编号
     * @param int $warehouseId 仓库ID
     * @return array
     */
    public function getActualSellBySkuCode($skuCodeArr = array(), $warehouseId = 0)
    {
        $return = array();
        
        if (empty($skuCodeArr) || empty($warehouseId)) {
            return $return;
        }
        //查询条件
        $map['pro_code'] = array('in', $skuCodeArr);
        $map['wh_id'] = $warehouseId;
        $map['status'] = 'qualified'; //合格
        $map['is_deleted'] = 0;
        
        //实时可售量需要排除掉得库位
        //收货区 发货区 降级存储区 加工损耗区 加工区 库内报损区
        $locationMark = array('RECV','PACK','Downgrade','Loss','WORK','Breakage');
        $where['code'] = array('in', $locationMark);
        $locationIdArr = M('location')->field('id')->where($where)->select();
        $locationIds = array();
        foreach ($locationIdArr as $value) {
            $locationIds[] = $value['id'];
        }
        
        $map['location_id'] = array('not in', $locationIds);
        //获取实时在库可售量
        $SellQty = M('stock')->field('pro_code,SUM(stock_qty-assign_qty) as qty')->where($map)->group('pro_code')->select();
        if (!empty($SellQty)) {
            foreach ($SellQty as $qty) {
                $index = strval($qty['pro_code']) . '#';
                $return[$index] = $qty['qty'];
            }
        }
                
        return $return;
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
        
        if (empty($skuCodeArr) || empty($warehouseId)) {
            return $return;
        }
        //查询SKU采购信息
        $map['stock_purchase_detail.pro_code'] = array('in', $skuCodeArr);
        $map['stock_purchase_detail.is_deleted'] = 0;
        $map['stock_purchase.created_time'] = array('gt', date('Y-m-d H:i:s', $stime));
        $map['stock_purchase.created_time'] = array('lt', date('Y-m-d H:i:s', $etime));
        $map['stock_purchase.wh_id'] = $warehouseId;
        $purchaseDetail = M('stock_purchase_detail')
                              ->field('pro_code,pro_qty,price_unit')
                              ->join('stock_purchase ON stock_purchase.id=stock_purchase_detail.pid')
                              ->where($map)->select();
        unset($map);
        if (empty($purchaseDetail)) {
            return $return;
        }
        //按照SKU将数据分类
        foreach ($purchaseDetail as $key => $detail) {
            $purchaseDetail[$detail['pro_code']][] = $detail;
            unset($purchaseDetail[$key]);
        }
        
        foreach ($purchaseDetail as $k => $value) {
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
        
        return $return;
    }
    
    /**
     * 根据SKU编号计算规定时间内平均销售价格及出库总数
     * @param string $skuCode SKU编号
     * @param int $warehouse 仓库ID
     * @param int $stime 开始时间
     * @param int $etime 结束时间
     * @return 
     */
    public function calculateSellPrice($skuCodeArr = array(), $warehouseId = 0, $stime = 0, $etime = 0)
    {
        $return = array();
        
        if (empty($skuCodeArr) || empty($warehouseId)) {
            return $return;
        }
        
        //查询出库详情获取出库单ID
        //$map['wh_id']      = $warehouseId;
        $map['stock_bill_out_detail.pro_code']   = array('in', $skuCodeArr);
        $map['stock_bill_out_detail.is_deleted'] = 0;
        $map['stock_bill_out.wh_id'] = $warehouseId;
        $map['stock_bill_out.type']  = 1; //销售类型
        $map['stock_bill_out.delivery_date'] = array('gt', date('Y-m-d H:i:s', $stime));
        $map['stock_bill_out.delivery_date'] = array('lt', date('Y-m-d H:i:s', $etime));
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
        /*
        foreach ($stockOutDetail as $k => $value) {
            $index = strval($k) . '#';
            $total_num   = 0; //总数量
            $total_price = 0; //总价格
            $return[$index]  = array('sum' => 0, 'price' => 0); //出库量 平均价
            foreach ($value as $val) {
                $total_num = bcadd($total_num, $val['delivery_qty'], 2);
                $total_price = bcadd($total_price, bcmul($val['delivery_qty'], $val['price'], 2), 2);
            }
            if ($total_num > 0 && $total_price > 0) {
                $return[$index]['sum'] = $total_num;
                $return[$index]['price'] = bcdiv($total_price, $total_num, 2);
            }
        }*/
        return $return;
    }
}