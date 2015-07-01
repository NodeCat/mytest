<?php
namespace Common\Controller;
use Think\Controller;
class IndexController extends AuthController {
    public function index(){
    	$this->display();
    }
}