<?php
namespace Wms\Controller;
use Think\Controller;
class IndexController extends CommonController {
    public function index(){
    	$this->display();
    }
    public function _before_index() {}
    public function odoo(){
    	$this->display();
    }
}