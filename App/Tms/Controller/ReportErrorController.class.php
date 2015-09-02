<?php
namespace Tms\Controller;
use Think\Controller;
class ReportErrorController extends \Common\Controller\CommonController
{
	protected $columns = array(
		'type' => '报错类型',
		'customer_name' => '客户姓名',
		'customer_mobile' => '客户手机号',
		'customer_address' => '客户地址',
		'line_name' => '线路',
		'shop_name' => '店铺名',
		'current_bd' => '现属销售',
		'driver_name' => '司机姓名',
		'driver_mobile' => '司机手机号',
        'report_time' => '报错时间',
		'is_deleted' => '状态',
	);
    
    protected $filter = array(
        'is_deleted' => array(
            '0' => '未处理',
            '1'=>'已处理',
        ),
    );

    protected $query = array (
        'te.created_time' => array (    
            'title' => '选择日期',     
            'query_type' => 'between',     
            'control_type' => 'datetime',     
            'value' => '',   
        ),
    );
    
    //设置列表页选项
    protected function before_index() {
        $this->table = array(
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array(
                'name'   => 'Dispatch/showline',
                'show'   => true,
                'new'    => true,
                'target' => '_blank',
                'icon'   => 'view',
                'title'  => '轨迹',
                'text'   => '查看轨迹'
                )
        );
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
        $status = A('Common/Order','Logic')->updateGeo($data);
        if ($status === 0) {
            $res = M('tms_report_error')->where(array('customer_id' => $customer_id))->save($map);
        }
        if ($res) {
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