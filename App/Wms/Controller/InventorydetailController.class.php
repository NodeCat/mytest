<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryDetailController extends CommonController {
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
        $this->toolbar =array(
            array('name'=>'add', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    public function index() {
        //$this->before($map,'index');
        $this->before_index();
        $this->lists('index');
    }

    //lists方法执行前，执行该方法
	protected function before_lists(&$M){
        $this->columns = array (
			'id' => '',
			'pro_code' => '产品标识',
			'location_code' => '库位',
			'pro_qty' => '盘点数量',
			'theoretical_qty' => '理论库存量',
			'diff_qty' => '差异量',
		);

		//根据inventory_id 查询对应code
		$inventory_id = I('id');
		$inventory_code = M('stock_inventory')->where('id = '.$inventory_id)->getField('code');
		$map['inventory_code'] = $inventory_code;
		$M->where($map);
    }

    //lists方法执行后，执行该方法
	protected function after_lists(&$data){
		//整理数据项
		foreach($data as $key => $data_detail){
			$data[$key]['diff_qty'] = $data_detail['theoretical_qty'] - $data_detail['pro_qty'];
		}
        $data['invetory_code'] = $data[0]['inventory_code'];
	}


}