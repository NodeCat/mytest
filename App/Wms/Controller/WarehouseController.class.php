<?php
namespace Wms\Controller;
use Think\Controller;
class WarehouseController extends CommonController {
   
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

}
