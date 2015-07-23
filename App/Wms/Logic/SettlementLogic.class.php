<?php
/**
 * 结算单Logic
 * User: san77
 * Date: 15/7/15
 * Time: 下午5:01
 */

namespace Wms\Logic;

class SettlementLogic
{
    public $error;

    /**
     * 获取采购单（预付款 并且 已生效 状态）
     * @param $map
     * @return mixed
     */
    public function getPurchaseList($map)
    {
        $where = array();
        $join  = array(
            "inner join warehouse on stock_purchase.wh_id=warehouse.id ",
            "inner join partner on stock_purchase.partner_id=partner.id ",
            "inner join user on stock_purchase.created_user = user.id ",
        );
        $field = "stock_purchase.id,stock_purchase.code,stock_purchase.paid_amount,stock_purchase.invoice_method as invoice_method_code,stock_purchase.status as state, warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as user_nickname";

        foreach($map as $key => $val){
            $where['stock_purchase.'.$key] = $val;
        }

        $where['stock_purchase.invoice_method'] = 0;        //付款方式，预付款
        $where['stock_purchase.status']         = 13;               //已完成状态
        $where['stock_purchase.is_deleted']     = 0;

        $model = M('stock_purchase');

        $purchaseResult = $model->field($field)->join($join)->where($where)->select();
        return $purchaseResult;
    }

    /**
     * 获取入库单（货到付款 且 已生效 状态，且 入库单是未付款状态）
     * @param $map
     * @return mixed
     */
    public function getStockInList($map)
    {
        $join = array(
            "inner join warehouse on stock_purchase.wh_id=warehouse.id ",
            "inner join partner on stock_purchase.partner_id=partner.id ",
            "inner join user on stock_purchase.created_user = user.id ",
        );
        $field = "stock_purchase.id,stock_purchase.code,stock_purchase.paid_amount,stock_purchase.invoice_method as invoice_method_code,stock_purchase.status as state, warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as user_nickname";

        foreach($map as $key => $val){
            $where['stock_purchase.'.$key]=$val;
        }

        $where['stock_purchase.invoice_method'] = 1;        //货到付款
        $where['stock_purchase.status']         = 13;       //已完成状态
        $where['stock_purchase.is_deleted']     = 0;
        //取采购单 货到付款 并且是 已生效状态，并且对应的入库单有 未付款的订单(入库单),并且是合格状态
        $join[] = "inner join erp_purchase_in_detail ON erp_purchase_in_detail.purchase_code=stock_purchase.code AND erp_purchase_in_detail.status='nopaid' AND erp_purchase_in_detail.pro_status!='unqualified'";
        $field = "erp_purchase_in_detail.id as id,stock_purchase.id as fid,stock_purchase.code as code, erp_purchase_in_detail.price_subtotal as paid_amount,stock_purchase.invoice_method as invoice_method_code,stock_purchase.status as state, warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as user_nickname";

        $model = M('stock_purchase');

        $purchaseDetailResult = $model->field($field)->join($join)->where($where)->select();

        return $purchaseDetailResult;
    }

    /**获取冲红单数据
     * @param $map
     * @return mixed
     */
    public function getRefundList($map)
    {
        foreach ($map as $key => $val) {
            $where['erp_purchase_refund.'.$key] = $val;
        }

        $where['erp_purchase_refund.status'] = 'norefund';      //未退款状态

        $join = array(
            "inner join warehouse on erp_purchase_refund.wh_id=warehouse.id ",
            "inner join partner on erp_purchase_refund.partner_id=partner.id ",
            "inner join user on erp_purchase_refund.created_user = user.id ",
        );
        $field = "erp_purchase_refund.id as id,erp_purchase_refund.code as code, erp_purchase_refund.for_paid_amount as paid_amount,erp_purchase_refund.invoice_method as invoice_method_code,erp_purchase_refund.status as state, warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as user_nickname";

        $model = M('erp_purchase_refund');

        $refundResult = $model->field($field)->join($join)->where($where)->select();

        return $refundResult;
    }

    /**
     * 获取退货单数据
     * @param $map
     */
    public function getPurchaseOutList($map)
    {
        foreach ($map as $key => $val) {
            $where['stock_purchase_out.' . $key] = $val;
        }

        $where['stock_purchase_out.status'] = 'refunded';    //已出库状态
        $where['stock_purchase_out.receivables_state'] = 'wait';        //已收款状态

        $model = M('stock_purchase_out');

        $join = array(
            "inner join warehouse on stock_purchase_out.wh_id=warehouse.id ",
            "inner join partner on stock_purchase_out.partner_id=partner.id ",
            "inner join user on stock_purchase_out.created_user = user.id ",
            "inner join stock_purchase_out_detail on stock_purchase_out_detail.pid=stock_purchase_out.id"   //联合查询字表中总价格
        );
        $field = "stock_purchase_out.id as id,stock_purchase_out.rtsg_code as code, SUM(stock_purchase_out_detail.real_return_qty*stock_purchase_out_detail.price_unit) as paid_amount, stock_purchase_out.receivables_state as state, warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as user_nickname";
        //获取退货单
        $refundResult = $model->field($field)->join($join)->where($where)->group('stock_purchase_out.id')->select();

        return $refundResult;
    }

