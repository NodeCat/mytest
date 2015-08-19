<?php
namespace Tms\Driver;

use Think\Controller;

class DistDriver extends Controller {

    //司机提货
    public function delivery() {
        $id = I('post.code',0);
        if (IS_GET) {
            //只显示当天的记录
            $map['mobile'] = session('user.mobile');
            $this->userid  = M('tms_user')->field('id')->where($map)->find();//传递出userid
            $map['status'] = '1';
            $start_date    = date('Y-m-d',NOW_TIME);
            $end_date      = date('Y-m-d',strtotime('+1 Days'));
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $delivery = M('tms_delivery')->where($map)->select();
            foreach ($delivery as &$val) {
                if ($val['type'] == '1') {
                   $status = M('tms_dispatch_task')->field('status')->find($val['dist_id']);
                   if ($status['status'] == '4'){
                        $val['s'] = '派送中';
                   } elseif ($status['status'] == '5') {
                        $val['s'] = '已完成';
                   }
                }
            }
            $this->data  = $delivery;
            $this->title = '提货扫码';
            $this->display('Driver/delivery');
            exit;
        } elseif (IS_POST && !empty($id)) {
            if (stripos($id,'D')===0) {
                $this->dist_id = strtoupper($id);
                $this->taskDelivery();
                exit;
            }
            $map['dist_id'] = $id;
            //$map['mobile'] = session('user.mobile');
            $map['status'] = '1';
            $start_date = date('Y-m-d',NOW_TIME);
            $end_date = date('Y-m-d',strtotime('+1 Days'));
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $map['type'] = '0';
            $M = M('tms_delivery');
            $delivery = $M->field('id,mobile,order_count')->where($map)->find();// 取出当前提货单信息
            unset($map['dist_id']);
            unset($map['type']);
            $sign = M('tms_sign_list')->field('delivery_time,created_time')->order('created_time DESC')->where(array('userid' => session('user.id')))->find();
            $map['mobile'] = session('user.mobile');
            $map['created_time'] = array('between',$sign['created_time'].','.$sign['delivery_time']);
            $delivery_all = $M->field('id,mobile,dist_id,order_count,type')->where($map)->order('created_time DESC')->select();//取出当前司机所有配送单信息
            unset($map);
            if (!empty($delivery)) {//若该配送单已被认领
                if($delivery['mobile'] == session('user.mobile')) {//如果认领的司机是同一个人
                    $this->error = '提货失败，该单据您已提货';
                }
                else {
                    //如果是另外一个司机认领的，则逻辑删除掉之前的认领纪录
                    $map['dist_id'] = $id;
                    $map['order'] = 'created_time DESC';
                    $bills = A('Tms/Dist', 'Logic')->billOut($map);
                    $orders = $bills['orders'];
                    unset($map);
                    foreach ($orders as $key => $value) {
                        if($value['order_info']['status_cn'] != "已装车") {
                            $status = '1';//只要一单不是以装车,就停止
                            break;
                        }
                        else {
                            $status = '2';
                        }
                    }
                    if ($status == '2') {
                        //如果别人提的还是已装车，那就还可以提
                        $map['id'] =$delivery['id'];
                        $data['status'] = '0';
                        $M->where($map)->save($data);
                    }
                    else {
                        // 如果别人提了，并且只要一单不是以装车，就不能提了
                        $this->error="该配送单已被他人提走并且在配送中,不能被认领";
                    }
                    unset($map);
                }
            }
            //查询该配送单的信息
            $wA = A('Wms/Distribution','Logic');
            $dist = $wA->distInfo($id);
            $yestoday = date('Y-m-d',strtotime('-1 Days'));
            if(!empty($this->error)) {

            } elseif (empty($dist)) {
                $this->error = '提货失败，未找到该单据';
            } elseif ($dist['status'] == '1') {
                // 未发运的单据不能被认领
                $this->error = '提货失败，未发运的配送单不能提货';
            } elseif ($dist['status'] == '3' || $dist['status'] == '4') {
                //已配送或已结算的配送单不能认领
                $this->error = '提货失败，完成配送或结算的配送单不能再次提货';
            } elseif (strtotime($dist['created_time']) < strtotime($yestoday) || strtotime($dist['created_time']) > strtotime($end_date)) {
                    $this->error = '提货失败，该配送单已过期';
            }
            //添加提货数据
            if (empty($this->error)) {
                $data['dist_id']      = $dist['id'];
                $data['dist_code']    = $dist['dist_code'];
                $data['mobile']       = session('user.mobile');
                $data['user_id']       = session('user.id');
                $data['order_count']  = $dist['order_count'];
                $data['sku_count']    = $dist['sku_count'];
                $data['line_count']   = $dist['line_count'];
                $data['total_price']  = $dist['total_price'];
                $data['site_src']     = $dist['company_id'];
                $data['created_time'] = get_time();
                $data['updated_time'] = get_time();
                $data['status']       = '1';
                //实例化Common下的OrderLogic
                $cA = A('Common/Order','Logic');
                if (!isset($orders) || empty($orders)) {
                    $map['dist_id'] = $dist['id'];
                    $map['order'] = 'created_time DESC';
                    $bills = A('Tms/Dist', 'Logic')->billOut($map);
                    $orders = $bills['orders'];
                    unset($map);
                }
                //遍历每个订单的取出路线id
                foreach ($orders as $v) {
                    $line_id[] = $v['line_id'];
                }
                $line_id = array_unique($line_id);//重复的去掉
                $lines = $cA->line(array('line_ids'=>$line_id));//取出所有路线
                // 把路线连接起来
                foreach ($lines as $key => $val) {
                    if ($key==0) {
                        $line_names = $val['name'];
                    } else {
                        $line_names .= '/' . $val['name'];
                    }
                }
                $data['line_name'] = $line_names;//写入devilery
                $citys = $cA->city();
                $data['city_id'] = $citys[$dist['city_id']];
                //添加一条记录到tms_delivery
                $res = $M->add($data);
                //判断是否已结款完成
                foreach ($delivery_all as $va) {
                    if($va['type'] == '0') {//提货
                        unset($map);
                        $map['dist_id'] = $va['dist_id'];
                        $map['order'] = 'created_time DESC';
                        $bill_outs = A('Tms/Dist', 'Logic')->billOut($map);
                        $ords = $bill_outs['orders'];
                        foreach ($ords as $v) {
                            if($v['order_info']['status_cn'] != "已完成") {
                                $status = '3';//只要有一个订单不是已完成，
                                break 2;
                            }
                            else {
                                $status = '4';// 已结款完成
                            }
                        }
                    } elseif ($va['type'] == '1') {//如果最新的是任务
                        $status = '4';
                        break;
                    }
                }
                unset($map);
                $map['status']  = '8';//已装车
                $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
                $map['driver_name'] = session('user.username');
                $map['driver_mobile'] = session('user.mobile');
                foreach ($orders as $val) {
                    $order_ids[] = $val['refer_code'];
                    $map['suborder_id'] = $val['refer_code'];
                    $res = $cA->set_status($map);
                }
                unset($map);
                $map['status']  = '1';
                $map['dist_id'] = $id;
                A('Wms/Distribution', 'Logic')->set_dist_detail_status($map);
                unset($map);
                if ($res) {
                    unset($map);
                    $map['pid']        = $id;
                    $map['is_deleted'] = 0;
                    $detail = M('stock_wave_distribution_detail')->where($map)->select();
                    $bill_out_ids = array_column($detail,'bill_out_id');
                    foreach ($bill_out_ids as $value) {
                        logs($value,'已装车'.'[司机]'.session('user.username').session('user.mobile'),'dist_detail');
                    }
                    $sres = A('Tms/SignIn', 'Logic')->sendDeliveryMsg($orders, $id);
                    $this->msg = "提货成功";
                    $M = M('TmsUser'); 
                    unset($map);                   
                    $map['mobile'] = session('user.mobile');
                    $user_data = $M->field('id')->where($map)->order('created_time DESC')->find(); 
                    unset($map);
                    $M = M('TmsSignList');
                    // 如果现有的配送单全部结款已完成，就再次签到，生成新的签到记录
                    if ($status=='4') {
                        $map['updated_time'] = $data['updated_time'];
                        $map['created_time'] = $data['created_time'];
                        $map['userid']       = $user_data['id'];
                        $M->add($map);
                        unset($map);
                        unset($status);
                    }
                    $map['created_time'] = array('between',$start_date.','.$end_date);
                    $map['userid']       =  $user_data['id'];
                    $sign_id = $M->field('id')->order('created_time DESC')->where($map)->find();//获取最新的签到记录
                    unset($map);
                    if ($dist['deliver_time']=='1') {
                        $map['period'] = '上午';
                    } elseif ($dist['deliver_time']=='2') {
                        $map['period'] = '下午';
                    }
                    $map['delivery_time'] = $data['created_time'];//加入提货时间
                    $map['id']            = $sign_id['id'];
                    $M->save($map); 
                    unset($map);
                }
                else {
                    $this->error = "提货失败";
                }
            }
        }else{

          $this->error = '提货失败,提货码不能为空';
        }
        if (empty($this->error)) {
            $map['mobile'] = session('user.mobile');
            $userid  = M('tms_user')->field('id')->where($map)->find();
            $res = array('status' =>'1', 'message' => '提货成功','code'=>$userid['id'],'tyep' => 0);
        } else {
            $msg = $this->error;
            $res = array('status' =>'0', 'message' =>$msg);
        }
        $this->ajaxReturn($res);     
    }

