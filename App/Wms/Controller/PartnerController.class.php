<?php
namespace Wms\Controller;
use Think\Controller;
class PartnerController extends CommonController {
	
	protected $query   = array ();

	//设置列表页选项
	protected function before_index() {
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['print']),'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }
}