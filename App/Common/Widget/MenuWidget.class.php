<?php
namespace Common\Widget;
use Think\Controller;
class MenuWidget extends Controller {
    public function getlist(){
        $user = I('session.user');
        if(empty($user['uid']))return;
        static $Auth    =   null;
        if (!$Auth) {
            $Auth       =   new \Common\Lib\Auth();
        }
        $menu = $Auth->getMenu($user['uid']);
    
		$cond=array('link' => CONTROLLER_NAME.'/'.ACTION_NAME,'level'=>array('in','2,3'), 'is_deleted'=>0,'module'=>MODULE_NAME);
		$cur=M('Menu')->field('id,pid,name,level')->where($cond)->order('level desc')->find();
        $menu['tab'] = $menu[$cur['level']][$cur['pid']];

        $menu['pid'] =$cur['pid'];
        $menu['cur'] = $cur['id'];
        $menu['title'] = $cur['name'];
        return $menu;
   }

}