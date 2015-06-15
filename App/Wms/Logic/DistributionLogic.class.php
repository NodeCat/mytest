<?php
namespace Wms\Logic;
/**
 * 配送路线逻辑封装
 * @author zhangchaoge
 *
 */
class DistributionLogic {
    /**
     * 搜索订单
     */
    public function search($search = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($search)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        
        $sql = "select * from stock_bill_out o inner jion stock_bill_out_detail d on d.pid=o.id 
                where o.company=" . $search['company_id'] . "
                o.wh_id=" . $search['wh_id'] . "
                d.";
        $map['company_id'] = $search['company_id'];
        $map['wh_id'] = $search['wh_id'];
        
    }
}