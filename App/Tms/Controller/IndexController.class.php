<?php
namespace Tms\Controller;
use Think\Controller;
class IndexController extends Controller {
    protected $car=array(
        'car_type'=>array('平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货'),
        'car_from'=>array('速派得','云鸟','58')
);

    protected $columns = array (   
        'username'     => '姓名',   
        'mobile'       => '电话',
        'car_num'      => '车牌号',
        'car_type'     => '车型',  
        'car_from'     => '派车平台',
        'sign_storge'  => '签到仓库',
        'created_time' => '最后签到时间',
        'line'         => '线路',
        'mark'         => '备注',
         
    );

    protected function _initialize(){
        if(!session('?user')) {
            if(ACTION_NAME != 'login' && ACTION_NAME != 'logout' && ACTION_NAME !='register') {
                $this->redirect('logout');
            }
            
        }
    }

    //导出司机信息
    public function export() {
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel(); 
        $i = 1;
        $columns = $this->columns;
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $Sheet = $this->get_excel_sheet($Excel);
        foreach ($columns as $key  => $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }
        $M  =  D('TmsSignList');
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //选择是哪个仓库
        if(I('post.sign_storge')){

            $map1['sign_storge']=I('post.sign_storge');
            $id=M('TmsUser');
            $id=$id->field('id')->where($map1)->select();
            foreach ($id as $value) {
                foreach ($value as $value1) {
                    $id1[]=$value1;
                }
            }
            if($id){
            $map['userid'] = array('in',$id1);
            }else{
            $map['userid']=NULL;
            }
        }



        $result = $M->relation(TRUE)->where($map)->group('userid')->order('created_time DESC')->select();
        $this->filter_list($result);
        for($j  = 0;$j<count($result) ; ++$j){
            $i  = 1;
            foreach ($columns as $key  => $value){
                $Sheet->setCellValue($ary[$i/27].$ary[$i%27].($j+2), $result[$j][$key]);
                ++$i;
            }
        }
        
        if(ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        date_default_timezone_set("Asia/Shanghai"); 
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = ".time().".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007'); 
        $objWriter->save('php://output');
        
    }
    
    protected function get_excel_sheet(&$Excel) {
        $Excel->getProperties()
        ->setCreator("Dachuwang")
        ->setLastModifiedBy("Dachuwang")
        ->setTitle("Dachuwang")
        ->setSubject("Dachuwang")
        ->setDescription("Dachuwang")
        ->setKeywords("Dachuwang")
        ->setCategory("Dachuwang");
        $Excel->setActiveSheetIndex(0);
        $Sheet  =  $Excel->getActiveSheet();          
        $Sheet->getDefaultColumnDimension()->setAutoSize(true);
        $Sheet->getDefaultStyle()->getFont()->setName('Arial');
        $Sheet->getDefaultStyle()->getFont()->setSize(13);
        return $Sheet;
    }

    
protected function filter_list(&$data,$type = '0',$filter = '') {
        if(!is_array($data)) return;
        if(empty($filter)){
            if(empty($this->filter)) {
                $file = strtolower(CONTROLLER_NAME);
                $filter = C($file.'.filter');
            }
            else {
                $filter = $this->filter;
            }
        }
        //反向转换
        if($type == '1') {
            $table = strtolower(CONTROLLER_NAME);
            foreach ($filter as $key => $val) {
                $val = array_flip($val);
                $filter[$table.'.'.$key] = $val ;
                unset($filter[$key]);
            }
        }
        else {
        }
        //二维数组
        if(is_array(reset($data))){
            foreach ($data as $key => $val) {
                foreach ($filter as $k => $v) {
                    if(!empty($v[$data[$key][$k]])) {
                        $data[$key][$k] = $v[$data[$key][$k]];
                    }
                }
            }
        }
        else{//一维数组
            foreach ($filter as $k => $v) {
                if(!empty($v[$data[$k]])) {
                    $data[$k] = $v[$data[$k]];
                }
            }
        }
    }



    public function index(){
        $this->redirect('delivery'); 
    }
    //司机列表显示
    public function lists(){
        $D=D("TmsSignList");
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //选择仓库
        if(I('post.sign_storge')){

            $map1['sign_storge']=I('post.sign_storge');
            $this->option=$map1['sign_storge'];

            $id=M('TmsUser');
            $id=$id->field('id')->where($map1)->select();
            foreach ($id as $value) {
                foreach ($value as $value1) {
                    $id1[]=$value1;
                }
            }
            if($id){
            $map['userid'] = array('in',$id1);
            
            }else{
            $map['userid']=NULL;
            }



        }
        $storge=D('List','Logic');
        $storge=$storge->storge();
        $this->assign('sign_storge',$storge);
        $list=$D->relation('TmsUser')->where($map)->group('userid')->order('created_time DESC')->select();
        $this->assign('list',$list);
        $this->display('tms:list'); 
    }
    //登陆
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
                $M1=M('TmsUser');
                $data=$M1->where($user)->find();                 
                if($data){
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
                    $M=M('TmsSignList');
                    $id=$M->field('id')->where($map)->find();
                    //如果已经签到过了那就改成最新的签到时间
                    if($id){
                        $userid['id']=$id['id'];
                        $M->save($userid);
                        session('user',$user);
                        $this->redirect('delivery');
                    }else{
                        $M->add($userid);//否则就签到

                    }
                }
                else{
                    $this->user=$user;
                    $this->title='信息登记';
                    //仓库里列表
                    $storge=D('List','Logic');
                    $storge=$storge->storge();
                    $this->assign('sign_storge',$storge);
                    //$car_type = array('平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货');
                    $this->assign('car',$this->car);
                    //$this->display('tms:register');
                    $this->register();
                }
                    
            }
        }
    }

    //司机第一次信息登记
    public function register(){

        if(IS_GET){
            if(session('?user')) {
                 $this->redirect('delivery');
            }
            else{
            $this->title = '请填写完整的签到信息';
            //仓库列表
            $storge=D('List','Logic');
            $storge=$storge->storge();
            $this->assign('sign_storge',$storge);
            $this->assign('car',$this->car);
            $this->display('tms:register'); 
            }   
        }
        if(IS_POST){
            $code = I('post.mobile/d',0);
            $name = I('post.username');
            $num  = I('post.car_num');
            $storge=I('post.sign_storge');
            if(empty($code) || empty($name) || empty($num)|| empty($storge)){
                $this->title ='请填写完整的签到信息';
                $this->error ='请补全你的签到信息';
                //仓库列表
                $storge=D('List','Logic');
                $storge=$storge->storge();                   
                $this->assign('sign_storge',$storge);
                $this->assign('car',$this->car);
                $this->display('tms:register');
                exit();
            }
            $date = date('Y-m-d H:i:s',NOW_TIME);
            $data = I('post.');dump($data);
            $data['created_time'] = $date;
            $data['updated_time'] = $date;
            unset($M);
            $M = M('TmsUser');
            $data = $M->create($data);
            if($M->add($data)){

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
                $this->title='请填写正确的信息！';
                //$car_type = array('平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货');
                $this->assign('car',$this->car);
                //仓库列表
                $storge=D('List','Logic');
                $storge=$storge->storge();
                $this->assign('sign_storge',$storge);

                $this->display('tms:register');

            }


        }  
    }
    

    public function logout() {
        session(null);
        session('[destroy]');
        $this->redirect('login');
    }
    public function orders(){
        //layout(false);
        $id = I('get.id',0);
        if(!empty($id)) {
            $M = M('tms_delivery');
            $res = $M->find($id);
            /*
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
            */
            //$map['dist_id'] = $res['id'];
            $map['dist_id'] = $id;
            $map['order_by'] = array('user_id'=>'ASC','created_time' => 'DESC');
            $A = D('Tms/Order','Logic');
            $orders = $A->order($map);
            foreach ($orders as &$val) {
                //`pay_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '支付方式：0货到付款（默认），1微信支付',
                //`pay_status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '支付状态：-1支付失败，0未支付，1已支付',
                switch ($val['pay_status']) {
                    case -1:
                        $s = '未付款';
                        break;
                    case 0:
                        $s = '未付款';
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
        $map['status']   = '6';
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

    public function delivery() {
        $id = I('post.code');
        if(IS_POST && !empty($id)) {
            $map['dist_code'] = $id;
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
                    $this->error = '该单据您已提货';
                }
                else {//如果是另外一个司机认领的，则逻辑删除掉之前的认领纪录
                    $map['id'] = $dist['id'];
                    $data['status'] = '0';
                    $M->where($map)->save($data);
                }
                unset($map);
            }

            //查询该配送单的信息
            $map['dist_number'] = substr($id, 2);
            $A = A('Tms/Order','Logic');
            $dist = $A->distInfo($map);
            if($id != $dist['dist_number']) {
                $this->error = '未找到该单据';
            }

            if($dist['status'] == '2') {//已发运的单据不能被认领
                $this->error = '该单据已发运';
            }
            if(empty($this->error)){
                
                $data['dist_id'] = $dist['id'];
                $data['mobile'] = session('user.mobile');
                $data['order_count'] = $dist['order_count'];
                $data['sku_count'] = $dist['sku_count'];
                $data['line_count'] = $dist['line_count'];
                $data['total_price'] = $dist['total_price'];
                $data['site_src'] = $dist['site_src'];
                $data['dist_code'] = $dist['dist_number'];
                $data['created_time'] = get_time();
                $data['updated_time'] = get_time();
                $data['status'] = '1';
                $lines = $A->line(array('line_ids'=>array($dist['line_id'])));
                $data['line_name'] = $lines[0]['name'];//dump($lines);dump($dist);exit();
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
                    $map['order_id'] = $val['id'];dump($map);
                    $res = $A->set_status($map);
                }
                dump($res);exit();
                unset($map);
                if($res) {
                    $this->msg = "提货成功。";
                }
                else {
                    $this->error = "提货失败";
                }
            }
        }

        //只显示当天的记录
        $map['mobile'] = session('user.mobile');
        $start_date = date('Y-m-d',NOW_TIME);
       // $start_date = date('Y-m-d',strtotime('-3 Days'));

        $end_date = date('Y-m-d',strtotime('+1 Days'));
        
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $this->data = M('tms_delivery')->where($map)->select();
        
        $this->title = '提货扫码';
        $this->display('tms:delivery');  

    }


}