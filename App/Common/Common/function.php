<?php
function get_type($type = '') {
	$map['type'] = $type;
	$map['is_deleted'] = 0 ;
	$data = M('category')->where($map)->select();
	return $data;
}
function get_sn($type = '', $wh_id = '') {
    $sql = "CALL sn('".$type."')";
    $n = M()->query($sql);
    $M = M('numbs');
    $res = $M->field('prefix,mid,suffix')->find($type);
    $sn = str_pad($n[0]['sn'],$res['suffix'],"0",STR_PAD_LEFT);
    $numb =$res['prefix'].$res['mid'].$sn;
    $date = date('ymd',NOW_TIME);
    $wh_id =  str_pad($wh_id,2,"0",STR_PAD_LEFT);
    $numb = str_replace(array('%date%','%wh_id%'), array($date,$wh_id), $numb);
	return $numb;
}
function get_batch($code=''){
    if(!empty($code)) {
        $code = get_sn('batch');
    }
    $data['code'] = $code;
    $data['product_date'] = get_time();
    M('stock_batch')->add($data);
    return $code;
}
function get_tablename() {
	$M = D(CONTROLLER_NAME);
    $table = $M->tableName;
    if(empty($table)) {
        $table = strtolower(CONTROLLER_NAME);
    }
    return $table;
}
function get_setting($table) {
	$M = M('module_table');
	$res = $M->field('list,query')->find(strtolower($table));
    if(!empty($res)) {
        if(!empty($res['list']) && $res['list'] != 'array ( )') {
            eval('$list = '.$res['list'].';');
        }
        if(!empty($res['query']) && $res['query'] != 'array ( )') {
            eval('$query = '.$res['query'].';');
        }
        
    }
	$data = array(
			'list' => $list ,
			'query'=> $query,
			);
	return $data;
}
//POST数据处理
//未输入值的txtbox为空，应当移除
//复选框未选择，则不会出现在post中，应添加条件并赋值为false
function queryFilter($data){
    foreach ($data as $key => $value) {
        if($value == '' || (is_array($value) && empty($value[1]))){
            unset($data[$key]);
        }
    }
    return $data;
}
//判断是否登陆
function is_login(){
    $user = session('user');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}
function clear_runtime() {
	if(file_exists(RUNTIME_FILE)){
	    unlink(RUNTIME_FILE); //删除RUNTIME_FILE;
	}
	$cachedirs = array(
        RUNTIME_PATH."/Cache/",
        RUNTIME_PATH."/Temp/",
        RUNTIME_PATH."/Data/"
        );
    foreach ($cachedirs as $cachedir) {
    	if ($dh = opendir($cachedir)) {     //打开Cache文件夹；
    	    while (($file = readdir($dh)) !== false) {    //遍历Cache目录，
    	        unlink($cachedir.$file);                //删除遍历到的每一个文件；
    	    }
    	    closedir($dh);
    	}  
    }
}
function set_site_config() {
    $config =   S('CONFIG_DATA');
    if(!$config){
        $config =   R('Config/get_site_config');
        S('CONFIG_DATA',$config);
    }
    C($config);
}

function check_maintain() {
	$res = C('MAINTENANCE');
	if($res === 'Closed'){
		$duration = C('DUTATION');
		return FALSE;
	}
	else {
		return TRUE;
	}
 }
//数据签名验证
function data_auth_sign($data) {
    //数据类型检测
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}
function check_verify($code, $id = 1){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}
/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @return string 
 */
function auth_md5($str, $key = 'dachuwang@!#$*&^%'){
	return '' === $str ? '' : md5(sha1($str) . $key);
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 (单位:秒)
 * @return string 
 */
function auth_encrypt($data, $key, $expire = 0) {
	$key  = md5($key);
	$data = base64_encode($data);
	$x    = 0;
	$len  = strlen($data);
	$l    = strlen($key);
	$char =  '';
	for ($i = 0; $i < $len; $i++) {
		if ($x == $l) $x=0;
		$char  .= substr($key, $x, 1);
		$x++;
	}
	$str = sprintf('%010d', $expire ? $expire + time() : 0);
	for ($i = 0; $i < $len; $i++) {
		$str .= chr(ord(substr($data,$i,1)) + (ord(substr($char,$i,1)))%256);
	}
	return str_replace('=', '', base64_encode($str));
}

/**
 * 系统解密方法
 * @param string $data 要解密的字符串 （必须是auth_encrypt方法加密的字符串）
 * @param string $key  加密密钥
 * @return string 
 */
function auth_decrypt($data, $key){
	$key    = md5($key);
	$x      = 0;
	$data   = base64_decode($data);
	$expire = substr($data, 0, 10);
	$data   = substr($data, 10);
	if($expire > 0 && $expire < time()) {
		return '';
	}
	$len  = strlen($data);
	$l    = strlen($key);
	$char = $str = '';
	for ($i = 0; $i < $len; $i++) {
		if ($x == $l) $x = 0;
		$char  .= substr($key, $x, 1);
		$x++;
	}
	for ($i = 0; $i < $len; $i++) {
		if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
			$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
		}else{
			$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
		}
	}
	return base64_decode($str);
}

function logs($id, $data, $action = ''){
    $M = M('log');
    if(empty($action)) {
    	$M->action      = ACTION_NAME.'/'.CONTROLLER;
    }
	else {
		$M->action = $action;
	}
	$M->content     = json_encode($data);
	$M->pk          = $id;
	$M->url 		= __SELF__;
	$M->ip          =  get_client_ip();
	$M->update_user = session('user.uid');
	$M->update_time = get_time();
    $M->add();
}
function get_time(){
    return date('Y-m-d H:i:s',NOW_TIME);
}
function mkdirs($dir){       
    if(!is_dir($dir)){       
        if(!mkdirs(dirname($dir))){       
            return false;       
        }
        if(!file_exists($dir)){
            if(!mkdir($dir,0777)){       
                return false;       
            }
        }
        return true; 
    }
    return true;       
}
function X($t, $id=null, $value = ''){
    if(empty($id)){
        return null;
    }
    else{
        if($value === '')
            $data=S($t.$id);
        else{
            S($t.$id,$value);
            return;
        }

        if(empty($data)){
            $data = M($t)->find($id);
            S($t.$id,$data,MT);
        }
    }
    return $data;
}