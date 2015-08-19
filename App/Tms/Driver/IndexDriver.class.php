<?php
namespace Tms\Driver;
use Think\Controller;
class IndexDriver extends Controller {
    protected function _initialize() {
        layout('siji');
        C('SITE_TITLE' , '司机配送系统 2.0');
        if (session('?user') && !session('?user.mobile')) {
            $this->redirect('Dispatch/index');exit();
        }

        if(!session('?user.mobile')) {
            if(ACTION_NAME != 'login' && ACTION_NAME != 'logout' && ACTION_NAME !='register') {
                $this->redirect('logout');
            }
        }

        if(defined('VERSION')) {
            $this->ver = '2.0';
            $action = array('delivery','orders','sign','reject','orderlist','report','taskorders','tasksign','signfinished','getpoint');
            if(in_array(ACTION_NAME, $action)) {
                $actionName = ACTION_NAME;
                A('Dist','Driver')->$actionName();
                exit();
            }
        }
    }
    
    public function index() {
        $this->redirect('delivery'); 
    }
    
    //登录
    public function login() {
        if (IS_GET) {
            if(session('?user')) {
                $this->redirect('delivery');
                exit;
            }
            else {
                $this->title = '请您签到';
                $this->display('Index:login');    
            }
        }
        if (IS_POST) {
            if(session('?user')) {
                $this->redirect('delivery');
                exit;
            }
            $mobile = I('post.code',0);
            if(!preg_match('/^0?1[34587]{1}\d{9}$/',$mobile)){
                $this->error = "您输入的手机号码格式不正确！";
                $this->display('Index:login');
                exit;

            }
            else {
                $user = M('TmsUser')->field('id,username,mobile')->where(array('mobile' => $mobile))->order('created_time DESC')->find();          
                if ($user) { // 如果以前签到过
                    $start_date = date('Y-m-d',NOW_TIME);
                    $end_date = date('Y-m-d',strtotime('+1 Days'));
                    $map['created_time'] = array('between',$start_date.','.$end_date);
                    $map['userid'] = $user['id'];
                    $map['is_deleted'] = '0';
                    $M = M('TmsSignList');
                    $sign = $M->field('id')->order('created_time DESC')->where($map)->find();
                    //如果今天已经签到过了那就改成最新的签到时间
                    if ($sign) {
                        $M->save(array('id' => $sign['id'],'updated_time' => get_time()));
                        session('user',$user); //把用户id、姓名、手机号写入session
                        $this->redirect('delivery');
                    } else {
                        if(strtotime($date) < mktime(12,0,0,date('m'),date('d'),date('Y'))) {
                            $data['period'] = '上午';
                        } else {
                            $data['period'] = '下午';
                        }
                        $data['userid'] = $user['id'];
                        $data['created_time'] = get_time();
                        $data['updated_time'] = get_time();
                        $M->add($data);//否则就签到
                        session('user',$user);
                        $this->redirect('delivery');
                    }
                } else {
                    $this->mobile = $mobile;
                    $this->register();
                }
                    
            }
        }
    }

    // 注销
    public function logout() {
        session(null);
        session('[destroy]');
        $this->redirect('login');
    }

    // 修改个人信息
    public function update(){
        if (IS_GET) {
            $this->person();
            exit();  
        }
        if (IS_POST) {
            $code     = I('post.mobile','');
            $name     = I('post.username');
            $num      = I('post.car_num');
            $car_type = I('post.car_type');
            $car_from = I('post.car_from');
            $storge   = I('post.warehouse');
            if(empty($code) || empty($name) || empty($num)) {
                $this->error ='请正确的填写修改信息';
                $this->person();
                exit();
            }
            $data = I('post.');
            $data['updated_time'] = get_time();
            $data['id'] = session('user.id');
            $res = M('TmsUser')->save($data);
            if ($res) {
                $user['username'] = $data['username'];
                $user['mobile']   = $data['mobile'];
                $user['id']       = session('user.id');
                session('user',$user);
                $this->msg='修改成功';
                $this->person();

            } else {
                $this->error='修改失败!';
                $this->person();
            }
        }  
    }

