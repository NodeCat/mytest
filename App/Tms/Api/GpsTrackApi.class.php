<?php
namespace Tms\Api;
use Think\Controller;

/**
 * GPS数据收集接口
 *
 * @author  jt
 */
class GpsTrackApi extends CommApi {
	// 获取司机路线信息
	public function getAddress() {
        $data = I('post.location');
        $data = htmlspecialchars_decode($data);
		//$data = '{"id":"3","points":[{"time":"afdsf","lng":116.382122,"lat":39.901176},{"time":"nsdfb","lng":116.387271,"lat":39.912501},{"time":"2015-07-18 01:58:28","lng":116.398258,"lat":39.904600}]}';
		$data  = json_decode($data,true);
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
            $distance +=$distance_sub;
		}
        $distance=sprintf('%.3f',$distance/1000);
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
        $map['delivery_end_time'] = $value['time'];//配送完成时间
    	$res = $D->save($map);// 把路程写入签到表和时间
        if($res) {
            $key=$sign_mg['id'].$sign_mg['mobile'];
    	    S(md5($key),$data,3600*24*5);
            $return = array('status' =>'1', 'message' => '成功');
            $this->ajaxReturn($return);
        } else {
            $return = array('status' => '0', 'message' => '路线获取失败' );
            $this->ajaxReturn($return);
        }
  	}

    

}