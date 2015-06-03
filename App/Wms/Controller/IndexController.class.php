<?php
namespace Wms\Controller;
use Think\Controller;
class IndexController extends AuthController {
    public function index(){
    	$this->display();
    }
}