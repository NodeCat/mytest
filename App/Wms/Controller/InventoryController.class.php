<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryController extends CommonController {
	//页面展示数据映射关系 例如取出数据是Qualified 显示为合格
	protected $filter = array(
			'type' => array('fast' => '快速盘点','again' => '复盘'),
			'is_diff' => array('0' => '无', '1' => '有'),
			'status' => array('noinventory' => '未盘点', 'inventory' => '盘点中', 'confirm' => '待确认', 'closed' => '已关闭'),
		);
	//重载index方法
	public function index(){
		$tmpl = IS_AJAX ? 'Table:list':'index';
		$this->before_index();
		$this->search_addon = true;
		$this->lists($tmpl);
	}

	//lists方法执行前，执行该方法
	protected function before_lists(&$M){
		//整理显示项
		foreach($this->columns as $key => $column){
			$columns[$key] = $column;
		}
		$columns['count_location'] = '总库位数';
		$columns['remark'] = '备注';
		$columns['created_user'] = '创建人';
		$columns['created_time'] = '创建时间';
		$this->columns = $columns;
	}

	//lists方法执行后，执行该方法
	protected function after_lists(&$data){
		//整理数据项
		foreach($data as $key => $data_detail){
			//根据inventory_code 查询对应的inventory_detail总数
			$count_location = M('stock_inventory_detail')->where('inventory_code = "'.$data_detail['code'].'"')->count();
			$data[$key]['count_location'] = $count_location;
		}

	}

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
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true','link'=>'Inventorydetail/index'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['print']),'new'=>'false'), 
            array('name'=>'edit', 'show' => !isset($auth['print']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
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
			switch($data['type']){
				case 'fast':
					$data['type'] = '快速盘点';
					break;
				case 'again':
					$data['type'] = '复盘';
					break;
				default:
					break;
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

			$inventory_detail_list = M('stock_inventory_detail')->where('inventory_code = "'.$data['code'].'"')->select();
			
			foreach($inventory_detail_list as $key => $inventory_detail){
				$inventory_detail_list[$key]['location_code'] = M('location')->where('id = '.$inventory_detail['location_id'])->getField('code');
			}

			//添加pro_name字段
			$inventory_detail_list = A('Pms','Logic')->add_fields($inventory_detail_list,'pro_name');

			$this->inventory_detail_list = $inventory_detail_list;
		}
	}

	//add方式执行之前，执行该方法
	protected function before_add(&$M){
		$data = $M->data();
		if(!empty($data['location_id'])){
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
				if(!empty($data['location_id']) && !in_array($browser_pro_code, $stock_pro_codes)){
					$this->msgReturn(0,$browser_pro_code.'不在对应区域id:'.$data['location_id'].'中，请重新确认');
				}
			}
		}

		//合并要盘点的pro_codes
		//如果浏览器中传递的pro_code为空，则直接返回区域中得pro_code
		if(empty($browser_pro_codes)){
			$inventory_pro_codes = $stock_pro_codes;
		}elseif(!empty($browser_pro_codes) && empty($data['location_id'])){
			$inventory_pro_codes = $browser_pro_codes;
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
				//$location_id = I('location_id');
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
		}
	}

	//执行add方法前，执行该方法
	public function _before_add(){
		$inventory_sn = get_sn('inventory');
		$this->inventory_sn = $inventory_sn;
	}

	//差异确认 对某个盘点单进行差异确认，确认后，生成库存调整单，调整库存量
	public function checkIsDiff(){
		if(IS_AJAX){
			$ids = I('ids');
			$map = array('id' => array('in',$ids));
			//根据盘点单ids 查询inventory_info
			$inventory_infos = M('stock_inventory')->where($map)->select();
			//检查是否存在 已经有差异的盘点单，如果有，则提示错误
			foreach($inventory_infos as $inventory_info){
				if($inventory_info['is_diff'] == 1 || $inventory_info['status'] == 'closed'){
					$this->msgReturn(0,'盘点单'.$inventory_info['code'].'已经经过差异确认，或者盘点单已经关闭');
				}
			}
			//开始处理盘点单
			foreach($inventory_infos as $inventory_info){
				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$inventory_details = M('stock_inventory_detail')->where('inventory_code = "'.$inventory_info['code'].'"')->select();

				//如果有盘点差异，则新建库存调整单
				$create_adjust_flag = $inventory_is_diff = false;
				foreach($inventory_details as $inventory_detail){
					if($inventory_detail['pro_qty'] != $inventory_detail['theoretical_qty']){
						$create_adjust_flag = $inventory_is_diff = true;
						$refer_code = $inventory_detail['inventory_code'];
						break;
					}
				}
				if($create_adjust_flag){
					//新建库存调整单
					$adjustment_code = get_sn('adjust');
					$adjust_data = array(
						'code'=>$adjustment_code,
						'type'=>'inventory',
						'refer_code'=>$refer_code,
						);
					M('stock_adjustment')->data($adjust_data)->add();
				}


				foreach($inventory_details as $inventory_detail){
					//如果实盘量和库存量不同，处理库存变化
					if($inventory_detail['pro_qty'] != $inventory_detail['theoretical_qty']){
						//根据pro_code location_id 更新库存表
						M('stock')->where('pro_code = "'.$inventory_detail['pro_code'].'" and location_id = '.$inventory_detail['location_id'])->data(array('stock_qty'=>$inventory_detail['pro_qty']))->save();
						//添加库存移动表记录
						//to do ....
						//新建库存调整单详情
						$adjusted_qty = $inventory_detail['pro_qty'] - $inventory_detail['theoretical_qty'];
						$adjust_detail_data = array(
							'adjustment_code' => $adjustment_code,
							'pro_code' => $inventory_detail['pro_code'],
							'origin_qty' => $inventory_detail['theoretical_qty'],
							'adjusted_qty' => $adjusted_qty,
							);
						M('stock_adjustment_detail')->data($adjust_detail_data)->add();
						unset($adjust_detail_data);
						unset($adjusted_qty);
					}
					//根据stock_inventory_detail_id 更新对应的status为done
					M('stock_inventory_detail')->where('id = '.$inventory_detail['id'])->data(array('status'=>'done'))->save();
				}

				if($inventory_is_diff){
					$inventory_is_diff = 1;
				}else{
					$inventory_is_diff = 0;
				}
				//更新为有差异
				M('stock_inventory')->where('id = '.$inventory_info['id'])->data(array('is_diff' => $inventory_is_diff,'status'=>'closed'))->save();
			}
			$this->msgReturn(1);
		}
	}

	//差异确认 对某个盘点单进行差异确认，确认后，生成库存调整单，调整库存量
	public function closed(){
		if(IS_AJAX){
			$ids = I('ids');
			$map = array('id' => array('in',$ids));
			//根据盘点单ids 查询inventory_info
			$inventory_infos = M('stock_inventory')->where($map)->select();
			
			//将对应盘点单置为closed
			foreach($inventory_infos as $inventory_info){
				M('stock_inventory')->where('id = '.$inventory_info['id'])->data(array('status'=>'closed'))->save();

				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$inventory_details = M('stock_inventory_detail')->where('inventory_code = "'.$inventory_info['code'].'"')->select();
				//根据stock_inventory_detail_id 更新对应的status为done
				foreach($inventory_details as $inventory_detail){
					M('stock_inventory_detail')->where('id = '.$inventory_detail['id'])->data(array('status'=>'done'))->save();
				}
			}
		}
		$this->msgReturn(1);
	}

	//差异复盘，如果有差异，则针对差异，创建新的盘点单，同时关闭之前的盘点单
	public function diffInventoryAgain(){
		if(IS_AJAX){
			$ids = I('ids');
			$map = array('id' => array('in',$ids));
			//根据盘点单ids 查询inventory_info
			$inventory_infos = M('stock_inventory')->where($map)->select();

			foreach($inventory_infos as $inventory_info){
				$inventory_is_diff = false;
				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$inventory_details = M('stock_inventory_detail')->where('inventory_code = "'.$inventory_info['code'].'"')->select();
				//判断是否有差异，如果有则新建复盘单，以及复盘详情
				foreach($inventory_details as $inventory_detail){
					if($inventory_detail['pro_qty'] != $inventory_detail['theoretical_qty']){
						$inventory_is_diff = true;
						//$refer_code = $inventory_detail['inventory_code'];
						break;
					}
				}

				if($inventory_is_diff){
					//新建复盘单
					$inventory_code = get_sn('inventory');
					$inventory_data = array(
						'location_id' => $inventory_info['location_id'],
						'code' => $inventory_code,
						'type' => 'again',
						'status' => 'noinventory',
						);
					M('stock_inventory')->data($inventory_data)->add();
					
					//创建复盘单详情
					foreach($inventory_details as $inventory_detail){
						if($inventory_detail['pro_qty'] != $inventory_detail['theoretical_qty']){
							$inventory_detail_data = array(
								'inventory_code' => $inventory_code,
								'pro_code' => $inventory_detail['pro_code'],
								'location_id' => $inventory_detail['location_id'],
								'pro_qty' => 0,
								'theoretical_qty' => $inventory_detail['theoretical_qty'],
								);
							M('stock_inventory_detail')->data($inventory_detail_data)->add();
							unset($inventory_detail_data);
						}
					}
					unset($inventory_data);
					unset($inventory_code);
					unset($inventory_details);
				}

				//将原盘点单状态置为closed
				M('stock_inventory')->where('id = '.$inventory_info['id'])->data(array('status'=>'closed'))->save();

				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$inventory_details = M('stock_inventory_detail')->where('inventory_code = "'.$inventory_info['code'].'"')->select();
				//根据stock_inventory_detail_id 更新对应的status为done
				foreach($inventory_details as $inventory_detail){
					M('stock_inventory_detail')->where('id = '.$inventory_detail['id'])->data(array('status'=>'done'))->save();
				}
			}
			$this->msgReturn(1);
		}
	}

	//手持设备扫描盘点 根据inventory_code返回对应详情
	public function getInvDetailByInvCode(){
		$inventory_code = I('inventory_code');
		$inventory_detail_infos = M('stock_inventory_detail')->where('inventory_code = "'.$inventory_code.'"')->select();

		$data['status'] = 1;
		$data['data'] = $inventory_detail_infos;

		$this->ajaxReturn($data);
	}

	//
}