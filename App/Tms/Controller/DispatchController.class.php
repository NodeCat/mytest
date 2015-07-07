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
        'mark'         => '备注',
         
    );
    protected $car=array(
        'car_type' =>array('平顶金杯','高顶金杯','冷藏金杯','全顺','依维柯','4.2M厢货','4.2M冷藏厢货','5.2M厢货','5.2M冷藏厢货','微面'),
        'car_from' =>array('速派得','云鸟','58','一号货车','京威宏','浩辉平台','雷罡平台','加盟车平台'),
        'warehouse'=>array(7=>'北京白盆窑仓库',6=>'北京北仓',9=>'天津仓库',10=>'上海仓库',5=>'成都仓库',11=>'武汉仓库',13=>'长沙仓库'),
    );
	public function index(){
        $D=D("TmsSignList");
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //选择仓库
        if(I('post.warehouse')){

            $map1['warehouse']=I('post.warehouse');
            $this->warehouse=$this->car['warehouse'][$map1['warehouse']];
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
            $map['userid'] = NULL;
            }
        }
        $this->assign('car',$this->car);
        $list=$D->relation('TmsUser')->where($map)->group('userid')->order('updated_time DESC')->select();
        $M = M('tms_delivery');
        unset($map);
        $map['created_time'] = array('between',$start_date.','.$end_date);
        foreach ($list as $key => &$value2) {
            $value2['warehouse']=$this->car['warehouse'][$value2['warehouse']];//把仓库id变成名字            
            $map['mobile'] = $value2['mobile'];
            $data = $M->where($map)->order('created_time DESC')->select();
            foreach ($data as $value3) {
                $lines .= '［'. $value3['line_name'].'］';
            }
            if($lines==NULL){
                $lines='无';
            }
            $value2['line_name'] = $lines;
            $lines=NULL;    
        }
        
        $this->assign('list',$list);
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
        $M  =  D('TmsSignList');
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //选择是哪个仓库
        if(I('post.warehouse')){

            $map1['warehouse']=I('post.warehouse');
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

        $result = $M->relation(TRUE)->where($map)->group('userid')->order('updated_time DESC')->select();
        //把仓库id变成名字 
        $M = M('tms_delivery');
        unset($map);
        $map['created_time'] = array('between',$start_date.','.$end_date);
        foreach ($result as $key => &$value2) {
            $value2['warehouse']=$this->car['warehouse'][$value2['warehouse']];//把仓库id变成名字            
            $map['mobile'] = $value2['mobile'];
            $data = $M->where($map)->order('created_time DESC')->select();
            foreach ($data as $value3) {
                $lines .= '［'. $value3['line_name'].'］';
            }
            if($lines==NULL){
                $lines='无';
            }
            $value2['line_name'] = $lines;
            $lines=NULL;    
        }

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
        $arrays=array();    //回仓列表的数组
        unset($map);
        //查询条件为配送单id
        $map['stock_wave_distribution_detail.pid'] = $res['dist_id'];
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
                    unset($map);
                    $map['pid'] = $stock_bill_out[$n]['id'];
                    //根据出库单id查询出所有出库单详情信息
                    $bill_out_detail = M('stock_bill_out_detail')->where($map)->select();
                    if(!empty($bill_out_detail)){
                        $all_orders = count($bill_out_detail);  //总订单数
                        for($i = 0; $i < count($bill_out_detail); $i++){
                            unset($map);
                            $map['bill_out_detail_id'] =  $bill_out_detail[$i]['id'];
                            $sign_qty = M('tms_sign_in_detail')->field('real_sign_qty')->where($map)->find();
                            if(empty($sign_qty)){
                                $delivering++;  //派送中数量加1
                                continue;   //若没有签收信息，不计算仓数量
                            }
                            if($sign_qty == 0){
                                $unsign_orders++;   //拒收单数加1
                            }
                            $sign_orders++; //已签收单数加1
                            $bill_out_qty = $bill_out_detail[$i]['delivery_qty'];  //配送数量
                            $arrays[]]['return_num'] = $bill_out_qty - $sign_qty;   //回仓数量
                            $arrays[]['pro_code'] =  $bill_out_detail[$i]['pro_code'];
                            $arrays[]['pro_name'] =  $bill_out_detail[$i]['pro_name'];
                        }
                        
                    }else{
                        $this->error("没有找到相应的订单");
                    }
                    
                }
            }else{
                $this->error("没有找到相应的订单");
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


