<?php
namespace Wms\Controller;
use Think\Controller;
class UserController extends CommonController {
	protected $columns = array(
		'id'		=> '',
        'username'  => '用户名',
		'nickname' 	=> '姓名',
        'role_name'      => '角色',
        'status'    => '状态',
	);
    protected $query   = array (
        'user.username' => array (
            'title' => '用户ID',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'user.nickname' => array (
            'title' => '用户名称',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
        'user.status' => array (
            'title' => '状态',
            'query_type' => 'eq',
            'control_type' => 'select',
            'value' => array('job'=>'在职','quit'=>'离职'),
        ),
    );
    //页面展示数据映射关系 例如取出数据是qualified 显示为合格
    protected $filter = array(
            'status' => array('job' => '在职','quit' => '离职'),
        );

	//设置列表页选项
	protected function before_index() {
		$this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => true,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
	}

    //在edit之前调用该方法
    public function _before_edit(){
        $user_id = I('id');

        //查询所有角色列表
        $map['is_deleted'] = 0;
        $auth_roles = M('auth_role')->where($map)->field('id,name')->select();
        unset($map);

        //查询哪些角色属于当前用户
        foreach($auth_roles as $k => $auth_role){
            $map['user_id'] = $user_id;
            $map['role_id'] = $auth_role['id'];
            $re = M('auth_user_role')->where($map)->find();
            unset($map);

            if(!empty($re)){
                $auth_roles[$k]['checked'] = true;
            }
        }

        $this->auth_role = $auth_roles;
    }

    //在save之前执行该方法
    protected function before_save(&$M){
        //角色id
        $roles = I('roles');
        //用户id
        $user_id = I('id');

        if(!empty($user_id)){
            //删除所有所有用户角色关系
            $map['user_id'] = $user_id;
            M('auth_user_role')->where($map)->delete();
            unset($map);
            if(!empty($roles)){
                foreach($roles as $role){
                    //写入auth_user_role 表，用户与角色对应关系表
                    $data['user_id'] = $user_id;
                    $data['role_id'] = $role;
                    $auth_user_role = D('AuthUserRole');
                    $data = $auth_user_role->create($data);
                    $auth_user_role->data($data)->add();
                    unset($data);
                }
            }
            
        }
    }

    //在lists之后执行该方法
    protected function after_lists(&$data){
        foreach($data as $k => $val){
            //根据id 查询auth_role
            $map['auth_user_role.user_id'] = $val['id'];
            $roles_name_list = M('auth_user_role')
            ->join('auth_role on auth_role.id = auth_user_role.role_id')
            ->where($map)->field('auth_role.name')->select();
            unset($map);

            $roles_names = '';
            if(!empty($roles_name_list)){
                foreach($roles_name_list as $roles_name){
                    $roles_names .= $roles_name['name'].' ';
                }
            }
            
            $data[$k]['role_name'] = $roles_names;
            unset($roles_names);
        }
    }

}