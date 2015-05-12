<?php
namespace Common\Widget;
use Think\Controller;
class MenuWidget extends Controller {
    public function getlist(){
        $M=M('Menu');
        $map = array('status'=>'1','is_deleted'=>0);
        $result= $M->field("id,name,level,pid,icon,link,target")->where($map)->order('pid,queue,id')->select();
        foreach ($result as $k => $v) {
            //$result[$k]['link'] = strtolower($v['link']);
            $result[$k]['link'] = $v['link'];
            $menu_link[$v['id']] = $result[$k]['link'];
        }
        $menu = $menu_link;

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
            if($v['level']=='2'){
                $data[$v['level']][$v['pid']][]=$v;
            }
            else{
                $data[$v['level']][$v['id']]=$v;
            }
        }
        $menu = $data;
        
		$cond=array('link' => CONTROLLER_NAME.'/'.ACTION_NAME,'level'=>2 );
		$cur=M('Menu')->field('pid,name')->where($cond)->find();
        $menu['tab'] = $menu[2][$cur['pid']];
        $menu['pid'] =$cur['pid'];
        $menu['title'] = $cur['name'];
        return $menu;
   }

}