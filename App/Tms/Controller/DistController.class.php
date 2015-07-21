<?php
namespace Tms\Controller;
use Think\Controller;
class DistController extends Controller {

	//司机提货
    public function delivery() {
        $id = I('post.code/d',0);
        if(IS_POST && !empty($id)) {
            $map['dist_id'] = $id;
            //$map['mobile'] = session('user.mobile');
            $map['status'] = '1';
            $start_date = date('Y-m-d',NOW_TIME);
            $end_date = date('Y-m-d',strtotime('+1 Days'));
            //$map['created_time'] = array('between',$start_date.','.$end_date);
            $M = M('tms_delivery');
            $dist = $M->field('id,mobile')->where($map)->find();
            unset($map);
            if(!empty($dist)) {//若该配送单已被认领
                if($dist['mobile'] == session('user.mobile')) {//如果认领的司机是同一个人
                    $this->error = '提货失败，该单据您已提货';
                }
                else {//如果是另外一个司机认领的，则逻辑删除掉之前的认领纪录
                    $map['id'] = $dist['id'];
                    $data['status'] = '0';
                    $M->where($map)->save($data);
                }
                unset($map);
            }

            //查询该配送单的信息
            //$map['dist_number'] = substr($id, 2);
            $A = A('Tms/Dist','Logic');
            $dist = $A->distInfo($id);
            //if($id != $dist['dist_number']) {
            if(empty($dist)) {
                $this->error = '提货失败，未找到该单据';
            }

            if($dist['status'] == '2') {//已发运的单据不能被认领
                //$this->error = '提货失败，该单据已发运';
            }
            $ctime = strtotime($dist['created_time']);
            if($ctime < strtotime($start_date) || $ctime > strtotime($end_date)) {
                //$this->error = '提货失败，该配送单已过期';
            }

            if(empty($this->error)) {
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
                $cA = A('Common/Order','Logic');//实例化Common下的OrderLogic
                $lines = $cA->line(array('line_ids'=>array($dist['line_id'])));
                $data['line_name'] = $lines[0]['name'];
                $citys = $cA->city();
                $data['city_id'] = $citys[$dist['city_id']];
                //添加一条记录到tms_delivery
                $res = $M->add($data);
                unset($map);
                $map['dist_id'] = $dist['id'];
                $map['order'] = array('created_time' => 'DESC');
                $orders = $A->billOut($map);
                unset($map);
                $map['status']  = '8';//已装车
                $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
                foreach ($orders as $val) {
                    $order_ids[] = $val['refer_code'];
                    $map['suborder_id'] = $val['refer_code'];
                    $res = $cA->set_status($map);
                }
                unset($map);
                if($res) {
                    $this->msg = "提货成功";
                }
                else {
                    $this->error = "提货失败";
                }
            }
        }

        //只显示当天的记录
        $map['mobile'] = session('user.mobile');
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        // dump($map);die();
        $this->data = M('tms_delivery')->where($map)->select();
        $this->title = '提货扫码';
        $this->display('tms:delivery');  

    }

