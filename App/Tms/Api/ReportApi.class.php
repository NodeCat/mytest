<?php
namespace Tms\Api;
use Think\Controller;
class ReportApi extends CommApi{
//根据客户id和报错类型type保存报错信息
public function report_error(){

	    $id = I('json.user_id');
	    $type = I('json.type');
	    if(empty($id) || empty($type)){
	    	$data = array('status' => '0','msg' => 'error');
	    	$this->ajaxReturn($data,'JSON');
	    }else{
		    $A = A('Common/Order','Logic');
		    //调用Order逻辑，根据客户id查询客户的信息
		    $res = $A->customer(array('id' => $id));	
		    if(empty($res)){
		    	$data = array('status' => '0','msg' => 'error');
		    	$this->ajaxReturn($data,'JSON');
		    }else{
		    	//保存报错信息到数据库
			    $M = M('tms_report_error');
			    $report['type'] = $type;
			    $report['customer_id'] = $id;
			    $report['customer_name'] = $res['name'];
			    $report['customer_address'] = $res['address'];
			    $report['company_id'] = $res['site_id'];
			    $report['company_name'] = $this->getCompany($res['site_id']);
			    $report['line_id'] = $res['line_id'];
			    $report['line_name'] = $res['line_name'];
			    $report['shop_name'] = $res['shop_name'];
			    $report['current_bd_id'] = $res['sale']['id'];
			    $report['current_bd'] = $res['sale']['name'];
			    $report['develop_bd'] = $res['invite_bd'];
			    $report['driver_name'] = I('session.user');
			    $report['driver_mobile'] = I('session.mobile');
			    $report['report_time'] = get_time();
			    $report['created_time'] = get_time();
			    $report['created_user'] = UID;
			    $count = $M->add($report);
			    if($count){
			    	$data = array('status' => '1','msg' => 'OK');
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