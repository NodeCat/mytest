<?php
namespace Tms\Controller;
use Think\Controller;
class DispatchController extends \Common\Controller\AuthController{

	protected $columns = array (   
        'username'     => '姓名',   
        'mobile'       => '电话',
        'car_num'      => '车牌号',
        'car_type'     => '车型',  
        'car_from'     => '派车平台',
        'warehouse'    => '签到仓库',
        'created_time' => '第一次签到时间',
        'updated_time' => '最后签到时间',
        'period'       => '时间段',
        'line_name'    => '线路',
        'sign_orders'  => '已签收',
        'unsign_orders'=> '已退货',
        'delivering'   => '配送中',
        'sign_finished'=> '已完成',
        'distance'     => '总里程/km',
        'fee'          => '当天运费',
        'mark'         => '备注',
         
    );

	public function index() {
        $cat = A('Common/Category','Logic');
        $this->carType = $cat->lists('car_type');
        $this->carFrom = $cat->lists('platform');
        $this->warehouse = A('Wms/Warehouse','Logic')->lists();
        $D = D("TmsSignList");
        $sign_date = I('post.sign_date', '' , 'trim');
        $start_date = $sign_date ? $sign_date : date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days', strtotime($start_date)));
        $map['created_time'] = array('between',$start_date.','.$end_date);

        //以仓库为单位的签到统计 
        $this->start_date = $start_date;
        //以仓库为单位的签到统计
        $warehouse = session('user.wh_id');
        $car_from  = I('post.car_from', '' ,'trim');
        if ($warehouse || $car_from) {
            if ($warehouse) {
                $map1['warehouse'] = $warehouse;
            }
            if ($car_from) {
                $map1['car_from'] = $car_from;
            }
            $this->ware = $warehouse;
            $this->car_from  = $car_from;
            $M = M('TmsUser');
            //按仓库把用户id取出来
            $user_ids = $M->field('id')->where($map1)->select();
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
        $map['is_deleted'] = 0;
        //把对应仓库的用户签到信息取出来
        $sign_lists=$D->relation('TmsUser')->where($map)->order('created_time DESC')->select();
        unset($map);
        $M = M('tms_delivery');
        $A = A('Tms/List','Logic');
        foreach ($sign_lists as $key => &$value) {
            $value['warehouse'] = $this->warehouse[$value['warehouse']];//把仓库id变成名字
            $value['car_type']  = $this->carType[$value['car_type']];
            $value['car_from']  = $this->carFrom[$value['car_from']];
            $map['mobile']      = $value['mobile'];
            $map['created_time'] = array('between',$value['created_time'].','.$value['delivery_time']);//只取得当次签到配送单的
            $map['status'] = '1';
            //$map['type'] = '0';
            //获取司机配送单的线路和id信息
            $delivery_msg = $M->where($map)->field('line_name,dist_id,type')->order('created_time DESC')->select();
            $value['sign_orders']   = 0;// 已签收
            $value['unsign_orders'] = 0;// 以退货
            $value['sign_finished'] = 0;// 已完成
            $value['delivering']    = 0;// 配送中  
            // 把配送单线路和配送单id遍历出来
            foreach ($delivery_msg as $val) {
                if ($val['type'] == '0') {
                    $delivery = $A->deliveryCount($val['dist_id']);
                    $value['sign_orders']   += $delivery['delivery_count']['sign_orders'];
                    $value['unsign_orders'] += $delivery['delivery_count']['unsign_orders'];
                    $value['sign_finished'] += $delivery['delivery_count']['sign_finished'];
                    $value['delivering']    += $delivery['delivery_count']['delivering'];
                }            
                if (empty($val['line_name'])) {// 配送路线为空就跳过
                    continue;
                }
                $lines .= '［'. $val['line_name'].'］<br/>';// 把路线加在一起
            }
            if($lines == NULL){
                $lines = '无';
            }
            $value['line_name'] = $lines;// 保存路线
            $lines = NULL;// 清空上一配送单路线
            //查看是否有报错
            unset($map);
            $map['driver_mobile'] = $value['mobile'];
            $map['is_deleted']    = '0';
            $map['created_time']  = array('between',$value['created_time'].','.$value['report_error_time']);
            $res = M('tms_report_error')->field('id')->where($map)->find();
            if ($res) {
                $value['report_error'] = '1';
            }
        }
        $this->assign('car',$this->car);
        $this->assign('list',$sign_lists);
        $this->display('driverlist'); 
    }

    // 司机轨迹页面的的输出
    public function showLine() {
        $id = I('get.id');
        $mobile = I('get.mobile');
        $sign_msg = M('tms_sign_list')->find($id);
        $map['status'] = '1';
        //$map['type']   = '0';
        $map['created_time'] = array('between',$sign_msg['created_time'].','.$sign_msg['delivery_time']);
        $map['mobile'] = $mobile ;
        $line = M('tms_delivery')->field('line_name')->where($map)->select();
        $i = 0;
        foreach ($line as $val) {
            if (empty($val['line_name'])) {// 配送路线为空就跳过
                continue;
            }
            if ($i==0) {
                $lines = $val['line_name'];// 把路线加在一起
                $i++;
            } else {
                $lines .= '、'. $val['line_name'];
            }
            
        }
        $this->lines = $lines;
        $key = $id.$mobile;
        $location = S(md5($key));
        $A = A('Tms/List','Logic');
        $customerAddress = $A->getCustomerAddress($mobile,$id);
        // dump($customerAddress);exit;
        $this->time = $A->timediff($sign_msg['delivery_time'],$sign_msg['delivery_end_time']);
        $this->distance = $sign_msg['distance'];
        $this->customer_count = $customerAddress['customer_count'];
        $this->assign('address',$customerAddress['geo_arrays']);
        $this->assign('points',$location['points']);
        $this->display('line');
    }

     //导出司机信息
    public function export() {
        $cat = A('Common/Category','Logic');
        $this->carType = $cat->lists('car_type');
        $this->carFrom = $cat->lists('platform');
        $this->warehouse = A('Wms/Warehouse','Logic')->lists();
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
        $D = D("TmsSignList");
        $sign_date = I('post.sign_date', '' , 'trim');
        $start_date = $sign_date ? $sign_date : date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days', strtotime($start_date)));
        $map['created_time'] = array('between',$start_date.','.$end_date);
        $this->start_date = $start_date;
        //以仓库为单位的签到统计
        $warehouse = session('user.wh_id');
        $car_from  = I('post.car_from', '' ,'trim');
        if ($warehouse || $car_from) {
            if ($warehouse) {
                $map1['warehouse'] = $warehouse;
            }
            if ($car_from) {
                $map1['car_from'] = $car_from;
            }
            $this->warehouse = $warehouse;
            $this->car_from  = $car_from;
            $M=M('TmsUser');
            //按仓库把用户id取出来
            $user_ids = $M->field('id')->where($map1)->select();
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
        $map['is_deleted'] = 0;
        $sign_lists=$D->relation('TmsUser')->where($map)->order('created_time DESC')->select();
        unset($M);
        $M = M('tms_delivery');
        unset($map);
        unset($value);
        unset($val);
        $A = A('Tms/List','Logic');
        foreach ($sign_lists as $key => &$value) {
            $value['warehouse'] = $this->warehouse[$value['warehouse']];//把仓库id变成名字
            $value['car_type']  = $this->carType[$value['car_type']];
            $value['car_from']  = $this->carFrom[$value['car_from']];            
            $map['mobile'] = $value['mobile'];
            $map['created_time'] = array('between',$value['created_time'].','.$value['delivery_time']);
            $map['status'] = '1';
            //$map['type']   = '0';
            //获取司机配送单的线路和id信息取出来
            $delivery_msg = $M->where($map)->field('line_name,dist_id,type')->order('created_time DESC')->select();
            $value['sign_orders']   = 0;// 已签收
            $value['unsign_orders'] = 0;// 以退货
            $value['sign_finished'] = 0;// 已完成
            $value['delivering']    = 0;// 配送中  
            // 把配送单线路和配送单id遍历出来
            foreach ($delivery_msg as $val) {
                 // dump($val);exit;
                if ($val['type'] == '0') {
                    $delivery = $A->deliveryCount($val['dist_id']);
                    $value['sign_orders']   += $delivery['delivery_count']['sign_orders'];
                    $value['unsign_orders'] += $delivery['delivery_count']['unsign_orders'];
                    $value['sign_finished'] += $delivery['delivery_count']['sign_finished'];
                    $value['delivering']    += $delivery['delivery_count']['delivering'];
                }            
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

    //保存运费
    public function saveFee() {
        $fees = I('post.fees');
        if(empty($fees)) {
            $re = array(
                'status' => -1,
                'msg'    => '数据不能为空',
            );
            $this->ajaxReturn($re);
        }
        $D = D('TmsSignList');
        foreach ($fees as $key => $value) {
            $s = $D->where(array('id' => $key))-> save(array('fee' => $value));
            $map['id'] = $key;
            $map['is_deleted'] = 0;
            $sign_info = $D->relation('TmsUser')->where($map)->find();
            unset($map);
            if (empty($sign_info)) {
                continue;
            }
            $map['mobile'] = $sign_info['mobile'];
            $map['created_time'] = array('between',$sign_info['created_time'].','.$sign_info['delivery_time']);//只取得当次签到配送单的
            $map['status'] = '1';
            $map['type'] = 1;
            $delivery_msg = M('tms_delivery')->where($map)->field('dist_id')->select();
            unset($map);
            if (empty($delivery_msg)) {
                continue;
            }
            $task_ids = array();
            foreach ($delivery_msg as $v) {
                $task_ids[] = $v['dist_id'];
            }
            $cou = count($task_ids);
            $task_fee = sprintf('%.2f', $value/$cou);
            $map['id'] = array('in',$task_ids);
            $map['is_deleted'] = 0;
            M('tms_dispatch_task')->where($map)->save(array('delivery_fee' => $task_fee));
            unset($map);
        }
        $re = array(
            'status' => 0,
            'msg'    => '保存成功',
        );
        $this->ajaxReturn($re);
    }

    public function deleteSign()
    {
        $id = I('post.id');
        $D  = D('TmsSignList');
        $res = $D->where(array('id' => $id))-> save(array('is_deleted' => '1'));
        if ($res) {
            $return = array(
                'status' => 1,
                'msg'    => '删除成功',
            );
        } else {
            $return = array(
                'status' => 0,
                'msg'    => '删除失败',
            );
        }
        $this->ajaxReturn($return);
    }
    
}