    //出库单列表
    public function orders() {
        $id = I('get.id',0);
        if(!empty($id)) {
            $oid = I('get.oid',0);
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
            $orders = $bills['orders'];
            $this->orderCount = $bills['orderCount'];
            foreach ($orders as &$val) {
                switch ($val['order_info']['pay_status']) {
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
                $val['order_info']['pay_status'] = $s;
                //从订单获取字段到出库单
                $val['shop_name']    = $val['order_info']['shop_name'];
                $val['mobile']       = $val['order_info']['mobile'];
                $val['remarks']      = $val['order_info']['remarks'];
                // $val['status_cn']    = '已装车';
                $val['status_cn']    = $val['order_info']['status_cn'];
                $val['total_price']  = $val['order_info']['total_price'];
                $val['minus_amount'] = $val['order_info']['minus_amount'];
                $val['deliver_fee']  = $val['order_info']['deliver_fee'];
                $val['final_price']  = $val['order_info']['final_price'];
                $val['deal_price']   = $val['order_info']['deal_price'];
                $val['sign_msg']     = $val['order_info']['sign_msg'];
                $val['user_id']      = $val['order_info']['user_id'];

                $val['geo'] = json_decode($val['order_info']['geo'],TRUE);
                //出库单对应的签收数据
                $sign_in = $M->table('tms_sign_in')->where(array('bill_out_id' => $val['id']))->find();
                //将签收实际数据对应到出库单详情
                foreach ($val['detail'] as &$v) {
                    if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                        if($sign_in) {
                            //该出库单详情对应的签收详情数据
                            $sign_in_detail = $M->table('tms_sign_in_detail')->where(array('bill_out_detail_id' => $v['id']))->find();
                            $val['final_price'] = $sign_in['receivable_sum'];
                            $val['deal_price']  = $sign_in['real_sum'];
                            $v['quantity']  = $sign_in_detail['real_sign_qty'];
                            $v['sum_price'] = $sign_in_detail['real_sign_qty'] * $sign_in_detail['price_unit'];
                            if($sign_in_detail['measure_unit'] !== $sign_in_detail['charge_unit']) {
                                $v['weight'] = $sign_in_detail['real_sign_wgt'];
                                $v['sum_price']     = $sign_in_detail['real_sign_wgt'] * $sign_in_detail['price_unit'];
                            }
                        }
                    }
                    else {
                        $v['quantity'] = $v['delivery_qty'];
                    }
                    //从订单详情获取SKU信息
                    foreach ($val['order_info']['detail'] as $value) {
                        if($v['pro_code'] == $value['sku_number']) {
                            $v['single_price']    = $value['single_price'];
                            $v['close_unit']      = $value['close_unit'];
                            $v['unit_id']         = $value['unit_id'];
                            $v['sum_price']       = $v['sum_price'] ? $v['sum_price'] : $value['sum_price'];
                            $v['order_detail_id'] = $value['id'];//获取订单详情ID，用于更新订单状态
                        }
                    }
                }
                // 获取打印小票要用的数据
                $val['printStr'] = A('Tms/billOut', 'Api')->printBill($val['order_info']);
                $lists[$val['user_id']][] = $val;
            }
            $this->dist_id = $res['dist_id'];
            $this->data = $lists;
            //默认展开订单数据
            $this->id   = $id;
            $this->oid  = $oid;

        }
        //小票数据
        $this->title = "客户签收";
        $this->id = $id;
        $this->signature_url = C('TMS_API_PATH') . '/SignIn/signature';
        $this->display('tms:sorders');
    }

    //司机签收
    public function sign() {
        //签收表主表数据
        $fdata = array(
            'dist_id'        => I('post.dist_id/d'),
            'bill_out_id'    => I('post.bid/d'),
            'receivable_sum' => I('post.final_price/f',0),
            'real_sum'       => I('post.deal_price/f',0),
            'sign_msg'       => I('post.sign_msg', '' ,'trim'),
            'status'         => 1,
            'sign_time'      => get_time(),
            'sign_driver'    => session('user.mobile'),
            'created_time'   => get_time(),
            'updated_time'   => get_time(),
        );
        //签收详情表数据
        $refer_code  = I('post.id/d',0);
        $detail_id   = I('post.pro_id');
        $quantity    = I('post.quantity');
        $weight      = I('post.weight', 0);
        $unit_id     = I('post.unit_id');
        $close_unit  = I('post.close_unit');
        $price_unit  = I('post.price_unit');
        $deal_price  = $fdata['real_sum'];
        //更新订单状态
        $re = $this->set_order_status($refer_code, $deal_price, $quantity,$weight ,$price_unit);
        $re = array('status' => 0);
        if($re['status'] === 0) {
            $M = M('tms_sign_in');
            //该出库单签收状态
            $sign_status = $M->field('id')
            ->where(array('bill_out_id' => $fdata['bill_out_id']))
            ->find();
            if($sign_status) {
                $M->where(array('id' => $sign_status['id']))->save($fdata);
                $sign_id = $sign_status['id'];
            }
            else {
                $sign_id = $M->add($fdata);
            }
            if($sign_id) {
                $cdata = array();
                //组合一个出库单的签收数据并更新
                foreach ($detail_id as $val) {
                    $tmp['pid']                = $sign_id;
                    $tmp['bill_out_detail_id'] = $val;
                    $tmp['real_sign_qty']      = $quantity[$val];
                    $tmp['real_sign_wgt']      = isset($weight[$val]) ? $weight[$val] : 0;
                    $tmp['measure_unit']       = $unit_id[$val];
                    $tmp['charge_unit']        = $close_unit[$val];
                    $tmp['price_unit']         = $price_unit[$val];
                    $tmp['created_time']       = get_time();
                    $tmp['updated_time']       = get_time();
                    $cdata[] = $tmp;
                    unset($tmp);
                }
                if(!$sign_status) {
                    //添加签收数据
                    M('tms_sign_in_detail')->addAll($cdata);
                }
                else {
                    //更新签收数据
                    foreach ($cdata as $value) {
                        unset($value['created_time']);
                        M('tms_sign_in_detail')
                        ->where(array('bill_out_detail_id' => $value['bill_out_detail_id']))
                        ->save($value);
                    }
                }
                //更新配送单详情－>配送单状态
                $A = A('Tms/Dist','Logic');
                $map['pid'] = $fdata['dist_id'];
                $map['bill_out_id'] = $fdata['bill_out_id'];
                $map['status'] = 1;
                $code = $A->set_dist_status($map);
                $msg = ($code === -1) ? '签收成功,配送单状态更新失败' : '签收成功';
            }
            
            $status = ($code === -1) ? -1 : 0;
            $json = array('status' => $status, 'msg' => $msg);
            //code:－1(更新失败);0(未执行更新);1(配送单详情状态更新成功);2(配送单完成)
            $this->ajaxReturn($json);
        }
        $this->ajaxReturn($re);
    }

