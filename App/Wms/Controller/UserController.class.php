<?php
namespace Wms\Controller;
use Think\Controller;
class UserController extends CommonController {
	protected $columns = array(
		'id'		=> '',
		'nickname' 	=> '姓名',
		'email' 	=> '邮箱',
		'mobile'		=> '手机'
	);
}