    /**
     * @param int $partner_id 供货商ID
     * @param float $total_amount 结算总金额
     * @param string $bill_number 发票号码
     * @param float $bill_amount 发票金额
     * @param string $sn 结算单号
     * @param array $paid 采购单ID
     * @param array $stock 入库单ID
     * @param array $refund 冲红单ID
     * @param array $purchaseOut 退货单ID
     */
    public function saveData($partner_id, $total_amount, $bill_number, $bill_amount, $sn, $paid, $stock, $refund, $purchaseOut)
    {
        $settlementData = array(
            'code'          => $sn,
            'partner_id'    => $partner_id,
            'total_amount'  => $total_amount,
            'invoice'       => $bill_number,
            'invoice_amount'=> $bill_amount,
        );

        $data   = array();
        $data1  = array();
        $data2  = array();
        $data3  = array();

        $where  = array();
        $time   = get_time();

        $model = D('Settlement');
        //创建结算单
        if ($model->create($settlementData)) {
            if (!$model->add()) {
                $this->error = $model->getError();
                return false;
            }

            //查询采购单数据
            if (!empty($paid)) {
                $purchaseModel = M('stock_purchase');
                $where['id']   = array('in',implode(',',$paid));
                $field         = 'code as order_code, wh_id, created_time as purchase_time, paid_amount as total_amount';
                $data          = $purchaseModel->field($field)->where($where)->select();

                foreach ($data as $key => $val) {
                    $data[$key]['code']         = $sn;
                    $data[$key]['stock_id']     = '';
                    $data[$key]['order_type']   = 1;
                    $data[$key]['created_user'] = UID;
                    $data[$key]['created_time'] = $time;
                    ksort($data[$key]);
                }
                unset($where);
            }

            //查询入库单数据
            if (!empty($stock)) {
                $where['erp_purchase_in_detail.id'] = array('in',implode(',',$stock));
                $join[] = "inner join stock_purchase ON erp_purchase_in_detail.purchase_code=stock_purchase.code";
                $field  = "erp_purchase_in_detail.purchase_code as order_code, stock_purchase.wh_id as wh_id,erp_purchase_in_detail.id as stock_id,erp_purchase_in_detail.updated_time  as purchase_time, erp_purchase_in_detail.price_subtotal as total_amount, erp_purchase_in_detail.price_subtotal as total_amount";

                $purchaseDetailModel = M('erp_purchase_in_detail');
                $data1 = $purchaseDetailModel->field($field)->join($join)->where($where)->select();

                foreach ($data1 as $key => $val) {
                    $data1[$key]['code']         = $sn;
                    $data1[$key]['order_type']   = 2;
                    $data1[$key]['created_user'] = UID;
                    $data1[$key]['created_time'] = $time;
                    ksort($data1[$key]);
                }
                unset($where);
            }

            //查询冲红单数据
            if (!empty($refund)) {
                $where['id'] = array('in',implode(',', $refund));
                $field       = 'code as order_code, wh_id, created_time as purchase_time, for_paid_amount as total_amount';

                $refundModel = M('erp_purchase_refund');
                $data2       = $refundModel->field($field)->where($where)->select();

                foreach ($data2 as $key => $val) {
                    $data2[$key]['code']         = $sn;
                    $data2[$key]['stock_id']     = '';
                    $data2[$key]['order_type']   = 3;
                    $data2[$key]['created_user'] = UID;
                    $data2[$key]['created_time'] = $time;
                    ksort($data2[$key]);
                }
                unset($where);
            }

            //查询退货单数据
            if (!empty($purchaseOut)) {
                $where['stock_purchase_out.id'] = array('in',implode(',', $purchaseOut));

                //关联查询，获取退款总金额（联合查询字表中总价格）
                $join = "inner join stock_purchase_out_detail on stock_purchase_out_detail.pid=stock_purchase_out.id";
                $field       = 'stock_purchase_out.rtsg_code as order_code, stock_purchase_out.wh_id as wh_id, SUM(stock_purchase_out_detail.real_return_qty*stock_purchase_out_detail.price_unit) as total_amount,  stock_purchase_out.created_time as purchase_time';

                $refundModel = M('stock_purchase_out');
                $data3       = $refundModel->field($field)->join($join)->where($where)->group('stock_purchase_out.id')->select();


                foreach ($data3 as $key => $val) {
                    $data3[$key]['code']         = $sn;
                    $data3[$key]['stock_id']     = '';
                    $data3[$key]['order_type']   = 4;
                    $data3[$key]['created_user'] = UID;
                    $data3[$key]['created_time'] = $time;
                    ksort($data3[$key]);
                }
            }

            $insertData = array_merge($data, $data1, $data2, $data3);

            $detail = D('SettlementDetail');
            if ($detail->addAll($insertData)) {
                return true;
            } else {
                $this->error = $detail->getError();
            }
        }
    }
}