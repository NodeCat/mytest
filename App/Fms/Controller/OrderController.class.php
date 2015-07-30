<?php
namespace Fms\Controller;
class OrderController extends \Common\Controller\AuthController {

	public function index() 
    {
        if (IS_POST) {
            $order_id = I('post.id',0);
        } elseif (IS_GET) {
            $order_id = I('get.id',0);
        }
        
        if ($order_id) {
            $map['refer_code'] = $order_id;
            $map['is_deleted'] = 0;
            $m = M('stock_bill_out');
            $bill_out_id = $m->field('id')->where($map)->find();
            $bill_out_id = $bill_out_id['id'];
            if (!$bill_out_id) {
                $this->error('未找到该订单。');exit;
            }
            $list_logic = A('Fms/List','Logic');
            $bill_out = $list_logic->bill_out_Info($bill_out_id);
            //抹零总计
            $wipe_zero_sum = 0;
            $Dist_Logic = A('Tms/Dist','Logic');
            unset($map);
            $map['bill_out_id'] = $bill_out_id;
            $map['is_deleted'] = 0;
            $sign_data = M('stock_wave_distribution_detail')->where($map)->find();
            unset($map);
            $map['pid'] = $sign_data['id'];
            $map['is_deleted'] = 0;
            //找到出库单的签收的所有详情信息
            $sign_data['detail'] = M('tms_sign_in_detail')->where($map)->select();
            //获得订单状态
            $bill_out['status_cn'] = $list_logic->get_status($sign_data['status']);

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
            $bill_out['pay_status'] = $s;
            //保存出库单详情的数组
            $value_detail = array();
            //遍历出库单详情，并拼装数据
            foreach ($bill_out['detail'] as $val) {
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
                            $bill_out['actual_price'] += $val['actual_sum_price'];
                            
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
                    $bill_out['actual_price'] = 0;
                }
                $value_detail[] = $val;
            }
            $bill_out['detail'] = $value_detail;
            //优惠金额
            $bill_out['minus_amount'] = $sign_data['minus_amount'];
            //支付减免
            $bill_out['pay_reduce']   = $sign_data['pay_reduce'];
            //运费
            $bill_out['deliver_fee']  = $sign_data['deliver_fee'];
            //司机实收金额
            $bill_out['deal_price']   = $sign_data['real_sum'];
            //押金
            $bill_out['deposit_sum']  = $sign_data['deposit'];
            $bill_out['sign_msg']     = $sign_data['sign_msg'];  
            
            if($bill_out['actual_price'] > 0) {
                //应收总计 ＝ 合计 － 优惠金额 － 支付减免 ＋ 运费
                $bill_out['pay_for_price'] = $bill_out['actual_price'] - $bill_out['minus_amount'] - $bill_out['pay_reduce'] + $bill_out['deliver_fee'];
                if($bill_out['pay_status'] != '已付款'){
                    $old_value = $bill_out['pay_for_price'];
                    //抹零处理
                    $bill_out['pay_for_price'] = $Dist_Logic->wipeZero($bill_out['pay_for_price']);
                    //抹零总计
                    $wipe_zero_sum = $sign_data['wipe_zero'];
                }

            }
            else {
                //应收总计
                $bill_out['pay_for_price'] = 0 ;
            }
            
            if($bill_out['pay_status']=='已付款'){
                //应收总计
                $bill_out['pay_for_price'] = 0;
                //司机实收金额
                $bill_out['deal_price'] = 0;
                //结算金额 ＋＝ 0
                $bill_out['pay_for_price_total'] += 0;
            }
            elseif($bill_out['status_cn']== '已拒收') {
                //应收总计
                $bill_out['pay_for_price'] = 0;
                //司机实收金额
                $bill_out['deal_price'] = 0;
                $bill_out['actual_price'] = 0;
                //结算金额 ＋＝ 0
                $bill_out['pay_for_price_total'] += 0;
            }
            elseif($bill_out['status_cn'] == '已完成') {
                //结算金额 ＋＝ 应收总计
                $bill_out['pay_for_price_total'] = $bill_out['deal_price'];
            }
            elseif($bill_out['status_cn'] == '已签收') {
                //结算金额 ＋＝ 应收总计
                $bill_out['pay_for_price_total'] = $bill_out['pay_for_price'];
            }
            //抹零总计
            $bill_out['wipe_zero_sum'] = $wipe_zero_sum;
            $logs = getlogs('dist_detail',$sign_data['id']);
            $this->assign('data',$bill_out);
            $this->assign('logs',$logs);
        }
    
