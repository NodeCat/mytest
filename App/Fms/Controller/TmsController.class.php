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
                $this->msgReturn('0','提货失败，未找到该单据');
            }
            
            unset($map);
            $map['dist_id'] = $id;
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
                    if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                        $val['actual_price'] += $v['actual_sum_price'];
                    }
                    else {
                        //$val['quantity'] +=$v['quantity'];
                    }
                }

              	$val['pay_for_price'] = $val['actual_price'] - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee'] + $val['service_fee'];
                $dist['pay_for_price_total'] += $val['pay_for_price'];
            }
            //dump($orders);
            $this->assign('dist', $dist);
            $this->assign('data', $orders);
        }
        $this->display('tms:orders');
    }

    public function pay() {
    	$id = I('get.id',0);
        $map['dist_id'] = $id;
        $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
        $A = A('Common/Order','Logic');
        $orders = $A->order($map);
        foreach ($orders as $val) {
        	if($val['status_cn'] != '已签收') {
        		$this->msgReturn('0','结算失败，该配送单含有未处理的订单');
        	}
        }
        unset($map);
        $map['status']  = '6';//已结算
        foreach ($orders as $val) {
        	foreach ($val['detail'] as &$v) {
                if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                    $val['actual_price'] += $v['actual_sum_price'];
                }
            }

          	$val['pay_for_price'] = $val['actual_price'] - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee'] + $val['service_fee'];
            $map['deal_price'] += $val['pay_for_price'];

            $order_ids[] = $val['id'];
            $map['suborder_id'] = $val['id'];
            $res = $A->set_status($map);
        }
    }
    protected function msgReturn($res, $msg='', $data = '', $url=''){
        $msg = empty($msg)?($res > 0 ?'操作成功':'操作失败'):$msg;
        if(IS_AJAX){
            $this->ajaxReturn(array('status'=>$res,'msg'=>$msg,'data'=>$data,'url'=>$url));
        }
        else if($res){ 
                $this->success($msg,$url);
            }
            else{
                $this->error($msg,$url);
            }
        exit();
    }

}