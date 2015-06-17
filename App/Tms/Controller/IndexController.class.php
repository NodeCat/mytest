<?php
namespace Tms\Controller;
use Think\Controller;
class IndexController extends Controller {
    protected function _initialize(){
        if(!session('?user')) {
            if(ACTION_NAME != 'login' && ACTION_NAME != 'logout') {
                $this->redirect('logout');
            }
        }
    }
    public function index(){
        $this->redirect('delivery');
    }
    //登录
    public function login() {
        if(IS_GET) {
            if(session('?user')) {
                $this->redirect('delivery');
            }
            else {
                $this->title = '请您签到';
                $this->display('Index:login');    
            }   
        }
        if(IS_POST) {
            $code = I('post.code/d',0);
            $name   = I('post.name');
            if(empty($code) || empty($name)){
                $this->error = "请输入您的身份信息";
                $this->display('Index:login');
            }
            else {
                $user = array('mobile'=> $code,'username' => $name);
                session('user',$user);
                $this->redirect('delivery');    
            }
        }
    }
    public function logout() {
        session(null);
        session('[destroy]');
        $this->redirect('login');
    }
    //配送单详情
    public function orders(){
        //layout(false);
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
                $this->display('tms:orders');
                exit();
            }
            
            $map['dist_id'] = $res['dist_id'];
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $A = A('Tms/Order','Logic');
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
                        $val['quantity'] +=$v['actual_quantity'];   
                        $v['quantity'] = $v['actual_quantity'];
                        $v['sum_price'] = $v['actual_sum_price'];
                    }
                    else {
                        $val['quantity'] +=$v['quantity'];
                    }
                }
                
            }
            //dump($orders);
            $this->data = $orders;
        }
        $this->title = "客户签收";
        $this->display('tms:orders');
    }

    //司机签收
    public function sign() {
        $map['order_id'] = I('post.id/d',0);
        $map['status'] = '6';
        $map['deal_price'] = I('post.deal_price/d',0);
        $map['sign_msg'] = I('post.sign_msg');

        $pro_id = I('post.pro_id');
        $price_unit = I('post.price_unit');
        $price_sum = I('post.price_sum');
        $quantity = I('post.quantity');
        foreach ($pro_id as $key => $val) {
            $row['id']= $val;
            $row['actual_price'] = $price_unit[$key];
            $row['actual_quantity'] = $quantity[$key];
            $row['actual_sum_price'] = $price_sum[$key];
            $map['order_details'][] = $row;
        }
        $map['driver'] = '司机'.session('user.username').session('user.mobile');
        $A = A('Tms/Order','Logic');
        $res = $A->set_status($map);
        $this->ajaxReturn($res);
    }

    //客户退货
    public function reject() {
        $map['order_id'] = I('post.id/d',0);
        $map['status'] = '7';
        $map['sign_msg'] = I('post.sign_msg');

        $map['driver'] = '司机'.session('user.username').session('user.mobile');
        $A = A('Tms/Order','Logic');
        $res = $A->set_status($map);
        $this->ajaxReturn($res);
    }
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
            $map['id'] = $id;
            $A = A('Tms/Order','Logic');
            $dist = $A->distInfo($map);
            
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
                $data['dist_id'] = $dist['id'];
                $data['dist_code'] = $dist['dist_number'];
                $data['mobile'] = session('user.mobile');
                $data['order_count'] = $dist['order_count'];
                $data['sku_count'] = $dist['sku_count'];
                $data['line_count'] = $dist['line_count'];
                $data['total_price'] = $dist['total_price'];
                $data['site_src'] = $dist['site_src'];
                $data['created_time'] = get_time();
                $data['updated_time'] = get_time();
                $data['status'] = '1';
                $lines = $A->line(array('line_ids'=>array($dist['line_id'])));
                $data['line_name'] = $lines[0]['name'];
                $citys = $A->city();
                $data['city_id'] = $citys[$dist['city_id']];
                
                $res = $M->add($data);
                unset($map);
                $map['dist_id'] = $dist['id'];
                $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
                $orders = $A->order($map);
                unset($map);
                $map['status']  = '8';//已装车
                $map['driver'] = '司机'.session('user.username').session('user.mobile');
                foreach ($orders as $val) {
                    $order_ids[] = $val['id'];
                    $map['order_id'] = $val['id'];
                    $res = $A->set_status($map);
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
        $this->data = M('tms_delivery')->where($map)->select();
        
        $this->title = '提货扫码';
        $this->display('tms:delivery');  
    }

}