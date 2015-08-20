<?php
namespace Fms\Controller;
class FmsController extends \Common\Controller\AuthController{
    
    /*
    *查询按钮执行代码
    *查询配送单及它的订单信息
    *@param:  id 配送单id或配送单号
    */
    public function orders(){
        $id = I('id',0);
        if(!empty($id)){
            $L = A('Fms/List','Logic');
            //根据配送单id或配送单号获得配送单信息及订单信息
            $array_result = $this->get_orders($id);
            $dist = $array_result['dist'];
            $orders = $array_result['orders'];
            //获得配送单的交货状态
            $status = $L->can_pay($dist['id']);
            $this->assign('status',$status);
            $this->assign('dist', $dist);
            $this->assign('data', $orders);
        }
        $this->display('tms:orders_fms');
    }
    /*
    *结算按钮执行代码
    *以车单维度回款功能
    *@param:  id 配送单id
    */
    public function pay() {
        $id = I('get.id',0);
        if (empty($id)) {
            $this->msgReturn('0','结算失败，提货码不能为空');
        }
        $fms_list = A('Fms/List','Logic');
        $map['id'] = $id;
        $dist = $fms_list->distInfo($map);
        if (empty($dist)) {
            $this->msgReturn('0','结算失败，未找到该配送单。');
        }
        $fms_list = A('Fms/List','Logic');
        //查询是否有退货，并且已创建拒收入库单
        $can = $fms_list->can_pay($id);
        if ($can == 3) {
            //有退货没有创建拒收入库单
            $this->msgReturn('0','结算失败，该配送单中有退货，请交货后再做结算');
        }

        //获得所有出库单id 
        $bill_out_ids = array_column($dist['detail'],'bill_out_id');
        $bill_outs = array();
        foreach ($bill_out_ids as $value) {
            //根据出库单id查出出库单信息
            $bill_out = $fms_list->bill_out_Info($value);
            if(!empty($bill_out)){
                $bill_outs[] = $bill_out;
            }
        }
        if (empty($bill_outs)) {
            $this->msgReturn('0','查询失败，未找到该配送单中的订单。');
        }
        //遍历所有出库单，并判断是否已经结算过，是否含有未处理的订单
        foreach ($bill_outs as $value){
            //找到该出库单的签收信息
            $dist_detail = $dist['detail'];
            for($i = 0; $i < count($dist_detail); $i++){
                if($dist_detail[$i]['bill_out_id'] == $value['id']){
                    $sign_data = $dist_detail[$i];
                }
            }
            //获得订单状态
            $value['status_cn'] = $fms_list->get_status($sign_data['status']);
            if($value['status_cn'] != '已签收' && $value['status_cn'] != '已拒收' ) {
                if($value['status_cn'] == '已完成') {
                    $this->msgReturn('0','结算失败,该配送单已结算过');
                }
                $this->msgReturn('0','结算失败，该配送单含有未处理的订单');
            }
        }
        //根据配送单id或配送单号获得配送单信息及订单信息
        $array_result = $this->get_orders($id);
        //配送单信息
        $dist = $array_result['dist'];
        //订单信息
        $orders = $array_result['orders'];
        //实例化模型
        $model = M();
        //启动事务
        $model->startTrans();
        //回写配送单详情状态
        foreach ($orders as $value) {
            unset($map);
            unset($data);
            $map['bill_out_id'] = $value['id'];
            $map['is_deleted'] = 0;
            $data['status'] = 4; //已完成
            $data['real_sum'] = $value['pay_for_price'];
            $s = $model->table('stock_wave_distribution_detail')->where($map)->save($data);
            logs($value['id'],'已完成','dist_detail');
        }
        //回写配送单状态
        unset($map);
        unset($data);
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $data['status'] = 4; //已结算
        $data['deal_price'] = $dist['pay_for_price_total'];
        $res = $model->table('stock_wave_distribution')->where($map)->save($data);

        //回写订单状态
        $order_ids = array_column($bill_outs,'refer_code');
        $A = A('Common/Order','Logic');
        //根据多个订单ID批量获取订单
        unset($map);
        $map = array('order_ids' => $order_ids, 'itemsPerPage' => count($order_ids));
        $orders = $A->order($map);
        if(empty($orders)) {
            //回滚事务
            $model->rollback();
            $this->msgReturn('0','结算失败，未找到该配送单中的hop的订单。');
        }
        $DistLogic = A('Tms/Dist','Logic');
        $flag = true;
        foreach ($orders as $val) {
            unset($map);   
            foreach ($val['detail'] as $v) {
                if($val['status_cn'] == '已签收') {
                    $val['actual_price'] += $v['actual_sum_price'];    
                }
                elseif($val['status_cn']=='已退货') {
                    $val['pay_for_price']    = 0;
                    $row['id']               = $v['id'];
                    $row['actual_price']     = 0;
                    $row['actual_quantity']  = 0;
                    $row['actual_sum_price'] = 0;
                    $map['order_details'][]  = $row;
                }
            }
            if ($val['actual_price'] > 0) {
                $val['pay_for_price'] = $val['actual_price'] - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee'] - $val['deposit']; 
                //支付状态不等于已支付，支付方式不等于账期支付,抹零
                if (!($val['pay_status'] == 1 || $val['pay_type'] == 2)) {
                    $val['pay_for_price'] = $DistLogic->wipeZero($val['pay_for_price']);
                 }
            } else {
                $val['pay_for_price']=0;
            }
            
            $map['status']  = '1';//已完成
            $map['deal_price'] = $val['pay_for_price'];
            $order_ids[] = $val['id'];
            $map['suborder_id'] = $val['id'];
            $map['cur']['name'] = '财务'.session('user.username');
            $res = $A->set_status($map);
            //若设置状态成功
            if($res['status'] == 0){
                $flag = $flag && true;
            }else{
                //若设置状态失败
                $flag = $flag && false;
            }  
        }
        //若所有订单状态更新成功
        if($flag){
            //提交事务
            $model->commit();
        }else{
            //回滚事务
            $model->rollback();
        }
        $this->msgReturn('1','结算成功。','',U('Fms/orders',array('id'=>$id)));
    }
    /*  根据配送单id或配送单号获得配送单信息及订单信息
    *   @param $dist_id配送单id或配送单号
    *   @return $array_result = array('dist' => $dist,'orders' => $orders)
    */
    protected function get_orders($dist_id = 0){
        $list_logic = A('Fms/List','Logic');
        $map['id'] = $dist_id;
        //按配送单id查询
        $dist = $list_logic->distInfo($map);
        if(empty($dist)) {
            unset($map);
            $map['dist_code'] = $dist_id;
            //按配送单号查询
            $dist = $list_logic->distInfo($map);
            if(empty($dist)) {
                $this->msgReturn('0','查询失败，未找到该单据');
            }
        }
        
        //抹零总计
        $wipe_zero_sum = 0;
        //押金总计
        $deposit_sum = 0;
        $Dist_Logic = A('Tms/Dist','Logic');
        //获得所有出库单id 
        $bill_out_ids = array_column($dist['detail'],'bill_out_id');
        $bill_outs = array();
        $orders = array();
        //查出所有配送单信息
        foreach ($bill_out_ids as $value) {
            //根据出库单id查出出库单信息
            $bill_out = $list_logic->bill_out_Info($value);
            if(!empty($bill_out)){
                $bill_outs[] = $bill_out;
            }
        }
        if(empty($bill_outs)) {
            $this->msgReturn('0','查询失败，未找到该配送单中的订单。');
        }
        //遍历所有出库单，并拼装数据
        foreach ($bill_outs as $value){
            //找到该出库单的签收信息
            $dist_detail = $dist['detail'];
            for($i = 0; $i < count($dist_detail); $i++){
                if($dist_detail[$i]['bill_out_id'] == $value['id']){
                    $sign_data = $dist_detail[$i];
                }
            }
           
            unset($map);
            $map['pid'] = $sign_data['id'];
            $map['is_deleted'] = 0;
            //找到出库单的签收的所有详情信息
            $sign_data['detail'] = M('tms_sign_in_detail')->where($map)->select();
            //获得订单状态
            $value['status_cn'] = $list_logic->get_status($sign_data['status']);
            $value['pay_status'] = $sign_data['pay_status'];

            //保存出库单详情的数组
            $value_detail = array();
            //遍历出库单详情，并拼装数据
            foreach ($value['detail'] as $val) {
                //签收详情信息
                $sign_detail = $sign_data['detail'];
                if(count($sign_detail) > 0){
                    //遍历签收详情信息找到该出库单详情对应的签收详情信息
                    for($i = 0; $i < count($sign_detail); $i++){
                        if($sign_detail[$i]['bill_out_detail_id'] == $val['id']){
                            //实收数量
                            $val['real_sign_qty'] = $sign_detail[$i]['real_sign_qty'];
                            if($sign_detail[$i]['measure_unit'] == $sign_detail[$i]['charge_unit']){
                                //计量单位
                                $val['unit_id'] = $sign_detail[$i]['measure_unit'];
                            }else{
                                //计量单位
                                $val['unit_id'] = $sign_detail[$i]['charge_unit'];
                            }
                            //实收小计
                            $val['actual_sum_price'] = bcmul($val['real_sign_qty'], $sign_detail[$i]['price_unit'], 2);
                            //合计
                            $value['actual_price'] += $val['actual_sum_price'];

                        }
                    }
                }else{
                    //实收数量
                    $val['real_sign_qty'] = 0;
                    //实收小计
                    $val['actual_sum_price'] = 0;
                    //计量单位
                    $val['unit_id'] = $val['measure_unit'];
                    //合计
                    $value['actual_price'] = 0;
                }
                $value_detail[] = $val;
            }
            $value['detail'] = $value_detail;
            //优惠金额
            $value['minus_amount'] = $sign_data['minus_amount'];
            //支付减免
            $value['pay_reduce']   = $sign_data['pay_reduce'];
            //运费
            $value['deliver_fee']  = $sign_data['deliver_fee'];
            //司机实收金额
            $value['deal_price']   = $sign_data['real_sum'];
            $value['sign_msg']     = $sign_data['sign_msg'];
            //押金
            $value['deposit']      = $sign_data['deposit'];
            //抹零
            $value['wipe_zero']    = $sign_data['wipe_zero'];
            $value['pay_type']     = $sign_data['pay_type'];
            
            if($value['actual_price'] > 0) {
                //应收总计 ＝ 合计 － 优惠金额 － 支付减免 ＋ 运费 - 押金
                $value['pay_for_price'] = $value['actual_price'] - $value['minus_amount'] - $value['pay_reduce'] + $value['deliver_fee'] - $value['deposit'];
                $deposit_sum   += $value['deposit'];
                //支付状态不等于已支付，支付方式不等于账期支付
                if(!($value['pay_status'] == 1 || $value['pay_type'] == 2)){
                    //抹零处理
                    $value['pay_for_price'] = $Dist_Logic->wipeZero($value['pay_for_price']);
                    //抹零总计
                    $wipe_zero_sum += $value['wipe_zero'];
                }
            }
            else {
                //应收总计
                $value['pay_for_price'] = 0 ;
            }
            
            switch ($value['status_cn']) {
                case '已拒收':
                    //应收总计
                    $value['pay_for_price'] = 0;
                    //司机实收金额
                    $value['deal_price'] = 0;
                    $value['actual_price'] = 0;
                    //结算金额 +＝ 0
                    $dist['show_pay_for_price_total'] += 0;
                    $dist['pay_for_price_total'] += 0;
                    break;

                case '已签收':
                case '已完成':
                    //支付状态等于已支付，支付方式等于账期支付
                    if($value['pay_status']==1 || $value['pay_type'] == 2){
                        //应收总计
                        //$value['pay_for_price'] = 0;
                        //司机实收金额
                        $value['deal_price'] = 0;
                        //结算金额 +＝ 0
                        $dist['show_pay_for_price_total'] += 0;
                        $dist['pay_for_price_total'] += $value['pay_for_price'];
                    } else {
                        if ($value['status_cn'] == '已签收') {
                            //结算金额
                            $dist['show_pay_for_price_total'] += $value['pay_for_price'];
                            $dist['pay_for_price_total'] += $value['pay_for_price'];
                        } else {
                            //结算金额
                            $dist['show_pay_for_price_total'] += $value['deal_price'];
                            $dist['pay_for_price_total'] += $value['deal_price'];
                        }
                    }
                    break;
                
                default:
                    //应收总计
                    $value['pay_for_price'] = 0;
                    //司机实收金额
                    $value['deal_price'] = 0;
                    //结算金额 ＝ 0
                    $dist['show_pay_for_price_total'] += 0;
                    $dist['pay_for_price_total'] += 0;
                    break;
            }
            $orders[] = $value;
        }
        //抹零总计
        $dist['wipe_zero_sum'] = $wipe_zero_sum;
        $dist['deposit_sum'] = $deposit_sum;
        $array_result = array('dist' => $dist,'orders' => $orders);
        
        return $array_result;
    }

    protected function msgReturn($res, $msg='', $data = '', $url='') {
        $msg = empty($msg)?($res > 0 ?'操作成功':'操作失败'):$msg;
        if(IS_AJAX) {
            $this->ajaxReturn(array('status'=>$res,'msg'=>$msg,'data'=>$data,'url'=>$url));
        }
        else if($res) { 
                $this->success($msg,$url);
            }
            else{
                $this->error($msg,$url);
            }
        exit();
    }

}