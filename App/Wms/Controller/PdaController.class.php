<?php
namespace Wms\Controller;
use Think\Controller;
class PdaController extends Controller {
	public function index(){
		C('LAYOUT_NAME','pda');
		$this->display();
	}
}