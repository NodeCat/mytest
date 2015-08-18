<?php
namespace Fms\Controller;
class OrderController extends \Common\Controller\AuthController {

	public function index() 
    {
        $order_id = I('id',0);
        
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
            $bill_out['pay_status'] = $sign_data['pay_status'];
            $bill_out['pay_type']   = $sign_data['pay_type'];
            
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
            $bill_out['deposit']  = $sign_data['deposit'];
            $bill_out['sign_msg']     = $sign_data['sign_msg'];  
            
            if($bill_out['actual_price'] > 0) {
                //应收总计 ＝ 合计 － 优惠金额 － 支付减免 ＋ 运费 - 押金
                $bill_out['pay_for_price'] = $bill_out['actual_price'] - $bill_out['minus_amount'] - $bill_out['pay_reduce'] + $bill_out['deliver_fee'] - $bill_out['deposit'];
                //支付状态不等于已支付，支付方式不等于账期支付
                if (!($bill_out['pay_status'] == 1 || $bill_out['pay_type'] == 2)) {
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
            
            switch ($bill_out['status_cn']) {
                case '已拒收':
                    //应收总计
                    $bill_out['pay_for_price'] = 0;
                    //司机实收金额
                    $bill_out['deal_price'] = 0;
                    $bill_out['actual_price'] = 0;
                    //结算金额 ＝ 0
                    $bill_out['pay_for_price_total'] = 0;
                    break;

                case '已签收':
                case '已完成':
                    if($bill_out['pay_status'] == 1 || $bill_out['pay_type'] == 2){
                        //应收总计
                        //$bill_out['pay_for_price'] = 0;
                        //司机实收金额
                        $bill_out['deal_price'] = 0;
                        //结算金额 ＝ 0
                        $bill_out['pay_for_price_total'] = 0;
                    } else {
                        if ($bill_out['status_cn'] == '已签收') {
                            //结算金额
                            $bill_out['pay_for_price_total'] = $bill_out['pay_for_price'];
                        } else {
                            //结算金额
                            $bill_out['pay_for_price_total'] = $bill_out['deal_price'];
                        }
                    }
                    break;
                
                default:
                    //应收总计
                    $bill_out['pay_for_price'] = 0;
                    //司机实收金额
                    $bill_out['deal_price'] = 0;
                    //结算金额 ＝ 0
                    $bill_out['pay_for_price_total'] = 0;
                    break;
            }
            //抹零总计
            $bill_out['wipe_zero_sum'] = $wipe_zero_sum;
            $logs = getlogs('dist_detail',$bill_out_id);
            $this->assign('data',$bill_out);
            $this->assign('logs',$logs);
        }
    
        $this->display('tms:order_pay');
    }
    //重置订单状态
    public function reset()
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
        $fms_list = A('Fms/List','Logic');
        //查询订单是否有退货，并且已创建拒收入库单
        $is_can = $fms_list->can_replace($bill_out_id);
        if ($is_can == 2) {
            $this->error('此订单有退货且已经交货，不能重置订单状态。');exit;
        }
        $dist_detail_id = $dist_detail_id['id'];
        $data['status']     = 1; //重置为已装车状态
        $data['real_sum']   = 0; //实收金额置0
        $data['deposit']    = 0;
        $data['wipe_zero']  = 0;
        $data['minus_amount'] = 0;
        $data['pay_reduce'] = 0;
        $data['deliver_fee'] = 0;
        $data['sign_msg']   = '';
        $data['reject_reason'] = '';
        $res = M('stock_wave_distribution_detail')->where($map)->save($data);
        //写日志logs($id = 0, $msg = '', $model = '', $action = '', $module = ‘')
        logs($bill_out_id,'已装车','dist_detail');

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

        unset($map);
        $map['suborder_id'] = $order_id;
        $map['deposit']     = 0;
        $map['neglect_payment'] = 0;
        //重置押金和抹零
        $res3 = $A->setDeposit($map);
        if ($res) {
            $this->success('重置成功！',U('Order/index',array('id' => $order_id)),2);
        } else {
            $this->error('重置失败！');
        }
    }
    //修改订单实收金额，减去抹零和押金
    public function pay()
    {
        $order_id = I('post.order_id',0);
        $bill_id  = I('post.bill_id',0);
        $deposit  = I('post.deposit',0);
        $sign_msg      = I('post.sign_msg',0);
        $is_wipezero = I('post.wipezero',0);

        if ($deposit == 0 && $is_wipezero == 0) {
            $this->error('请选择抹零或者输入押金后，再修改。');
        }
        if (empty($deposit)) {
            $deposit = 0;
        }
        if ($order_id && $bill_id) {
            //实例化模型
            $model = M();
            //启动事务
            $model->startTrans();
            $map['bill_out_id'] = $bill_id;
            $map['is_deleted']  = 0;
            $dist_detail = M('stock_wave_distribution_detail')->where($map)->find();
            if (empty($dist_detail)) {
                $this->error('未找到该订单。');exit;
            }
            if ($dist_detail['status'] != 4) {
                $this->error('此订单不是已完成状态，不能修改订单实收金额。');exit;
            }
            $deal_price = $dist_detail['real_sum'];
            if ($is_wipezero) {
                $old_value  = $deal_price;
                $deal_price = floor($deal_price);
                $wipezero   = $old_value - $deal_price;
                if ($wipezero == 0 && $deposit == 0) {
                    $this->error('已经没有零钱，无需再抹零。');exit;
                }
            } else {
                $wipezero   = 0;
            }
            $wipe_zero_sum      = $dist_detail['wipe_zero'];
            $deposit_sum        = $dist_detail['deposit'];
            $data['real_sum']   = $deal_price - $deposit;
            $data['sign_msg']   = $sign_msg;
            $data['wipe_zero']   = $wipe_zero_sum + $wipezero;
            $data['deposit']    = $deposit_sum + $deposit;
            if ($data['real_sum'] < 0) {
                $this->error('修改失败，输入的押金超过了该订单的应收金额！');exit;
            }
            if ($data['real_sum'] > $dist_detail['receivable_sum']) {
                $this->error('修改失败，输入了非法的押金，导致该订单的实收金额超过了应收金额！');exit;
            }
            $res = $model->table('stock_wave_distribution_detail')->where($map)->save($data);
            logs($bill_id,'修改订单实收金额，抹零'.$wipezero.'押金'.$deposit,'dist_detail');
            
            $A = A('Common/Order','Logic');
            $dist_M = M('stock_wave_distribution');
            unset($map);
            unset($data);
            $map['id'] = $dist_detail['pid'];
            $map['is_deleted'] = 0;
            $dist_deal_price = $dist_M->where($map)->find();
            $dist_deal_price = $dist_deal_price['deal_price'];
            $data['deal_price'] = $dist_deal_price - $wipezero - $deposit;
            $cn = $model->table('stock_wave_distribution')->where($map)->save($data);
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
            if ($res && $cn) {
                //提交事务
                $model->commit();
                $this->success('修改成功！',U('Order/index',array('id' => $order_id)),2);  
            } else {
                //回滚事务
                $model->rollback();
                $this->error('修改失败！');
            } 
        }
    }

}