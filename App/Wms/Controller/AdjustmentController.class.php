<?php
namespace Wms\Controller;
use Think\Controller;
class AdjustmentController extends CommonController {
    //页面展示数据映射关系 例如取出数据是Qualified 显示为合格
    protected $filter = array(
        'type' => array('inventory' => '盘点','move' => '库存移动'),
        //'is_diff' => array('0' => '无', '1' => '有'),
        //'status' => array('noinventory' => '未盘点', 'inventory' => '盘点中', 'confirm' => '待确认', 'closed' => '已关闭'),
        'status' => array('qualified' => '合格'),
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
            array('name'=>'print' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    //edit方法执行前，执行该方法
	protected function before_edit(&$data){
		//替换编辑页面的展示信息
		if(!IS_AJAX){
            $map['adjustment_code'] = $data['code'];
			$adjustment_detail_list = M('stock_adjustment_detail')->where($map)->select();
            unset($map);

            foreach($adjustment_detail_list as $key => $adjustment_detail){
				//$adjustment_detail_list[$key]['location_code'] = M('location')->where('id = '.$inventory_detail['location_id'])->getField('code');
                $adjustment_detail_list[$key] = $adjustment_detail;
            }

            //添加pro_name字段
            $adjustment_detail_list = A('Pms','Logic')->add_fields($adjustment_detail_list,'pro_name');

			$this->adjustment_detail_list = $adjustment_detail_list;
		}
	}

    //在search方法执行后，执行该方法
    protected function after_search(&$map){
        //替换调整单type查询条件
        if($map['stock_adjustment.type'][1]){
            $map['stock_adjustment.type'][1] = cn_to_en($map['stock_adjustment.type'][1]);
        }
    }
}