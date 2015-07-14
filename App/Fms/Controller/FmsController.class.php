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
        if(!empty($id)) {
            $map['id'] = $id;
            //按配送单id查询
            $dist = $this->distInfo($map);
            if(empty($dist)) {
                unset($map);
                $map['dist_code'] = $id;
                //按配送单号查询
                $dist = $this->distInfo($map);
                if(empty($dist)) {
                    $this->msgReturn('0','查询失败，未找到该单据');
                }
            }
            //dump($dist);
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
                                $val['real_sign_qty'] = $sign_detail[$i]['real_sign_qty'];
                                if($sign_detail[$i]['measure_unit'] == $sign_detail[$i]['charge_unit']){
                                    $val['unit_id'] = $sign_detail[$i]['measure_unit'];
                                }else{
                                    $val['unit_id'] = $sign_detail[$i]['charge_unit'];
                                }

                                $val['actual_sum_price'] = $val['real_sign_qty'] * $sign_detail[$i]['price_unit'];
                                $value['actual_price'] += $val['actual_sum_price'];
                            }
                        }
                    }else{
                        $val['real_sign_qty'] = 0;
                        $val['actual_sum_price'] = 0;
                        $val['unit_id'] = $val['measure_unit'];
                        $value['actual_price'] = 0;
                    }
                    $value_detail[] = $val;
                }
                $value['detail'] = $value_detail;

                $value['minus_amount'] = $sign_data['minus_amount'];
                $value['pay_reduce'] = $sign_data['pay_reduce'];
                $value['deliver_fee'] = $sign_data['deliver_fee'];
                $value['deal_price'] = $sign_data['real_sum'];
                
                if($value['actual_price'] > 0) {
                    $value['pay_for_price'] = $value['actual_price'] - $value['minus_amount'] - $value['pay_reduce'] + $value['deliver_fee'];
                }
                else {
                    $value['pay_for_price'] = 0 ;
                }
                
                if($value['pay_status']=='已付款'){
                    $value['pay_for_price'] = 0;
                    $value['deal_price'] = 0;

                }
                elseif($value['status_cn']== '已退货') {
                    $value['pay_for_price'] = 0;
                    $value['deal_price'] = 0;
                    $value['actual_price'] = 0;
                    $dist['pay_for_price_total'] += 0;
                }
                elseif($value['status_cn'] == '已完成') {
                    $dist['pay_for_price_total'] += $value['pay_for_price'];
                }
                elseif($value['status_cn'] == '已签收') {
                    $dist['pay_for_price_total'] += $value['pay_for_price'];
                }
                $orders[] = $value;
            }
            //dump($orders);
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
        if(empty($id)) {
            $this->msgReturn('0','结算失败，提货码不能为空');
        }
        $map['id'] = $id;
        $dist = $this->distInfo($map);
        if(empty($dist)) {
            $this->msgReturn('0','结算失败，未找到该配送单。');
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
        if(empty($bill_outs)) {
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
            if($value['status_cn'] != '已签收' && $value['status_cn'] != '已退货' ) {
                if($value['status_cn'] == '已完成') {
                    $this->msgReturn('0','结算失败,该配送单已结算过');
                }
                $this->msgReturn('0','结算失败，该配送单含有未处理的订单');
            }
        }
        
        //回写签收状态
        foreach ($bill_outs as $value) {
            unset($map);
            $map['bill_out_id'] = $value['id'];
            $data['status'] = 4; //已完成
            $sign = M('stock_wave_distribution_detail');
            //找到该出库单的签收信息
            $s = $sign->where($map)->save($data);
        }
        $order_ids = array_column($bill_outs,'refer_code');
        /*
        //回写订单状态
        $A = A('Common/Order','Logic');
        //根据多个订单ID批量获取订单
        $order_list = $A->getOrderInfoByOrderIdArr($order_ids);
        $orders = $order_list['list'];
        if(empty($orders)) {
            $this->msgReturn('0','结算失败，未找到该配送单中的订单。');
        }
        
        unset($map);
        foreach ($orders as $val) {
            $val['pay_for_price'] = $val['actual_price'] - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee'];    
            foreach ($val['detail'] as $v) {
                if($val['status_cn'] == '已签收') {
                    $val['pay_for_price'] += $v['actual_sum_price'];    
                }
                elseif($val['status_cn']=='已退货') {
                    $val['pay_for_price']    = 0;
                    $row['id']               = $v['id'];
                    $row['actual_price']     = 0;
                    $row['actual_quantity']  = 0;
                    $row['actual_sum_price'] = 0;
                    $map['order_details'][]  = $row;
                }
                if($val['pay_status']=='已付款'){
                    $val['pay_for_price']=0;
                }
            }
            $map['status']  = '1';//已完成
            $map['deal_price'] = $val['pay_for_price'];
            $order_ids[] = $val['id'];
            $map['suborder_id'] = $val['id'];
            $map['cur']['name'] = session('user.username');
            $res = $A->set_status($map);
            unset($map);
        }*/
        $this->msgReturn('1','结算成功。','',U('Fms/orders',array('id'=>$id)));
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
    public function distInfo($map){
        if(empty($map)){
            return null;
        }
        $dist = M('stock_wave_distribution')->where($map)->find();
        unset($map);
        //查询条件为配送单id
        $map['pid'] = $dist['id'];
        //根据配送单id查配送详情单里与出库单相关联的出库单id
        $dist_detail = M('stock_wave_distribution_detail')->where($map)->select();
        $dist['detail'] = $dist_detail;
        return $dist;
    }
    /*根据出库单id获得出库单信息
    *@param id出库单id
    *@return $info结果集
    */
    public function bill_out_Info($id){
        if(empty($id)){
            return null;
        }
        $bill_out = M('stock_bill_out')->find($id);
        unset($map);
        //查询条件为出库单id
        $map['pid'] = $id;
        //根据配送单id查配送详情单里与出库单相关联的出库单id
        $bill_out_detail = M('stock_bill_out_detail')->where($map)->select();
        $bill_out['detail'] = $bill_out_detail;
        return $bill_out;
    }
    public function get_status($status = 0){
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