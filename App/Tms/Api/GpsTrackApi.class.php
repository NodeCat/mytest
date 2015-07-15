<?php
namespace Tms\Api;
use Think\Controller;

/**
 * GPS数据接口
 */
class GpsTrackApi extends CommApi {
	// 获取司机路线信息
	public function getAddress() {

		$M = D('TmsSignList');
		$start_date = date('Y-m-d',NOW_TIME);
        $end_date = date('Y-m-d',strtotime('+1 Days'));
		$map['created_time'] = array('between',$start_date.','.$end_date);
    	$map['userid']  =3;
    	$sign_mg= $M->relation('TmsUser')->order('created_time DESC')->where($map)->find();//获取最新的签到记录
    	$md=$sign_mg['id'].$sign_mg['mobile'];
    	dump($md);
    	S('123',$sign_mg,3360);
   		//$this->display('tms:line');
	}
	// 司机轨迹页面的的输出
	public function showLine() {
		$data=array('116.405467','39.907761');
		$this->assign('data',$data);
		dump(S('123'));
		$this->display('tms:line');
	}

}