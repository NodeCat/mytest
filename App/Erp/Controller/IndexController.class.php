<?php
namespace Erp\Controller;
use Think\Controller;
class IndexController extends \Common\Controller\AuthController {
    public function index(){
    	$this->display();
    }
}