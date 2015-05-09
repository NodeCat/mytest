<?php
namespace Wms\Controller;
use Think\Controller;
class AdjustmentController extends CommonController {
	//设置列表页选项
	public function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
    }

    //edit方法执行前，执行该方法
	protected function before_edit(&$data){
		//替换编辑页面的展示信息
		if(!IS_AJAX){
			$adjustment_detail_list = M('stock_adjustment_detail')->where('adjustment_code = "'.$data['code'].'"')->select();
			foreach($adjustment_detail_list as $key => $adjustment_detail){
				//$adjustment_detail_list[$key]['location_code'] = M('location')->where('id = '.$inventory_detail['location_id'])->getField('code');
			}

			$this->adjustment_detail_list = $adjustment_detail_list;
		}
	}
}