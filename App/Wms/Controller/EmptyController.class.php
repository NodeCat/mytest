<?php
namespace Wms\Controller;
use Think\Controller;

class EmptyController extends Controller {
	public function _empty(){
       if(strpos(C('EMPTY_CONTROLLER'), CONTROLLER_NAME.',')!==false){
   			R("Wms/Common/".ACTION_NAME);
   		}
   		else {
   			$this->display('Index:404');
   		}
  	}
}