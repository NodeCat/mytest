<?php
namespace Erp\Controller;
use Think\Controller;

class EmptyController extends Controller {
	protected function _initialize(){
        layout(!IS_AJAX);
  }
	public function _empty(){
       if(strpos(C('EMPTY_CONTROLLER'), CONTROLLER_NAME.',')!==false){
   			R("Wms/Common/".ACTION_NAME);
   		}
   		else {
   			$this->display('Index:404');
   		}
  	}
}