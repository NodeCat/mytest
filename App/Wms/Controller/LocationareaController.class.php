<?php
namespace Wms\Controller;
use Think\Controller;
class LocationareaController extends CommonController {
   
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
        if(ACTION_NAME == 'index') {
            $map['type'] = '1';
            $M = $M->where($map);
        }else if (ACTION_NAME == 'index_location') {
            $map['type'] = '2';
            $M = $M->where($map);
            $data = $this->columns;
            
            $data["id"] = '';
            $data["wh_id"] = '仓库标识';
            $data["code"] = '区域标识';
            $data["name"] = '库位标识';
            $data['picking_line'] = '拣货路线';
            $data['putaway_line'] = '上架线路';
            $data['type'] = '库位类型名称';
            $data['is_mixed_pro'] = '混放货品';
            $data['is_mixed_batch'] = '混放批次';
            $data['status'] = '区域状态';
            $this->columns = $data;
        }
    }
       
    protected function after_add($id) {
        $location = M('locationarea');
        $data['path'] = $id . '.';
        $location->where('id=' . $id)->save($data);

    }

    protected function before_edit(&$data) {
        $warehouse = M('warehouse');
        $wh_code = $warehouse->where('id=' . $data['wh_id'])->getField('code');
        $data['wh_code'] = $wh_code;
    }


}
