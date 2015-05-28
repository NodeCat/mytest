<?php
namespace Wms\Controller;
use Think\Controller;
class WarehouseController extends CommonController {
    protected function before_delete ($ids) {
        $location_area = M('location'); 
        foreach ($ids as $val) {
            $res = $location_area->where('type=1 AND is_deleted=0 AND wh_id=' . $val)->count();
            if($res) {
	            $this->msgReturn(0,'仓库下存在区域，无法删除');
            }
            
        }
    }
   
    protected function before_lists(&$M) {
        //无效仓库不能展示在区域创建的选择中
        if(ACTION_NAME == 'refer') {
            $map['status'] = 2;
        }
            
        $M = $M->where($map);
    
    }

}
