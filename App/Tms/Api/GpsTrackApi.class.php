<?php
namespace Tms\Api;
use Think\Controller;

/**
 * GPS数据接口
 */
class GpsTrackApi extends CommApi {
	// 获取司机路线信息
	public function getAddress() {
        $location = I('post.');
		//$location = '{"id":"213","points":[{"time":"afdsf","lng":116.382122,"lat":39.901176},{"time":"nsdfb","lng":116.387271,"lat":39.912501},{"time":"nsdfb","lng":116.398258,"lat":39.904600}]}';
		$data  = json_decode($location,true);
		$A = A('Tms/Gps','Logic'); 
        $i=0;
        $distance = 0;
        static $wgs_pre;
        // 计算路程
		foreach ($data['points'] as $value) {
            $wgs = $A->gcj_decrypt($value['lat'],$value['lng']);
            if($i==0){
                $wgs_pre = $wgs;
                $i++;
                continue;
                }
            $distance_sub=$A->distance($wgs_pre['lat'],$wgs_pre['lng'],$wgs['lat'],$wgs['lng']);
            $wgs_pre = $wgs;
            // dump($distance_sub);
            $distance +=$distance_sub;
		}
        $distance=sprintf('%.3f',$distance/1000);
        //dump($distance);
		$D = D('TmsSignList');
		$start_date = date('Y-m-d',NOW_TIME);
        $end_date   = date('Y-m-d',strtotime('+1 Days'));
		$map['created_time'] = array('between',$start_date.','.$end_date);
        $map['userid'] = $data['id'];
    	//$map['userid'] = 3;
    	$sign_mg = $D->relation('TmsUser')->order('created_time DESC')->where($map)->find();//获取最新的签到记录
        unset($map);
        $map['id'] = $sign_mg['id'];
        $map['distance'] = $distance;
    	$D->save($map);// 把路程写入签到表
        $key=$sign_mg['id'].$sign_mg['mobile'];
    	S(md5($key),$location,3360);
   		//$this->display('tms:line');
	}
	// 司机轨迹页面的的输出
	public function showLine() {
		$id     = I('get.id');
        $mobile = I('get.mobile');
        $key    = $id.$mobile;
        $location = S(md5($key));
        //$location = json_decode($location,true);
        $customerAddress = A('Tms/List','Logic')->getCustomerAddress($mobile,$id);
        // dump($customerAddress);
        // dump($location['points']);
        $this->assign('address',$customerAddress);
		$this->assign('points',$location['points']);
		$this->display('tms:line');
	}

    

}