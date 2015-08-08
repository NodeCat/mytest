<?php
namespace Erp\Controller;
use Think\Controller;
class AuthRoleController extends CommonController {
	protected $columns = array(
		'id'		=> '',
        'name' => '名称',
        //'rules' => '权限',
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
            array('name'=>'view', 'show' => false,'new'=>'true'), 
            array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => isset($this->auth['delete']),'new'=>'false'),
            'setauth'=>array('name'=>'setauth','title'=>'设置权限','icon'=>'cog' ,'show' => true,'new'=>'true','link'=>"Authority/edit"),
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
}