    //司机签收后订单回调
    protected function set_order_status($refer_code, $deal_price, $quantity,$weight, $price_unit, $sign_msg) {
        $map['suborder_id'] = $refer_code;
        $map['status']   = '6';
        $map['deal_price'] = $deal_price;
        $map['sign_msg'] = $sign_msg;
        $detail_ids = I('post.order_detail_id');
        foreach ($detail_ids as $key => $val) {
            if(intval($val) > 0) {
                $row['id']= $val;
                $row['actual_price'] = $price_unit[$key];
                $row['actual_quantity'] = empty($weight[$key])?$quantity[$key]:$weight[$key];
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
                'dist_id'      => I('post.dist_id/d'),
                'bill_out_id'  => I('post.bid/d'),
                'sign_msg'     => I('post.sign_msg', '' ,'trim'),
                'status'       => 2,
                'sign_time'    => get_time(),
                'sign_driver'  => session('user.mobile'),
                'created_time' => get_time(),
                'updated_time' => get_time()
            );
            $M = M('tms_sign_in');
            //签收表中是否有拒收纪录
            $signin = $M->field('id')
            ->where(array('bill_out_id' => $fdata['bill_out_id']))
            ->find();
            //向签收表添加或更新一条记录，status为0
            if($signin) {
                $M->where(array('id' => $signin['id']))->save($fdata);
            }
            else {
                $M->add($fdata);
            }
            //更新配送单详情－>配送单状态
            $A = A('Tms/Dist','Logic');
            $map['pid'] = $fdata['dist_id'];
            $map['bill_out_id'] = $fdata['bill_out_id'];
            $map['status'] = 2;
            $code = $A->set_dist_status($map);
        }
        $this->ajaxReturn($res);
    }

