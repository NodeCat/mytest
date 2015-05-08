<?php
namespace Wms\Controller;
use Think\Controller;
class LocationController extends CommonController {
   
    public function _before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => !isset($auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => !isset($auth['resume']))
            ),
        );
        $this->status_type='0';
    }

    protected function before_lists(&$M) {
            $map['type'] = '2';
            $M = $M->where($map);
            
            $data = $this->columns;
            unset($data); 
            $data["id"] = '';
            $data["wh_id"] = '仓库标识';
            $data["code"] = '区域标识';
            $data["name"] = '库位标识';
            $data['picking_line'] = '拣货路线';
            $data['putaway_line'] = '上架线路';
            $data['type_id'] = '库位类型名称';
            $data['is_mixed_pro'] = '混放货品';
            $data['is_mixed_batch'] = '混放批次';
            $data['status'] = '库位状态';
            $this->columns = $data;
    }
      
    protected function after_lists($data) {
         $location_detail = M('location_detail');
         $location_type = M('location_type');
         foreach($data as &$val) {dump($val);
            $list = $location_detail->getByLocation_id($val['id']);
            $val['picking_line'] = $list['picking_line'];
            $val['putaway_line'] = $list['putaway_line'];
            $val['is_mixed_pro'] = $list['is_mixed_pro'];
            $val['is_mixed_batch'] = $list['is_mixed_batch'];
            //$type = $location_type->getById($val['']);
            //$data['type'] = $type['name'];
         }
         dump($data);exit;
    }

    protected function after_add($id) {
        $location = M('location');
        $data['path'] = $id . '.';
        $location->where('id=' . $id)->save($data);

    }

    protected function before_edit(&$data) {
        $warehouse = M('warehouse');
        $wh_code = $warehouse->where('id=' . $data['wh_id'])->getField('code');
        $data['wh_code'] = $wh_code;
    }


}