    //出库单列表
    public function orders() {
        $id = I('get.id',0);
        if(!empty($id)) {
            $oid = I('get.oid/d',0);
            $M = M('tms_delivery');
            $res = $M->find($id);
            if(empty($res)) {
                $this->error = '未找到该提货纪录。';
            }
            elseif($res['mobile'] != session('user.mobile')) {
                $this->error ='不能查看该配送单，您的手机号码与提货人不符合。';
            }
            if(!empty($this->error)) {
                $this->title = "客户签收";
                $this->display('Driver/sorders');
                exit();
            }
            $this->dist = $res;
            //查询出库单列表
            $map['dist_id'] = $res['dist_id'];
            $map['order'] = 'created_time DESC';
            $A = A('Tms/Dist','Logic');
            $bills = $A->billOut($map);
            if($bills) {
                $orders = $bills['orders'];
                $this->orderCount = $bills['orderCount'];
                foreach ($orders as &$val) {
                    //押金
                    $val['deposit']    = $val['order_info']['deposit'];
                    //获取支付状态的中文
                    $s = $A->getPayStatusByCode($val['order_info']['pay_status']);
                    $val['pay_status'] = $s;
                    $val['pay_type']   = $val['order_info']['pay_type'];
                    $val['order_info']['pay_status'] = $s;
                    //从订单获取字段到出库单
                    $val['shop_name']       = $val['order_info']['shop_name'];
                    $val['mobile']          = $val['order_info']['mobile'];
                    $val['remarks']         = $val['order_info']['remarks'];
                    // $val['status_cn']    = '已装车';
                    $val['status_cn']       = $val['order_info']['status_cn'];
                    $val['total_price']     = $val['order_info']['total_price'];
                    $val['minus_amount']    = $val['order_info']['minus_amount'];
                    $val['pay_reduce']      = $val['order_info']['pay_reduce'];
                    $val['deliver_fee']     = $val['order_info']['deliver_fee'];
                    $val['final_price']     = $val['order_info']['final_price'];
                    $val['receivable_sum']  = $val['order_info']['final_price'];
                    if ($val['pay_status'] == '已付款' || $val['pay_type'] == 2) {
                        $val['real_sum']   = $val['order_info']['final_price'];
                    } else {
                       $val['real_sum']    = $A->wipeZero($val['order_info']['final_price']);
                    }
                    $val['sign_msg']        = $val['order_info']['sign_msg'];
                    $val['user_id']         = $val['order_info']['user_id'];
                    //收获地址坐标
                    $val['geo'] = json_decode($val['order_info']['geo'],TRUE);
                    $sign_in = $M->table('stock_wave_distribution_detail')
                        ->where(array('bill_out_id' => $val['id']))
                        ->find();
                    foreach ($val['detail'] as &$v) {
                        if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                            //该出库单详情对应的签收数据
                            $dmap['bill_out_detail_id'] = $v['id'];
                            $dmap['is_deleted'] = 0;
                            $sign_in_detail = $M->table('tms_sign_in_detail')->where($dmap)->find();
                            unset($dmap);
                            $val['receivable_sum'] = $sign_in['receivable_sum'];
                            $val['real_sum'] = $sign_in['real_sum'];
                            $v['quantity']  = $sign_in_detail['real_sign_qty'];
                            $v['sum_price'] = $sign_in_detail['real_sign_qty'] * $sign_in_detail['price_unit'];
                            if($sign_in_detail['measure_unit'] !== $sign_in_detail['charge_unit']) {
                                $v['weight'] = $sign_in_detail['real_sign_wgt'];
                                $v['sum_price']     = $sign_in_detail['real_sign_wgt'] * $sign_in_detail['price_unit'];
                            }
                        }
                        else {
                            $v['quantity'] = $v['delivery_qty'];
                        }
                        //从订单详情获取SKU信息
                        foreach ($val['order_info']['detail'] as &$value) {
                            if($v['pro_code'] == $value['sku_number']) {
                                $v['single_price']    = $value['single_price'];
                                $v['close_unit']      = $value['close_unit'];
                                $v['unit_id']         = $value['unit_id'];
                                $v['sum_price']       = $v['sum_price'] ? $v['sum_price'] : $value['sum_price'];
                                $v['order_detail_id'] = $value['id'];//获取订单详情ID，用于更新订单状态
                                $value['delivery_quantity'] = $v['delivery_qty'];
                            }
                        }
                    }
                    //获取打印小票要用的数据
                    $val['printStr'] = A('Tms/PrintBill', 'Logic')->printBill($val['order_info']);
                    $lists[$val['user_id']][] = $val;
                }
                $this->dist_id = $res['dist_id'];
                $this->data = $lists;
            } else {
                $this->error ='没有该配送单数据';
            }
            //提货单ID和订单ID，用于签收后自动展开
            $this->id   = $id;
            $this->oid  = $oid;
        }
        $this->title = "客户签收";
        //电子签名保存接口
        $this->signature_url = C('TMS_API_PATH') . '/SignIn/signature';
        $this->display('Driver/sorders');
    }

    //司机签收
    public function sign() {
        //实收数量或重量
        $quantity    = I('post.quantity');
        $weight      = I('post.weight', 0);
        $flagQty = array_sum($quantity);
        $flagWgt = empty($weight) ? 0 : array_sum($weight);
        //押金
        $deposit = I('post.deposit',0);
        
        if (ceil($flagQty) == 0 && ceil($flagWgt) == 0) {
            $re = array(
                'status' => -1,
                'msg'    => '签收数量不能全部为空'
            );
            $this->ajaxReturn($re);           
        }
        $bill_out_id = I('post.bid/d', 0);
        if (!$bill_out_id) {
            $res = array(
                'status' => -1,
                'msg'    => '出库单ID参数错误'
            );
            $this->ajaxReturn($res);
        }
        $wA = A('Wms/Distribution', 'Logic');
        $map['bill_out_id'] = $bill_out_id;
        $map['is_deleted'] = 0;
        $dist_details = $wA->getDistDetails($map);
        unset($map);
        //该出库单对应配送单详情
        $dist_detail = $dist_details['list'][0];
        if(!$dist_detail) {
            $res = array(
                'status' => -1,
                'msg'  => '没有对应的配送单详情'
            );
            $this->ajaxReturn($res);
        }
        //配送单ID
        $dist_id = $dist_detail['pid'];
        //订单信息
        $refer_code  = I('post.id/d',0);
        $cA = A('Common/Order', 'Logic');
        $orderInfo = $cA->getOrderInfoByOrderId($refer_code);
        //出库单详情
        $bill_details = $wA->get_out_detail(array($bill_out_id));
        if (empty($bill_details)) {
            $res = array(
                'status' => -1,
                'msg'  => '没有出库单详情'
            );
            $this->ajaxReturn($res);
        }
        $receivable_sum = 0;
        //出库单详情关联订单详情,计算应收总额
        $bill_id_details = array();
        $price_unit = array();
        foreach ($bill_details as $val) {
            foreach ($orderInfo['info']['detail'] as $v) {
                if ($val['pro_code'] == $v['sku_number']) {
                    $val['order_detail'] = $v;
                }
            }
            //出库单详情ID对应单价
            $price_unit[$val['id']] = $val['order_detail']['single_price'];
            //出库单详情ID对应详情数据
            $bill_id_details[$val['id']] = $val;
            //应收金额
            $unit_num = isset($weight[$val['id']]) ? $weight[$val['id']] : $quantity[$val['id']];
            $receivable_sum += $val['order_detail']['single_price'] * $unit_num;
        }
        //实收抹零
        $A = A('Tms/Dist','Logic');
        $receivable_sum -= $orderInfo['info']['minus_amount'];
        $receivable_sum += $orderInfo['info']['deliver_fee'];
        //应收减去押金
        $receivable_sum = $receivable_sum - $deposit;
        if ($receivable_sum < 0) {
            $res = array(
                'status' => -1,
                'msg'  => '输入的押金不能大于应收金额，请重新输入'
            );
            $this->ajaxReturn($res);
        }
        //付款状态为已付款和账期支付的不进行抹零处理
        if (!($orderInfo['info']['pay_status'] == 1 || $orderInfo['info']['pay_type'] == 2)) {
            $deal_price = $A->wipeZero($receivable_sum);
            $wipe_zero  = round($receivable_sum - $deal_price,2);
        } else {
            $deal_price = $receivable_sum;
        }
        $sign_msg = I('post.sign_msg', '' ,'trim');
        //签收表主表数据
        $fdata = array(
            'receivable_sum' => $receivable_sum,
            'real_sum'       => $deal_price,
            'minus_amount'   => $orderInfo['info']['minus_amount'],
            'pay_reduce'     => $orderInfo['info']['pay_reduce'],
            'deliver_fee'    => $orderInfo['info']['deliver_fee'],
            'pay_status'     => $orderInfo['info']['pay_status'],
            'pay_type'       => $orderInfo['info']['pay_type'],
            'wipe_zero'      => isset($wipe_zero) ? $wipe_zero : 0,
            'deposit'        => $deposit,
            'sign_msg'       => $sign_msg,
            'status'         => 2,//签收
            'delivery_ontime' => $A->isSignOntime($dist_id),
            'sign_time'      => get_time(),
            'sign_driver'    => session('user.mobile'),
        );
        //更新订单状态
        $re = $this->set_order_status($refer_code, $deal_price, $quantity, $weight, $price_unit, $sign_msg);
        if($re['status'] === 0) {
            unset($map);
            $map['suborder_id'] = $refer_code;
            $map['deposit']     = $deposit;
            $map['neglect_payment'] = $wipe_zero;
            //更新订单的抹零和押金
            $res2 = $cA->setDeposit($map);
            //保存签收数据到配送单详情
            $datas = array(
                'id'   => $dist_detail['id'],
                'data' => $fdata,
            );
            $s = $wA->saveSignDataToDistDetail($datas);
            //配送单详情的状态为2:已签收或者更成功
            if($dist_detail['status'] == 2 || $s) {
                logs($bill_out_id,'已签收'.'[司机]'.session('user.username').session('user.mobile'),'dist_detail');
                $cdata = array();
                //组合一个签收详情数据
                foreach ($bill_id_details as $detail_id => $detail) {
                    $net_weight = empty($detail['order_detail']['net_weight']) ? 0 : $detail['order_detail']['net_weight'];
                    $tmp['pid']                = $dist_detail['id'];
                    $tmp['bill_out_detail_id'] = $detail_id;
                    $tmp['delivery_qty']       = $detail['delivery_qty'];
                    $tmp['delivery_wgt']       = $detail['delivery_qty'] * $net_weight;
                    $tmp['real_sign_qty']      = $quantity[$detail_id];
                    $tmp['real_sign_wgt']      = $tmp['real_sign_qty'] * $net_weight;
                    $tmp['reject_qty']         = $tmp['delivery_qty'] - $tmp['real_sign_qty'];
                    $tmp['reject_wgt']         = $tmp['delivery_wgt'] - $tmp['real_sign_wgt'];
                    $tmp['measure_unit']       = $detail['order_detail']['unit_id'];
                    $tmp['charge_unit']        = $detail['order_detail']['close_unit'];
                    $tmp['price_unit']         = $detail['order_detail']['single_price'];
                    $tmp['sign_sum']           = $tmp['real_sign_qty'] * $tmp['price_unit'];
                    $tmp['delivery_sum']       = $tmp['delivery_qty'] * $tmp['price_unit'];
                    if (isset($weight[$detail_id])) {
                        $tmp['real_sign_wgt'] = $weight[$detail_id];
                        $tmp['sign_sum']      = $tmp['real_sign_wgt'] * $tmp['price_unit'];
                        $tmp['delivery_sum']  = $tmp['delivery_wgt'] * $tmp['price_unit'];
                    }
                    $tmp['reject_sum']         = $tmp['delivery_sum'] - $tmp['sign_sum'];
                    $tmp['created_time']       = get_time();
                    $tmp['updated_time']       = get_time();
                    $cdata[] = $tmp;
                    unset($tmp);
                }
                $bill_out_detail_ids = array_keys($bill_id_details);
                $bdmap['bill_out_detail_id'] = array('in', $bill_out_detail_ids);
                $sdM = M('tms_sign_in_detail');
                $sdM->where($bdmap)->save(array('is_deleted' => 1));
                //添加签收详情数据
                $sdM->addAll($cdata);
                //更新配送单详情－>配送单状态
                unset($map);
                $map['dist_id'] = $dist_id;
                $map['status']  = 2;
                $s = $wA->set_dist_status($map);
                $status = $s['status'];
                $msg = ($status === -1) ? '签收成功,配送单状态更新失败' : '签收成功';
            }
            //给母账户发送短信
            $sres = A('Tms/SignIn', 'Logic')->sendParentAccountMsg($orderInfo['info']);
            $json = array('status' => $status, 'msg' => $msg);
            //status:－1(更新失败或未执行更新);0(更新成功);
            $this->ajaxReturn($json);
        }
        $this->ajaxReturn($re);
    }

    //司机签收后订单回调
    protected function set_order_status($refer_code, $deal_price, $quantity, $weight, $price_unit, $sign_msg) {
        $map['suborder_id'] = $refer_code;
        $map['status']   = '6';
        $map['deal_price'] = $deal_price;
        $map['sign_msg'] = $sign_msg;
        $detail_ids = I('post.order_detail_id');
        foreach ($detail_ids as $key => $val) {
            if(intval($val) > 0) {
                $row['id']= $val;
                $row['actual_price'] = $price_unit[$key];
                $row['actual_quantity'] = isset($weight[$key]) ? $weight[$key]: $quantity[$key];
                $row['actual_sum_price'] = $row['actual_price'] * $row['actual_quantity'];
                $map['order_details'][] = $row;
            }
        }
        $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
        $cA = A('Common/Order','Logic');
        $res = $cA->set_status($map);
        return  $res;
    }

    //客户退货
    public function reject() {
        $map['suborder_id'] = I('post.id/d',0);
        $map['status'] = '7';
        $map['sign_msg'] = I('post.sign_msg');
        $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
        //押金
        $deposit = I('post.deposit',0);
        if ($deposit > 0) {
            $res = array(
                'status' => -1,
                'msg'  => '整单拒收不能退押金！'
            );
            $this->ajaxReturn($res);
        }
        $cA = A('Common/Order','Logic');
        $res = $cA->set_status($map);
        if($res['status'] === 0) {
            $orderInfo = $cA->getOrderInfoByOrderId($map['suborder_id']);
            $sA = A('Tms/SignIn', 'Logic');
            $reject_codes = I('post.reject_reason');
            $reasons = $sA->getReasonByCode($reject_codes);
            unset($map);
            //该出库单对应配送单详情
            $bill_out_id = I('post.bid/d');
            $wA = A('Wms/Distribution', 'Logic');
            $map['bill_out_id'] = $bill_out_id;
            $map['is_deleted'] = 0;
            //该出库单对应配送单详情
            $dist_details = $wA->getDistDetails($map);
            unset($map);
            if(!$dist_details) {
                $res = array(
                    'status' => -1,
                    'msg'  => '没有对应的配送单详情'
                );
                $this->ajaxReturn($res);
            }
            $dist_detail = $dist_details['list'][0];
            $dist_id = $dist_detail['pid'];
            //签收表主表数据
            $fdata = array(
                'sign_msg'        => I('post.sign_msg', '' ,'trim'),
                'reject_reason'   => $reasons,
                'status'          => 3,//拒收
                'pay_status'      => $orderInfo['info']['pay_status'],
                'pay_type'        => $orderInfo['info']['pay_type'],
                'delivery_ontime' => A('Tms/Dist' ,'Logic')->isSignOntime($dist_id),
                'sign_time'       => get_time(),
                'sign_driver'     => session('user.mobile'),
            );
            //向配送单详情更新拒收信息
            $datas = array(
                'id'   => $dist_detail['id'],
                'data' => $fdata,
            );
            $s = $wA->saveSignDataToDistDetail($datas);
            if($dist_detail['status'] == 3 || $s) {
                logs($bill_out_id,'已拒收'.'[司机]'.session('user.username').session('user.mobile'),'dist_detail');
                //出库单详情
                $bill_details = $wA->get_out_detail(array($bill_out_id));
                //出库单详情关联订单详情
                $bill_id_details = array();
                foreach ($bill_details as $val) {
                    foreach ($orderInfo['info']['detail'] as $v) {
                        if ($val['pro_code'] == $v['sku_number']) {
                            $val['order_detail'] = $v;
                        }
                    }
                    $bill_id_details[$val['id']] = $val;
                }
                $cdata = array();
                //组合一个拒收详情数据
                foreach ($bill_id_details as $detail_id => $detail) {
                    $net_weight = empty($detail['order_detail']['net_weight']) ? 0 : $detail['order_detail']['net_weight'];
                    $tmp['pid']                = $dist_detail['id'];
                    $tmp['bill_out_detail_id'] = $detail_id;
                    $tmp['delivery_qty']       = $detail['delivery_qty'];
                    $tmp['delivery_wgt']       = $detail['delivery_qty'] * $net_weight;
                    $tmp['reject_qty']         = $tmp['delivery_qty'];
                    $tmp['reject_wgt']         = $tmp['delivery_wgt'];
                    $tmp['measure_unit']       = $detail['order_detail']['unit_id'];
                    $tmp['charge_unit']        = $detail['order_detail']['close_unit'];
                    $tmp['price_unit']         = $detail['order_detail']['single_price'];
                    $tmp['delivery_sum']       = $tmp['delivery_qty'] * $tmp['price_unit'];
                    $tmp['reject_sum']         = $tmp['delivery_sum'];
                    $tmp['created_time']       = get_time();
                    $tmp['updated_time']       = get_time();
                    $cdata[] = $tmp;
                    unset($tmp);
                }
                $bill_out_detail_ids = array_keys($bill_id_details);
                $bdmap['bill_out_detail_id'] = array('in', $bill_out_detail_ids);
                $sdM = M('tms_sign_in_detail');
                $sdM->where($bdmap)->save(array('is_deleted' => 1));
                //添加签收详情数据
                $sdM->addAll($cdata);
                //更新配送单详情－>配送单状态
                $map['dist_id'] = $dist_id;
                $map['status']  = '3';
                $s = $wA->set_dist_status($map);
                $status = $s['status'];
                $msg = ($status === -1) ? '更新成功,配送单状态更新失败' : '更新成功';
                $res = array(
                    'status' => 0,
                    'msg'    => $msg,
                );
            }
            //发送短信
            if ($reasons) {
                $sres = $sA->sendRejectMsg($orderInfo['info'], $reasons);
            }
        }
        $this->ajaxReturn($res);
    }

    //司机当日收货统计
    public function report() {

        $map['mobile'] = session('user.mobile');
        $map['status'] = '1';
        $map['type']   = '0';
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $this->userid = session('user.id');
        $this->data = M('tms_delivery')->where($map)->select();
        $this->title = '今日订单总汇';
        $this->display('Driver/report');
    }

    // 车单纬度统计
    public function orderList() {

        $id = I('get.id',0);
        if(empty($id)){
            $this->error = '未找到该提货纪录。';
        }
        $M = M('tms_delivery');
        $res = $M->find($id);
            
        if(empty($res)) {
            $this->error = '未找到该提货纪录。';
        } elseif ($res['mobile'] != session('user.mobile')) {
            $this->error ='不能查看该配送单，您的手机号码与提货人不符合。';
        }
        if(!empty($this->error)) {
            $this->title = "客户签收";
            $this->display('Driver/orders');
            exit();
        }

        $all_orders     = 0;  //总订单统计
        $sign_orders    = 0;  //签收单统计
        $unsign_orders  = 0;  //拒收单统计
        $delivering     = 0;  //派送中订单数统计
        $sign_finished  = 0;  //已完成订单数统计
        $sum_deal_price  = 0.0;   //回款数
        $arrays=array();    //回仓列表的数组
        
        $dist_logic = A('Tms/Dist','Logic');
        //获得出库单列表
        unset($map); 
        $map['dist_id'] = $res['dist_id'];
        $result = A('Wms/StockOut', 'Logic')->bill_out_list($map);
        if($result['status'] === 0) {
            $bill_outs = $result['list'];
        } else {
            $this->error('没有找到该配送单');exit;
        }
        
        //若查出的签收信息非空
        if (!empty($bill_outs)) { 
            //总订单数
            $all_orders = count($bill_outs);
            foreach ($bill_outs as $bill_out) { 
                unset($map);
                $map['bill_out_id'] = $bill_out['id'];
                $map['is_deleted']  = 0;
                $sign_data = M('stock_wave_distribution_detail')->where($map)->find();
                switch ($bill_out['sign_status']) {
                    case '2':
                        $sign_orders++; //已签收订单数加1
                        foreach ($bill_out['detail'] as $value) {
                            unset($map);
                            $map['bill_out_detail_id'] = $value['id'];
                            $map['is_deleted'] = 0;
                            $sign_in_detail = M('tms_sign_in_detail')->where($map)->find();
                            $sign_qty = $sign_in_detail['real_sign_qty']; //签收数量
                            $unit = $sign_in_detail['measure_unit']; //计量单位
                            $delivery_qty = $value['delivery_qty']; //配送数量
                            $quantity = $delivery_qty - $sign_qty; //回仓数量
                            if($quantity > 0){
                                $key  = $value['pro_code'];    //sku号
                                $arrays[$key]['quantity'] +=  $quantity; //回仓数量
                                $arrays[$key]['name'] =  $value['pro_name'];   //sku名称
                                $arrays[$key]['unit_id'] = $unit;   //单位
                            }
                            $bill_out['actual_price'] += $sign_qty * $sign_in_detail['price_unit'];
                        }
                        break;

                    case '3':
                        $unsign_orders++;   //已拒收订单数加1
                        foreach ($bill_out['detail'] as $val) {
                            $key  = $val['pro_code'];    //sku号
                            $arrays[$key]['quantity'] +=  $val['delivery_qty']; //回仓数量
                            $arrays[$key]['name'] =  $val['pro_name'];   //sku名称
                            $arrays[$key]['unit_id'] = $val['measure_unit'];   //单位    
                        } 
                        break;

                    case '4':
                        $sign_finished++;   //已完成订单数加1
                        break;
                    
                    default:
                        # code...
                        break;
                }
                if ($bill_out['actual_price'] > 0) {
                    //应收总计 ＝ 合计 － 优惠金额 － 支付减免 ＋ 运费 - 押金
                    $bill_out['pay_for_price'] = $bill_out['actual_price'] - $sign_data['minus_amount'] - $sign_data['pay_reduce'] + $sign_data['deliver_fee'] - $sign_data['deposit'];
                    //不是微信支付，并且不是账期支付的抹零
                    if (!($sign_data['pay_status'] == 1 || $sign_data['pay_type'] == 2)) {
                        $bill_out['pay_for_price'] =  $dist_logic->wipeZero($bill_out['pay_for_price']);
                    } else {
                        //付款状态是已付款和帐期支付的不计算回款数
                        $bill_out['pay_for_price'] = 0;
                    } 
                } else {
                    $bill_out['pay_for_price'] = 0;
                }
                //回款数
                $sum_deal_price += $bill_out['pay_for_price']; 
            }  
        }

        $list['dist_id'] = $res['dist_id'];
        $list['sum_deal_price']  = $sum_deal_price;//回款数
        $list['sign_orders'] = $sign_orders;//已签收
        $list['unsign_orders'] = $unsign_orders;//未签收
        $list['sign_finished']  = $sign_finished;  // 已完成
        $list['delivering'] = $all_orders - $sign_orders - $unsign_orders - $sign_finished;//派送中
        $L = A('Fms/List','Logic');
        $status = $L->can_pay($res['dist_id']);
        $this->status = $status;
        $this->list = $list;
        $this->back_lists = $arrays;
        $this->title =$res['dist_code'].'车单详情';
        $this->display('Driver/orderlist');
    }

    //统一的返回方法
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

    //任务提货
    private function taskDelivery()
    {   
        $id = $this->dist_id;
        $map['dist_code'] = $id;
        $map['status']  = '1';
        $start_date     = date('Y-m-d',NOW_TIME);
        $end_date       = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $dist = M('tms_delivery')->field('id,mobile,dist_id,user_id')->where($map)->find();// 取出当前提货单信息
        unset($map['dist_code']);
        $sign = M('tms_sign_list')->field('delivery_time,created_time')->order('created_time DESC')->where(array('userid' => session('user.id')))->find();
        $map['mobile'] = session('user.mobile');
        $map['created_time'] = array('between',$sign['created_time'].','.$sign['delivery_time']);
        $dist_all = M('tms_delivery')->field('id,mobile,dist_id,order_count,type')->where($map)->order('created_time DESC')->select();//取出当前司机所有配送单信息
        unset($map);
        if (!empty($dist)) {//若该配送单已被认领
            if ($dist['mobile'] == session('user.mobile')) {//如果认领的司机是同一个人
                $this->error = '领单失败,该单据您已提过';
            } else {//如果是另外一个司机认领的，则逻辑删除掉之前的认领纪录
                $nodes = M('tms_task_node')->field('status')->where(array('pid' => $dist['dist_id']))->select();
                foreach ($nodes as $value) {
                    if($value['status'] != '1') {
                        $status = '1';//不是派遣中,就停止
                        break;
                    } else {
                        $status = '2';
                    }
                }
                if ($status == '2') {//如果别人提的还是派遣中，那就还可以提
                    M('tms_delivery')->save(array('id' => $dist['id'],'status'=>'0'));
                } else {// 如果别人提了，并且只要一单不是已领单，就不能提了
                    $this->error = '该配送单已被他人提走并且在配送中,不能被认领';
                }
                unset($status);
            }
        }
        $task = M('tms_dispatch_task')->where(array('code' => $id))->find();
        $ctime = strtotime($task['created_time']);
        $start_date1 = date('Y-m-d',strtotime('-1 Days'));
        $end_date1 = date('Y-m-d',strtotime('+1 Days'));
        if (empty($task)) {
            $this->error = '领单失败，未找到该单据';
        } elseif ($task['status'] != '3' && $task['status'] != '4') {//该单据不是配送中或待派车就不能认领
            $this->error = '领单失败,该单不能被认领';
        } elseif ($ctime < strtotime($start_date1) || $ctime > strtotime($end_date1)) {
            $this->error = '领单失败，该任务单已过期';
        }
        if (empty($this->error)) {
            $data['dist_id']      = $task['id'];
            $data['dist_code']    = $task['code'];
            $data['mobile']       = session('user.mobile');
            $data['user_id']      = session('user.id');
            $data['total_price']  = $task['task_fee'];
            $data['created_time'] = get_time();
            $data['updated_time'] = get_time();
            $data['status']       = '1';
            $data['line_name']    = $task['task_name'];
            $data['type']         = '1';
            $res = M('tms_delivery')->add($data);
            if ($res) {
                foreach ($dist_all as $va) {
                    if($va['type'] == '0') {//如果最新的是提货
                        $status = '4';
                        break;
                    } elseif ($va['type'] == '1') {
                        $tasks = M('tms_dispatch_task')->field('id')->where(array('status' => array('neq','5'),'id' => $va['dist_id']))->find();
                        if ($tasks) {//如果任务还没完成
                            $status = '3';
                            break;
                        } else {
                            $status = '4';
                        }

                    }
                }
                $user = M('tms_user')->field('id,car_type,car_from')->where(array('mobile' => $data['mobile']))->find();
                M('tms_dispatch_task')->save(array('id' => $task['id'],'status' => '4','driver_id' => $user['id'],'car_type' => $user['car_type'],'platform' => $user['car_from']));
                M('tms_task_node')->where(array('pid' => $task['id']))->save(array('status' =>'1'));
                // 如果现有的配送单全部结款已完成，就再次签到，生成新的签到记录
                if ($status=='4') {
                    $map['updated_time'] = $data['updated_time'];
                    $map['created_time'] = $data['created_time'];
                    $map['userid']       = $user['id'];
                    M('tms_sign_list')->add($map);
                    unset($map);
                    unset($status);
                }
                $map['is_deleted']   = '0';
                $map['created_time'] = array('between',$start_date.','.$end_date);
                $map['userid']       =  $user['id'];
                $sign_id = M('TmsSignList')->field('id')->order('created_time DESC')->where($map)->find();//获取最新的签到记录
                unset($map);
                if ($task['deliver_time']=='1') {
                    $map['period'] = '上午';
                } elseif ($task['deliver_time']=='2') {
                    $map['period'] = '下午';
                }
                $map['delivery_time'] = $data['created_time'];//加入提货时间
                $map['id']            = $sign_id['id'];
                M('TmsSignList')->save($map); 
                unset($map);
                $this->msg = "提货成功"; 
            } else {
                $this->error = "提货失败";
            }
        }
        if (empty($this->error)) {
            unset($map);
            $map['mobile'] = session('user.mobile');
            $userid  = M('tms_user')->field('id')->where($map)->find();
            $res = array('status' =>'1', 'message' => '提货成功','code' => $userid['id']);
        } else {
            $msg = $this->error;
            $res = array('status' =>'0', 'message' =>$msg);
        }
        $this->ajaxReturn($res);
    }

    //配送任务列表
    public function taskOrders()
    {
        $id = I('get.id',0);
        if(!empty($id)) {
            $res = M('tms_delivery')->find($id);
            if(empty($res)) {
                $this->error = '未找到该提货纪录。';
            }
            elseif($res['mobile'] != session('user.mobile')) {
                $this->error ='不能查看该任务单，您的手机号码与领单人不符合';
            }
            if(!empty($this->error)) {
                $this->title = "客户签收";
                $this->display('tms:delivery');
                exit();
            }
            $this->dist = $res;
            $taskList = M('tms_task_node')
                ->where(array('pid' => $res['dist_id']))
                ->order(array('created_time' => 'ASC'))
                ->select();
            $this->taskCount = count($taskList);
            foreach ($taskList as &$val) {
                $val['geo'] = json_decode($val['geo'],TRUE);
                switch ($val['status']) {
                    case '1':
                        $val['status'] = '派遣中';
                        break;
                    case '2':
                        $val['status'] = '已签到';
                        $this->signed = 1;
                        break;
                    case '3':
                        $val['status'] = '已完成';
                        $this->signed = 2;
                        $this->over = 1;
                        break;
                }
            }
            $this->data = $taskList;
        }
        $this->title = "任务签到";
        $this->display('Driver/taskorders');
    }

    //任务签到
    public function taskSign()
    {
        $id    = I('post.id');
        $queue = I('post.queue');
        $pid   = I('post.pid');
        $status = M('tms_task_node')->field('status')->find($id);
        //如果状态不是任务开始
        if ($status['status'] != '1') {
            $return = array(
                'status' => 0,
                'msg'    => '签到失败下',
            );
            $this->ajaxReturn($return);
            exit;
        }
        $result= M('tms_task_node')->field('id')->where(array('pid' => $pid,'queue'=>array('lt',$queue),'status' => '1'))->find();
        if (!$result) {
            $time = date('Y-m-d H:i:s',NOW_TIME);
            $res = M('tms_task_node')->save(array('id' => $id,'status' => '2','sign_time' => $time));
            if ($res) {
                $return = array(
                    'status' => 1,
                    'msg'    => '签到成功',
                );
            } else {
                $return = array(
                    'status' => 0,
                    'msg'    => '签到失败',
                );
            }
        } else {
            $return = array(
                'status' => 0,
                'msg'    => '请按签到顺序签到',
            );
        }

        $this->ajaxReturn($return);
    }


    //任务结束
    public function signFinished()
    {
        $dist_id = I('post.id');
        $nodes = M('tms_task_node')->field('status')->where(array('pid' => $dist_id))->select();
        foreach ($nodes as $value) {
            if($value['status'] != '2') {
                $status = '1';//不是已签收,就停止
                break;
            } else {
                $status = '2';
            }
        }
        //如果全部签收
        if ($status == '2') {        
            $res = M('tms_dispatch_task')->save(array('id' => $dist_id,'status' => '5'));
            if ($res) {
                $result = M('tms_task_node')->where(array('pid' => $dist_id))->save(array('status' => '3'));
            }
        }
        if ($result) {
            $return = array(
                'status' => 1,
                'msg'    => '任务完成',
            );
        } else {
            $return = array(
                'status' => 0,
                'msg'    => '任务结束失败',
            );
        }
        $this->ajaxReturn($return);
    }

    // 司机任务签到收集点
    public function getPoint()
    {
    //"{'id':2,'lng':'12112','lat':'1213','time':'2015-08-09'}"
        $point = I('post.');
        $geo = array('lng' => $point['lng'],'lat' => $point['lat']);
        $geo = json_encode($geo);
        $time = date('Y-m-d H:i:s',NOW_TIME);
        if ($point['lng'] != '' && $point['lat'] != '') {
            $res = M('tms_task_node')->save(array('id' => $point['id'],'geo_new' => $geo,'updated_time' => $time));
        }
        if ($res) {
            $return = array(
                'status' => 1,
                'msg'    => '收集成功',
            );
        } else {
            $return = array(
                'status' => 0,
                'msg'    => '点位收集失败',
            );
        }
        $this->ajaxReturn($return);
    }
}
