<?php
/**
 * Date: 15/7/29
 * Time: 下午6:15
 */

namespace Wms\Logic;

class RepertoryLogic
{
    /**
     * 获取期初数量
     * @param $time         期初时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getSnapList($time, $pro_codes)
    {
        //初期数量
        $where['snap_time'] = $time;
        $where['pro_code']  = $pro_codes;
        $Model      = D('Repertory');
        $start_cost = $Model->field("pro_code, SUM(`stock_qty`) as stock_qty, SUM(price_unit) as price_unit")->where($where)->group('pro_code')->select();
        $list       = array();
        foreach ($start_cost as $val) {
            $list[$val['pro_code']] = $val;
        }
        return $list;
    }


    /**
     * 获取采购入库数据
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getPurchaseList($start_time, $end_time, $pro_codes)
    {
        //采购入库单
        $purchase = M('erp_purchase_in_detail');
        $where['pro_code']  = $pro_codes;
        $where['DATE_FORMAT(`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $purchaseDetail = $purchase->field("pro_code, SUM(`pro_qty`) as pro_qty, SUM(price_subtotal) as total_amount")->where($where)->group('pro_code')->select();
        $purchaseList = array();
        foreach ($purchaseDetail as $val) {
            $purchaseList[$val['pro_code']] = $val;
        }

        return $purchaseList;
    }

    /**
     * 获取采购入库数据明细
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_code     查询的SKU
     * @return array
     */
    public function getPurchaseDetail($start_time, $end_time, $pro_code)
    {
        $purchase = M('erp_purchase_in_detail');
        $where['erp_purchase_in_detail.pro_code']  = $pro_code;
        $where['DATE_FORMAT(erp_purchase_in_detail.created_time,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $join  = array(
            " INNER JOIN stock_purchase ON stock_purchase.code=erp_purchase_in_detail.purchase_code",
            " INNER JOIN partner ON partner.id=stock_purchase.partner_id"
        );
        $filed = "erp_purchase_in_detail.stock_in_code as code, erp_purchase_in_detail.pro_qty, erp_purchase_in_detail.price_unit , erp_purchase_in_detail.price_subtotal as total_amount, partner.name as partner_name, DATE_FORMAT(erp_purchase_in_detail.created_time,'%Y-%m-%d') as created_time";
        $purchaseDetail = $purchase->field($filed)->join($join)->where($where)->select();
        $purchaseList   = array();
        if (!empty($purchaseDetail)) {
            foreach ($purchaseDetail as $val) {
                $val['type'] = 'in';
                $purchaseList[$val['created_time']][] = $val;
            }
        }

        return $purchaseList;
    }

    /**
     * 获取加工入库单数据
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getProcessList($start_time, $end_time, $pro_codes)
    {
        $where['status'] = 2;
        $where['pro_code'] = $pro_codes;
        $where['DATE_FORMAT(`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $process = M('erp_process_in_detail');
        $processDetail = $process->field("pro_code, real_qty, SUM(real_qty*price) as total_amount ")->where($where)->group('pro_code')->select();
        $processList = array();
        foreach ($processDetail as $val) {
            $processList[$val['pro_code']] = $val;
        }
        return $processList;
    }

    /**
     * 获取加工入库单SKU数据明细
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_code     查询的SKU
     * @return array
     */
    public function getProcessDetail($start_time, $end_time, $pro_code)
    {
        $where['erp_process_in_detail.status'] = 2;
        $where['erp_process_in_detail.pro_code'] = $pro_code;
        $where['DATE_FORMAT(erp_process_in_detail.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $join = array(
            "INNER JOIN erp_process_in ON erp_process_in.id=erp_process_in_detail.pid",                                     //获取批次号
            "INNER JOIN erp_process_sku_relation ON erp_process_sku_relation.p_pro_code=erp_process_in_detail.pro_code",    //获取比例量
            //根据批次号和SKU，获取商品价格
            "INNER JOIN stock_bill_in_detail ON stock_bill_in_detail.refer_code=erp_process_in.code AND stock_bill_in_detail.pro_code=erp_process_in_detail.pro_code",
        );
        $field = "erp_process_in.refer_code as code, erp_process_in_detail.real_qty as pro_qty, stock_bill_in_detail.price_unit, (SUM(erp_process_sku_relation.ratio)*erp_process_in_detail.real_qty*stock_bill_in_detail.price_unit) as total_amount, DATE_FORMAT(erp_process_in_detail.`created_time`,'%Y-%m-%d') as created_time";
        $process = M('erp_process_in_detail');
        $processDetail = $process->field($field)->join($join)->where($where)->group('NULL')->select();

        $processList   = array();
        if (!empty($processDetail)) {
            foreach ($processDetail as $val) {
                $val['type'] = 'in';
                $processList[$val['created_time']][] = $val;
            }
        }

        return $processList;
    }

