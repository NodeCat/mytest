<?php
namespace Wms\Controller;
use Think\Controller;
class LocationTypeController extends CommonController {
    
    protected function before_lists(&$M) {
            $data = $this->columns;
            $data["length"] = '长度(cm)';
            $data['width'] = '宽度(cm)';
            $data['height'] = '高度(cm)';
            $data['load'] = '载重量(kg)';
            $this->columns = $data;
    }
    
    protected function before_delete ($ids) {
        $location_detail = M('location_detail'); 
        $map['type_id'] = array('in', $ids);
        $map['is_deleted'] = 0;
        $res = $location_detail->where($map)->count();
        if($res) {
	        $this->msgReturn(0,'库位存在此库位类型，无法删除');
        }
    }   

}
