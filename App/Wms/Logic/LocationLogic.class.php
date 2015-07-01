<?php
namespace Wms\Logic;

class LocationLogic{

    public function getParentById($id) {
        $parent = M('location');
        $map['id'] = $id; 
        $pid = $parent->where($map)->getField('pid');
        
        unset($map);
        $map['id'] = $pid;
        $parent_info = $parent->where($map)->find();
        return $parent_info;
    }

    //根据区域标识，查询当前仓库的发货区的location_id
    public function getPackLocationId($area_name = array()){
        $map['wh_id'] = session('user.wh_id');
        $map['code'] = array('in',$area_name);
        $location_infos = M('Location')->where($map)->field('id')->select();
        unset($map);
        $location_id = array();
        foreach($location_infos as $location_info){
            $location_id[] = $location_info['id'];
        }

        $map['pid'] = array('in',$location_id);
        $area_location_info = M('Location')->where($map)->field('id')->select();

        return $area_location_info;
    }
    
}