    // 车单纬度统计
    public function orderList(){

        $id = I('get.id',0);
        if(empty($id)){
            $this->error = '未找到该提货纪录。';
        }
        $M = M('tms_delivery');
        $res = $M->find($id);
            
        if(empty($res)) {
            $this->error = '未找到该提货纪录。';
        }elseif($res['mobile'] != session('user.mobile')) {
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
        $sum_deal_price  = 0;   //回款数
        $arrays=array();    //回仓列表的数组
        unset($map);
        $map['id'] = $res['dist_id'];
        //总订单数
        $order_count = M('stock_wave_distribution')->field('order_count')->where($map)->find();
        if(empty($order_count)){
            $this->error('没有找到该配送单');
        }
        $all_orders = $order_count['order_count'];
        unset($map);
        //查询条件为配送单id
        $map['dist_id'] = $res['dist_id'];
        //根据配送单id查询签收表
        $sign_data = M('tms_sign_in')->where($map)->select();
        //若查出的签收信息非空
        if(!empty($sign_data)){   
                for($n = 0; $n < count($sign_data); $n++){
                    if($sign_data[$n]['status'] == 2){
                        $unsign_orders++;   //拒收单数加1
                    }elseif($sign_data[$n]['status'] == 1){
                        $sign_orders++; //已签收单数加1
                    }
                    unset($map);
                    $map['pid'] = $sign_data[$n]['id'];
                    //根据出库单id查询出所有出库单详情信息
                    $sign_in_detail = M('tms_sign_in_detail')->where($map)->select();
                    if(!empty($sign_in_detail)){
                        
                        for($i = 0; $i < count($sign_in_detail); $i++){
                            unset($map);
                            $map['id'] =  $sign_in_detail[$i]['bill_out_detail_id'];
                            //配送数量
                            $delivery = M('stock_bill_out_detail')->where($map)->find();
                            $delivery_qty = $delivery['delivery_qty']; 
                            //如果计量单位和计价单位相等就取签收数量
                            if($sign_in_detail[$i]['measure_unit'] == $sign_in_detail[$i]['charge_unit']){
                                $sign_qty = $sign_in_detail[$i]['real_sign_qty']; //签收数量
                                $unit = $sign_in_detail[$i]['measure_unit']; //计量单位
                            //如果计量单位和计价单位不相等就取签收重量
                            }else{
                                $sign_qty = $sign_in_detail[$i]['real_sign_wgt']; //签收重量
                                $unit = $sign_in_detail[$i]['charge_unit']; //计价单位
                            }
                            $quantity = $delivery_qty - $sign_qty; //回仓数量
                            if($quantity > 0){
                                $key  = $delivery['pro_code'];    //sku号
                                $arrays[$key]['quantity'] =  $quantity; //回仓数量
                                $arrays[$key]['name'] =  $delivery['pro_name'];   //sku名称
                                $arrays[$key]['unit_id'] = $unit;   //单位
                            }
                            $sum_deal_price += $sign_qty * $sign_in_detail[$i]['price_unit'];  //回款
                        }
                        
                    }
                    
                }  
        }
            
        $list['dist_id'] = $res['dist_id'];
        $list['sum_deal_price']  = $sum_deal_price;//回款数
        $list['sign_orders'] = $sign_orders;//已签收
        $list['unsign_orders'] = $unsign_orders;//未签收
        $list['delivering'] = $all_orders - $sign_orders - $unsign_orders;//派送中
        $this->list = $list;
        $this->back_lists = $arrays;
        $this->title =$res['dist_code'].'车单详情';
        $this->display('tms:orderlist');
    }

    //保存客户签名
    public function signature() {
        $suborder_id = I('post.oid/d',0);
        $signature   = I('post.path','','trim');
        if($suborder_id && $signature) {
            //查询出库单ID
            $M = M('stock_bill_out');
            $map['refer_code'] = $suborder_id;
            $map['is_deleted'] = 0;
            $bill_out = $M->field('id')
            ->where($map)
            ->find();
            if($bill_out) {
                unset($map['refer_code']);
                $map['bill_out_id'] = $bill_out['id'];
                //更新签名图片至配送单详情表
                $s = M('stock_wave_distribution_detail')
                ->where($map)
                ->save(array('signature' => $signature));
                //保存签名成功
                if($s) {
                    $re = array(
                        'code' => 0,
                        'msg'  => '保存签名成功'
                    );
                }
                //保存签名失败
                else {
                    $re = array(
                        'code' => -1,
                        'msg'  => '保存签名失败'
                    );
                }
            }
            //订单号对应出库单失败
            else {
                $re = array(
                    'code' => -1,
                    'msg'  => '获取出库单数据失败'
                );
            }
        }
        //传入参数错误
        else {
            $re = array(
                'code' => -1,
                'msg'  => '订单ID或签名不能为空'
            );
        }
        $this->ajaxReturn($re);
    }

    public function test() {
        if(IS_POST) {
            dump($_FILES);
        }else{
            $this->display('tms:test');
        }
    }

}
