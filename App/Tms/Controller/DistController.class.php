<?php
namespace Tms\Controller;
use Think\Controller;
class DistController extends Controller {

	//司机提货
    public function delivery() {
        $id = I('post.code/d',0);
        if (IS_GET) {
            //只显示当天的记录
            $map['mobile'] = session('user.mobile');
            $this->userid  = M('tms_user')->field('id')->where($map)->find();//传递出userid
            $map['status'] = '1';
            $start_date    = date('Y-m-d',NOW_TIME);
            $end_date      = date('Y-m-d',strtotime('+1 Days'));
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $this->data  = M('tms_delivery')->where($map)->select();
            $this->title = '提货扫码';
            $this->display('tms:delivery');
            exit;
        } elseif (IS_POST && !empty($id)) {
            $map['dist_id'] = $id;
            //$map['mobile'] = session('user.mobile');
            $map['status'] = '1';
            $start_date = date('Y-m-d',NOW_TIME);
            $end_date = date('Y-m-d',strtotime('+1 Days'));
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $M = M('tms_delivery');
            $delivery = $M->field('id,mobile,order_count')->where($map)->find();// 取出当前提货单信息
            unset($map['dist_id']);
            $map['mobile'] = session('user.mobile');
            $delivery_all = $M->field('id,mobile,dist_id,order_count')->where($map)->select();//取出当前司机所有配送单信息
            unset($map);
            if (!empty($delivery)) {//若该配送单已被认领
                if($delivery['mobile'] == session('user.mobile')) {//如果认领的司机是同一个人
                    $this->error = '提货失败，该单据您已提货';
                }
                else {
                    //如果是另外一个司机认领的，则逻辑删除掉之前的认领纪录
                    $map['dist_id'] = $id;
                    $map['order'] = array('created_time' => 'DESC');
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
            if (empty($dist)) {
                $this->error = '提货失败，未找到该单据';
            }

            if ($dist['status'] == '2') {
                //已发运的单据不能被认领
                //$this->error = '提货失败，该单据已发运';
            }
            $ctime = strtotime($dist['created_time']);
            $start_date1 = date('Y-m-d',strtotime('-1 Days'));
            $end_date1 = date('Y-m-d',strtotime('+1 Days'));
            if($ctime < strtotime($start_date1) || $ctime > strtotime($end_date1)) {
                $this->error = '提货失败，该配送单已过期';
            }
            //添加提货数据
            if (empty($this->error)) {
                $data['dist_id']      = $dist['id'];
                $data['dist_code']    = $dist['dist_code'];
                $data['mobile']       = session('user.mobile');
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
                    $map['order'] = array('created_time' => 'DESC');
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
                    unset($map);
                    $map['dist_id'] = $va['dist_id'];
                    $map['order'] = array('created_time' => 'DESC');
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
                }
                unset($map);
                $map['status']  = '8';//已装车
                $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
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
                    $this->msg = "提货成功";
                    $M = M('TmsUser');                    
                    $map['mobile'] = session('user.mobile');
                    $user_data = $M->field('id')->where($map)->order('created_time DESC')->find(); 
                    unset($map);
                    unset($M);
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
            $res = array('status' =>'1', 'message' => '提货成功','code'=>$userid['id']);
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
                $this->display('tms:sorders');
                exit();
            }
            $this->dist = $res;
            //查询出库单列表
            $map['dist_id'] = $res['dist_id'];
            $map['order'] = array('created_time' => 'DESC');
            $A = A('Tms/Dist','Logic');
            $bills = $A->billOut($map);
            if($bills) {
                $orders = $bills['orders'];
                $this->orderCount = $bills['orderCount'];
                foreach ($orders as &$val) {
                    //获取支付状态的中文
                    $s = $A->getPayStatusByCode($val['order_info']['pay_status']);
                    $val['pay_status'] = $s;
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
                    $val['real_sum']        = $A->wipeZero($val['order_info']['final_price']);
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
        $this->display('tms:sorders');
    }

    //司机签收
    public function sign() {
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
        $dist_details = $wA->getDistDetails($map);
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
        //实收数量或重量
        $quantity    = I('post.quantity');
        $weight      = I('post.weight', 0);
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
        $deal_price = $A->wipeZero($receivable_sum);
        $sign_msg = I('post.sign_msg', '' ,'trim');
        //签收表主表数据
        $fdata = array(
            'receivable_sum' => $receivable_sum,
            'real_sum'       => $deal_price,
            'minus_amount'   => $orderInfo['info']['minus_amount'],
            'pay_reduce'     => $orderInfo['info']['pay_reduce'],
            'deliver_fee'    => $orderInfo['info']['deliver_fee'],
            'pay_status'     => $orderInfo['info']['pay_status'],
            'sign_msg'       => $sign_msg,
            'status'         => 2,//签收
            'sign_time'      => get_time(),
            'sign_driver'    => session('user.mobile'),
        );
        //更新订单状态
        $re = $this->set_order_status($refer_code, $deal_price, $quantity, $weight, $price_unit, $sign_msg);
        if($re['status'] === 0) {
            //保存签收数据到配送单详情
            $datas = array(
                'id'   => $dist_detail['id'],
                'data' => $fdata,
            );
            $s = $wA->saveSignDataToDistDetail($datas);
            //配送单详情的状态为2:已签收或者更成功
            if($dist_detail['status'] == 2 || $s) {
                $cdata = array();
                //组合一个签收详情数据
                foreach ($bill_id_details as $detail_id => $detail) {
                    $tmp['pid']                = $dist_detail['id'];
                    $tmp['bill_out_detail_id'] = $detail_id;
                    $tmp['real_sign_qty']      = $quantity[$detail_id];
                    $tmp['real_sign_wgt']      = isset($weight[$detail_id]) ? $weight[$detail_id] : 0;
                    $tmp['measure_unit']       = $detail['order_detail']['unit_id'];
                    $tmp['charge_unit']        = $detail['order_detail']['close_unit'];
                    $tmp['price_unit']         = $detail['order_detail']['single_price'];
                    $tmp['created_time']       = get_time();
                    $tmp['updated_time']       = get_time();
                    $cdata[] = $tmp;
                    unset($tmp);
                }
                if($dist_detail['status'] == 2) {
                    //更新签收数据
                    foreach ($cdata as $value) {
                        unset($value['created_time']);
                        $dmap = array(
                            'bill_out_detail_id' => $value['bill_out_detail_id'],
                            'is_deleted' => 0
                        );
                        M('tms_sign_in_detail')
                            ->where($dmap)
                            ->save($value);
                    }
                }
                else {
                    //添加签收数据
                    M('tms_sign_in_detail')->addAll($cdata);
                }
                //更新配送单详情－>配送单状态
                $map['dist_id'] = $dist_id;
                $map['status']  = 2;
                $s = $wA->set_dist_status($map);
                $status = $s['status'];
                $msg = ($status === -1) ? '签收成功,配送单状态更新失败' : '签收成功';
            }
            
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
        $cA = A('Common/Order','Logic');
        $res = $cA->set_status($map);
        if($res['status'] === 0) {
            unset($map);
            //签收表主表数据
            $fdata = array(
                'sign_msg'       => I('post.sign_msg', '' ,'trim'),
                'status'         => 3,//拒收
                'sign_time'      => get_time(),
                'sign_driver'    => session('user.mobile'),
            );
            $bill_out_id = I('post.bid/d');
            $wA = A('Wms/Distribution', 'Logic');
            $map['bill_out_id'] = $bill_out_id;
            $dist_details = $wA->getDistDetails($map);
            //该出库单对应配送单详情
            $dist_detail = $dist_details['list'][0];
            //向出库单详情更新拒收信息
            if($dist_detail) {
                $datas = array(
                    'id'   => $dist_detail['id'],
                    'data' => $fdata,
                );
                $s = $wA->saveSignDataToDistDetail($datas);
                $res = array(
                    'status' => 0,
                    'msg'    => '更新成功'
                );
            }
            else {
                $res = array(
                    'status' => -1,
                    'msg'    => '出库单不存在'
                );
            }
        }
        $this->ajaxReturn($res);
    }

    //司机当日收货统计
    public function report() {

        $map['mobile'] = session('user.mobile');
        $map['status'] = '1';
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $this->data = M('tms_delivery')->where($map)->select();
        $this->title = '今日订单总汇';
        $this->display('tms:report');
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
            $this->display('tms:orders');
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
            for ($n = 0; $n < count($bill_outs); $n++) { 
                switch ($bill_outs[$n]['sign_status']) {
                    case '2':
                        $sign_orders++; //已签收订单数加1
                        foreach ($bill_outs[$n]['detail'] as $value) {
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
                            if ($sign_data[$n]['pay_status'] != 1) {
                                $sum_deal_price += f_mul($sign_qty, $sign_in_detail['price_unit']);  //回款
                            }
                        }
                        $sum_deal_price =  $dist_logic->wipeZero($sum_deal_price);  
                        break;

                    case '3':
                        $unsign_orders++;   //已拒收订单数加1
                        foreach ($bill_outs[$n]['detail'] as $val) {
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
                 
            }  
        }
            
        $list['dist_id'] = $res['dist_id'];
        $list['sum_deal_price']  = $sum_deal_price;//回款数
        $list['sign_orders'] = $sign_orders;//已签收
        $list['unsign_orders'] = $unsign_orders;//未签收
        $list['sign_finished']  = $sign_finished;  // 已完成
        $list['delivering'] = $all_orders - $sign_orders - $unsign_orders - $sign_finished;//派送中
        $this->list = $list;
        $this->back_lists = $arrays;
        $this->title =$res['dist_code'].'车单详情';
        $this->display('tms:orderlist');
    }

}
