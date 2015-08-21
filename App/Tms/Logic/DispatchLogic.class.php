<?php
/**
 * 调度端，调度任务
 */
namespace Tms\Logic;

class DispatchLogic
{
    /**
     * [avgDeliveryFee 均摊运费到任务]
     * @param  [type] $delivery [任务ID数组]
     * @return [type]           [description]
     */
    public function avgDeliveryFeeToTask($ids, $fee)
    {
        $M = M('tms_dispatch_task');
        $map['id'] = array('in', $ids);
        $map['is_deleted'] = 0;
        //去掉已删除的
        $tasks = $M->field('id')->where($map)->select();
        unset($map);
        $task_ids = array_column($tasks, 'id');
        $map['id'] = array('in', $task_ids);
        //平均运费
        $cou = count($task_ids);
        $task_fee = sprintf('%.2f', $fee/$cou);
        $res = $M->where($map)->save(array('delivery_fee' => $task_fee));
        return $res;
    }

    /**
     * [avgDeliveryFee 均摊运费到订单]
     * @param  [type] $delivery [description]
     * @return [type]           [description]
     */
    public function avgDeliveryFeeToOrder($ids, $fee)
    {
        $wA = A('Wms/Distribution', 'Logic');
        //未删除配送单列表
        $dist = $wA->distList($ids);
        $dist_ids = array_column($dist, 'id');
        //配送单详情
        $details = $wA->getDistDetailsByPid($dist_ids);
        $dist_detail_ids = array_column($details, 'id');
        //平均运费到配送单详情
        $cou = count($dist_detail_ids);
        $avg_fee = sprintf('%.2f', $fee/$cou);
        $map['id'] = $dist_detail_ids;
        $map['data'] = array('avg_fee' => $avg_fee);
        $res = $wA->saveSignDataToDistDetail($map);
        return $res;
    }
}