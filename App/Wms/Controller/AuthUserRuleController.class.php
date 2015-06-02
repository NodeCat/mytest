<?php
namespace Wms\Controller;
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
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'true'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['add']),'new'=>'false'), 
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
            $this->roles= M('auth_role')->getField('id,name,description');
            $this->display("Auth/authority");
        }
    }
}