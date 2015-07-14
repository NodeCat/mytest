<?php
namespace Fms\Controller;
class TmsController extends \Common\Controller\AuthController{
    public function orders(){
        $id = I('id',0);
        if(!empty($id)) {
            $map['id'] = $id;
            $A = A('Common/Order','Logic');
            $dist = $A->distInfo($map);
            if(empty($dist)) {
                unset($map);
                $map['dist_number'] = substr($id, 2);
                $dist = $A->distInfo($map);
                if(empty($dist)) {
                    $this->msgReturn('0','查询失败，未找到该单据');
                }
            }
            
            unset($map);
            $map['dist_id'] = $dist['id'];
            $map['itemsPerPage'] = $dist['order_count'];
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $A = A('Common/Order','Logic');
            $orders = $A->order($map);
            foreach ($orders as &$val) {
                //`pay_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '支付方式：0货到付款（默认），1微信支付',
                //`pay_status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '支付状态：-1支付失败，0未支付，1已支付',
                switch ($val['pay_status']) {
                    case -1:
                        $s = '货到付款';
                        break;
                    case 0:
                        $s = '货到付款';
                        break;
                    case 1:
                        $s = '已付款';
                    default:
                        # code...
                        break;
                };
                $val['pay_status'] = $s;
                $val['geo'] = json_decode($val['geo'],TRUE);
                foreach ($val['detail'] as &$v) {
                    if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成') {
                        $val['actual_price'] += $v['actual_sum_price'];
                    }
                    elseif($val['status_cn']== '已退货') {
                        $v['actual_quantity']  = 0;
                        $v['actual_sum_price'] = 0;
                    }
                }
                if($val['actual_price'] > 0) {
                    $val['pay_for_price'] = $val['actual_price'] - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee'];
                }
                else {
                    $val['pay_for_price'] = 0 ;
                }
                
                if($val['pay_status']=='已付款'){
                    $val['pay_for_price'] = 0;
                    $val['deal_price'] = 0;

                }
                elseif($val['status_cn']== '已退货') {
                    $val['pay_for_price'] = 0;
                    $val['deal_price'] = 0;
                    $val['actual_price'] = 0;
                }
                elseif($val['status_cn'] == '已完成') {
                    $dist['pay_for_price_total'] += $val['deal_price'];
                }
                elseif($val['status_cn'] == '已签收') {
                    $dist['pay_for_price_total'] += $val['pay_for_price'];
                }
                
            }
            //dump($orders);
            $this->assign('dist', $dist);
            $this->assign('data', $orders);
        }
        $this->display('tms:orders');
    }
    public function pay() {
        $id = I('get.id',0);
        if(empty($id)) {
            $this->msgReturn('0','结算失败，提货码不能为空');
        }
        $map['dist_id'] = $id;
        $map['itemsPerPage'] = 'all';
        $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
        $A = A('Common/Order','Logic');
        $orders = $A->order($map);
        if(empty($orders)) {
            $this->msgReturn('0','结算失败，未找到该配送单中的订单。');
        }
        foreach ($orders as $val) {
            if($val['status_cn'] != '已签收' && $val['status_cn'] != '已退货' ) {
                if($val['status_cn'] == '已完成') {
                    $this->msgReturn('0','结算失败,该配送单已结算过');
                }
                $this->msgReturn('0','结算失败，该配送单含有未处理的订单');
            }
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
            $map['cur']['name'] = 'fms-'.session('user.username');
            $res = $A->set_status($map);
            unset($map);
        }
        $this->msgReturn('1','结算成功。','',U('Tms/orders',array('id'=>$id)));
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