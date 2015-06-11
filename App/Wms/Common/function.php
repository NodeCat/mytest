<?php
function getField($table,$fields,$condition=null){
  $data=M($table)->where($condition)->getField($fields);
  return $data;
}
function match($table,$field,$fields){
  $id=I('q');
  $map[$field] = array('like','%'.$id.'%');
  $data = getField($$table,$fields,$map);
  return json_encode($data);
}
function set_session($uid){
    $user = M('User')->find($uid);
    static $Auth    =   null;
    if (!$Auth) {
        $Auth       =   new \Common\Lib\Auth();
    }

    $user_roles = $Auth->getRoles($uid);
    foreach ($user_roles as $value) {
        $roles[] = $value['id'];
    }
    
    $roles = implode('_', $roles);

    $rules = $Auth->getRule($uid);
    
    if(!empty($rules)) {
        $wh = current($rules);
        $rules = implode($rules, ',');
    }
    if(empty($wh)) {
        $rules = '-1,0';
        $wh = '0';
    }
    
    /* 记录登录SESSION和COOKIES */
    $auth = array(
        'uid'             => $user['id'],
        'username'        => $user['nickname'],
        'role'            => $roles,
        'rule'           => $rules,
        'wh_id'           => $wh
    );

    session('user', $auth);
    session('user_auth_sign', data_auth_sign($auth));
}

function destory_session() {
    //$role = I('session.user_auth.role');
    //S("_ROLE_MENU_LIST_".$role, null);
    //S('_AUTH_LIST_'.$role.'_4',null);
    session('user', null);
    session('user_auth_sign', null);
    session('[destroy]');
}

function validator($vo){
        $str ='{';
        $str .= $vo['null']=='NO'?'required:true,':'';
        $str .= strpos($vo['type'],'char')!==false?'maxlength:'.substr($vo['type'],strpos($vo['type'],"(")+1,-1).',':'';
        if(strpos($vo['type'],'int')!==false){
            $str .= 'digits:true,';
            switch(strtoupper(substr($vo['type'],0,3)))
            {
                case 'TIN'://TINYINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,255]':'range:[-128,127]';
                  break;  
               case 'SMA'://SMALLINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,65535]':'range:[-32768,32767]';
                  break; 
                case 'MED'://MEDIUMINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,16777215]':'range:[-8388608,8388607]';
                  break; 
                case 'INT'://INT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,4294967295]':'range:[-2147483648,2147483647]';
                  break; 
                case 'BIG'://BIGINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,18446744073709551615]':'range:[-9223372036854775808,9223372036854775807]';
                  break; 
            }
        }
        $str .= strpos($vo['type'],'decimal')!==false?'number:true,':'';
        $str .= strpos($vo['type'],'float')!==false?'number:true,':'';
        $str .= strpos($vo['type'],'double')!==false?'number:true,':'';
        $str .= strpos($vo['type'],'year')!==false?'range:[0,9999],digits:true,':'';
        $str .= strpos($vo['type'],'timestamp')!==false?'dateISO:true,':'';
        $str .= strpos($vo['type'],'datetime')!==false?'dateISO:true,':'';
        $str .= strpos($vo['type'],'time')!==false?'date:true,':'';
        $str.='}';
        return $str;
    }
    function control_type($vo){
        if($vo['key']==='PRI')$type='hidden';
        elseif(strpos($vo['type'],'char')!==false)$type='text';
        elseif(strpos($vo['type'],'text')!==false)$type='area';
        elseif(strpos($vo['type'],'enum')!==false)$type='select';
        elseif($vo['type']=='datetime' || $vo['type']=='timestamp')$type='datetime';
        elseif($vo['type']=='date')             $type='date';
        elseif($vo['type']=='time')             $type='time';
        elseif(strpos($vo['type'],'bit')!==false)$type='checkbox';
        //else if(strpos($vo['type'],'set')!==false)$type='checkboxs';
        else if(
                strpos($vo['type'],'int')!==false 
            ||  strpos($vo['type'],'decimal')!==false 
            ||  strpos($vo['type'],'float')!==false 
            ||  strpos($vo['type'],'double')!==false
            )
            $type='digit';

        return $type;
    }

function where_array_to_str($where = array(), $relation = 'AND'){
    if(empty($where)){
        return false;
    }
    
    foreach($params['where'] as $condition => $val){
        $where_str .= $condition.' = '.$val.' '.$relation.' ';
    }
    
    $where_str = substr($where_str, 0, strlen($where_str) - 4);
    
    return $where_str;
}

//英文转中文
function en_to_cn($str){
    $filter = array(
        'qualified' => '合格',
        'unqualified' => '残次',
        'freeze' => '冻结',
        'in' => '收货',
        'on' => '上架',
        'move_location' => '库存移动',
        'move' => '库存移动',
        'inventory' => '盘点',
        'change_status' => '状态调整',
        'noinventory' => '未盘点',
        'inventorying' => '盘点中',
        'confirm' => '待审核',
        'closed' => '已作废',
        'fast' => '快速盘点',
        'again' => '复盘',
        'status' => '状态调整',
        'unite' => '组合',
        'split' => '拆分',
        'draft' => '草稿',
        'make' => '已生产',
        'pass' => '已生效',
        'reject' => '已驳回',
        'close' => '已作废',
        );

    return $filter[$str];
}

//中文转英文
function cn_to_en($str){
    $filter = array(
        '盘点' => 'inventory',
        '库存移动' => 'move',
        '收货' => 'in',
        '上架' => 'on',
        '库存移动' => 'move_location',
        );

    return $filter[$str];
}

//根据单号返回单据中文类型
function get_type_by_bill($bill_code){
    if(strstr($bill_code,'PD')){
        $type = '盘点单';
    }
    if(strstr($bill_code,'STOCK')){
        $type = '状态调整';
    }

    return $type;
}

//根据USER_AGENT判断是否是移动端
function is_mobile_terminal(){
    $mobile_ua = array(
        'iPhone',
        'Android',
        );
    foreach($mobile_ua as $ua){
        if(strstr($_SERVER['HTTP_USER_AGENT'], $ua)){
            return true;
        }
    }
    return false;
}