<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryController extends CommonController {
	//页面展示数据映射关系 例如取出数据是Qualified 显示为合格
	protected $filter = array(
			'type' => array('fast' => '快速盘点'),
			'is_diff' => array('0' => '无', '1' => '有'),
			'status' => array('noinventory' => '未盘点', 'inventory' => '盘点中', 'confirm' => '待确认', 'closed' => '已关闭'),
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
    }

	//serach方法执行后，执行该方法
	protected function after_search(&$map){
		if(IS_AJAX){
			//用于重新整理查询条件
			//盘点单类型
			$inventory_type = I('type');
			if(!empty($inventory_type)){
				$map['stock_inventory.type'] = array('eq',$inventory_type);
			}
			//盘点单状态
			$inventory_status = I('status');
			if(!empty($inventory_status)){
				$map['stock_inventory.status'] = array('eq',$inventory_status);
			}
			//有无差异
			$inventory_is_diff = I('is_diff');
			$map['stock_inventory.is_diff'] = array('eq',$inventory_is_diff);
			
		}
	}

	//edit方法执行前，执行该方法
	protected function before_edit(&$data){
		//替换编辑页面的展示信息
		if(IS_AJAX){
			//根据warehouse.id 查询仓库name
			$location_name = M('location')->where('id = '.$data['location_id'])->getField('name');
			$data['location_name'] = $location_name;
		}
		//view展示
		else{
			if($data['type'] == 'fast'){
				$data['type'] = '快速盘点';
			}
			switch($data['status']){
				case 'noinventory':
					$data['status'] = '未盘点';
					break;
				case 'inventory':
					$data['status'] = '盘点中';
					break;
				case 'confirm':
					$data['status'] = '待确认';
					break;
				case 'closed':
					$data['status'] = '已关闭';
					break;
				default:
					break;
			}

			$inventory_detail_list = M('stock_inventory_detail')->where('inventory_code = '.$data['code'])->select();
			foreach($inventory_detail_list as $key => $inventory_detail){
				$inventory_detail_list[$key]['location_code'] = M('location')->where('id = '.$inventory_detail['location_id'])->getField('code');
			}

			$this->inventory_detail_list = $inventory_detail_list;
		}
	}

	//add方式执行之前，执行该方法
	protected function before_add(&$M){
		$data = $M->data();
		//根据区域location_id 查询stock中所有在该区域location_id的pro_code
		$location_ids = M('location')->where('pid = '.$data['location_id'].' and type = 2')->getField('id',true);
		if(empty($location_ids)){
			$this->msgReturn(0,'该区域id:'.$data['location_id'].'不存在库位');
		}
		//根据区域内的所有库位id，查询对应的库存
		$map = array('location_id' => array('in',$location_ids));
		$stock_pro_codes = M('Stock')->where($map)->getField('pro_code',true);
		if(empty($stock_pro_codes)){
			$this->msgReturn(0,'该区域id:'.$data['location_id'].'中，没有任何sku');
		}
		//获得页面传递过来的pro_codes，如果不为空，则需要匹配，pro_codes是否在提交过来的location_id范围内
		$browser_pro_codes = I('pro_codes');
		if(!empty($browser_pro_codes)){
			$browser_pro_codes = explode("\n", $browser_pro_codes);
			//判断提交过来的pro_codes中，是否有不存在对应的location_id，如果有，则提示报错，该pro_code不在对应的location_id中
			foreach($browser_pro_codes as $key => $browser_pro_code){
				if(empty($browser_pro_code)){
					continue;
				}
				$browser_pro_codes[$key] = $browser_pro_code = trim($browser_pro_code);
				if(!in_array($browser_pro_code, $stock_pro_codes)){
					$this->msgReturn(0,$browser_pro_code.'不在对应区域id:'.$data['location_id'].'中，请重新确认');
				}
			}
		}

		//合并要盘点的pro_codes
		if(empty($browser_pro_codes)){
			$inventory_pro_codes = $stock_pro_codes;
		}else{
			$inventory_pro_codes = array_intersect($browser_pro_codes,$stock_pro_codes);
		}

		//生成要盘点的所有pro_codes
		$this->inventory_pro_codes = $inventory_pro_codes;
	}

	//save方法执行之后，执行该方案
	protected function after_save($id){
		if(IS_POST){
			if(ACTION_NAME == 'add'){
				//盘点单创建完毕，准备写入盘点单详情
				//根据inventory_id 查询inventory_code
				$inventory_code = M('stock_inventory')->where('id = '.$id)->getField('code');
				//获得区域id
				$location_id = I('location_id');
				//获得所有要盘点的pro_codes
				//根据inventory_pro_codes 查询对应的库存量stock_qty
				$map['pro_code'] = array('in', $this->inventory_pro_codes);
				$stock_lists = M('Stock')->where($map)->getField('pro_code,stock_qty,location_id',true);
				//插入盘点详情表，stock_inventory_detail
				foreach($stock_lists as $pro_code => $stock_list){
					$data_list[] = array(
						'inventory_code'=>$inventory_code,
						'pro_code'=>$pro_code,
						'location_id'=>$stock_list['location_id'],
						'pro_qty'=>0,
						'theoretical_qty'=>$stock_list['stock_qty'],
						);
				}

				//写入数据
				M('stock_inventory_detail')->addAll($data_list);
			}
			if(ACTION_NAME == 'edit'){
				//修改盘点单时，进行以下操作
				//如果是有差异，则需要生成调整单，同时关闭盘点单
				
			}
		}
	}
}