<?php
namespace Wms\Api;
use Think\Controller;
//API接口不涉及模版输出，仅返回JSON或XML格式数据，因此不一定得继承Controller
class CommApi extends Controller{
	protected function _initialize(){
        //API返回JSON格式数据，因此关闭模版布局
        layout(FALSE);
        if(!defined('UID')) {
+            define('UID',2);
+        }
        return;
        //仅允许POST方式请求
        IS_POST || $this->error('403:Forbidden');
        //IP地址及访问频率限制，对黑名单中的访问者直接返回FALSE
        if(!$this->check_limit()){
			$this->error('403:Forbidden');
        }

        //模块访问控制，判断站点维护及禁止访问的模块
        $access = $this->check_access();
        if ( $access === false ) {          
            $this->error('403:Forbidden');
        }

        //检查token
        if(FALSE === $this->auth()) {
            $this->error('403:Forbidden');    
        }
    }

    protected function auth(){
		$client_id = I('post.app_key/d',0);
        $client_timestamp = I('post.ts/d',0);
        $client_token = I('post.token');

		if(!(empty($client_id) || empty($client_token) || empty($client_timestamp))) {
            //时间戳允许误差在配置文件中定义
            $time_diff = abs(NOW_TIME - $client_timestamp);
            if($time_diff < C('api_time_deviation') * 1000) {
                $M = M('api_auth');
                $map['id'] = $client_id;
                $app_secret = $M->where($map)->getField('app_secret');
                if(!empty($key)) {
                    $server_token = md5(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME.$timestamp.$app_secret);
                    if($client_token === $server_token) {
                        return TRUE;
                    }
                }
            }
			
		}
        return FALSE;
    }

    protected function check_limit() {
		//检查黑名单IP
		$ip  = get_client_ip();
		$res = S('forbid_ip:'. $ip);
		if(!empty($res)) {
			S('forbid_ip:'. $ip, ++$res);
			return FALSE;
		}
		//纪录访问频率;
		$key       = 'limit:api:'.$ip;
		$limit     = S($key);
        S($key,++$limit);
        return TRUE;
    }
    
    protected function check_access() {
    	$M = M('Config');
    	//整站检查，检查是否站点维护
    	$res = C('MANTENANCE');
    	if($res['val'] === 'Closed'){
    		$this->duration = $res['duration'];
    		$this->ajaxReturn(0, 'Service maintenance');
    	}
    }

    //api 统一返回状态值，0表示失败，1表示成功
    protected function msg($status='0', $msg='', $data='',$type='json'){
        $res['status'] = $status;
        $res['msg']    = $msg;
        $res['data']   = $data;
        $this->ajaxReturn($res, $type);
    }

    protected function ajaxReturn($data,$type='',$json_option=0) {
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data,$json_option));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data,$json_option).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);            
            default     :
                // 用于扩展其他返回格式数据
                Hook::listen('ajax_return',$data);
        }
    }
    private function _before(&$data) {
    	$func = 'before_'. ACTION_NAME;
		if(method_exists($this, $func)){
            $this->$func($data);
        }
    }
    private function _after(&$data,$res = TRUE) {
    	$func = 'after_'. ACTION_NAME;
		if(method_exists($this, $func)){
            $this->$func($data, $res);
        }
    }

}