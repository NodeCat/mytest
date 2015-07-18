<?php
namespace Tms\Controller;
use Think\Controller;
class IndexController extends Controller {
    protected $car=array(
        'car_type' =>array(0=>'请选择你的车型','平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货','微面'),
        'car_from' =>array(0=>'请选择派车平台','速派得','云鸟','58','一号货车','京威宏','浩辉平台','雷罡平台','加盟车平台','北京汇通源国际物流有限公司','自有车'),
        'warehouse'=>array(0=>'请选择签到仓库',7=>'北京白盆窑仓库',6=>'北京北仓',9=>'天津仓库',10=>'上海仓库',5=>'成都仓库',11=>'武汉仓库',13=>'长沙仓库'),
    );

    protected function _initialize() {
        if(!session('?user')) {
            if(ACTION_NAME != 'login' && ACTION_NAME != 'logout' && ACTION_NAME !='register') {
                $this->redirect('logout');
            }
        }

        if(defined('VERSION')) {
            $this->ver = '2.0';
            $action2 = array('delivery','orders','sign','reject');
            if(in_array(ACTION_NAME, $action2)) {
                R('Dist/'.ACTION_NAME);
                exit();
            }
        }
    }

    public function index() {
        $this->redirect('delivery'); 
    }
    
    //登录
    public function login() {
        if(IS_GET) {
            if(session('?user')) {
                $this->redirect('delivery');
                exit;
            }
            else {
                $this->title = '请您签到';
                $this->display('Index:login');    
            }   
        }
        if(IS_POST) {
            if(session('?user')) {
                $this->redirect('delivery');
                exit;
            }
            $code = I('post.code',0);
            if(!preg_match('/^0?1[34587]{1}\d{9}$/',$code)){
                $this->error = "您输入的手机号码格式不正确！";
                $this->display('Index:login');
                exit;

            }
            else {
                $user['mobile'] = $code;
                $M1=M('TmsUser');
                $data=$M1->field('id,username')->where($user)->order('created_time DESC')->find();          
                if($data['id']){ // 如果以前签到过
                    $user['username'] = $data['username'];// 把用户名写入session
                    $date = date('Y-m-d H:i:s',NOW_TIME);
                    $userid['userid']=$data['id'];
                    $userid['updated_time']=$date;
                    $userid['created_time']=$date;
                    $start_date = date('Y-m-d',NOW_TIME);
                    $end_date = date('Y-m-d',strtotime('+1 Days'));
                    unset($map);
                    $map['created_time'] = array('between',$start_date.','.$end_date);
                    $map['userid']=$data['id'];
                    unset($M);
                    $M = M('TmsSignList');
                    $id = $M->field('id')->order('created_time DESC')->where($map)->find();
                    //如果今天已经签到过了那就改成最新的签到时间
                    if($id['id']){
                        $userid['id']=$id['id'];
                        unset($userid['created_time']);
                        $M->save($userid);
                        session('user',$user);
                        $this->redirect('delivery');
                    }else{
                        $M->add($userid);//否则就签到
                        session('user',$user);
                        $this->redirect('delivery');
                    }
                }else{
                    $this->user=$user;
                    $this->title='信息登记';
                    $this->assign('car',$this->car);
                    $this->display('tms:register');
                }
                    
            }
        }
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
    
    // 车单纬度回款报表
    public function orderList() {
        // 获取配送id
        $id = I('get.id',0);
        if(!empty($id)) {
            $M = M('tms_delivery');
            $res = $M->find($id);
            if(empty($res)) {
                $this->error = '未找到该配送单纪录。';
            }
            elseif($res['mobile'] != session('user.mobile')) {
                $this->error ='不能查看该配送单详情，您的手机号码与提货人不符合。';
            }
            if(!empty($this->error)) {
                $this->title = "车单详情";
                $this->display('tms:orderlist');
                exit();
            }
            $A = A('Tms/List','Logic');
            $delivery         = $A->deliveryCount($res['dist_id']);
            $this->list       = $delivery['delivery_count'];
            $this->back_lists = $delivery['back_lists']; 
            $this->title      = $res['dist_code'].'车单详情';    
        }
        $this->display('tms:orderlist');
    }

    // 修改个人信息
    public function update(){

        if(IS_GET){
            //仓库列表
            $this->assign('warehouse',$storge);
            $this->assign('car',$this->car);
            $this->person();
            exit();  
        }
        if(IS_POST){
            $code     = I('post.mobile','');
            $name     = I('post.username');
            $num      = I('post.car_num');
            $car_type = I('post.car_type');
            $car_from = I('post.car_from');
            $storge   = I('post.warehouse');

            if(empty($code) || empty($name) || empty($num) || $car_type=='请选择你的车型' || $car_from=='请选择派车平台' || empty($storge)) {
                $this->error ='请正确的填写修改信息';
                $this->person();
                exit();
            }
            $date = date('Y-m-d H:i:s',NOW_TIME);
            $data = I('post.');
            $data['updated_time'] = $date;
            unset($M);
            $M = M('TmsUser');
            unset($map);
            $map['mobile']=session('user.mobile');
            $id=$M->field('id')->where($map)->order('updated_time')->find();
            $data['id']=$id['id'];
            $savedata = $M->create($data);
            if($M->save($savedata)){

                $user['username'] = $data['username'];
                $user['mobile']   =$data['mobile'];
                session('user',$user);
                $this->msg='修改成功';
                $this->person();

            }else{
                $this->error='修改失败!';
                $this->person();
            }
        }  
    }

    // 个人信息
    public function person(){
        unset($M);
        $M = M('TmsUser');
        unset($map);
        $map['mobile']=session('user.mobile');
        $data= $M->where($map)->order('updated_time')->find();
        $this->title='个人信息';
        $this->assign('car',$this->car);
        $data['warehouse']=$this->car['warehouse'][$data['warehouse']];
        $this->data=$data;
        $this->display('tms:person');
    }

    //司机第一次信息登记
    public function register(){

        if(IS_GET){
            if(session('?user')) {
                 $this->redirect('delivery');
                 exit;
            }
            else{
            $this->title = '请填写完整的签到信息';
            $this->assign('car',$this->car);
            $this->display('tms:register'); 
            exit();
            }   
        }
        if(IS_POST){
            $code   = I('post.mobile/d',0);
            $name   = I('post.username');
            $num    = I('post.car_num');
            $car_type = I('post.car_type');
            $car_from = I('post.car_from');
            $warehouse = I('post.warehouse');
            $user['mobile']   = $code;
            $user['username'] = $name;
            $user['car_num']  = $num;
            $user['car_type'] = $car_type;
            $user['car_from'] = $car_from;
            $user['warehouse']= $this->car['warehouse'][$warehouse];
            if(empty($code) || empty($name) || empty($num)|| $car_type=='请选择你的车型' || $car_from=='请选择派车平台' || empty($warehouse)) {
                $this->error ='请补全你的签到信息';
                if(empty($name)) {
                    $this->error ='请输入你的姓名';
                }
                elseif(empty($num)) {
                    $this->error ='请输入你的车牌号';                
                }
                elseif($car_type=='请选择你的车型') {
                    $this->error ='请选择你的车型';
                }
                elseif($car_from=='请选择派车平台') {
                    $this->error ='请选择派车平台';
                }
                elseif(empty($storge)) {
                    $this->error ='请选择你的签到仓库';
                }
                $this->user=$user;
                $this->title ='请填写完整的签到信息';
                $this->assign('car',$this->car);
                $this->display('tms:register');
                exit;
            }
            $date = date('Y-m-d H:i:s',NOW_TIME);
            $data = I('post.');
            $data['created_time'] = $date;
            $data['updated_time'] = $date;
            unset($M);
            $M = M('TmsUser');
            $data = $M->create($data);
            if($M->add($data)){
                unset($user);
                $user['username'] = $data['username'];
                $user['mobile']   =$data['mobile'];
                session('user',$user);
                $userid = $M->field('id')->where($user)->find();
                $data['userid'] = $userid['id'];
                unset($M);
                $M=M('TmsSignList');
                $M->data($data)->add();
                $this->redirect('delivery');

            }else{

                session(null);
                session('[destroy]');
                $this->title='请填写正确的信息!';
                $this->assign('car',$this->car);
                $this->display('tms:register');
            }
        }  
    }
    
    // 注销
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
            $this->dist = $res;
            $map['dist_id'] = $res['dist_id'];
            $map['itemsPerPage'] = $res['order_count'];
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $A = A('Common/Order','Logic');
            $orderList = $A->order($map);
            $this->orderCount = count($orderList);
            foreach ($orderList as &$val) {
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
                $orders[$val['user_id']][] = $val;
            }
            $this->data = $orders;
        }
        $this->title = "客户签收";
        $this->display('tms:orders');
    }

    //司机签收
    public function sign() {
        $map['suborder_id'] = I('post.id/d',0);
        $map['status']   = '6';
        $map['deal_price'] = I('post.deal_price/f',0);
        $map['sign_msg'] = I('post.sign_msg');
        $pro_id = I('post.pro_id');
        $price_unit = I('post.price_unit');
        $price_sum = I('post.price_sum');
        $quantity = I('post.quantity');
        foreach ($pro_id as $key => $val) {
            $row['id']= $val;
            $row['actual_price'] = $price_unit[$key];
            $row['actual_quantity'] = $quantity[$key];
            $row['actual_sum_price'] = $row['actual_price'] * $row['actual_quantity'];
            $map['order_details'][] = $row;
        }
        $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
        
        $A = A('Common/Order','Logic');
        $res = $A->set_status($map);
        $this->ajaxReturn($res);
    }

    //客户退货
    public function reject() {
        $map['suborder_id'] = I('post.id/d',0);
        $map['status'] = '7';
        $map['sign_msg'] = I('post.sign_msg');
        $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
        $A = A('Common/Order','Logic');
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
            $map['created_time'] = array('between',$start_date.','.$end_date);
            $M = M('tms_delivery');
            $dist = $M->field('id,mobile,order_count')->where($map)->find();// 取出当前提货单信息
            unset($map['dist_id']);
            $map['mobile'] = session('user.mobile');
            $dist_all = $M->field('id,mobile,dist_id,order_count')->where($map)->select();//取出当前司机所有配送单信息
            unset($map);
            if(!empty($dist)) {//若该配送单已被认领
                if($dist['mobile'] == session('user.mobile')) {//如果认领的司机是同一个人
                    $this->error = '提货失败，该单据您已提货';
                }
                else {//如果是另外一个司机认领的，则逻辑删除掉之前的认领纪录
                    $map['dist_id']      = $id;
                    $map['order_by']     = array('user_id'=>'ASC','created_time' => 'DESC');
                    $map['itemsPerPage'] = $dist['order_count'];
                    $A   = A('Common/Order','Logic');
                    $ord = $A->order($map);
                    unset($map);
                    foreach ($ord as $key => $value) {
                        if($value['status_cn'] != "已装车") {
                            $status = '1';//只要一单不是以装车,就停止
                            break;
                        }
                        else {
                            $status = '2';
                        }
                    }
                    if($status == '2') {//如果别人提的还是已装车，那就还可以提
                        $map['id'] =$dist['id'];
                        $data['status'] = '0';
                        $M->where($map)->save($data);
                    }
                    else {// 如果别人提了，并且只要一单不是以装车，就不能提了
                        $this->error="该配送单已被他人提走并且在配送中,不能被认领";
                    }
                }
                unset($map);
            }

            //查询该配送单的信息
            //$map['dist_number'] = substr($id, 2);
            $map['id'] = $id;
            $A = A('Common/Order','Logic');
            $dist = $A->distInfo($map);
            unset($map);
            //if($id != $dist['dist_number']) {
            if(empty($dist)) {
                $this->error = '提货失败，未找到该单据';
            }

            if($dist['status'] == '2') {//已发运的单据不能被认领
                //$this->error = '提货失败，该单据已发运';
            }
            $ctime = strtotime($dist['created_time']);
            $start_date1 = date('Y-m-d',strtotime('-1 Days'));
            $end_date1 = date('Y-m-d',strtotime('+1 Days'));
            if($ctime < strtotime($start_date1) || $ctime > strtotime($end_date1)) {
                //$this->error = '提货失败，该配送单已过期';
            }
            // 用配送单id获取订单详情
            $map['dist_id'] = $id;
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $map['itemsPerPage'] = $dist['order_count'];
            $orders = $A->order($map);
            unset($map);
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
                //遍历每个订单的取出路线id
                foreach ($orders as $v) {
                    $line_id[] = $v['line_id'];
                # code...
                }
                $line_id = array_unique($line_id);//重复的去掉
                $lines = $A->line(array('line_ids'=>$line_id));//取出所有路线
                // 把路线连接起来
                foreach ($lines as $key => $val) {
                    if($key==0) {
                        $line_names = $val['name'];
                    }
                    else{
                        $line_names .='/'.$val['name'];
                    }
                }
                $data['line_name'] = $line_names;//写入devilery
                $citys = $A->city();
                $data['city_id'] = $citys[$dist['city_id']];

                //判断是否已结款完成
                foreach ($dist_all as $va) {
                    $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
                    $map['dist_id'] = $va['dist_id'];
                    $map['itemsPerPage'] = $va['order_count'];
                    $ords = $A->order($map);
                    foreach ($ords as $v) {
                        if($v['status_cn'] != "已完成") {
                            $status = '3';//只要有一个订单不是已完成，
                            break 2;
                        }
                        else {
                            $status = '4';// 已结款完成
                        }
                    }
                }
                $res = $M->add($data);
                // 设置订单状态
                $map['status']  = '8';//已装车
                $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
                foreach ($orders as $val) {
                    $order_ids[] = $val['id'];
                    $map['suborder_id'] = $val['id'];
                    $res = $A->set_status($map);
                }
                unset($map);
                if($res) {
                    $this->msg = "提货成功";
                    $M = M('TmsUser');                    
                    $map['mobile'] = session('user.mobile');
                    $user_data = $M->field('id')->where($map)->order('created_time DESC')->find(); 
                    unset($map);
                    unset($M);
                    $M = M('TmsSignList');
                    // 如果现有的配送单全部结款已完成，就再次签到，生成新的签到记录
                    if($status=='4'){
                    $map['updated_time'] = $data['updated_time'];
                    $map['created_time'] = $data['created_time'];
                    $map['userid']       = $user_data['id'];
                    $M->add($map);
                    unset($map);
                    }

                    $map['created_time'] = array('between',$start_date.','.$end_date);
                    $map['userid']       =  $user_data['id'];
                    $sign_id = $M->field('id')->order('created_time DESC')->where($map)->find();//获取最新的签到记录
                    $map['delivery_time'] = $data['created_time'];//加入提货时间
                    $map['id']            = $sign_id['id'];
                    $M->save($map); 
                    unset($map);
                }
                else {
                    $this->error = "提货失败";
                }
            }
        }

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

    }

    /*
     * 功能：根据配送单id 生成相应的客退入库单
     * @para：$dist_id,配送单id
     * $return: null
    */
    public function deliver_goods(){
        //配送单id
        $dist_id = I('get.dist_id/d',0);
        if(!empty($dist_id)){
            //判断是否已经创建过客退入库单
            $L = A('Tms/List','Logic');
            $status = $L->view_return_goods_status($dist_id);
            if($status){ 
                $this->error("交货申请已收到");
            }
        }else{
            $this->error("没有找到相应的车单");
        }
        unset($map);
        //查询条件为配送单id
        $map['stock_wave_distribution_detail.pid'] = $dist_id;
        //根据配送单id查配送详情单里与出库单相关联的出库单id
        $bill_out_id = M('stock_wave_distribution_detail')->field('bill_out_id')->where($map)->select();
        //若查出的出库单id非空
        if(!empty($bill_out_id)){   
            $bill_out_id = array_column($bill_out_id,'bill_out_id');
            unset($map);
            //查询条件为出库单的id
            $map['id'] = array('in',$bill_out_id);
            //根据出库单id查出出库单的所有信息
            $stock_bill_out = M('stock_bill_out')->where($map)->select();
            //若查出的出库单信息非空
            if(!empty($stock_bill_out)){
                for($n = 0; $n < count($stock_bill_out); $n++){
                    $bill_out_qty = 0;  //配送数量
                    $real_sign_qty = 0; //实收数量
                    unset($map);
                    $map['pid'] = $stock_bill_out[$n]['id'];
                    //根据出库单id查询出所有出库单详情信息
                    $bill_out_detail = M('stock_bill_out_detail')->where($map)->select();
                    if(!empty($bill_out_detail)){
                        for($i = 0; $i < count($bill_out_detail); $i++){
                            $bill_out_qty += $bill_out_detail[$i]['delivery_qty'];  //累加出库单详情的配送数量  
                        }
                        $detail_id = array_column($bill_out_detail,'id');
                        unset($map);
                        //根据出库单详情表的id查询签收表的签收情况
                        $map['bill_out_detail_id'] = array('in', $detail_id);
                        $sign_data = M('tms_sign_in_detail')->where($map)->select();
                        if(!empty($sign_data)){
                            for($j = 0; $j < count($sign_data); $j++){  
                                $real_sign_qty += $sign_data[$j]['real_sign_qty'];  //累加签收表里的实际签收数量
                            }
                        }else{
                            $this->error("没有找到相应的签收单");
                        }

                    }else{
                        $this->error("没有找到相应的订单");
                    }
                    //比较配送数量和实收数量
                    $diff = $bill_out_qty - $real_sign_qty;
                    if($diff > 0){
                        //创建客退入库单
                        $Min = D('Wms/StockIn');    //实例化Ｗms的入库单模型
                                            
                        //$bill = $Min->create();
                        $bill['refer_code'] = $stock_bill_out[$n]['id'];//关联单据为出库id
                        $bill['code'] = get_sn('wms_back_in');   //生成客退入库单号
                        $bill['type'] = '3';    //入库类型为客退入库单
                        $bill['status'] = '21';     //待收货状态
                        $bill['batch_code'] = get_batch($bill['code']); //获得批次
                        $bill['wh_id'] = $stock_bill_out[$n]['wh_id'];  //仓库id
                        $bill['company_id'] = $stock_bill_out[$n]['company_id'];   //
                        $bill['partner_id'] = '';   //供应商
                        $bill['type'] = 3;  //入库类型为客退入库单
                        $bill['created_user'] = 2;   //uid默认为2
                        $bill['created_time'] = get_time();
                        
                        if(!empty($bill_out_detail)){
                        foreach ($bill_out_detail as $key => $val) {
                            $v['pro_code'] = $val['pro_code'];
                            $v['pro_name'] = $val['pro_name'];
                            $v['pro_attrs'] = $val['pro_attrs'];
                            $v['pro_uom'] = '';     //计量单位留空　
                            $v['expected_qty'] = $diff; //写入预期入库数量
                            $v['prepare_qty'] = 0;
                            $v['done_qty'] = 0;
                            $v['wh_id'] = $bill['wh_id'];
                            //$v['type'] = 'in';
                            $v['refer_code'] = $val['pid']; //写入相关联的出库单id
                            $v['pid'] = $bill['id'];
                            $v['price_unit'] = '';  //计价单位留空
                            $bill['detail'][] = $v;
                        }                   
                            $res = $Min->relation('detail')->add($bill); //写入客退入库单详情
                            
                        }
                        
                    }
                }
            }else{
                $this->error("没有找到相应的订单");
            }
            
        }else{
            $this->error("没有找到相应的车单");
        }
        $this->success("交货申请已收到");
    }



    // 地图模式
    public function navigation() {
        //如果不是ajax请求
        if(!IS_AJAX){
        $this->error('请求错误','',1);
        exit;
        }
        //只显示当天的记录
        $map['mobile'] = session('user.mobile');
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $map['status'] = '1';
        unset($M);
        $M = M('tms_delivery');
        $data = $M ->where($map)->select();
        unset($map);
        $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
        $A = A('Common/Order','Logic');
        $geo_array=array();
        foreach ($data as $key => $value) {
            // dump($value['dist_id']);
            $map['dist_id'] = $value['dist_id'];
            $map['itemsPerPage'] = $value['order_count'];
            $orders = $A->order($map);
            foreach ($orders as $keys => $values) {
                $values['geo'] = json_decode($values['geo'],TRUE);
                //如果地址为空的话跳过
                if($values['geo']['lng'] == '' || $values['geo']['lat'] == '' ) {
                    continue;
                }
                $geo = $values['geo'];
                $geo['order_id'] = $value['id'];
                $geo['user_id']  = $values['user_id'];
                $geo['address']  = '['.$values['shop_name'].']'.$values['deliver_addr'];
                // 只要有一单还没送完颜色就是0
                if($values['status_cn']=='已签收' || $values['status_cn']=='已退货' || $values['status_cn']=='已完成' ) {
                    if($geo_array[$values['user_id']]['color_type'] == NULL || $geo_array[$values['user_id']]['color_type'] != 0 ) {
                        $geo['color_type'] = 3;
                    }
                    else{
                        $geo['color_type'] = 0;
                    }      
                }
                else{
                    $geo['color_type'] = 0;
                }   
                $geo_array[$values['user_id']] = $geo;//把地图位置和信息按用户id存储，重复的覆盖               
            }            
        }
        $geo_array  = array_values($geo_array);
        $geo_arrays =json_encode($geo_array,JSON_UNESCAPED_UNICODE);
        $this->ajaxReturn($geo_arrays);
    }
   
 

}
