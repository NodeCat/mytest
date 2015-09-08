<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Erp\Logic;

class PurchasesLogic{

    /**
     * 根据条件获取要求的sku
     * getSkuInfoByWhId
     *  
     * @param String $wh_id 仓库id
     * @param Array $pro_codeArr sku 码数组
     * @param Date delivery_date 时间
     * @param String delivery_ampm am上午 pm下午 
     * @author liuguangping@dachuwang.com
     * @return Array $returnRes;
     * 
     */
    public function getSkuInfoByWhId($pro_codeArr = array(), $wh_id='', $delivery_date='', $delivery_ampm=''){

        if($wh_id){
            $where['b.wh_id'] = $wh_id;
        }
        if($delivery_date){

            $where['b.delivery_date'] = $delivery_date;
        }
        if($delivery_ampm){

            $where['b.delivery_ampm'] = $delivery_ampm; 
        }
        $page_size = C('PAGE_SIZE');
        $where['b.status'] = array('in','1,3');//待生产or波次中
        $where['b.type'] = 1;//销售订单
        $returnRes = array();
        $total = count($pro_codeArr);
        $totalPage = ceil($total/$page_size);
        if(intval($total)>0){
            $m = M();
            for($j=1; $j<=$totalPage;$j++){
                
                $pro_code = array_splice($pro_codeArr, 0, $page_size);
                $where['d.pro_code'] = array('in',$pro_code);
                $join  = array(
                    'inner join stock_bill_out as b on b.id=d.pid',
                    'left join erp_process_sku_relation as r on d.pro_code = r.p_pro_code and r.is_deleted=0',
                    'inner join warehouse ON warehouse.id=b.wh_id'
                );
                $filed = "r.ratio,b.wh_id,d.pro_code,r.c_pro_code, warehouse.name as wh_name";
                $m->table('stock_bill_out_detail as d')
                    ->join($join)
                    ->field($filed)
                    ->where($where)
                    ->group('b.wh_id,d.pro_code,r.c_pro_code')->order('r.c_pro_code desc');
                $result = $m->select();
                
                if($result){
                    $returnRes = array_merge($returnRes,$result);
                }

            }
        }
        return $returnRes;

    }

    //采购需求没有选择分类时做逻辑操作
    public function getSkuInfoByWhIdUp($wh_id,$delivery_date='', $delivery_ampm=''){
        $m               = M();
        $where           = array();
        $where['b.status'] = array('in','1,3');//待生产or波次中
        $where['b.type'] = 1;//销售订单
      
        if($wh_id){
            $where['b.wh_id'] = $wh_id;
        }
        if($delivery_date){

            $where['b.delivery_date'] = $delivery_date;
        }
        if($delivery_ampm){

            $where['b.delivery_ampm'] = $delivery_ampm; 
        }
        $result = array();

        $join   = array(
            'inner join stock_bill_out as b on b.id=d.pid',
            'left join erp_process_sku_relation as r on d.pro_code = r.p_pro_code and r.is_deleted=0',
            'inner join warehouse ON warehouse.id=b.wh_id'
        );
        $filed = "r.ratio,b.wh_id,d.pro_code,r.c_pro_code, warehouse.name as wh_name";

        $m->table('stock_bill_out_detail as d')
                      ->join($join)
                      ->field($filed)
                      ->where($where)
                      ->group('b.wh_id,d.pro_code,r.c_pro_code')->order('r.c_pro_code desc');

        $result = $m->select();
        
        return $result;
    }
    
