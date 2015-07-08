<?php
namespace Tms\Controller;
use Think\Controller;
class DispatchController extends Controller{

	protected $columns = array (   
        'username'     => '姓名',   
        'mobile'       => '电话',
        'car_num'      => '车牌号',
        'car_type'     => '车型',  
        'car_from'     => '派车平台',
        'warehouse'    => '签到仓库',
        'created_time' => '第一次签到时间',
        'updated_time' => '最后签到时间',
        'line_name'    => '线路',
        'sign_orders'  => '已签收',
        'unsign_orders'=> '已退货',
        'delivering'   => '配送中',
        'sign_finished'=> '已完成',
        'mark'         => '备注',
         
    );
    protected $car=array(
        'car_type' =>array('平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货','微面'),
        'car_from' =>array('速派得','云鸟','58','一号货车','京威宏','浩辉平台','雷罡平台','加盟车平台','北京汇通源国际物流有限公司','自有车'),
        'warehouse'=>array(7=>'北京白盆窑仓库',6=>'北京北仓',9=>'天津仓库',10=>'上海仓库',5=>'成都仓库',11=>'武汉仓库',13=>'长沙仓库'),
    );
	public function index() {
        $D=D("TmsSignList");
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //以仓库为单位的签到统计
        if(I('post.warehouse')) {
            $map1['warehouse']=I('post.warehouse');
            $this->warehouse=$this->car['warehouse'][$map1['warehouse']];
            $M=M('TmsUser');
            //按仓库把用户id取出来
            $user_ids=$M->field('id')->where($map1)->select();
            foreach ($user_ids as $value) {
                foreach ($value as $val) {
                    $userid[]=$val;
                }
            }
            if(!empty($user_ids)) { 
                $map['userid'] = array('in',$userid);           
            }
            else {
                $map['userid'] = NULL;
            }
        }
        //把对应仓库的用户签到信息取出来
        $sign_lists=$D->relation('TmsUser')->where($map)->group('userid')->order('updated_time DESC')->select();
        unset($M);
        $M = M('tms_delivery');
        unset($map);
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $map['status'] = '1';
        unset($value);
        unset($val);
        $A = A('Tms/List','Logic');
        foreach ($sign_lists as $key => &$value) {
            $value['warehouse'] = $this->car['warehouse'][$value['warehouse']];//把仓库id变成名字            
            $map['mobile']      = $value['mobile'];
            //获取司机配送单的线路和id信息
            $delivery_msg = $M->where($map)->field('line_name,dist_id')->order('created_time DESC')->select();
            $value['sign_orders']   = 0;// 已签收
            $value['unsign_orders'] = 0;// 以退货
            $value['sign_finished'] = 0;// 已完成
            $value['delivering']    = 0;// 配送中  
            // 把配送单线路和配送单id遍历出来
            foreach ($delivery_msg as $val) {
                $delivery = $A->deliveryCount($val['dist_id']);
                $value['sign_orders']   += $delivery['delivery_count']['sign_orders'];
                $value['unsign_orders'] += $delivery['delivery_count']['unsign_orders'];
                $value['sign_finished'] += $delivery['delivery_count']['sign_finished'];
                $value['delivering']    += $delivery['delivery_count']['delivering'];        
                if(empty($val['line_name'])){// 配送路线为空就跳过
                    continue;
                }
                $lines .= '［'. $val['line_name'].'］<br/>';// 把路线加在一起
            }
            if($lines == NULL){
                $lines = '无';
            }
            $value['line_name'] = $lines;// 保存路线
            $lines = NULL;// 清空上一配送单路线    
        }
        $this->assign('car',$this->car);
        $this->assign('list',$sign_lists);
        $this->display('tms:driverlist'); 
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
        foreach ($columns as $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }
        $D = D('TmsSignList');
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //以仓库为单位的签到统计
        if(I('post.warehouse')) {
            $map1['warehouse']=I('post.warehouse');
            $this->warehouse=$this->car['warehouse'][$map1['warehouse']];
            $M=M('TmsUser');
            //按仓库把用户id取出来
            $user_ids=$M->field('id')->where($map1)->select();
            foreach ($user_ids as $value) {
                foreach ($value as $val) {
                    $userid[]=$val;
                }
            }
            if(!empty($user_ids)) { 
                $map['userid'] = array('in',$userid);           
            }
            else {
                $map['userid'] = NULL;
            }
        }
        //把对应仓库的用户签到信息取出来
        $sign_lists=$D->relation('TmsUser')->where($map)->group('userid')->order('updated_time DESC')->select();
        unset($M);
        $M = M('tms_delivery');
        unset($map);
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $map['status'] = '1';
        unset($value);
        unset($val);
        $A = A('Tms/List','Logic');
        foreach ($sign_lists as $key => &$value) {
            $value['warehouse']=$this->car['warehouse'][$value['warehouse']];//把仓库id变成名字            
            $map['mobile'] = $value['mobile'];
            //获取司机配送单的线路和id信息取出来
            $delivery_msg = $M->where($map)->field('line_name,dist_id')->order('created_time DESC')->select();
            $value['sign_orders']   = 0;// 已签收
            $value['unsign_orders'] = 0;// 以退货
            $value['sign_finished'] = 0;// 已完成
            $value['delivering']    = 0;// 配送中  
            // 把配送单线路和配送单id遍历出来
            foreach ($delivery_msg as $val) {
                 // dump($val);exit;
                $delivery = $A->deliveryCount($val['dist_id']);
                $value['sign_orders'] += $delivery['delivery_count']['sign_orders'];
                $value['unsign_orders'] += $delivery['delivery_count']['unsign_orders'];
                $value['sign_finished'] += $delivery['delivery_count']['sign_finished'];
                $value['delivering'] += $delivery['delivery_count']['delivering'];        
                if(empty($val['line_name'])){// 配送路线为空就跳过

                    continue;
                }
                $lines .= '［'. $val['line_name'].'］';// 把路线加在一起

                }
            if($lines == NULL){
                $lines = '无';
            }
            $value['line_name'] = $lines;// 保存路线
            //$value['delivery_count'] = $delivery['delivery_count'];// 保存司机配送统计
            $lines = NULL;// 清空上一配送单路线    
        }
        unset($value);
        for($j  = 0;$j<count($sign_lists) ; ++$j){
            $i  = 1;
            foreach ($columns as $key  => $value){
                $Sheet->setCellValue($ary[$i/27].$ary[$i%27].($j+2),$sign_lists[$j][$key]);
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
        $values  = 0;   //回款数
        $arrays=array();    //回仓列表的数组
        unset($map);
        $map['id'] = $res['dist_id'];
        //总订单数
        $all_orders = M('stock_wave_distribution')->field('order_count')->where($map)->find();
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
                            $delivery_qty = M('stock_bill_out_detail')->field('delivery_qty')->where($map)->find();
                            //如果计量单位和计价单位相等就取签收数量
                            if($sign_in_detail[$i]['measure_unit'] == $sign_in_detail[$i]['charge_unit']){
                                $sign_qty = $sign_in_detail[$i]['real_sign_qty']; //签收数量
                                $unit = $sign_in_detail[$i]['measure_unit']; //计量单位
                            //如果计量单位和计价单位不相等就取签收重量
                            }else{
                                $sign_qty = $sign_in_detail[$i]['real_sign_wgt']; //签收重量
                                $unit = $sign_in_detail[$i]['charge_unit']; //计价单位
                            }
                            $key  = $sign_in_detail[$i]['pro_code'];    //sku号
                            $arrays[$key]['quantity'] = $delivery_qty - $sign_qty;   //回仓数量
                            $arrays[$key]['pro_name'] =  $sign_in_detail[$i]['pro_name'];   //sku名称
                            $arrays[$key]['unit_id'] = $unit;   //单位
                            $values += $sign_qty * $sign_in_detail[$i]['price_unit'];  //回款
                        }
                        
                    }else{
                        $this->error("没有找到相应的订单");
                    }
                    
                }
            
        }else{
            $this->error("没有找到相应的车单");
        }
            
        $list['dist_id'] = $res['dist_id'];
        $list['values']  = $values;//回款数
        $list['sign_orders'] = $sign_orders;//已签收
        $list['unsign_orders'] = $unsign_orders;//未签收
        $list['delivering'] = $delivering;//派送中
        $this->list = $list;
        $this->back_lists = $arrays;
        $this->title =$res['dist_code'].'车单详情';
        $this->display('tms:orderlist');
    }
   
}


