<?php
namespace Tms\Controller;
use Think\Controller;
class ListController extends Controller{

    public function index(){
        
        $D=D("TmsSignList");
        $list=$D->relation('TmsUser')->Select();
        //dump($list);
    }

}