    /**
     * 采购价格修改
     * @param $purchaseId
     * @param $purchasePrice
     */
    public function updatePurchasePrice($purchaseId = 0, $purchasePrice = array()) 
    {
        $return = array();
        
        if (empty($purchasePrice)) {
            return $return;
        }
        
        //获取所有详情
        $idArr = array_column($purchasePrice, 'id');
        
        $map['id']         = array('in', $idArr);
        $map['is_deleted'] = 0;
        
        $purchaseDetailInfo = M('stock_purchase_detail')->where($map)->getField('id, price_unit, pro_qty');
        unset($map);
        
        //更新价格
        $price_total = 0;
        foreach ($purchasePrice as $info) {
            if ($info['price_unit'] != $purchaseDetailInfo[$info['id']]['price_unit']) {
                //记录修改日志
                $back = $this->createUpdatePriceLog($purchaseId, $info);
                if (!$back) {
                    return $return;
                }
                
                $map['id'] = $info['id'];
                $update['price_unit']     = $info['price_unit'];
                $update['price_subtotal'] = bcmul($info['price_unit'], $purchaseDetailInfo[$info['id']]['pro_qty']);
                $affected = M('stock_purchase_detail')->where($map)->save($update);
                if (!$affected) {
                    return $return;
                }
        
                $price_total = bcadd($price_total, $update['price_subtotal']);
                unset($map);
                unset($update);
                $return[] = $info;
            } else {
                $price_total = bcadd($price_total, bcmul($purchaseDetailInfo[$info['id']]['price_unit'], $purchaseDetailInfo[$info['id']]['pro_qty'], 2), 2);
            }
        }
        
        //更新总金额
        $map['id'] = $purchaseId;
        $updateMain['price_total'] = $price_total;
        $affectedRow = M('stock_purchase')->where($map)->save($updateMain);
        if (!$affectedRow) {
            return $return;
        }
        
        //如果存在冲红单则更新冲红单总金额
        $where['stock_purchase.id'] = $purchaseId;
        $purchaseRefund = M('erp_purchase_refund')
            ->field('erp_purchase_refund.*')
            ->join('stock_purchase ON stock_purchase.code=erp_purchase_refund.refer_code')
            ->where($where)
            ->find();
        unset($map);
        if (!empty($purchaseRefund)) {
            $map['id'] = $purchaseRefund['id'];
            $data['erp_purchase_refund.price_total'] = $price_total;
            $updateLine = M('erp_purchase_refund')->where($where)->save($data);
            if (!$updateLine) {
                return $return;
            }
        }   
        
        return $return;
    }
    
    /**
     * 根据采购单号更新到货单价格
     * @param $purchaseID
     * @param $purchasePrice
     */
    public function updateStockBillInDetailPrice($purchaseID = 0, $purchasePrice = array())
    {
        $return = false;
        
        if (empty($purchaseID) || empty($purchasePrice)) {
            return $return;
        }
        
        foreach ($purchasePrice as $value) {
            $map['stock_purchase.id'] = $purchaseID;
            $map['stock_purchase_detail.id'] = $value['id'];
            $update['stock_bill_in_detail.price_unit'] = $value['price_unit'];
            $result = M('stock_bill_in_detail')
                ->field('stock_bill_in_detail.*')
                ->join('stock_bill_in ON stock_bill_in.id=stock_bill_in_detail.pid')
                ->join('stock_purchase ON stock_purchase.code=stock_bill_in.refer_code')
                ->join('stock_purchase_detail ON stock_purchase_detail.pro_code=stock_bill_in_detail.pro_code')
                ->where($map)
                ->find();
            if (empty($result)) {
                continue;
            }
            
            $where['id'] = $result['id'];
            $update['price_unit'] = $value['price_unit'];
            $affected = M('stock_bill_in_detail')->where($where)->save($update);
            
            if (!$affected) {
                return $return;
            }
            
            unset($map);
            unset($update);
        }
        
        $return = true;
        return $return;
    }
    
    /**
     * 更新采购入库单价格
     */
    public function updateErpPurchaseInDetail($purchaseID = 0, $purchasePrice = array())
    {
        $return = false;
        
        if (empty($purchaseID) || empty($purchasePrice)) {
            return $return;
        }
        
        foreach ($purchasePrice as $value) {
            $map['stock_purchase.id'] = $purchaseID;
            $map['stock_purchase_detail.id'] = $value['id'];
            
            $M = M('erp_purchase_in_detail');
            $info = $M
                ->field('erp_purchase_in_detail.*')
                ->join('stock_purchase ON stock_purchase.code=erp_purchase_in_detail.purchase_code')
                ->join('stock_purchase_detail ON stock_purchase_detail.pro_code=erp_purchase_in_detail.pro_code')
                ->where($map)
                ->find();
            if (empty($info)) {
                continue;
            }
            unset($map);
            //小计
            $map['id'] = $info['id'];
            $update['price_unit'] = $value['price_unit'];
            $update['price_subtotal'] = bcmul($info['pro_qty'], $value['price_unit'], 2);
            
            $affected = $M->where($map)->save($update);
            if (!$affected) {
                return $return;
            }
            unset($map);
            unset($update);
        }
        
        $return = true;
        return $return;
    }
    
