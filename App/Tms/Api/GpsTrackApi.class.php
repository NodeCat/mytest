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
        $data = json_decode($data,true); 
        $start_date = date('Y-m-d',NOW_TIME);
        $end_date   = date('Y-m-d',strtotime('+1 Days'));
        $data['id'] = strtoupper($data['id']);
        if(stripos($data['id'],'D')===0) {//单个任务轨迹
            $type = 1;
        } elseif (stripos($data['id'],'T')===0) {// 签到车次轨迹
            $type = 0;
        }
        $data['id'] = substr($data['id'],1);
        if ($type == 1) {//单个任务轨迹
            $task = M('tms_dispatch_task')->field('id,code,distance')->find($data['id']);
            $key = $task['code'];// 任务号
        } else {//提货或提任务总里程
            $sign_mg = M('tms_user')
            ->alias('A')
            ->join('tms_sign_list B ON A.id = B.userid')
            ->field('A.mobile,B.id,B.distance')
            ->where(array('A.id' => $data['id'],'B.created_time' => array('between',$start_date.','.$end_date)))
            ->order(array('B.created_time' => 'DESC'))
            ->find();
            $key = $sign_mg['id'].$sign_mg['mobile'];// 键名
        }
        if (floatval($sign_mg['distance']) > 0 || floatval($task['distance']) > 0) {
            $data_old = S(md5($key));
            $data['points'] = array_merge($data_old['points'],$data['points']);
        }
        $A = A('Tms/Gps','Logic'); 
        $i = 0;
        $distance = 0;
        static $wgs_pre;
        // 计算路程
        foreach ($data['points'] as $value) {
            $wgs = $A->gcj_decrypt($value['lat'],$value['lng']);
            if($i == 0){
                $wgs_pre = $wgs;
                $i++;
                continue;
                }
            $distance_sub = $A->distance($wgs_pre['lat'],$wgs_pre['lng'],$wgs['lat'],$wgs['lng']);
            $wgs_pre      = $wgs;
            $distance    += $distance_sub;
        }
        $distance = sprintf('%.3f',$distance/1000);
        // 写入路程和时间
        if ($type == 1) {//单个任务轨迹
            $time = A('Tms/List','Logic')->timediff($data['points'][0]['time'],$value['time']);
            $time = json_encode($time);
            $res = M('tms_dispatch_task')->save(array('id' => $task['id'],'distance' => $distance,'take_time' => $time));

        } else {//总里程轨迹
            $res = M('tms_sign_list')->save(array('id' => $sign_mg['id'],'distance' => $distance,'delivery_end_time' => $value['time']));// 把路程和时间写入签到表
        }

        if ($res) {
            S(md5($key),$data,3600*24*5);
            $return = array('status' =>'1', 'message' => '成功');
            $this->ajaxReturn($return);
        } else {
            $return = array('status' => '0', 'message' => '路线获取失败' );
            $this->ajaxReturn($return);
        }
    }

}