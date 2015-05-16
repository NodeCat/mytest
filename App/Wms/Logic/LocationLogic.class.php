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
}
