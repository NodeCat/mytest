<?php
namespace Wms\Controller;
use Think\Controller;
class StockMoveDetailController extends CommonController {
	//页面展示数据映射关系 例如取出数据是qualified 显示为合格
	protected $filter = array(
			'type' => array('in' => '收货','on' => '上架','move_location' => '库存移动'),
		);
	//设置列表页选项
	public function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> false,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    //在search方法执行后，执行该方法
    protected function after_search(&$map){
        //替换调整单type查询条件
        if($map['stock_move.type'][1]){
            $map['stock_move.type'][1] = cn_to_en($map['stock_move.type'][1]);
        }
    }
}