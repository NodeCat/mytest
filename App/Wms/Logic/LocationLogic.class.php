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
    public function getPackLocationId($area_name = 'PACK'){
        $map['wh_id'] = session('user.wh_id');
        $map['code'] = $area_name;
        $location_info = M('Location')->where($map)->field('id')->find();
        unset($map);
        $map['pid'] = $location_info['id'];
        $area_location_info = M('Location')->where($map)->field('id')->find();

        return $area_location_info;
    }
    
}
