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

            if(empty($this->error)){
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
        $this->display('tms:sdelivery');  

    }

    //出库单列表
    public function orders(){
        $id = I('get.id',0);
        if(!empty($id)) {
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
            //查询出库单列表
            $map['dist_id'] = $res['dist_id'];
            $map['order'] = array('created_time' => 'DESC');
            $A = A('Tms/Dist','Logic');
            $orders = $A->billOut($map);
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
                foreach ($val['detail'] as &$v) {
                    if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                        //该出库单详情对应的签收数据
                        $sign_in = $M->table('tms_sign_in')->where(array('bill_out_detail_id' => $v['id']))->find();
                        $v['quantity']  = $sign_in['real_sign_qty'];
                        $v['sum_price'] = $sign_in['real_sign_qty'] * $sign_in['price_unit'];
                        if($sign_in['measure_unit'] !== $sign_in['charge_unit']) {
                            $v['weight'] = $sign_in['real_sign_wgt'];
                            $v['sum_price']     = $sign_in['real_sign_wgt'] * $sign_in['price_unit'];
                        }
                    }
                    else {
                        $v['quantity'] = $val['delivery_qty'];
                    }
                    //从订单详情获取SKU信息
                    foreach ($val['order_info']['detail'] as $value) {
                        if($v['pro_code'] == $value['sku_number']){
                            $v['single_price']    = $value['single_price'];
                            $v['close_unit']      = $value['close_unit'];
                            $v['unit_id']         = $value['unit_id'];
                            $v['sum_price']       = $v['sum_price'] ? $v['sum_price'] : $value['sum_price'];
                            $v['order_detail_id'] = $value['id'];//获取订单详情ID，用于更新订单状态
                        }
                    }
                }
            }
            $this->dist_id = $res['dist_id'];
            $this->data = $orders;
        }
        $this->title = "客户签收";
        $this->display('tms:sorders');
    }

    //司机签收
    public function sign() {
        //接收签收数据
        $dist_id     = I('get.dist_id/d');
        $refer_code  = I('post.refer_code/d',0);
        $detail_id   = I('post.pro_id');
        $quantity    = I('post.quantity');
        $weight      = I('post.weight', 0);
        $unit_id     = I('post.unit_id');
        $close_unit  = I('post.close_unit');
        $price_unit  = I('post.price_unit');
        $final_price = I('post.final_price/d',0);
        $deal_price  = I('post.deal_price/d',0);
        //更新订单状态
        $re = $this->set_order_status($refer_code, $deal_price, $quantity, $price_unit);
        if($re['status'] === 0) {
            $M = M('tms_sign_in');
            //该出库单签收状态
            $sign_status = $M->table('stock_wave_distribution_detail')
            ->field('status')
            ->where(array('bill_out_id' => $refer_code))
            ->find()['status'];
            $data = array();
            //组合一个出库单的签收数据并更新
            foreach ($detail_id as $val) {
                $tmp['dist_id']            = $dist_id;
                $tmp['bill_out_detail_id'] = $val;
                $tmp['real_sign_qty']      = $quantity[$val];
                $tmp['real_sign_wgt']      = isset($weight[$val]) ? $weight[$val] : 0;
                $tmp['measure_unit']       = $unit_id[$val];
                $tmp['charge_unit']        = $close_unit[$val];
                $tmp['price_unit']         = $price_unit[$val];
                $tmp['receivable_sum']     = $final_price;
                $tmp['real_sum']           = $deal_price;
                $tmp['sign_driver']        = session('user.mobile');
                $tmp['sign_time']          = get_time();
                $tmp['created_time']       = get_time();
                $tmp['updated_time']       = get_time();
                $data[] = $tmp;
                unset($tmp);
            }
            if($sign_status == 0) {
                $M->addAll($data);
                //更新配送单详情－>配送单状态
                $A = A('Tms/Dist','Logic');
                $map['pid'] = $dist_id;
                $map['bill_out_id'] = $refer_code;
                $code = $A->set_dist_status($map);
                $msg = ($code === -1) ? '签收成功,配送单状态更新失败' : '签收成功';
            }
            else {
                //更新签收数据
                foreach ($data as $value) {
                    unset($value['created_time']);
                    $M->where(array('bill_out_detail_id' => $value['bill_out_detail_id']))->save($value);
                }
                $code = 0;
                $msg = '更新成功';
            }
            $status = ($code === -1) ? -1 : 0;
            $json = array('status' => $code, 'msg' => $msg);
            //code:－1(更新失败);0(未执行更新);1(配送单详情状态更新成功);2(配送单完成)
            $this->ajaxReturn($json);
        }
        $this->ajaxReturn($re);
    }

    //司机签收后订单回调
    public function set_order_status($refer_code, $deal_price, $quantity, $price_unit) {
        $map['suborder_id'] = $refer_code;
        $map['status']   = '6';
        $map['deal_price'] = $deal_price;
        $map['remark'] = I('post.sign_msg');
        $detail_ids = I('post.order_detail_id');
        foreach ($detail_ids as $key => $val) {
            if(intval($val) > 0) {
                $row['id']= $val;
                $row['actual_price'] = $price_unit[$key];
                $row['actual_quantity'] = $quantity[$key];
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
        $map['suborder_id'] = I('post.refer_code/d',0);
        $map['status'] = '7';
        $map['remark'] = I('post.sign_msg');
        $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
        $cA = A('Common/Order','Logic');
        $res = $cA->set_status($map);
        $this->ajaxReturn($res);
    }
}