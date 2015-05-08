<?php
namespace Wms\Controller;
use Think\Controller;

class AuthController extends Controller {
	protected function _initialize(){
        layout(!IS_AJAX);
        //判断是否需要登陆才能访问，post下返回session timeout错误，
        if(!is_login()){
            $this->redirect('Login/index', 'please login'); 
        }
        if(!defined('UID')) {
            define('UID',session('user.uid'));
        }
        if(!check_maintain()){
            destory_session();
            layout(FALSE);
            $this->display('index:closed');
            exit();
        }
        return ;
        //模块访问控制，判断站点维护及禁止访问的模块，及是否需要登陆才能访问
        $access = $this->check_access();
        if ( $access === false ) {
            $this->error('unauthorized');
        }
        
        //IP地址及访问频率限制，对黑名单中的访问者直接返回FALSE
        if(C('check_limit') && !$this->check_limit()){
			$this->error('403:forbidden');
        }

        

        //检查节点权限
        $rule  = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        if ( !$this->check_rule($rule)){
            $this->error('unauthorized');
        }
    }

    protected function api_auth(){
    	if(IS_POST) {
    		$client_id = I('post._client_id');
    		if(!empty($client_id)) {
    			$client_token = I('post._api_tk');
    			$M = M('api_auth');
    			$map['id'] = $client_id;
    			$key = $M->where($map)->getField('token');
    			if(!empty($key)) {
    				$token = md5(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME.date('ymd',NOW_TIME).$key);
    				if($client_token === $token) {
    					return TRUE;
    				}
    			}
    			return FALSE;
    		}
    		else {
    			return TRUE;
    		}
    		
    	}
    }
    protected function check_limit() {
		//检查黑名单IP
		$ip  = get_client_ip();
		$res = S('forbid_ip:'. $ip);
		if(!empty($res)) {
			S('forbid_ip:'. $ip, ++$res);
			return FALSE;
		}
		//检测访问频率
		$timestamp = NOW_TIME/(C('request_limit_unit')*1000);
		$key       = 'limit:'.$ip.':'.$timestamp;
		$limit     = S($key);
        //访问频率达到了进入黑名单的地步，则直接拉入黑名单
		if($limit > C('request_forbid_limit')) {
			S('forbid_ip:'. $ip, $limit);
        	return FALSE;
        }
		//访问频率限制,超过系统设置则加入到suspect列表中，供管理员查看
        if($limit > C('request_waring_limit')) {
        	S('suspect:'.$timestamp, $ip.':'.$limit); 
        }
        return TRUE;
    }
    protected function check_access() {
    	
    	{
    	//模块检查
    		//某些限制访问的模块仅能从限制的IP访问
    		$res = $M->find('forbid_moudle');    
    		if(in_array(CONTROLLER_NAME,explode(',', $res['val']))) {
    			$res = $M->find('allow_ip');
    			if(in_array($ip, explode(',',$res['val']))) {
    				return TRUE;
    			}
    			else {
    				return FALSE;
    			}
    		}
    		else {
    		//url路径安全检查
    			//对url进行关键字过滤，防止tp框架本身有漏洞而导致的通过tp框架文件执行任意代码或注入漏洞
    			//过滤url路径中出现的'thinkphp'关键字，防止进入tp目录
    			//过滤../，防止目录跳转
    			if(!strpos(__SELF__, "../") || !strpos(dirname(__SELF__),'ThinkPHP')) {
    				return FALSE;
    			}
    		}
    		return TRUE;
    	}
    }
}