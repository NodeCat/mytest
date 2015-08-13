<?php
namespace Tms\Controller;
use Think\Controller;
class IndexController extends Controller {
    protected $car=array(
        'car_type' =>array(0=>'请选择你的车型','平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货','7.6M厢货','微面'),
        'car_from' =>array(0=>'请选择派车平台','速派得','云鸟','58','一号货车','京威宏','浩辉平台','雷罡平台','加盟车平台','北京汇通源国际物流有限公司','自有车'),
        'warehouse'=>array(0=>'请选择签到仓库',7=>'北京白盆窑仓库',8=>'北京北仓',9=>'天津仓库',10=>'上海仓库',5=>'成都仓库',11=>'武汉仓库',13=>'长沙仓库'),
    );

    protected function _initialize() {
        layout('siji');

        if (session('?user') && !session('?user.mobile')) {
            $this->redirect('Dispatch/home');exit();
        }

        if(!session('?user.mobile')) {
            if(ACTION_NAME != 'login' && ACTION_NAME != 'logout' && ACTION_NAME !='register') {
                $this->redirect('logout');
            }
        }

        if(defined('VERSION')) {
            $this->ver = '2.0';
            $action2 = array('delivery','orders','sign','reject','orderList','report');
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
                    $map['is_deleted'] = '0';
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
                        if(strtotime($date) < mktime(12,0,0,date('m'),date('d'),date('Y'))) {
                            $userid['period'] = '上午';
                        } else {
                            $userid['period'] = '下午';
                        }
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
        $userid     = M('tms_user')->field('id')->where($map)->find();
        $this->userid = $userid['id'];
        $map['type']   = '0';
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
                $user['mobile']   = $data['mobile'];
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
                if(strtotime($date) < mktime(12,0,0,date('m'),date('d'),date('Y'))) {
                    $data['period'] = '上午';
                } else {
                    $data['period'] = '下午';
                }
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
            $dist_logic = A('Tms/Dist','Logic');
            foreach ($orderList as &$val) {
                $final_sum = 0;
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
                }
                $val['pay_status'] = $s;
                $val['geo'] = json_decode($val['geo'],TRUE);
                foreach ($val['detail'] as &$v) {
                    if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                        $val['quantity'] +=$v['actual_quantity'];
                        $v['delivery_quantity'] = $v['quantity'];
                        $v['quantity'] = $v['actual_quantity'];
                        $v['sum_price'] = $v['actual_sum_price'];
                        $final_sum += $v['actual_sum_price'];
                    }
                    else {
                        $val['quantity'] +=$v['quantity'];
                    }
                }
                if($val['status_cn'] == '已签收' || $val['status_cn'] == '已完成' || $val['status_cn'] == '已回款') {
                    $val['receivable_sum'] = $final_sum - $val['minus_amount'] - $val['pay_reduce'] + $val['deliver_fee'];
                } elseif ($val['status_cn'] == '已退货') {
                    $val['receivable_sum'] = 0;
                } else {
                    $val['receivable_sum'] = $val['final_price'];
                }
                //抹零
                if ($val['status_cn'] != '已签收' && $val['status_cn'] != '已完成' && $val['status_cn'] != '已回款') {
                    $val['deal_price'] = $dist_logic->wipeZero($val['final_price']);
                }
                $val['printStr'] = A('Tms/PrintBill', 'Logic')->printBill($val);
                $orders[$val['user_id']][] = $val;
            }
            $this->data = $orders;
            //提货单ID和订单ID，用于签收后自动展开
            $oid = I('get.oid/d',0);
            $this->id  = $id;
            $this->oid = $oid;
        }
        $this->title = "客户签收";
        //电子签名保存接口
        $this->signature_url = C('TMS_API_PATH') . '/SignIn/signature';
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
        $id = I('post.code',0);
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
            $dist = $M->field('id,mobile,order_count')->where($map)->find();// 取出当前提货单信息
            unset($map['dist_id']);
            unset($map['type']);
            $map['mobile'] = session('user.mobile');
            $dist_all = $M->field('id,mobile,dist_id,order_count,type')->where($map)->order('created_time DESC')->select();//取出当前司机所有配送单信息
            unset($map);
            if (!empty($dist)) {//若该配送单已被认领
                if ($dist['mobile'] == session('user.mobile')) {//如果认领的司机是同一个人
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
                    if ($status == '2') {//如果别人提的还是已装车，那就还可以提
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
            $map['id'] = $id;
            $A = A('Common/Order','Logic');
            $dist = $A->distInfo($map);
            unset($map);
            //if($id != $dist['dist_number']) {
            if(empty($dist)) {
                $this->error = '提货失败，未找到该单据';
            }

            if ($dist['status'] == '2') {//已发运的单据不能被认领
                //$this->error = '提货失败，该单据已发运';
            }
            $ctime = strtotime($dist['created_time']);
            $start_date1 = date('Y-m-d',strtotime('-1 Days'));
            $end_date1 = date('Y-m-d',strtotime('+1 Days'));

            if ($ctime < strtotime($start_date1) || $ctime > strtotime($end_date1)) {
                //$this->error = '提货失败，该配送单已过期';
            }
            // 用配送单id获取订单详情
            $map['dist_id'] = $id;
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $map['itemsPerPage'] = $dist['order_count'];
            $orders = $A->order($map);
            unset($map);
            if (empty($this->error)) {
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
                    if ($va['type'] == '0') {
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
                    } elseif ($va['type'] == '1') {
                        $status = '4';
                        break;
                    }
                }

                $res = $M->add($data);
                // 设置订单状态
                $map['status']  = '8';//已装车
                $map['cur']['name'] = '司机'.session('user.username').session('user.mobile');
                $map['driver_name'] = session('user.username');
                $map['driver_mobile'] = session('user.mobile');
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
                    if ($status=='4') {
                        $map['updated_time'] = $data['updated_time'];
                        $map['created_time'] = $data['created_time'];
                        $map['userid']       = $user_data['id'];
                        $M->add($map);
                        unset($map);
                        unset($status);
                    }
                    $map['is_deleted'] = '0';
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
                } else {
                    $this->error = "提货失败";
                }
            }
        } else {
          $this->error = '提货失败,提货码不能为空';
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


    /*
     * 功能：根据配送单id 生成相应的客退入库单
     * @para：$dist_id,配送单id
     * $return: null
    */
    public function deliver_goods(){
        //配送单id
        $dist_id = I('get.dist_id/d',0);
        $fms_list = A('Fms/List','Logic');
        if(!empty($dist_id)){
            $is_can = $fms_list->can_pay($dist_id);
            if ($is_can == 1) {
                $this->error("此车单没有退货，无需交货。");
            }
            if ($is_can == 2) {
                $this->error("已经交货，无需再次交货。");
            }
            
        }else{
            $this->error("没有找到相应的车单");
        }
        unset($map);
        $map['dist_id'] = $dist_id;
        $res = A('Wms/StockOut', 'Logic')->bill_out_list($map);
        $stock_bill_out = $res['list'];
        foreach ($stock_bill_out as $value) {
            if ($value['sign_status'] == 2 || $value['sign_status'] == 3 || $value['sign_status'] == 4) {
                continue;
            } else {
                $this->error('此车单中有正在派送中的订单，请签收或拒收后再提出交货申请。');exit;
            }
        }
        //获得配送单号
        unset($map);
        $map['id'] = $dist_id;
        $map['is_deleted'] = 0;
        $dist_code = M('stock_wave_distribution')->field('dist_code')->where($map)->find();
        $dist_code = $dist_code['dist_code'];
        
        //若查出的出库单信息非空
        if(!empty($stock_bill_out)){
            $Min = D('Wms/StockIn');    //实例化Ｗms的入库单模型
            for($n = 0; $n < count($stock_bill_out); $n++){
                //创建拒收入库单
                unset($bill);
                $bill['pid'] = $dist_id;
                $bill['refer_code'] = $stock_bill_out[$n]['code'];//关联单据为出库单号
                $bill['code'] = get_sn('rejo');   //生成拒收入库单号
                $bill['status'] = '21';     //待收货状态
                $bill['batch_code'] = '';//get_batch($bill['code']); //获得批次
                $bill['wh_id'] = $stock_bill_out[$n]['wh_id'];  //仓库id
                $bill['company_id'] = $stock_bill_out[$n]['company_id'];   
                $bill['partner_id'] = '';   //供应商
                $bill['type'] = 7;  //入库类型为拒收入库单
                $bill['created_user'] = 2;   //uid默认为2
                $bill['created_time'] = get_time();
                $bill['updated_user'] = 2;   //uid默认为2
                $bill['updated_time'] = get_time();
                unset($map);
                $map['pid'] = $stock_bill_out[$n]['id'];
                $map['is_deleted'] = 0;
                //根据出库单id查询出所有出库单详情信息
                $bill_out_detail = $stock_bill_out[$n]['detail'];
                if(!empty($bill_out_detail)){
                    $A = A('Tms/List','Logic');
                    foreach ($bill_out_detail as $key => $val) {
                        $real_sign_qty = 0; //签收数量先置为0
                        $batch = '';
                        
                        switch ($stock_bill_out[$n]['sign_status']) {
                            case '0':
                                //若是已分拨状态
                                $this->error('此车单中有已分拨的订单，请提货并签收或拒收后再提出交货申请。');exit;
                                break;
                            case '1':
                                //若是已装车状态
                                $this->error('此车单中有正在派送中的订单，请签收或拒收后再提出交货申请。');exit;
                                break;
                            case '2':
                                //若是已签收状态
                                unset($map);
                                $map['bill_out_detail_id'] = $val['id'];
                                $map['is_deleted'] = 0;
                                $sign_data = M('tms_sign_in_detail')->where($map)->select();
                                if(!empty($sign_data)) {
                                    $real_sign_qty = $sign_data[0]['real_sign_qty']; //签收数量
                                }
                                //获得最久远的批次
                                $batch = $A->get_long_batch($dist_code,$val['pro_code']);
                                break;
                            case '3':
                                //若已经拒收
                                $real_sign_qty = 0;
                                //获得最近的批次
                                $batch = $A->get_lasted_batch($dist_code,$val['pro_code']);
                                break;
                            case '4':
                                //若是已经完成
                                $this->error('此车单已经完成，无需交货。');exit;
                                break;
                            default:
                                # code...
                                break;
                        }
                        
                        //若没有退货
                        if(($val['delivery_qty'] - $real_sign_qty) <= 0){
                            continue;
                        }
                        $v['pro_code'] = $val['pro_code'];
                        $v['pro_name'] = $val['pro_name'];
                        $v['pro_attrs'] = $val['pro_attrs'];
                        $v['pro_uom'] = isset($val['measure_unit']) ? $val['measure_unit'] : '';     //计量单位
                        $v['expected_qty'] = $val['delivery_qty'] - $real_sign_qty; //写入预期入库数量
                        $v['prepare_qty'] = 0;
                        $v['done_qty'] = 0;
                        $v['wh_id'] = $bill['wh_id'];
                        $v['refer_code'] = $bill['refer_code']; //写入相关联的出库单号
                        $v['pid'] = $bill['id'];
                        $v['price_unit'] = isset($val['price']) ? $val['price'] : 0;  //单价
                        $v['created_time'] = get_time();
                        $v['updated_time'] = get_time();
                        $v['created_user'] = 2;   //uid默认为2
                        $v['updated_user'] = 2;   //uid默认为2
                        $v['batch'] = $batch;
                        $bill['detail'][] = $v;
                        
                        $container['refer_code'] = $bill['code'];   //关联拒收入库单号
                        $container['pro_code'] = $val['pro_code'];
                        $container['batch'] = isset($batch) ? $batch : '';
                        $container['wh_id'] = $bill['wh_id'];
                        $container['location_id'] = '';
                        $container['qty'] = $v['expected_qty'];
                        $container['created_time'] = get_time();
                        $container['updated_time'] = get_time();
                        $container['created_user'] = 2;   //uid默认为2
                        $container['updated_user'] = 2;   //uid默认为2
                        $M = M('stock_bill_in_container');
                        $s = $M->add($container);    //写入拒收入库单详情表的详情表
                    }  
                    if(!empty($bill['detail'])){
                        $res = $Min->relation('detail')->add($bill); //写入拒收入库单 
                    }  
                }       
            }
        }else{
            $this->error("没有找到相应的订单");
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
        $map['type'] = '0';
        unset($M);
        $M = M('tms_delivery');
        $data = $M ->where($map)->select();
        unset($map);
        $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
        $A = A('Common/Order','Logic');
        $geo_array=array();
        foreach ($data as $key => $value) {
            $map['dist_id'] = $value['dist_id'];
            if (defined('VERSION')) {
                $A = A('Tms/Dist','Logic');
                $bills = $A->billOut($map);
                $orders = $bills['orders'];
            } else { 
                $map['itemsPerPage'] = $value['order_count'];
                $orders = $A->order($map);
            }
            foreach ($orders as $keys => $values) {
                if (defined('VERSION')) {
                    $values = $values['order_info'];
                }
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


    //任务提货
    private function taskDelivery()
    {   
        $id             = $this->dist_id;
        $map['dist_code'] = $id;
        $map['status']  = '1';
        $start_date     = date('Y-m-d',NOW_TIME);
        $end_date       = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $dist = M('tms_delivery')->field('id,mobile,dist_id,user_id')->where($map)->find();// 取出当前提货单信息
        unset($map['dist_code']);
        $map['mobile'] = session('user.mobile');
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
            //$this->error = '领单失败,该单不能被认领';
        } elseif ($ctime < strtotime($start_date1) || $ctime > strtotime($end_date1)) {
            //$this->error = '领单失败，该任务单已过期';
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
                    if($va['type']=='0') {
                        $status = '4';
                        break;
                    } elseif ($va['type']=='1') {
                        $task = M('tms_dispatch_task')->field('id')->where(array('status' => array('neq','5')))->find($va['dist_id']);
                        if ($task) {
                            $status = '3';
                            break;
                        } else {
                            $status = '4';
                        }

                    }
                }
                $user = M('tms_user')->field('id,car_type,car_from')->where(array('mobile' => $data['mobile']))->find();
                M('tms_dispatch_task')->save(array('id' => $task['id'],'status' => '4','driver_id' => $user['id']));
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
        $this->display('tms:taskorders');
    }

    /*public function taskStart()
    {
        $dist_id = I('post.id');
        $res = M('tms_task_node')->where(array('pid' => $dist_id))->save(array('status' => '2'));
        if ($res) {
            $return = array(
                'status' => 1,
                'msg'    => '任务开始成功',
            );
        } else {
            $return = array(
                'status' => 0,
                'msg'    => '任务开始失败,请重新开始',
            );
        }
        $this->ajaxReturn($return);
    }*/

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
        //dump($point);exit;
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
    

    //根据客户id和报错类型type保存报错信息
    public function report_error(){
        $id = I('post.id');
        $type = I('post.type');
        if(empty($id) || empty($type)){
            $data = array('status' => '0','msg' => '参数不能为空');
            $this->ajaxReturn($data,'JSON');
        }else{
            $A = A('Common/Order','Logic');
            //调用Order逻辑，根据客户id查询客户的信息
            $res = $A->customer(array('id' => $id));
            if(empty($res)){
                $data = array('status' => '0','msg' => '没有此客户');
                $this->ajaxReturn($data,'JSON');
            }else{
                //保存报错信息到数据库
                $M = M('tms_report_error');
                if (is_array($type)) {
                    $report['type'] = implode(',', $type);
                } else {
                    $report['type'] = $type;
                }
                $report['customer_id'] = $id;
                $report['customer_name'] = $res['name'];
                $report['customer_address'] = $res['address'];
                $report['customer_mobile'] = $res['mobile'];
                $report['company_id'] = $res['site_id'];
                $report['company_name'] = $this->getCompany($res['site_id']);
                $report['line_id'] = $res['line_id'];
                $report['line_name'] = $res['line_name'];
                $report['shop_name'] = $res['shop_name'];
                $report['current_bd_id'] = isset($res['sale']['id']) ? $res['sale']['id'] : '0';
                $report['current_bd'] = $res['sale']['name'];
                $report['develop_bd'] = $res['invite_bd'];
                $report['driver_name'] = session('user.username');
                $report['driver_mobile'] = session('user.mobile');
                $report['report_time'] = get_time();
                $report['created_time'] = get_time();
                $report['created_user'] = UID;
                $count = $M->add($report);
                if($count){
                    //获取司机当前的签到id
                    $id = M('tms_sign_list')
                        ->field('tms_sign_list.id')
                        ->join('tms_user ON tms_user.id = tms_sign_list.userid')
                        ->where(array('tms_user.mobile' => session('user.mobile')))
                        ->order(array('tms_sign_list.created_time' => 'DESC'))
                        ->find();
                    M('tms_sign_list')->save(array('id' => $id['id'],'report_error_time' => $report['report_time']));
                    $data = array('status' => '1','msg' => '报错成功');
                    $this->ajaxReturn($data);
                } else {
                    $data = array('status' => '0','msg' => '报错失败');
                    $this->ajaxReturn($data);
                }
            }
        }
    }
    //根据系统id获得系统名字
    public function getCompany($id){
        $name = M('company')->field('name')->find($id);
        return $name['name'];
    }

}
