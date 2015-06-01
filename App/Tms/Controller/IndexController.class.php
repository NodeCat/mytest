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
    	$this->display();    
    }
    public function login() {
        if(IS_GET) {
            if(session('?user')) {
                $this->redirect('delivery');
            }
            else {
                $this->title = '请先登录';
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
    public function orders(){
        $id = I('get.id',0);
        if(!empty($id)) {
            $map['dist_id'] = $id;
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $A = A('Tms/Order','Logic');
            $orders = $A->order($map);
            foreach ($orders as &$val) {
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
    public function sign() {
        $map['order_id'] = I('post.id/d',0);
        $map['status'] = '6';
        $map['deal_price'] = I('post.deal_price/d',0);
        $map['sign_msg'] = '';

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

        $A = A('Tms/Order','Logic');
        $res = $A->sign($map);
        $this->ajaxReturn($res);
    }
    public function reject() {
        $map['order_id'] = I('post.id/d',0);
        $map['status'] = '7';
        $map['sign_msg'] = '';
        $A = A('Tms/Order','Logic');
        $res = $A->sign($map);
        $this->ajaxReturn($res);
    }
    public function delivery() {
        $id = I('post.code');
        if(IS_POST && !empty($id)) {
            $map['dist_code'] = $id;
            $map['mobile'] = session('user.mobile');
            $start_date = date('Y-m-d',NOW_TIME);
            $end_date = date('Y-m-d',strtotime('+1 Days'));
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $dist = M('tms_delivery')->field('id')->where($map)->select();
            unset($map);
            if(!empty($dist)) {
                $this->error = '该单据您已提货';
            }
            else {
                $map['dist_number'] = substr($id, 2);
                $A = A('Tms/Order','Logic');
                $dist = $A->distInfo($map);
                if(empty($dist)) {
                    $this->error = '未找到该单据';
                }
                else {
                    $data['dist_id'] = $dist['id'];
                    $data['mobile'] = session('user.mobile');
                    $data['order_count'] = $dist['order_count'];
                    $data['sku_count'] = $dist['sku_count'];
                    $data['line_count'] = $dist['line_count'];
                    $data['total_price'] = $dist['total_price'];
                    $data['site_src'] = $dist['site_src'];
                    $data['created_time'] = get_time();
                    $data['dist_code'] = $dist['dist_number'];

                    $lines = $A->line(array('line_ids'=>array($dist['line_id'])));
                    $data['line_name'] = $lines[0]['name'];//dump($lines);dump($dist);exit();
                    $citys = $A->city();
                    $data['city_id'] = $citys[$dist['city_id']];
                    
                    $M = M('tms_delivery');
                    $res = $M->add($data);
                    unset($map);
                    if($res) {
                        $this->msg = "提货成功。";
                    }
                    else {
                        $this->error = "提货失败";
                    }
                }
            }
        } 
        $map['mobile'] = session('user.mobile');
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $this->data = M('tms_delivery')->where($map)->select();
        
        $this->title = '提货扫码';
        $this->display('tms:delivery');  
    }

}