    // 个人信息
    public function person(){
        $map['mobile'] = session('user.mobile');
        $data = M('TmsUser')->where($map)->order('updated_time')->find();
        $this->title ='个人信息';
        $cat = A('Common/Category','Logic');
        $this->carType = $cat->lists('car_type');
        $this->carFrom = $cat->lists('platform');
        $this->warehouse = A('Wms/Warehouse','Logic')->lists();
        $this->data = $data;
        $this->display('Driver/person');
    }

    //司机第一次信息登记
    public function register(){
        $cat = A('Common/Category','Logic');
        $this->carType = $cat->lists('car_type');
        $this->carFrom = $cat->lists('platform');
        $this->warehouse = A('Wms/Warehouse','Logic')->lists();
        if (IS_GET) {
            if(session('?user')) {
                 $this->redirect('delivery');
                 exit;
            }
            else{
                $this->title = '请填写完整的签到信息';
                $this->display('Driver/register'); 
            }   
        }
        if (IS_POST) {
            $mobile = I('post.mobile/d',0) ? I('post.mobile/d',0) : $this->mobile;
            $name   = I('post.username');
            $num    = I('post.car_num');
            $car_type = I('post.car_type');
            $car_from = I('post.car_from');
            $ware = I('post.warehouse');
            $data = I('post.');
            $data['mobile'] = $mobile;
            if(!preg_match('/^0?1[34587]{1}\d{9}$/',$mobile) || empty($name) || empty($num)|| empty($car_type) || empty($car_from) || empty($ware)) {
                if (!preg_match('/^0?1[34587]{1}\d{9}$/',$mobile)) {
                    $data['mobile']   = '';
                    $this->error ='请输入正确的手机号码';
                }
                elseif (empty($name)) {
                    $this->error ='请输入你的姓名';
                }
                elseif (empty($num)) {
                    $this->error ='请输入你的车牌号';                
                }
                elseif (empty($car_type)) {
                    $this->error ='请选择你的车型';
                }
                elseif (empty($car_from)) {
                    $this->error ='请选择派车平台';
                }
                elseif (empty($ware)) {
                    $this->error ='请选择你的签到仓库';
                }
                $this->user  = $data;
                $this->title ='请填写完整的签到信息';
                $this->display('Driver/register');
                exit;
            }
            $data['created_time'] = get_time();
            $data['updated_time'] = get_time();
            $res = M('TmsUser')->add($data);
            if ($res) {
                $user['username'] = $data['username'];
                $user['mobile']   = $data['mobile'];
                $user['id']       = $res;
                session('user',$user);
                unset($data);
                if (strtotime(get_time()) < mktime(12,0,0,date('m'),date('d'),date('Y'))) {
                    $data['period'] = '上午';
                } else {
                    $data['period'] = '下午';
                }
                $data['userid'] = $user['id'];
                $data['created_time'] = get_time();
                $data['updated_time'] = get_time();
                M('TmsSignList')->data($data)->add();
                $this->redirect('delivery');
            } else {
                session(null);
                session('[destroy]');
                $this->user  = I('post.');
                $this->title ='请填写正确的信息!';
                $this->display('Driver/register');
            }
        }  
    }

    /*
     * 功能：根据配送单id 生成相应的客退入库单
     * @para：$dist_id,配送单id
     * $return: null
    */
    public function deliverGoods(){
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
                                $batch = $A->get_long_batch($stock_bill_out[$n]['code'],$val['pro_code']);
                                break;
                            case '3':
                                //若已经拒收
                                $real_sign_qty = 0;
                                //获得最近的批次
                                $batch = $A->get_lasted_batch($stock_bill_out[$n]['code'],$val['pro_code']);
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
                        $v['batch'] = isset($batch) ? $batch : '';
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

    //根据客户id和报错类型type保存报错信息
    public function reportError(){
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