    /**
     * 获取销售出库单数据
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getStockOutList($start_time, $end_time, $pro_codes)
    {
        $stockOut = M('stock_bill_out_detail');
        $where['stock_bill_out_detail.pro_code'] = $pro_codes;
        $where['DATE_FORMAT(stock_bill_out_detail.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $join = array(
            'INNER JOIN stock_bill_out ON stock_bill_out.id=stock_bill_out_detail.pid AND stock_bill_out.type=1 AND stock_bill_out.is_deleted=0',
            'INNER JOIN stock_wave_distribution_detail ON stock_wave_distribution_detail.bill_out_id=stock_bill_out.id',
            'INNER JOIN stock_wave_distribution ON stock_wave_distribution.id=stock_wave_distribution_detail.pid',
            'INNER JOIN stock_bill_out_container ON stock_bill_out_container.refer_code=stock_wave_distribution.dist_code AND stock_bill_out_container.pro_code=stock_bill_out_detail.pro_code',
            #'INNER JOIN stock_bill_in_detail ON stock_bill_in_detail.refer_code=stock_bill_out_container.batch AND stock_bill_in_detail.pro_code=stock_bill_out_container.pro_code',
            #'INNER JOIN stock_bill_in ON stock_bill_in.id=stock_bill_in_detail.pid'
        );

        $filed = " stock_bill_out_container.batch, stock_bill_out_detail.price, stock_bill_out_detail.pro_code, SUM(stock_bill_out_detail.delivery_qty*stock_bill_out_detail.price) as total_amount, SUM(stock_bill_out_detail.delivery_qty) as delivery_qty";
        $stockOutDetail = $stockOut->field($filed)->join($join)->where($where)->group('stock_bill_out_detail.pro_code')->select();

        $stockOutList = array();
        foreach ($stockOutDetail as $val) {
            $stockOutList[$val['pro_code']] = $val;
        }
        return $stockOutList;
    }

    /**
     * 获取销售出库单数据明细
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_code     查询的SKU
     * @return array
     */
    public function getStockOutDetail($start_time, $end_time, $pro_code)
    {
        $stockOut = M('stock_bill_out_detail');
        $where['stock_bill_out_detail.pro_code'] = $pro_code;
        $where['DATE_FORMAT(stock_bill_out_detail.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $join = array(
            'INNER JOIN stock_bill_out ON stock_bill_out.id=stock_bill_out_detail.pid AND stock_bill_out.type=1  AND stock_bill_out.is_deleted=0'
        );
        $filed = "stock_bill_out.code ,stock_bill_out_detail.delivery_qty as pro_qty, stock_bill_out_detail.price as price_unit, (stock_bill_out_detail.delivery_qty*stock_bill_out_detail.price) as total_amount, DATE_FORMAT(stock_bill_out_detail.`created_time`,'%Y-%m-%d') as created_time";
        $stockOutDetail = $stockOut->field($filed)->join($join)->where($where)->select();

        $stockOutList   = array();
        if (!empty($stockOutDetail)) {
            foreach ($stockOutDetail as $val) {
                $val['type'] = 'stock_out';
                $stockOutList[$val['created_time']][] = $val;
            }
        }

        return $stockOutList;
    }

