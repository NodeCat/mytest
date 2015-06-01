<?php
namespace Common\Lib;

class Auth{

    //默认配置
    protected $_config = array(
        'AUTH_ON'           => true,                      // 认证开关
        'AUTH_TYPE'         => 1,                         // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP'        => 'auth_role',         // 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'auth_user_role',    // 用户-用户组关系表
        'AUTH_RULE'         => 'auth_authority',    // 权限规则表
        'AUTH_USER'         => 'user'               // 用户信息表
    );

    public function __construct() {
        $prefix = C('DB_PREFIX');
        $this->_config['AUTH_GROUP'] = $prefix.$this->_config['AUTH_GROUP'];
        $this->_config['AUTH_RULE'] = $prefix.$this->_config['AUTH_RULE'];
        $this->_config['AUTH_USER'] = $prefix.$this->_config['AUTH_USER'];
        $this->_config['AUTH_GROUP_ACCESS'] = $prefix.$this->_config['AUTH_GROUP_ACCESS'];
        if (C('AUTH_CONFIG')) {
            //可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }

    /**
      * 检查权限
      * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
      * @param uid  int           认证用户的id
      * @param string mode        执行check的模式
      * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
      * @return boolean           通过验证返回true;失败返回false
     */
    public function getAuthsByModule($module,$name, $uid, $type=4) {
        if (!$this->_config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid,$type);
        
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $map=array(
            'group' => $module,
            'module'=>array('in',$name),
            'type'=> $type,
            'status'=>1,
        );
        //读取用户组所有权限规则
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->field('action,url')->select();
        $list = array();
        foreach ( $rules as $rule ) {
            $auth = strtolower($rule['url']);
            if (in_array($auth , $authList)){
                $list[$rule['action']] = $auth ;
            }
        }
        return $list;
    }

    public function getMenu($uid){
        $M=M('Menu');
        $cur = CONTROLLER_NAME.'/'.ACTION_NAME;
        $map = array('status'=>'1','is_deleted'=>0);
        $result= $M->where($map)->order('pid,queue,id')->getField("id,id,name,level,pid,icon,link,module,target,show");
        foreach ($result as $k => $v) {
            if($v['show'] == 0 && $v['link'] != $cur){//对于设置隐藏的菜单，并且不是当前链接，则直接跳过
                unset($result[$k]);
                continue;
            }

            if(!empty($v['link'])) {
                $result[$k]['url'] = strtolower($v['module'].'/'.$v['link']);
            }
            else {
                $menu_link_empty[$k] = $v;//所有为空的菜单记录下来
            }
            $menu_link[$v['id']] = $result[$k]['url'];
        }

        $authList = $this->getAuthList($uid);//获取当前用户的权限可访问的菜单列表
        $menu_auth = array_intersect($menu_link,$authList);//取权限列表和菜单列表的交集
        
        foreach ($menu_auth as $key => $val) {
            $keys = array_keys($menu_link, $val);
            foreach ($keys as $k => $v) {
                $menu[$v] = $result[$v];
            }
        }
        
        //将链接为空的菜单再插入回用户可访问的菜单列表
        $result = array_merge($menu_link_empty,$menu);
        
        //菜单分级
        foreach ($result as $k => $v) {
            $data[$v['level']][]=$v;
        }

        $data=array();
        $index[0]=0;
        foreach ($result as $key => $value) {
            $data[$value['level']+1][$key]=null;
            $index[$value['id']]=$key;
        }
        foreach (array_reverse($data,true) as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$key-1][$index[$result[$k]['pid']]][$k]=$data[$key][$k];
            }
            unset($data[$key]);
        }
        $menu = $data[0][0];

        foreach ($menu as $k => $v) {
            if(empty($v) && empty($result[$k]['link'])){
                unset($result[$k]);
            }
        }
        $data=array();
        foreach ($result as $k => $v) {
            $data[$v['level']][$v['pid']][$v['id']]=$v;
        }
        foreach ($menu as $k => $v) {
            if(!array_key_exists($k, $data[1])) {
                unset($data[0][0][$k]);
            }
        }
        //dump($data);exit();
        return $data;

    }


    public function check($name, $uid, $type='4', $mode='url', $relation='or') {
        if (!$this->_config['AUTH_ON'])
            return true;

        $authList = $this->getAuthList($uid,$type);
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //保存验证通过的规则名
        if ($mode=='url') {
            $REQUEST = unserialize( strtolower(serialize($_REQUEST)) );
        }
        foreach ( $authList as $auth ) {
            $query = preg_replace('/^.+\?/U','',$auth);
            if ($mode=='url' && $query!=$auth ) {
                parse_str($query,$param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST,$param);
                $auth = preg_replace('/\?.*$/U','',$auth);
                if ( in_array($auth,$name) && $intersect==$param ) {  //如果节点相符且url参数满足
                    $list[] = $auth ;
                }
            }else if (in_array($auth , $name)){
                $list[] = $auth ;
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }

        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  uid int     用户id
     * @return array       用户所属的用户组 array(
     *                                         array('uid'=>'用户id','role_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *                                         ...)   
     */
    public function getRoles($uid) {
        static $roles = array();
        if (isset($roles[$uid]))
            return $roles[$uid];
        $user_groups = M()
            ->table($this->_config['AUTH_GROUP_ACCESS'] . ' ur')
            ->where("ur.user_id='$uid' and r.status='1'")
            ->join($this->_config['AUTH_GROUP']." r on ur.role_id=r.id")
            ->field('r.id,rules')->select();
        $roles[$uid]=$user_groups?$user_groups:array();
        return $roles[$uid];
    }

    /**
     * 获得权限列表
     * @param integer $uid  用户id
     * @param integer $type 
     */
    protected function getAuthList($uid,$type='4') {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',',(array)$type);
        if (isset($_authList[$uid.$t])) {
            return $_authList[$uid.$t];
        }
        $user = I('session.user_auth');
        
        //$authList = S('_AUTH_LIST_'.$user['role'].'_'.$type);
        if( $this->_config['AUTH_TYPE']==2 && !empty($authList)){
            return $authList;
        }

        //读取用户所属用户组
        $roles = $this->getRoles($uid);
        $ids = array();//保存用户所属用户组设置的所有权限规则id
        foreach ($roles as $role) {
            $ids = array_merge($ids, explode(',', trim($role['rules'], ',')));
        }

        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid.$t] = array();
            return array();
        }
        $map=array(
            'id'=>array('in',$ids),
            'type'=> $type,
            'status'=>1,
        );
        //读取用户组所有权限规则
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->field('condition,url')->select();

        //循环规则，判断结果。
        $authList = array();   //
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //根据condition进行验证
                $user = $this->getUserInfo($uid);//获取用户信息,一维数组

                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['url']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['url']);
            }
        }
        //$_authList[$uid.$t] = $authList;
        if($this->_config['AUTH_TYPE']==2){
            //S('_AUTH_LIST_'.$user['role'].'_'.$type, $authList, MT);
        }
        return array_unique($authList);
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid) {
        static $userinfo=array();
        if(!isset($userinfo[$uid])){
             $userinfo[$uid]=M()->where(array('id'=>$uid))->table($this->_config['AUTH_USER'])->find();
        }
        return $userinfo[$uid];
    }

}