    /**
     * 更新冲红单价格
     */
    public function updateErpPurchaseRefund($purchaseID = 0, $purchasePrice = array())
    {
        $return = false;
        
        if (empty($purchaseID) || empty($purchasePrice)) {
            return $return;
        }
        
        //冲红单ID
        $refundId = 0;
        foreach ($purchasePrice as $value) {
            $map['stock_purchase.id'] = $purchaseID;
            $map['stock_purchase_detail.id'] = $value['id'];
            
            $purchaseRefund = M('erp_purchase_refund_detail')
                ->field('erp_purchase_refund_detail.*')
                ->join('erp_purchase_refund ON erp_purchase_refund.id=erp_purchase_refund_detail.pid')
                ->join('stock_purchase ON stock_purchase.code=erp_purchase_refund.refer_code')
                ->join('stock_purchase_detail ON stock_purchase_detail.pro_code=erp_purchase_refund_detail.pro_code')
                ->where($map)
                ->find();
            unset($map);
            if (empty($purchaseRefund)) {
                continue;
            }
            if ($refundId <= 0) {
                $refundId = $purchaseRefund['pid'];
            }
            //更新价格
            $map['id'] = $purchaseRefund['id'];
            $update['price_unit'] = $value['price_unit'];
            $affected = M('erp_purchase_refund_detail')->where($map)->save($update);
            if (!$affected) {
                return $return;
            }
            unset($map);
            unset($update);
        }
        if ($refundId > 0) {
            //获取修改后的详情
            $where['pid'] = $refundId;
            $purchaseRefundDetail = M('erp_purchase_refund_detail')->where($where)->select();
            
            //计算总价格
            $totalPrice = 0;
            //计算待退价格
            $backPrice  = 0;
            foreach($purchaseRefundDetail as $val){
                $totalPrice = bcadd($totalPrice, bcmul($val['qualified_qty'], $val['price_unit'], 2), 2);
                $backPrice  = bcadd($backPrice, bcmul(bcsub($val['expected_qty'], $val['qualified_qty'], 2), $val['price_unit'], 2));
            }
            
            
            unset($where);
            
            $where['id'] = $refundId;
            $purchaseRefundInfo = D('erp_purchase_refund')->where($where)->find();
            if (empty($purchaseRefundInfo)) {
                return $return;
            }
            
            unset($where);
            $where['id'] = $refundId;
            $data['price_total']     = $totalPrice;
            $data['for_paid_amount'] = $backPrice;
            
            $updateLine = D('erp_purchase_refund')->where($where)->save($data);
            if (!$updateLine) {
                return $return;
            }
            
        }
        $return = true;
        
        return $return;
    }
    
    /**
     * 更新采购退货单
     */
    public function updatePurchaseOut($purchaseID = 0, $purchasePrice = array())
    {
        $return = false;
        
        if (empty($purchaseID) || empty($purchasePrice)) {
            return $return;
        }
        
        foreach ($purchasePrice as $value) {
            $map['stock_purchase.id'] = $purchaseID;
            $map['stock_purchase_detail.id'] = $value['id'];
            
            $M = M('stock_purchase_out_detail');
            $info = $M
                ->field('stock_purchase_out_detail.*')
                ->join('stock_purchase_out ON stock_purchase_out.id=stock_purchase_out_detail.pid')
                ->join('stock_purchase ON stock_purchase.code=stock_purchase_out.refer_code')
                ->join('stock_purchase_detail ON stock_purchase_detail.pro_code=stock_purchase_out_detail.pro_code')
                ->where($map)
                ->find();
            
            unset($map);
            if (empty($info)) {
                continue;
            }
            
            $map['id'] = $info['id'];
            $update['price_unit'] = $value['price_unit'];
        
            $affected = $M->where($map)->save($update);
            if (!$affected) {
                return $return;
            }
            unset($map);
            unset($update);
        }
        
        $return = true;
        return $return;
        
    }
    