    /**
     * 获取加工出库单数据
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getProcessOutList($start_time, $end_time, $pro_codes)
    {
        $where['erp_process_out_detail.pro_code'] = $pro_codes;
        $where['erp_process_out_detail.status']   = 2;
        $where['DATE_FORMAT(erp_process_out_detail.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $join = array(
            "INNER JOIN erp_process_out_price ON erp_process_out_price.pro_code=erp_process_out_detail.pro_code "
        );
        $processOut = M('erp_process_out_detail');
        $filed = "erp_process_out_detail.pro_code, erp_process_out_detail.real_qty, SUM(erp_process_out_price.pro_qty) as pro_qty,erp_process_out_price.price,SUM(erp_process_out_price.pro_qty*erp_process_out_price.price) as total_amount";
        $processOutDetail = $processOut->field($filed)->join($join)->where($where)->group('erp_process_out_detail.pro_code')->select();
        $processOutList = array();
        if (!empty($processOutDetail)) {
            foreach ($processOutDetail as $val) {
                $processOutList[$val['pro_code']] = $val;
            }
        }
        return $processOutList;
    }

    /**
     * 获取加工出库单数据明细
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_code    查询的SKU
     * @return array
     */
    public function getProcessOutDetail($start_time, $end_time, $pro_code)
    {
        $where['erp_process_out_detail.pro_code'] = $pro_code;
        $where['erp_process_out_detail.status']   = 2;
        $where['DATE_FORMAT(erp_process_out_detail.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $join = array(
            "INNER JOIN erp_process_out ON erp_process_out.id=erp_process_out_detail.pid",
            "INNER JOIN erp_process_out_price ON erp_process_out_price.pro_code=erp_process_out_detail.pro_code "
        );
        $processOut = M('erp_process_out_detail');
        $filed = "erp_process_out.code as code, erp_process_out_detail.real_qty as pro_qty, erp_process_out_price.price as price_unit, (erp_process_out_price.pro_qty*erp_process_out_price.price) as total_amount, DATE_FORMAT(erp_process_out_detail.`created_time`,'%Y-%m-%d') as created_time";
        $processOutDetail = $processOut->field($filed)->join($join)->where($where)->select();

        $processOutList   = array();
        if (!empty($processOutDetail)) {
            foreach ($processOutDetail as $val) {
                $val['type'] = 'process_out';
                $processOutList[$val['created_time']][] = $val;
            }
        }

        return $processOutList;
    }

