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
        /*
        $authList = $Auth->getAuthList($uid);

        $M=M('Menu');
        $map = array('status'=>'1','is_deleted'=>0);
        $result= $M->field("id,name,level,pid,icon,link,concat(module,'/',link) url,target,show")->where($map)->order('pid,queue,id')->select();
        foreach ($result as $k => $v) {
            $result[$k]['link'] = strtolower($v['link']);
            $menu_link[$v['id']] = $result[$k]['link'];
        }
        $menu = $menu_link;
        
        $menu = array_intersect($menu_link,$authList);
        foreach ($result as $k => $v) {
            if(!(empty($v['link']) || in_array($v['link'],$menu))){
               unset($result[$k]);
            }
            else{
                $data[$v['level']][]=$v;
            }
        }
        $data=array();
        $index[0]=0;
        foreach ($result as $key => $value) {
            $data[$value['level']+1][$key]=null;
            $index[$value['id']]=$key;
        }
        foreach (array_reverse($data,true) as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$key-1][$index[$result[$k]['pid']]][$k]=$data[$key][$k];
            }
            unset($data[$key]);
        }
        $menu = $data[0][0];

        foreach ($menu as $k => $v) {
            if(empty($v) && empty($result[$k]['link'])){
                unset($result[$k]);
            }
        }
        $data=array();
        foreach ($result as $k => $v) {
            $data[$v['level']][$v['pid']][$v['id']]=$v;
        }
        $menu = $data;
        */
    
		$cond=array('link' => CONTROLLER_NAME.'/'.ACTION_NAME,'level'=>array('in','2,3'), 'is_deleted'=>0,'module'=>MODULE_NAME);
		$cur=M('Menu')->field('id,pid,name,level')->where($cond)->order('level desc')->find();
        $menu['tab'] = $menu[$cur['level']][$cur['pid']];

        $menu['pid'] =$cur['pid'];
        $menu['cur'] = $cur['id'];
        $menu['title'] = $cur['name'];
        return $menu;
   }

}