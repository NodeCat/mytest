<?php
namespace Common\Controller;
use Think\Controller;

class AuthController extends Controller {
	protected function _initialize(){
        layout(!IS_AJAX);
        //判断是否需要登陆才能访问，post下返回session timeout错误，
        if(!is_login()){
            $url = urlencode(__SELF__);
            redirect(U('Login/index').'?url='.$url,0, '请您先登录。'); 
        }
        if(!defined('UID')) {
            define('UID',session('user.uid'));
        }
        if(!defined('WHID')) {
            define('WHID',session('user.rule'));
        }
        if(!check_maintain()){
            destory_session();
            layout(FALSE);
            $this->display('Index:closed');
            exit();
        }

        $this->auth=$this->getAuth();

        if(session('user.uid') == 1){
            C('SHOW_PAGE_TRACE',TRUE);
            //return true;
        }

        /*
        //检查节点权限
        $rule  = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;

        if ( !$this->check_rule($rule)){
            $this->error('权限不足，不能访问');
        }
        */
        //模块访问控制，判断站点维护及禁止访问的模块，及是否需要登陆才能访问
        /*$access = $this->check_access();
        if ( $access === false ) {
            $this->error('unauthorized');
        }
        
        //IP地址及访问频率限制，对黑名单中的访问者直接返回FALSE
        if(C('check_limit') && !$this->check_limit()){
			$this->error('403:forbidden');
        }
        */
        //检查节点权限
        $rule  = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        if ( !$this->check_rule($rule)){
            $this->error('未授权访问！');
        }
    }

    protected function check_rule($cur_rule){
        
        $cur_user = session('user');
        $user_roles = session('user.role');
        
        if(empty($user_roles)){
            return false;
        }

        //根据id 查询auth_role
        $user_roles_arr = explode('_', $user_roles);
        $map['id'] = array('in',$user_roles_arr);
        $rules = M('auth_role')->where($map)->field('rules')->select();
        unset($map);

        if(empty($rules)){
            return false;
        }

        $rules_arr = array();
        foreach($rules as $rule){
            $arr = explode(',', $rule['rules']);
            foreach($arr as $val){
                $rules_arr[$val] = $val;
            }
        }

        //根据URL 查询auth_authority
        $res = array();
        $map['url'] = array('eq',$cur_rule);
        $url_arr = M('auth_authority')->where($map)->field('id,log,title')->find();
        
        if(in_array($url_arr['id'], $rules_arr)){
            if($url_arr['log'] == 1) {
                $M = D(CONTROLLER_NAME);
                $model = $M->tableName;
                if(empty($model)) {
                    $model = strtolower(CONTROLLER_NAME);
                }
                $pk = $M->getPk();
                $id = I($pk);
                logs($id,$url_arr['title'],$model);
            }
            return true;
        }

        if($cur_user['uid'] == 1){
            return true;
        }
        return false;
    }

    final protected function getAuth() {
        static $Auth    =   null;
        if (!$Auth) {
            $Auth       =   new \Common\Lib\Auth();
        }
        $rules = $Auth->getAuthsByModule(MODULE_NAME,  CONTROLLER_NAME, UID) ;
        return $rules;
    }
    /**
     * 权限检测
     * @param string  $rule    检测的规则
     * @param string  $mode    check模式
     * @return boolean
     */
    final protected function checkRule($rule, $type='4', $mode='main'){
        static $Auth    =   null;
        if (!$Auth) {
            $Auth       =   new \Common\Lib\Auth();
        }
        if(!$Auth->check($rule,UID,$type,$mode)){
            return false;
        }
        return true;
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