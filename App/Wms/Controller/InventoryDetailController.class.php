<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryDetailController extends CommonController {
    protected $columns = array('id' => '',
            'pro_code' => '产品标识',
            'location_code' => '库位',
            'pro_qty' => '盘点数量',
            'theoretical_qty' => '理论库存量',
            'diff_qty' => '差异量',
            );
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
            array('name'=>'view', 'show' => false,'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
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
        $tmpl = IS_AJAX ? 'Table:list':'index';
        //$this->before($map,'index');
        $this->before_index();
        $this->lists($tmpl);
    }

    //lists方法执行前，执行该方法
	protected function before_lists(&$M){

		//根据inventory_id 查询对应code
		$inventory_id = I('id');
        $map['id'] = $inventory_id;
		$inventory_code = M('stock_inventory')->where($map)->getField('code');
        unset($map);
		$map['inventory_code'] = $inventory_code;
		$M->where($map);
    }

    //lists方法执行后，执行该方法
	protected function after_lists(&$data){
		//整理数据项
		foreach($data as $key => $data_detail){
			$data[$key]['diff_qty'] = $data_detail['theoretical_qty'] - $data_detail['pro_qty'];
		}
        $this->invetory_code = $data[0]['inventory_code'];
	}

}