        $this->display('tms:order_pay');
    }
    //重置订单状态
    public function replace()
    {
        $order_id = I('get.id',0);
        $map['refer_code'] = $order_id;
        $map['is_deleted'] = 0;
        $m = M('stock_bill_out');
        $bill_out_id = $m->field('id')->where($map)->find();
        $bill_out_id = $bill_out_id['id'];
        if (!$bill_out_id) {
            $this->error('未找到该订单。');exit;
        }
        unset($map);
        $map['bill_out_id'] = $bill_out_id;
        $map['is_deleted']  = 0;
        $dist_detail_id = M('stock_wave_distribution_detail')->where($map)->find();
        if ($dist_detail_id['status'] != 2 && $dist_detail_id['status'] != 3) {
            $this->error('此订单不是已签收或已拒收状态，不能重置订单状态。');exit;
        }
        $dist_detail_id = $dist_detail_id['id'];
        $data['status']     = 1; //重置为已装车状态
        $data['real_sum']   = 0; //实收金额置0
        $data['sign_msg']   = '';
        $data['reject_reason'] = '';
        $res = M('stock_wave_distribution_detail')->where($map)->save($data);
        //写日志logs($id = 0, $msg = '', $model = '', $action = '', $module = ‘')
        logs($bill_out_id,'重置为已装车状态'.'[财务'.session('user.username').']','dist_detail');

        unset($map);
        $map['pid'] = $dist_detail_id;
        $map['is_deleted'] = 0;
        unset($data);
        $data['is_deleted'] = 1;
        //找到出库单的签收的所有详情信息
        $res1 = M('tms_sign_in_detail')->where($map)->save($data);

        $A = A('Common/Order','Logic');
        unset($map);
        $map['status']  = '8';//已装车
        $map['deal_price'] = 0;
        $map['suborder_id'] = $order_id;
        $map['cur']['name'] = '财务'.session('user.username');
        $res2 = $A->set_status($map);

        if ($res && $res1 && $res2) {
            $this->success('重置成功！',U('Order/index',array('id' => $order_id)),2);
        }
    }
    //修改订单实收金额，减去抹零和押金
    public function modify()
    {
        $order_id = I('post.order_id',0);
        $bill_id  = I('post.bill_id',0);
        $wipezero = I('post.wipezero',0);
        $deposit  = I('post.deposit',0);
        $wipe_zero_sum = I('post.wipe_zero_sum',0);
        $deposit_sum   = I('post.deposit_sum',0);
        $deal_price    = I('post.deal_price',0);
        $sign_msg      = I('post.sign_msg',0);
        
        if ($order_id && $bill_id) {
            $map['bill_out_id'] = $bill_id;
            $map['is_deleted']  = 0;
            $dist_detail = M('stock_wave_distribution_detail')->where($map)->find();
            if ($dist_detail['status'] != 4) {
                $this->error('此订单不是已完成状态，不能修改订单实收金额。');exit;
            }
            $data['real_sum']   = $deal_price - $wipezero - $deposit;
            $data['sign_msg']   = $sign_msg;
            $data['wipezero']   = $wipe_zero_sum + $wipezero;
            $data['deposit']    = $deposit_sum + $deposit;
            $res = M('stock_wave_distribution_detail')->where($map)->save($data);
            logs($bill_id,'修改订单实收金额，'.$sign_msg.'[财务'.session('user.username').']','dist_detail');
            if ($res) {
                $A = A('Common/Order','Logic');
                unset($map);
                $map['status']  = '1'; //已完成
                $map['deal_price'] = $deal_price - $wipezero - $deposit;
                $map['suborder_id'] = $order_id;
                $map['remark']      = $sign_msg;
                $map['cur']['name'] = '财务'.session('user.username');
                $res1 = $A->set_status($map);
                unset($map);
                $map['suborder_id'] = $order_id;
                $map['deposit']     = $deposit_sum + $deposit;
                $map['neglect_payment'] = $wipe_zero_sum + $wipezero;
                $res2 = $A->setDeposit($map);
                $this->success('修改成功！',U('Order/index',array('id' => $order_id)),2);  
            }
        }
    }

}