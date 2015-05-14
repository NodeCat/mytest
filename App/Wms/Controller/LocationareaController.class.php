<?php
namespace Wms\Controller;
use Think\Controller;
class LocationareaController extends CommonController {

    protected function before_delete ($ids) {
        $location = M('location'); 
        foreach ($ids as $val) {
            $res = $location->where('type=2 AND is_deleted=0 AND pid=' . $val)->count();
            if($res) {
	            $this->msgReturn(0,'区域下存在库位，无法删除');
            }
            
        }
    }

    protected function before_lists(&$M) {
        $wh_id = I('get.wh_id');
        if($wh_id) {
            $map['wh_id'] = $wh_id;
        }
        $map['type'] = '1';
        $M = $M->where($map);
        $data = $this->columns;
        unset($data);
        $data['id'] = '区域id';
        $data['wh_code'] = '仓库标识';
        $data['code'] = '区域标识'; 
        $data['status'] = '库存状态';
        $this->columns = $data;
    }
     
    protected function after_lists(&$data) {
        /*$warehouse = M('warehouse');
        foreach($data as &$val) {
            $list = $warehouse->getById($val['wh_id']);
            $val['warehouse_code'] = $list['code'];
        }*/
        //dump($data);exit;
    }

    protected function after_add($id) {
        $location = M('location');
        $data['path'] = $id . '.';
        $location->where('id=' . $id)->save($data);

    }
    
    protected function before_search(&$query) { 
            $location = M('location');
            $wh_id = session('user.wh_id');
            unset($query['location.code']); 
            $location_area = $location->where('is_deleted=0 AND type=1 AND wh_id=' . $wh_id)->select();
            $area_code = array_column($location_area, 'code', 'id');
            $status = array(
                '0' =>'请选择',
                '1' =>'合格状态',
                '2' =>'残次状态'
            );
            $query['location.id'] = array(
                'title' => '区域标识',
                'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $area_code
            );
            $query['location.name'] = array(
                'title' => '区域名称',
                'query_type' => 'like',
                'control_type' => 'text',
                'value' => '' 
            );
            $query['location.status'] = array(
                'title' => '库存状态',
                'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $status
            );
            $this->query = $query;
    } 
}
