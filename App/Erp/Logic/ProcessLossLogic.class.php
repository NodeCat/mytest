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
    public function getStockLoss($c_code, $p_code, $start_time, $end_time, $location_ids, $wh_id)
    {
        //根据条件查找加工单 和加工单详情
        if (!empty($start_time) && !empty($end_time)) {
            $map['erp_process.created_time'] = array('between', "$start_time,$end_time");
        }
        $map['erp_process.is_deleted'] = 0;
        if(!empty($wh_id)){
            $map['erp_process.wh_id'] = $wh_id;
        }
        $map['erp_process_sku_relation.is_deleted'] = 0;
        $map['erp_process_detail.p_pro_code'] = array('in', $p_code);
        $process_detail_info = M('erp_process')
        ->join('INNER JOIN erp_process_detail ON erp_process.id=erp_process_detail.pid')
        ->join('INNER JOIN erp_process_sku_relation ON erp_process_detail.p_pro_code=erp_process_sku_relation.p_pro_code')
        ->field('erp_process_detail.p_pro_code, erp_process_detail.p_pro_name, erp_process_detail.real_qty as p_pro_num, erp_process_sku_relation.c_pro_code, erp_process_sku_relation.ratio')
        ->where($map)->select();
        unset($map);

        $result = array();
        foreach($process_detail_info as $k => $val){
            //统计父sku
            $result[$val['c_pro_code']]['p_pro_code'] = $val['p_pro_code'];
            $result[$val['c_pro_code']]['p_pro_name'] = $val['p_pro_name'];
            //统计父sku实际加工量
            $result[$val['c_pro_code']]['p_pro_num'] = bcadd($result[$val['c_pro_code']]['p_pro_num'],$val['p_pro_num'],2);
            //统计子sku
            $result[$val['c_pro_code']]['c_pro_code'] = $val['c_pro_code'];
            //统计比例
            $result[$val['c_pro_code']]['ratio'] = $val['ratio'];
            //计算子sku实际消耗量
            $result[$val['c_pro_code']]['c_pro_num'] = bcadd($result[$val['c_pro_code']]['c_pro_num'], bcmul($val['p_pro_num'], $val['ratio'], 2), 2);
            //子sku
            $c_pro_code_arr[] = $val['c_pro_code'];
        }

        //从库存交易日志中，查询XA-001（加工损耗库位）上的子sku数量 
        $map['location_id'] = array('in',$location_ids);
        $map['direction'] = 'INPUT';
        if (!empty($start_time) && !empty($end_time)) {
            $map['created_time'] = array('between', "$start_time,$end_time");
        }
        if(!empty($getStockLoss)){
            $map['pro_code'] = array('in', $c_pro_code_arr);
        }
        $Loss_info = M('stock_move')->field('pro_code,move_qty,created_time,batch')->where($map)->select();
        unset($map);

        $tmp_by_pro_code = array();
        foreach($Loss_info as $k => $val){
            if(isset($tmp_by_pro_code[$val['pro_code']])){
                $tmp_by_pro_code[$val['pro_code']]['move_qty'] = bcadd($tmp_by_pro_code[$val['pro_code']]['move_qty'], $val['move_qty'], 2);
            }else{
                $tmp_by_pro_code[$val['pro_code']]['move_qty'] = $val['move_qty'];
            }
            //计算加权平均价格（损耗）
            $sku_price = A('Process','Logic')->get_price_by_sku($val['batch'],$val['pro_code']);
            $tmp_by_pro_code[$val['pro_code']]['total_amount'] = bcadd($tmp_by_pro_code[$val['pro_code']]['total_amount'], bcmul($sku_price, $val['move_qty'], 2), 2);
            unset($sku_price);
        }

        foreach($result as $k => $val){
            //统计实际损耗数
            $result[$k]['c_loss_number'] = bcadd($result[$k]['c_loss_number'], $tmp_by_pro_code[$k]['move_qty'], 2);
            //计算加权平均价格（损耗）
            $result[$k]['total_amount'] = $tmp_by_pro_code[$k]['total_amount'];
        }

        return $result;
    }

    /**
     * 根据code查询location所在库位的ID
     * @param $code     要查询的code标识
     * @return array
     */
    public function getLocationList($code, $wh_id = '')
    {
        $map['code'] = array('in', $code);
        if(!empty($wh_id)){
            $map['wh_id'] = $wh_id;
        }
        $model = M('location');
        $location_ids = $model->where($map)->getField('id', true);
        return $location_ids;
    }
}