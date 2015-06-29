<?php
namespace Wms\Controller;
use Think\Controller;
class LocationController extends CommonController {

    public function before_index(){
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($this->auth['view']),'new'=>'false'), 
            array('name'=>'edit', 'show' => isset($this->auth['view']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => isset($this->auth['delete']),'new'=>'false'),
            array('name'=>'import' ,'show' => isset($this->auth['import']),'new'=>'false'),
            array('name'=>'export' ,'show' => isset($this->auth['export']),'new'=>'true'),
            array('name'=>'print' ,'show' => isset($this->auth['printpage']),'new'=>'false'),
            array('name'=>'setting' ,'show' => isset($this->auth['setting']),'new'=>'false'),
        );
    }
    
    protected function before_lists(&$M) {
        $map['location.type'] = '2';
        $M = $M->where($map);
        
        $data = $this->columns;
        unset($data); 
        $data["id"] = '';
        $data["warehouse_code"] = '仓库标识';
        $data["area_name"] = '区域名称';
        $data["code"] = '库位标识';
        $data['picking_line'] = '拣货路线';
        $data['putaway_line'] = '上架线路';
        $data['type_name'] = '库位类型名称';
        $data['is_mixed_pro'] = '混放货品';
        $data['is_mixed_batch'] = '混放批次';
        $data['status'] = '库位状态';
        $this->columns = $data;
    }

    protected function before_search(&$query) { 
        $location = M('location');
        $wh_id = session('user.wh_id');
      
        $map['type'] = 1;
        $map['wh_id'] = $wh_id;
        $map['is_deleted'] = 0;
        $location_area = $location->where($map)->select();
        $area_name = array_column($location_area, 'name', 'id');
        
        $query = $this->query;
        $query['location.pid'] = array(
            'title' => '区域',
            'query_type' => 'eq',
            'control_type' => 'select',
            'value' => $area_name
        );
        $query = array_reverse($query,ture);
        $this->query = $query;
    }
      
    protected function after_lists(&$data) {
         $location_detail = M('location_detail');
         $location_type = M('location_type');
         $location_area = M('location');
         
         foreach($data as &$val) {
            $list = $location_detail->getByLocation_id($val['id']);
            $val['picking_line'] = $list['picking_line'];
            $val['putaway_line'] = $list['putaway_line'];
            $val['is_mixed_pro'] = $list['is_mixed_pro'];
            $val['is_mixed_batch'] = $list['is_mixed_batch'];
            
            $type = $location_type->getById($list['type_id']);
            $val['type_name'] = $type['name'];

            $area = $location_area->getById($val['pid']);
            $val['area_name'] = $area['name'];
         }
    }

    protected function before_add($M) {
        $data = I('post.');
        if(empty($data['wh_id']) || empty($data['area_id']) || empty($data['code']) || empty($data['type_id']) || empty($data['is_mixed_pro']) || empty($data['is_mixed_batch'])) {
            $this->msgReturn(0,'请填写完整信息');
        }
        //获取所属区域的库存状态，将其设为此新建库位的默认库存状态    
        $location_area = M('location');
        $map['id'] = $data['area_id'];
        $M->status = $location_area->where($map)->getField('status');
        
    }
    
    protected function after_add($data) {
        $post_data = I('post.');
        $location_detail = M('location_detail');
        $location = M('location'); 
        if($data) {
            $list['location_id'] = $data;
            $list['picking_line'] = $post_data['picking_line'];
            $list['putaway_line'] = $post_data['putaway_line'];
            $list['type_id'] = $post_data['type_id'];
            $list['is_mixed_pro'] = $post_data['is_mixed_pro'];
            $list['is_mixed_batch'] = $post_data['is_mixed_batch'];

            $location_detail->data($list)->add();
        }
            $location_data['pid'] = $post_data['area_id'];
            $location_data['path'] = $post_data['area_id'] . '.' . $data  . '.'; 
            $map['id'] = $data;
            $location->where($map)->save($location_data); 
    }

    protected function after_save() {
        if(ACTION_NAME == 'edit') {
            
            $post_data = I('post.');
            if(empty($post_data['pid']) || empty($post_data['code']) || empty($post_data['type_id']) || empty($post_data['is_mixed_pro']) || empty($post_data['is_mixed_batch'])) {
            
                $this->msgReturn(0,'请填写完整信息');
            
            }
            
            $location = M('location');
            
            //若编辑库位的所属区域，需要将库位的库存状态修改成新所属区域的库存状态
            unset($map);
            $map['id'] = $post_data['pid'];
            $status = $location->where($map)->getField('status');
            unset($map);
            $map['id'] = $post_data['id'];
            $data['status'] = $status;
            $location->where($map)->save($data);

            $location_detail = M('location_detail');
            $list['location_id'] = $post_data['id'];
            $list['picking_line'] = $post_data['picking_line'];
            $list['putaway_line'] = $post_data['putaway_line'];
            $list['type_id'] = $post_data['type_id'];
            $list['is_mixed_pro'] = $post_data['is_mixed_pro'];
            $list['is_mixed_batch'] = $post_data['is_mixed_batch'];
            unset($map);
            $map['location_id'] = $post_data['id'];
            $location_detail->where($map)->save($list);
        
            $location_data['path'] = $post_data['pid'] . '.' . $post_data['id'] . '.'; 
            unset($map);
            $map['id'] = $post_data['id'];
            $location->where($map)->save($location_data); 
        }
    }
    
    protected function before_delete ($ids) {
        $location = M('stock'); 
        foreach ($ids as $val) {
            $map['location_id'] = $val;

            $res = $location->where($map)->count();
            if($res) {
	            $this->msgReturn(0,'库存内有库存量，无法删除');
            }
            
        }
    }

    protected function after_delete($ids) {
        $location_detail = M('location_detail');
            $map['location_id'] = array('in',$ids);
            $data['is_deleted'] = 1;
            $res = $location_detail->where($map)->save($data);
        
    }

    public function printpage(){
        $location_ids = I('location_ids');
        if(empty($location_ids)){
            return false;
        }

        $map['id'] = array('in',$location_ids);
        $location_infos = M('location')->where($map)->field('id,pid,code')->select();
        unset($map);

        foreach($location_infos as $key => $location_info){
            //根据pid查询对应的区域信息
            $map['id'] = $location_info['pid'];
            $area_info = M('location')->where($map)->find();
            unset($map);

            $location_infos[$key]['area_name'] = $area_info['name'];
        }

        $data['location_infos'] = $location_infos;

        layout(false);
        $this->assign($data);
        $this->display('Location:print');
    }
}
