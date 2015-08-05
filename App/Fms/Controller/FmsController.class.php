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
            //根据配送单id或配送单号获得配送单信息及订单信息
            $array_result = $this->get_orders($id);
            $dist = $array_result['dist'];
            $orders = $array_result['orders'];
            
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
        $map['id'] = $id;
        $dist = $this->distInfo($map);
        if (empty($dist)) {
            $this->msgReturn('0','结算失败，未找到该配送单。');
        }
        $fms_list = A('Fms/List','Logic');
        //查询是否有退货，并且已创建拒收入库单
        $can = $fms_list->can_pay($id);
        if (!$can) {
            $this->msgReturn('0','结算失败，该配送单中有退货，请交货后再做结算');
        }

        //获得所有出库单id 
        $bill_out_ids = array_column($dist['detail'],'bill_out_id');
        $bill_outs = array();
        foreach ($bill_out_ids as $value) {
            //根据出库单id查出出库单信息
            $bill_out = $this->bill_out_Info($value);
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
            $value['status_cn'] = $this->get_status($sign_data['status']);
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
            $data['wipe_zero'] = $value['wipe_zero'];
            $s = $model->table('stock_wave_distribution_detail')->where($map)->save($data);
            logs($value['id'],'修改订单实收金额，'.$sign_msg.'[财务'.session('user.username').']','dist_detail');
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
        $order_list = $A->getOrderInfoByOrderIdArr($order_ids);
        $orders = $order_list['list'];
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
                $val['pay_for_price'] = $val['actual_price'] - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee']; 
                //抹零
                if ($val['pay_status'] != '已付款') {
                    $val['pay_for_price'] = $DistLogic->wipeZero($val['pay_for_price']);
                 }
            } else {
                $val['pay_for_price']=0;
            }
            if($val['pay_status']=='已付款'){
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
        
        $map['id'] = $dist_id;
        //按配送单id查询
        $dist = $this->distInfo($map);
        if(empty($dist)) {
            unset($map);
            $map['dist_code'] = $dist_id;
            //按配送单号查询
            $dist = $this->distInfo($map);
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
            $bill_out = $this->bill_out_Info($value);
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
            $value['status_cn'] = $this->get_status($sign_data['status']);

            switch ($sign_data['pay_status']) {
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
                    # code...
                    break;
            };
            $value['pay_status'] = $s;
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
                            $val['actual_sum_price'] = $val['real_sign_qty'] * $sign_detail[$i]['price_unit'];
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
            
            if($value['actual_price'] > 0) {
                //应收总计 ＝ 合计 － 优惠金额 － 支付减免 ＋ 运费
                $value['pay_for_price'] = $value['actual_price'] - $value['minus_amount'] - $value['pay_reduce'] + $value['deliver_fee'];
                if($value['pay_status'] != '已付款'){
                    $old_value = $value['pay_for_price'];
                    //抹零处理
                    $value['pay_for_price'] = $Dist_Logic->wipeZero($value['pay_for_price']);
                    
                    if ($value['status_cn'] == '已完成') {
                        $wipe_zero_sum += $value['wipe_zero'];
                        $deposit_sum   += $value['deposit'];
                    } else {
                        $value['wipe_zero'] = round($old_value - $value['pay_for_price'],2);
                        //抹零总计
                        $wipe_zero_sum += $value['wipe_zero'];
                    }
                    
                }
            }
            else {
                //应收总计
                $value['pay_for_price'] = 0 ;
            }
            
            if($value['pay_status']=='已付款'){
                //应收总计
                $value['pay_for_price'] = 0;
                //司机实收金额
                $value['deal_price'] = 0;
            }
            elseif($value['status_cn']== '已拒收') {
                //应收总计
                $value['pay_for_price'] = 0;
                //司机实收金额
                $value['deal_price'] = 0;
                $value['actual_price'] = 0;
                //结算金额 ＋＝ 0
                $dist['pay_for_price_total'] += 0;
            }
            elseif($value['status_cn'] == '已完成') {
                //结算金额 ＋＝ 应收总计
                $dist['pay_for_price_total'] += $value['deal_price'];
            }
            elseif($value['status_cn'] == '已签收') {
                //结算金额 ＋＝ 应收总计
                $dist['pay_for_price_total'] += $value['pay_for_price'];
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

    /*根据配送单id或者配送单号dist_code获得配送单信息
    *@param array(id,dist_code)
    *@return $dist结果集
    */
    protected function distInfo($map){
        if(empty($map)){
            return null;
        }
        $map['is_deleted'] = 0;
        $dist = M('stock_wave_distribution')->where($map)->find();
        if (!empty($dist)) {
            unset($map);
            //查询条件为配送单id
            $map['pid'] = $dist['id'];
            $map['is_deleted'] = 0;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $dist_detail = M('stock_wave_distribution_detail')->where($map)->select();
            $dist['detail'] = $dist_detail;
        }
        return $dist;
    }
    /*根据出库单id获得出库单信息
    *@param id出库单id
    *@return $info结果集
    */
    protected function bill_out_Info($id){
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
    protected function get_status($status = 0){
        switch ($status) {
            case 0:
                $s = '已分拨';
                break;
            case 1:
                $s = '已装车';
                break;
            case 2:
                $s = '已签收';
                break;
            case 3:
                $s = '已拒收';
                break;
            case 4:
                $s = '已完成';
                break;
            default: 
                $s = '未处理'; 
                break;
        } 
        return $s;   
    }
    
}