    /**
     * 获取采购退货单数据
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getRefundList($start_time, $end_time, $pro_codes)
    {
        $where['is_deleted'] = 0;
        $where['pro_code'] = $pro_codes;
        $where['DATE_FORMAT(`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $refund = M('stock_purchase_out_detail');
        $refundDetail = $refund->field('pro_code, sum(real_return_qty) as real_qty, sum(price_unit*real_return_qty) as total_amount')->where($where)->group('pro_code')->select();
        $refundList = array();
        foreach ($refundDetail as $val) {
            $refundList[$val['pro_code']] = $val;
        }
        return $refundList;
    }

    /**
     * 获取采购退货单数据明细
     * @param $start_time   开始时间
     * @param $end_time     结束时间
     * @param $pro_codes    查询的SKU
     * @return array
     */
    public function getRefundDetail($start_time, $end_time, $pro_codes)
    {
        $where['stock_purchase_out_detail.is_deleted'] = 0;
        $where['stock_purchase_out_detail.pro_code'] = $pro_codes;
        $where['DATE_FORMAT(stock_purchase_out_detail.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        $refund = M('stock_purchase_out_detail');
        $join  = array(
            'INNER JOIN stock_purchase_out ON stock_purchase_out.id=stock_purchase_out_detail.pid'
        );
        $filed = "stock_purchase_out.rtsg_code as code, stock_purchase_out_detail.real_return_qty as pro_qty, stock_purchase_out_detail.price_unit, (stock_purchase_out_detail.price_unit*stock_purchase_out_detail.real_return_qty) as total_amount, DATE_FORMAT(stock_purchase_out_detail.`created_time`,'%Y-%m-%d') as created_time";
        $refundDetail = $refund->field($filed)->join($join)->where($where)->select();
        $refundList = array();
        if (!empty($refundDetail)){
            foreach ($refundDetail as $val) {
                $val['type'] = 'refund_out';
                $refundList[$val['created_time']][] = $val;
            }
        }

        return $refundList;
    }

    /**
     * @param $start_time       开始时间
     * @param $end_time         结束时间
     * @param $pro_codes        查询的SKU
     * @param $data             待处理数组
     * $param $price_rate       税率
     */
    public function getDataList($start_time, $end_time, $pro_codes, &$data, $price_rate = 1)
    {
        //初期时间，获取前一天的结余
        $_time_1 = date('Y-m-d', (strtotime($start_time)-86400));

        //获取初期数量
        $startList      = $this->getSnapList($_time_1, $pro_codes);
        //获取期末数量
        $endList        = $this->getSnapList($end_time, $pro_codes);
        //采购入库单
        $purchaseList   = $this->getPurchaseList($start_time, $end_time, $pro_codes);
        //加工入库单
        $processList    = $this->getProcessList($start_time, $end_time, $pro_codes);
        //销售出库单
        $stockOutList   = $this->getStockOutList($start_time, $end_time, $pro_codes);
        //加工出库单
        $processOutList = $this->getProcessOutList($start_time, $end_time, $pro_codes);
        //采购退货单
        $refundList     = $this->getRefundList($start_time, $end_time, $pro_codes);

        $getPrice       = D('Process', 'Logic');

        foreach ($data as $key => $val) {
            //初期成本
            $data[$key]['first_nums']           = $startList[$val['pro_code']]['stock_qty'] ? $startList[$val['pro_code']]['stock_qty'] : 0;        //期初数量
            $data[$key]['first_amount']         = $startList[$val['pro_code']]['price_unit'] ? $startList[$val['pro_code']]['price_unit'] : 0;       //期初成本(含税)
            //期初成本(不含税)
            $data[$key]['first_amounts']        = $this->numbers_format_2($data[$key]['first_amount'] / $price_rate);

            //采购入库
            $data[$key]['purchase_nums']        = $purchaseList[$val['pro_code']]['pro_qty'] ? $purchaseList[$val['pro_code']]['pro_qty'] : 0;       //采购入库数
            //采购入库金额(含税)
            $data[$key]['purchase_amount']      = $purchaseList[$val['pro_code']]['total_amount'] ? $purchaseList[$val['pro_code']]['total_amount'] : 0;
            $data[$key]['purchase_in_amount']   = $this->numbers_format_2($purchaseList[$val['pro_code']]['total_amount'] / $price_rate);  //采购入库金额(不含税)



            //加工入库金额计算
            $data[$key]['process_nums']         = $processList[$val['pro_code']]['real_qty'] ? $processList[$val['pro_code']]['real_qty'] : 0;  //加工入库数
            //加工入库金额(含税)
            $data[$key]['process_in_amount']    = $processList[$val['pro_code']]['total_amount'] ? $processList[$val['pro_code']]['total_amount'] : 0;
            $data[$key]['process_in_amounts']   = $this->numbers_format_2($data[$key]['process_in_amount'] / $price_rate); //加工入库金额(不含税)

            //入库数
            $data[$key]['instock_num']          = $data[$key]['process_in_amount'] + $data[$key]['purchase_nums'];      //入库数
            $data[$key]['instock_num']          = $data[$key]['instock_num'] ? $data[$key]['instock_num'] : 0;
            $data[$key]['instock_amount']       = $data[$key]['purchase_amount'] + $data[$key]['process_in_amount']; //入库金额(含税)
            $data[$key]['instock_amount']       = $data[$key]['instock_amount'] ? $data[$key]['instock_amount'] : 0;

            $data[$key]['instock_amounts']      = $this->numbers_format_2($data[$key]['purchase_in_amount'] + $data[$key]['process_in_amount']); //入库金额(不含税)
            $data[$key]['insotck_cost']         = $this->numbers_format_2($data[$key]['instock_amounts'] / $data[$key]['instock_num']);     //入库加权平均成本

            //销售出库
            //获取单价
            $price_unit = $getPrice->get_price_by_sku($stockOutList[$val['pro_code']]['batch'], $val['pro_code']);
            $data[$key]['sale_cost_nums']       =  $stockOutList[$val['pro_code']]['delivery_qty'] ? $stockOutList[$val['pro_code']]['delivery_qty'] : 0;//销售数量
            if ($price_unit > 0) {
                $data[$key]['sale_cost_amount'] = $this->numbers_format_2($price_unit * $stockOutList[$val['delivery_qty']]);
            } else {
                $data[$key]['sale_cost_amount']     =  $this->numbers_format_2($stockOutList[$val['pro_code']]['total_amount']);     //销售成本（含税）
            }
            //销售成本（未含税）
            $data[$key]['sale_cost_amounts']    =  $this->numbers_format_2($data[$key]['sale_cost_amount'] / $price_rate);
            $data[$key]['sale_income']          =  $stockOutList[$val['pro_code']]['total_amount'];

            //加工出库
            $data[$key]['process_out_num']      = $processOutList[$val['pro_code']]['pro_qty'] ? $processOutList[$val['pro_code']]['pro_qty'] : 0;      //加工出库数
            //加工出库金额(含税)
            $data[$key]['process_out_amount']   = $processOutList[$val['pro_code']]['total_amount'] ? $processOutList[$val['pro_code']]['total_amount'] : 0;
            //加工出库金额(不含税)
            $data[$key]['process_out_amounts']  = $this->numbers_format_2($processOutList[$val['pro_code']]['total_amount'] / $price_rate);

            //采购退货
            $data[$key]['purchase_return_nums']     = $refundList[$val['pro_code']]['real_qty'] ? $refundList[$val['pro_code']]['real_qty'] : 0;     //采购退货数
            //采购退货金额(含税)
            $data[$key]['purchase_return_amount']   = $refundList[$val['pro_code']]['total_amount'] ? $refundList[$val['pro_code']]['total_amount'] : 0;
            //采购退货金额(不含税)
            $data[$key]['purchase_return_amounts']  = $this->numbers_format_2($refundList[$val['pro_code']]['total_amount'] / $price_rate);

            //出库数量
            $data[$key]['stock_out_nums']       = $data[$key]['sale_cost_nums'] + $data[$key]['process_out_num'] + $data[$key]['purchase_return_nums'];    //出库数量
            //出库金额（含税）
            $data[$key]['stock_out_amount']     = $data[$key]['sale_cost_amount'] + $data[$key]['process_out_amount'] + $data[$key]['purchase_return_amount'];
            //出库金额（未含税）
            $data[$key]['stock_out_amounts']    = $data[$key]['sale_cost_amounts'] + $data[$key]['process_out_amounts'] + $data[$key]['purchase_return_amounts'];
            //出库加权平均成本
            $data[$key]['stock_out_cost']       = $this->numbers_format_2($data[$key]['stock_out_amount'] / $data[$key]['stock_out_nums']);

            $data[$key]['profit_nums']          = 0;
            $data[$key]['profit_amount']        = 0;
            $data[$key]['profit_amounts']       = 0;

            //期末成本
            $data[$key]['last_nums']            = $endList[$val['pro_code']]['stock_qty'] ? $endList[$val['pro_code']]['stock_qty'] : 0;          //期末数量
            $data[$key]['last_amount']          = $endList[$val['pro_code']]['price_unit'] ? $endList[$val['pro_code']]['price_unit'] : 0;         //期末成本(含税)
            $data[$key]['last_amounts']         = $endList[$val['pro_code']]['price_unit'] ? $endList[$val['pro_code']]['price_unit'] : 0;         //期末成本(不含税)
        }
    }

    //格式化金额，截取2位小数
    private function numbers_format_2($number)
    {
        if ($number <= 0) {
            return 0;
        }
        $p= stripos($number, '.');
        return substr($number,0,$p+3);
    }
}