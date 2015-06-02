<?php
namespace Wms\Controller;
use Think\Controller;
class LocationAreaController extends CommonController {

    protected function before_delete ($ids) {
        $location = M('location'); 
        $map['pid'] = array('in', $ids);
        $map['type'] = 2;
        $map['is_deleted'] = 0;

        $res = $location->where($map)->count();
        if($res) {
	        $this->msgReturn(0,'区域下存在库位，无法删除');
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
        $data['name'] = '区域名称';
        $data['status'] = '库存状态';
        $this->columns = $data;
    }
     
    protected function after_lists(&$data) {
        /*$warehouse = M('warehouse');
        foreach($data as &$val) {
            $list = $warehouse->getById($val['wh_id']);
            $val['warehouse_code'] = $list['code'];
        }*/
    }
    
    protected function after_save(&$data) {
        $location = M('location');
        //如果修改区域的库存状态,则同时修改此区域下所有的库位的库存状态
        $map['id'] = $data;
        $status = $location->where($map)->getField('status');
        unset($map);
        $map['pid'] = $data;
        $map['is_deleted'] = 0;
        $list['status'] = $status;
        $location->where($map)->save($list);
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
            
            $map['is_deleted'] = 0;
            $map['type'] = 1;
            $map['wh_id'] = $wh_id;
            $location_area = $location->where($map)->select();
            $area_code = array_column($location_area, 'code', 'id');
            $status = array(
                'qualified' =>'合格状态',
                'unqualified' =>'残次状态',
                'freeze' => '冻结'
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
