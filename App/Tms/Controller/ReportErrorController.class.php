<?php
namespace Tms\Controller;
use Think\Controller;
class ReportErrorController extends Controller{
	protected $columns = array(
		'type' => '报错类型',
		'customer_name' => '客户姓名',
		'customer_mobile' => '客户手机号',
		'customer_address' => '客户地址',
		'company_name' => '系统',
		'line_name' => '线路',
		'shop_name' => '店铺名',
		'current_bd' => '现属销售',
		'driver_name' => '司机姓名',
		'driver_mobile' => '司机手机号',
		'report_time' => '报错时间',
	);
	public function index(){
		$this->title = "导出位置报错信息";
		$this->display('export');
	}
	public function export(){
		$this->title = "导出位置报错信息";
		$this->display();
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
                $report['current_bd_id'] = $res['sale']['id'];
                $report['current_bd'] = $res['sale']['name'];
                $report['develop_bd'] = $res['invite_bd'];
                $report['driver_name'] = session('user.username');
                $report['driver_mobile'] = session('user.mobile');
                $report['report_time'] = get_time();
                $report['created_time'] = get_time();
                $report['created_user'] = UID;
                $count = $M->add($report);
                if($count){
                    $data = array('status' => '1','msg' => '报错成功');
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

	//根据选择的日期区间导出位置报错信息到excel表格
	public function export_excel(){
		$start_time = I('post.start_time');
		$end_time = I('post.end_time');
		if(empty($start_time) || empty($end_time)){
			$this->error('请选择日期区间');
		}
		$date_zone['report_time'] = array('between',$start_time.','.$end_time);
		$res = M('tms_report_error')->where($date_zone)->select();
		if(!$res){
            $this->error('要导出数据为空！');
        }

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

        for($j  = 0;$j<count($res) ; ++$j){
            $i  = 1;
            foreach ($columns as $key  => $value){
                $Sheet->setCellValue($ary[$i/27].$ary[$i%27].($j+2), $res[$j][$key]);
                ++$i;
            }
        }
        
        date_default_timezone_set("Asia/Shanghai");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = errorReport-".date('Y-m-d-H-i-s',time()).".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
        $objWriter->save('php://output');
	}

    /**
     * 修改商家的地址坐标
     *
     * @author   jt
     */
    public function updatePoint()
    {
        $data = I('post.');
        $customer_id   = $data['id'];
        $map['updated_time'] = get_time();
        $map['updated_user'] = UID;
        $map['is_deleted'] = '1';
        $res = M('tms_report_error')->where(array('customer_id' => $customer_id))->save($map);
        if ($res) {
            $status = A('Common/Order','Logic')->updateGeo($data);
        }
        if ($status === 0) {
            $return = array(
                'status' => 1,
                'msg'    => '地址修改成功',
            );
        } else {
            $return = array(
                'status' => 0,
                'msg'    => '地址修改失败',
            );
        }
        $this->ajaxReturn($return);
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
}