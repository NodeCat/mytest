<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryDetailController extends CommonController {
    protected $columns = array('id' => '',
            'location_code' => '库位',
            'pro_code' => '货品标识',
            'pro_name' => '货品名称',
            'theoretical_qty' => '原数量',
            'pro_qty' => '实盘量',
            'diff_qty' => '差异量',
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
        //添加pro_name字段
        $data = A('Pms','Logic')->add_fields($data,'pro_name');

        //根据盘点code 查询盘点单信息
        $map['code'] = $data[0]['inventory_code'];
        $inventory_info = M('stock_inventory')->where($map)->find();

        //添加 创建人
        $map['id'] = $inventory_info['created_user'];
        $inventory_info['created_user_nickname'] = M('user')->where($map)->getField('nickname');
        unset($map);

        //添加 盘点人
        $map['id'] = $inventory_info['updated_user'];
        $inventory_info['updated_user_nickname'] = M('user')->where($map)->getField('nickname');
        unset($map);

        //添加盘点结束时间
        $map['inventory_code'] = $data[0]['inventory_code'];
        $inventory_info['end_time'] = M('stock_inventory_detail')->where($map)->order('updated_time desc')->getField('updated_time');

        $this->inventory_info = $inventory_info;

    }

    //save方法执行后，执行该方法
    protected function after_save($id){
        //变更盘点详情状态
        $map['id'] = $id;
        $data['status'] = 'done';
        M('stock_inventory_detail')->where($map)->save($data);
        unset($data);
        //unset($map);

        //根据inventory_code 查询盘点单信息
        $inventory_code = M('stock_inventory_detail')->where($map)->getField('inventory_code');
        unset($map);
        $map['code'] = $inventory_code;
        $inventory_info = M('stock_inventory')->where($map)->find();
        unset($map);

        //更新对应盘点单状态
        //获得所有盘点详情的状态
        $map['inventory_code'] = $inventory_code;
        $inventory_detail = M('stock_inventory_detail')->where($map)->group('status')->getField('status',true);
        unset($map);

        //如果所有盘点详情状态都为done，则更新盘点单为待确认
        if(count($inventory_detail) == 1 && $inventory_detail[0] == 'done'){
            $map['code'] = $inventory_code;
            $data['status'] = 'confirm';
            M('stock_inventory')->where($map)->save($data);
            unset($data);
        }

    }
}