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
        'period'       => '时间段',
        'line_name'    => '线路',
        'sign_orders'  => '已签收',
        'unsign_orders'=> '已退货',
        'delivering'   => '配送中',
        'sign_finished'=> '已完成',
        'ontime'       => '准点率',
        'distance'     => '总里程/km',
        'fee'          => '当天运费',
        'mark'         => '备注',
         
    );

	public function index() {
        $sign_lists = $this->getSignList();
        $this->assign('list',$sign_lists);
        $this->display('driverlist'); 
    }

    /**
     * [getSignList 获取司机列表数据]
     * @return [type] [description]
     */
    protected function getSignList($export = 0)
    {
        //车型、平台、仓库列表
        $cat = A('Common/Category','Logic');
        $this->carType = $cat->lists('car_type');
        $this->carFrom = $cat->lists('platform');
        $this->warehouse = A('Wms/Warehouse','Logic')->lists();
        $D = D("TmsSignList");
        //日期筛选
        $sign_date = I('post.sign_date');
        $start_date = $sign_date ? $sign_date : date('Y-m-d',NOW_TIME);
        $this->start_date = $start_date;
        //平台筛选
        $car_from  = I('post.car_from');
        if ($car_from) {
            $platform = "WHERE tu.car_from = {$car_from}";
        }
        $wh_id = session('user.wh_id');
        $this->car_from  = $car_from;
        //以仓库为单位的签到统计
        $A = A('Tms/List','Logic');
        $sql = "SELECT a.*, tu.*, b.line_name, b.dist_ids, b.type
            FROM (
            SELECT id sid, wh_id swh_id, userid user_id, created_time screated_time, distance,
            DATE( delivery_time ) deliver_date, fee, period
            FROM tms_sign_list
            WHERE DATE(created_time) = '{$start_date}' AND wh_id = {$wh_id} AND is_deleted =0
            )a
            INNER JOIN (
            SELECT user_id, DATE( d.created_time ) deliver_date, d.type type, (
            CASE wd.deliver_time
            WHEN 1 
            THEN  '上午'
            ELSE  '下午'
            END
            )period, GROUP_CONCAT( dist_id ) dist_ids, CONCAT_WS(',',line_name ) line_name
            FROM tms_delivery d
            INNER JOIN stock_wave_distribution wd ON d.dist_id = wd.id
            WHERE DATE(d.created_time) = '{$start_date}' AND d.type = 0 AND wd.is_deleted = 0
            GROUP BY d.user_id, deliver_date, deliver_time
            )b ON a.user_id = b.user_id
            AND a.deliver_date = b.deliver_date
            AND a.period = b.period
            INNER JOIN tms_user tu ON a.user_id = tu.id {$platform}
            ORDER BY a.sid DESC";
        $sign_lists = $D->query($sql);
        //取出所有配送单ID
        $dist_ids = array_column($sign_lists, 'dist_ids');
        $dist_ids = implode(',', $dist_ids);
        $dist_ids = explode(',', $dist_ids);
        //所有配送单详情数据
        $dist_details = A('Wms/Distribution', 'Logic')->getDistDetailsByPid($dist_ids);
        $dist_id_detail = array();
        //配送单ID对应详情列表
        foreach ($dist_details as $key => $value) {
            $dist_id_detail[$value['pid']][] = $value;
        }
        foreach ($sign_lists as &$value) {
            //仓库、车型、平台的中文名称
            $value['warehouse'] = $this->warehouse[$value['swh_id']];
            $value['car_type']  = $this->carType[$value['car_type']];
            $value['car_from']  = $this->carFrom[$value['car_from']];
            //签到记录对应的配送详情列表
            $value['dist_ids'] = explode(',', $value['dist_ids']);
            foreach ($value['dist_ids'] as $va) {
                $details = array_merge($dist_id_detail[$va]);
            }
            //配送状态、准点率统计
            $deliveryStatis = $A->deliveryStatis($details);
            $value['sign_orders']   = $deliveryStatis['sign_orders'];
            $value['unsign_orders'] = $deliveryStatis['unsign_orders'];
            $value['sign_finished'] = $deliveryStatis['sign_finished'];
            $value['delivering']    = $deliveryStatis['delivering'];
            $value['ontime']        = $deliveryStatis['ontime'];
            // 把配送单线路遍历出来
            if ($value['line_name']) {
                $lines = explode(',', $value['line_name']);
                $lines = array_map(function($item) use (&$lines){
                    return '[' . $item .']';
                },$lines);
                $spec = $export ? '' : '<br />';
                $value['line_name'] = implode($spec, $lines);
            } else {
                $value['line_name'] = '无';
            }
        }
        return $sign_lists;
    }
    
    /**
     * [showLine 司机轨迹页面]
     * @return [type] [description]
     */
    public function showLine() {
        $id = I('get.id');
        $mobile = I('get.mobile');
        $sign_msg = M('tms_sign_list')->find($id);
        $map['status'] = '1';
        //$map['type']   = '0';
        $map['created_time'] = array('between',$sign_msg['created_time'].','.$sign_msg['delivery_time']);
        $map['mobile'] = $mobile ;
        $line = M('tms_delivery')->where($map)->getField('line_name',true);
        $lines = implode('、',array_filter($line));
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

    /**
     * [export 导出司机列表数据]
     * @return [type] [description]
     */
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
        $sign_lists = $this->getSignList(1);
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

    /**
     * [saveFee 保存运费]
     * @return [type] [description]
     */
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
            //保存运费到签到列表
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
            //该条签到记录对应的提货记录
            $delivery_msg = M('tms_delivery')->where($map)->field('dist_id,type')->select();
            unset($map);
            if (empty($delivery_msg)) {
                continue;
            }
            $type = $delivery_msg[0]['type'];
            $ids = array_column($delivery_msg, 'dist_id');
            if ($type == 1) {
                //均摊运费到单个任务
                A('Tms/Dispatch','Logic')->avgDeliveryFeeToTask($ids, $value);
            } else {
                //均摊运费到订单
                A('Tms/Dispatch', 'Logic')->avgDeliveryFeeToOrder($ids, $value);
            }
        }
        $re = array(
            'status' => 0,
            'msg'    => '保存成功',
        );
        $this->ajaxReturn($re);
    }

    /**
     * [deleteSign 删除签到记录]
     * @return [type] [description]
     */
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

    /**
     * [signCode 显示打印签到码]
     * @return [type] [description]
     */
    public function signCode()
    {
        $code = A('Tms/Dist', 'Logic')->getSignCode();
        if ($code) {
            $key = 'sign_code_' . $code;
            $wh_id = S($key);
            $warehouse = A('Wms/Distribution', 'Logic')->getWarehouseById($wh_id);
            $this->warehouse = $warehouse;
        } else {
            $code = '获取签到码失败，请联系技术人员';
        }
        $weeks=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        $this->code = $code;
        $this->date = date('Y-m-d',time());
        $this->week = $weeks[date('w')];
        $this->display('Index/sign-code');
    }
    
}


