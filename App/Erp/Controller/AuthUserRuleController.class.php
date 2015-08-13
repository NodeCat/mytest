<?php
namespace Erp\Controller;
use Think\Controller;
class AuthUserRuleController extends CommonController {
	protected $columns = array(
		'id'		=> '',
        'nickname' => '用户名称',
        //'rules' => '权限',
	);

    //设置列表页选项
    protected function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true'), 
            array('name'=>'delete' ,'show' => isset($this->auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($this->auth['add']),'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }
    public function edit(){
        if(IS_POST){
            $this->save();
        }
        else{
            //获得所有用户信息
            $map['is_deleted'] = 0;
            $this->users = M('user')->where($map)->getField('id,username,email,nickname');
            //获得所有仓库信息
            $this->warehouse = M('warehouse')->getField('id,code, name, address');
            $this->display();
        }
    }

    //根据仓库id 查询对应的仓库
    public function getWhInfoByUserId(){
        $user_id = I('userId');
        $map['user_id'] = $user_id;
        $auth_user_rule_list = M('auth_user_rule')->where($map)->field('user_id,rule_id,type')->select();
        unset($map);

        $res['status'] = 'succ';
        $res['data'] = $auth_user_rule_list;

        return $this->ajaxReturn($res);
    }

    //设置仓库id 与 用户id的对应auth_user_rule关系记录
    public function setWhIdAndUserId(){
        $user_id = I('userId');
        $wh_ids = substr(I('whId'),0,strlen(I('whId')) - 1);
        $type = I('type');

        if(empty($user_id) || empty($type)){
            $res['status'] = 'fail';
            $res['msg'] = '参数错误';

            return $this->ajaxReturn($res);
        }

        if(!empty($user_id)){
            //删除所有user_id的记录
            $map['user_id'] = $user_id;
            M('auth_user_rule')->where($map)->delete();
        }
        

        if(!empty($wh_ids)){
            //重新写入记录
            $wh_id_arr = explode(',', $wh_ids);

            foreach($wh_id_arr as $wh_id){
                $data['user_id'] = $user_id;
                $data['rule_id'] = $wh_id;
                $data['type'] = $type;
                $data['status'] = 1;

                $authUserRule = D('AuthUserRule');
                $data = $authUserRule->create($data);
                $authUserRule->data($data)->add();
            }
        }
        

        $res['status'] = 'succ';

        return $this->ajaxReturn($res);
    }
}