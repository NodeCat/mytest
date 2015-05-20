<?php
namespace Wms\Controller;
use Think\Controller;
class StockMoveDetailController extends CommonController {
	//页面展示数据映射关系 例如取出数据是qualified 显示为合格
	protected $filter = array(
			'type' => array('in' => '收货','on' => '上架','move_location' => '库存移动'),
		);

    protected $columns = array('id' => '',
            'wh_code' => '仓库',
            'created_time' => '操作时间',
            'user_nickname' => '操作人',
            'pro_code' => '货品号',
            'pro_name' => '货品名',
            'src_location_code' => '原库位',
            'dest_location_code' => '目标库位',
            'refer_code' => '关联单据',
            'type' => '类型',
            'direction' => '方向',
            'move_qty' => '变化数量',
            'batch' => '批次',
            );

    protected $query   = array (
        'stock_move.refer_code' => array (
            'title' => '关联单据',
            'query_type' => 'like',
            'control_type' => 'text',
            'value' => '',
        ),
    );
	//设置列表页选项
	public function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
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
    /*protected function after_search(&$map){
        //替换调整单type查询条件
        if($map['stock_move.type'][1]){
            $map['stock_move.type'][1] = cn_to_en($map['stock_move.type'][1]);
        }
    }*/

}