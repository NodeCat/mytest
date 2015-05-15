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
    $uid =$uid;
    $user = M('User')->find($uid);
    /*
    $user_roles = M()
        ->table('auth_user_role ur')
        ->join("auth_role r on ur.role_id=r.id")
        ->where("ur.user_id='$uid' and r.status='1'")
        ->field('r.id')->select();
    foreach ($user_roles as $value) {
        $roles[] = $value['id'];
    }
    *
    $roles = implode('_', $roles);
    /* 记录登录SESSION和COOKIES */
    $auth = array(
        'uid'             => $user['id'],
        'username'        => $user['nickname'],
        'role'            => $roles,
        'wh_id'           => 1,
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
        );

    return $filter[$str];
}

//中文转英文
function cn_to_en($str){
    $filter = array(
        '盘点' => 'inventory',
        '库存移动' => 'move',
        );

    return $filter[$str];
}