    /**
     * 创建价格修改记录
     */
    public function createUpdatePriceLog($purchaseID = 0, $purchasePrice = array())
    {
        $return = false;
        
        if (empty($purchaseID) || empty($purchasePrice)) {
            return $return;
        }
        
        //获取采购单号
        $map['id'] = $purchaseID;
        $purchaseCode = M('stock_purchase')->where($map)->getField('code');
        unset($map);
        
        //获取SKU信息
        $map['id'] = $purchasePrice['id'];
        $skuInfo = M('stock_purchase_detail')->where($map)->find();
        
        $data = array(
        	    'refer_code' => $purchaseCode,
            'pro_code'   => $skuInfo['pro_code'],
            'purchase_user' => $skuInfo['created_user'],
            'purchase_time' => $skuInfo['created_time'],
            'old_price'     => $skuInfo['price_unit'],
            'new_price'     => $purchasePrice['price_unit'],
            'created_user'  => session('user.uid'),
            'created_time'  => get_time(),
            'updated_user'  => session('user.uid'),
            'updated_time'  => get_time(),
            'is_deleted'    => 0,
        );
        if (M('erp_purchase_price_log')->create($data)) {
            $affected = M('erp_purchase_price_log')->add();
            if (!$affected) {
                return $return;
            }
        }
        
        $return = true;
        return $return;
    }
    
    /**
     * 判断是否生成结算单
     */
    public function checkoutIsCreatedSettment($purchaseId = 0)
    {
        $return = false;
        
        if (empty($purchaseId)) {
            return $return;
        }
        
        $M = M('erp_settlement');
        $map['stock_purchase.id'] = $purchaseId;
        $map['erp_settlement.status'] = array('neq', 11);
        //采购单
        $settlement = $M
            ->field('erp_settlement.*')
            ->join('erp_settlement_detail ON erp_settlement_detail.code=erp_settlement.code')
            ->join('stock_purchase ON stock_purchase.code=erp_settlement_detail.order_code')
            ->where($map)
            ->find();
        if (!empty($settlement)) {
            $return = true;
            return $return;
        }
        //到货单
        $stockBillIn = $M
            ->field('erp_settlement.*')
            ->join('erp_settlement_detail ON erp_settlement_detail.code=erp_settlement.code')
            ->join('stock_bill_in ON stock_bill_in.code=erp_settlement_detail.order_code')
            ->join('stock_purchase ON stock_purchase.code=stock_bill_in.refer_code')
            ->where($map)
            ->find();
        if (!empty($stockBillIn)) {
            $return = true;
            return $return;
        }
        //入库单
        $purchaseIn = $M
            ->field('erp_settlement.*')
            ->join('erp_settlement_detail ON erp_settlement_detail.code=erp_settlement.code')
            ->join('erp_purchase_in_detail ON erp_purchase_in_detail.stock_in_code=erp_settlement_detail.order_code')
            ->join('stock_purchase ON stock_purchase.code=erp_purchase_in_detail.purchase_code')
            ->where($map)
        ->find();
        if (!empty($purchaseIn)) {
            $return = true;
            return $return;
        }
        
        //冲红单
        $refund = $M
            ->field('erp_settlement.*')
            ->join('erp_settlement_detail ON erp_settlement_detail.code=erp_settlement.code')
            ->join('erp_purchase_refund ON erp_purchase_refund.code=erp_settlement_detail.order_code')
            ->join('stock_purchase ON stock_purchase.code=erp_purchase_refund.refer_code')
            ->where($map)
            ->find();
        if (!empty($refund)) {
            $return = true;
            return $return;
        }
        
        //退货单
        $purchaseOut = $M
            ->field('erp_settlement.*')
            ->join('erp_settlement_detail ON erp_settlement_detail.code=erp_settlement.code')
            ->join('stock_purchase_out ON stock_purchase_out.rtsg_code=erp_settlement_detail.order_code')
            ->join('stock_purchase ON stock_purchase.code=stock_purchase_out.refer_code')
            ->where($map)
            ->find();
        
        if (!empty($purchaseOut)) {
            $return = true;
            return $return;
        }
        
        return $return;
    }
    
}
/* End of file InsalesLogic.class.php */
/* Location: ./Application/Logic/InsalesLogic.class.php */