<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryController extends CommonController {
	//页面展示数据映射关系 例如取出数据是Qualified 显示为合格
	protected $filter = array(
			'type' => array('fast' => '快速盘点','again' => '复盘'),
			'is_diff' => array('0' => '无', '1' => '有'),
			'status' => array('noinventory' => '未盘点', 'inventorying' => '盘点中', 'confirm' => '待确认', 'closed' => '已关闭'),
		);
	protected $columns = array('id' => '',
            'code' => '盘点单号',
            'location_name' => '区域',
            'type' => '盘点类型',
            'status' => '状态',
            'is_diff' => '是否有差异',
            'count_location' => '总库位数',
            'remark' => '备注',
            'user_nickname' => '创建人',
            'created_time' => '创建时间', 
            );
	protected $query   = array (
		'stock_inventory.code' => array (
		    'title' => '盘点单号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => 'name',
		),
		'stock_inventory.type' => array (
		    'title' => '盘点类型',
		    'query_type' => 'eq',
		    'control_type' => 'select',
		    'value' => array('fast'=>'快速盘点'),
		),
		'stock_inventory.status' => array (
		    'title' => '盘点状态',
		    'query_type' => 'eq',
		    'control_type' => 'select',
		    'value' => array('noinventory'=>'未盘点','inventorying'=>'盘点中','confirm'=>'待确认','closed'=>'已关闭'),
		),
		'stock_inventory.is_diff' => array (
		    'title' => '有无差异',
		    'query_type' => 'eq',
		    'control_type' => 'select',
		    'value' => array(0=>'无',1=>'有'),
		),
		'stock_inventory.created_user' => array (
		    'title' => '创建人',
		    'query_type' => 'eq',
		    'control_type' => 'text',
		    'value' => '',
		),
		'stock_inventory.created_time' =>    array (    
            'title' => '开始时间',     
            'query_type' => 'between',     
            'control_type' => 'datetime',     
            'value' => '',   
        ), 
	);
	//重载index方法
	public function index(){
		$tmpl = IS_AJAX ? 'Table:list':'index';
		$this->before_index();
		$this->search_addon = true;
		$this->lists($tmpl);
	}

	//lists方法执行前，执行该方法
	/*protected function before_lists(&$M){
		//整理显示项
		foreach($this->columns as $key => $column){
			$columns[$key] = $column;
		}
		$columns['count_location'] = '总库位数';
		$columns['remark'] = '备注';
		$columns['user_nickname'] = '创建人';
		$columns['created_time'] = '创建时间';
		unset($columns['created_user']);
		$this->columns = $columns;
	}*/

	//lists方法执行后，执行该方法
	protected function after_lists(&$data){
		//整理数据项
		foreach($data as $key => $data_detail){
			//根据inventory_code 查询对应的inventory_detail总数
			$map['inventory_code'] = $data_detail['code'];
			$count_location = M('stock_inventory_detail')->where($map)->count();
			unset($map);
			$data[$key]['count_location'] = $count_location;
		}

	}

	//设置列表页选项
	protected function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true','link'=>'InventoryDetail/index'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'print','link'=>'printpage','icon'=>'print','title'=>'打印', 'show'=>isset($this->auth['printpage']),'new'=>'true','target'=>'_blank')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($this->auth['add']),'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

	//serach方法执行后，执行该方法
	protected function after_search(&$map){
		if(IS_AJAX){
			//用于重新整理查询条件
			if(!empty($map['stock_inventory.created_user'])){
				$created_user_name = $map['stock_inventory.created_user'][1];

				$user_info_map['nickname'] = $created_user_name;
				$user_info = M('user')->where($user_info_map)->find();
				unset($user_info_map);

				$map['stock_inventory.created_user'][1] = $user_info['id'];
			}
		}
	}

	//edit方法执行前，执行该方法
	protected function before_edit(&$data){
		//替换编辑页面的展示信息
		if(IS_AJAX){
			//根据warehouse.id 查询仓库name
			$map['id'] = $data['location_id'];
			$location_name = M('location')->where($map)->getField('name');
			unset($map);
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
				case 'inventorying':
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

			$map['inventory_code'] = $data['code'];
			$inventory_detail_list = M('stock_inventory_detail')->where($map)->select();
			unset($map);

			foreach($inventory_detail_list as $key => $inventory_detail){
				$map['id'] = $inventory_detail['location_id'];
				$inventory_detail_list[$key]['location_code'] = M('location')->where($map)->getField('code');
				unset($map);
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
			$map['pid'] = $data['location_id'];
			$map['type'] = 2;
			$location_ids = M('location')->where($map)->getField('id',true);
			unset($map);
			if(empty($location_ids)){
				$this->msgReturn(0,'该区域id:'.$data['location_id'].'不存在库位');
			}
			//根据区域内的所有库位id，查询对应的库存
			$map = array('location_id' => array('in',$location_ids));
			$stock_pro_codes = M('Stock')->where($map)->getField('pro_code',true);
			unset($map);
			if(empty($stock_pro_codes)){
				$this->msgReturn(0,'该区域id:'.$data['location_id'].'中，没有任何sku');
			}

			$this->inventory_location_ids = $location_ids;
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

	protected function before_save(&$M){
		//生成盘点单号
		$inventory_sn = get_sn('inventory');
		$M->code = $inventory_sn;
	}

	//save方法执行之后，执行该方案
	protected function after_save($id){
		if(IS_POST){
			if(ACTION_NAME == 'add'){
				//盘点单创建完毕，准备写入盘点单详情
				//根据inventory_id 查询inventory_code
				$map['id'] = $id;
				$inventory_code = M('stock_inventory')->where($map)->getField('code');
				unset($map);
				//获得区域id
				//$location_id = I('location_id');
				//获得所有要盘点的pro_codes
				//根据inventory_pro_codes 查询对应的库存量stock_qty
				$map['pro_code'] = array('in', $this->inventory_pro_codes);
				$map['wh_id'] = session('user.wh_id');
				$map['location_id'] = array('in', $this->inventory_location_ids);
				//$stock_lists = M('Stock')->where($map)->getField('pro_code,stock_qty,location_id',true);
				$stock_lists = M('Stock')->field('pro_code, location_id, sum(stock_qty) as stock_qty')->group('location_id,pro_code')->where($map)->select();
				unset($map);
				//插入盘点详情表，stock_inventory_detail
				foreach($stock_lists as $pro_code => $stock_list){
					$data_list[] = array(
						'inventory_code'=>$inventory_code,
						'pro_code'=>$stock_list['pro_code'],
						'location_id'=>$stock_list['location_id'],
						'pro_qty'=>0,
						'theoretical_qty'=>$stock_list['stock_qty'],
						);
				}

				//写入数据
				$stock_inventory_detail = D('Inventory_detail');
				foreach($data_list as $key => $value){
					$data_list[$key] = $stock_inventory_detail->create($value);
				}
				$stock_inventory_detail->addAll($data_list);
			}
		}
	}

	//执行add方法前，执行该方法
	/*public function _before_add(){
		$inventory_sn = get_sn('inventory');
		$this->inventory_sn = $inventory_sn;
	}*/

	//差异确认 对某个盘点单进行差异确认，确认后，生成库存调整单，调整库存量
	public function checkIsDiff(){
		if(IS_AJAX){
			$ids = I('ids');
			$map = array('id' => array('in',$ids));
			//根据盘点单ids 查询inventory_info
			$inventory_infos = M('stock_inventory')->where($map)->select();
			unset($map);
			//检查是否存在 已经有差异的盘点单，如果有，则提示错误
			foreach($inventory_infos as $inventory_info){
				if($inventory_info['status'] == 'closed'){
					$this->msgReturn(0,'盘点单'.$inventory_info['code'].'已经经过差异确认，或者盘点单已经关闭');
				}
				if($inventory_info['status'] != 'confirm'){
					$this->msgReturn(0,'盘点单'.$inventory_info['code'].'的状态不是待确认，请操作完毕再进行确认');
				}
			}
			//开始处理盘点单
			foreach($inventory_infos as $inventory_info){
				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$map['inventory_code'] = $inventory_info['code'];
				$inventory_details = M('Stock_inventory_detail')->where($map)->select();
				unset($map);

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
						'wh_id'=>session('user.wh_id'),
						);
					$stock_adjustment = D('Adjustment');
					$adjust_data = $stock_adjustment->create($adjust_data);
					$stock_adjustment->data($adjust_data)->add();
					unset($stock_adjustment);
				}


				foreach($inventory_details as $inventory_detail){
					//如果实盘量和库存量不同，处理库存变化
					if($inventory_detail['pro_qty'] != $inventory_detail['theoretical_qty']){
						$map['pro_code'] = $inventory_detail['pro_code'];
						$map['location_id'] = $inventory_detail['location_id'];
						//根据pro_code location_id 查询是否有记录
						$stock_info = M('stock')
						->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')
						->where($map)
						->order('stock_batch.product_date')
						->field('stock.*,stock_batch.product_date')
						->group('stock_batch.code')
						->find();
						unset($map);

						//判断 盘盈 盘亏 
						//盘盈 添加盘盈批次 创建新的库存记录
						if($inventory_detail['pro_qty'] > $inventory_detail['theoretical_qty']){
							//盘盈批次：有库存记录的用最早批次，没库存的用最近批次
							$this->profit_todo($stock_info,$inventory_detail);

							//默认为合格状态
							$stock_info['status'] = (empty($stock_info['status'])) ? 'qualified' : $stock_info['status'];
						}
						//盘亏 按照先进先出原则 减去最早的批次量
						if($inventory_detail['pro_qty'] < $inventory_detail['theoretical_qty']){
							//根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
							$this->loss_todo($inventory_detail);
						}

						//新建库存调整单详情
						$adjusted_qty = $inventory_detail['pro_qty'] - $inventory_detail['theoretical_qty'];
						$adjust_detail_data = array(
							'adjustment_code' => $adjustment_code,
							'pro_code' => $inventory_detail['pro_code'],
							'origin_qty' => $inventory_detail['theoretical_qty'],
							'adjusted_qty' => $adjusted_qty,
							'origin_status' => $stock_info['status'],
							'adjust_status' => $stock_info['status'],
							);
						$stock_adjustment_detail = D('AdjustmentDetail');
						$adjust_detail_data = $stock_adjustment_detail->create($adjust_detail_data);
						$stock_adjustment_detail->data($adjust_detail_data)->add();
						unset($stock_adjustment_detail);
						unset($adjust_detail_data);
						unset($adjusted_qty);
						unset($stock_info);
					}
					//根据stock_inventory_detail_id 更新对应的status为done
					$map['id'] = $inventory_detail['id'];
					M('stock_inventory_detail')->where($map)->data(array('status'=>'done'))->save();
					unset($map);
				}

				if($inventory_is_diff){
					$inventory_is_diff = 1;
				}else{
					$inventory_is_diff = 0;
				}
				//更新为有差异
				$map['id'] = $inventory_info['id'];
				M('stock_inventory')->where($map)->data(array('is_diff' => $inventory_is_diff,'status'=>'closed'))->save();
				unset($map);
			}
			$this->msgReturn(1);
		}
	}

	//盘盈处理
	public function profit_todo($stock_info,$inventory_detail){
		//盘盈批次：有库存记录的用最早批次，没库存的用最近批次
		if(empty($stock_info)){
			//没库存的 查询所有采购单 再查询最近的到货单 当做批次 最近批次
			$map['pro_code'] = $inventory_detail['pro_code'];
			$batch = M('stock_purchase_detail')
			->join(' stock_purchase on stock_purchase.id = stock_purchase_detail.pid')
			->join(' stock_bill_in on stock_bill_in.refer_code = stock_purchase.code')
			->where($map)
			->order('stock_purchase.created_time desc')
			->field('stock_bill_in.code as batch')
			->find();
			unset($map);

			if(!empty($batch['batch'])){
				$data['location_id'] = $inventory_detail['location_id'];
				$data['pro_code'] = $inventory_detail['pro_code'];
				$data['batch'] = $batch['batch'];
				//管理批次号
				get_batch($data['batch']);
				$data['stock_qty'] = $inventory_detail['pro_qty'] - $inventory_detail['theoretical_qty'];
				//$data['refer_code'] = $data['batch'];
				A('Stock','Logic')->addStock($data);
				unset($data);

				//写入库存交易日志
				$stock_move_data = array(
					'wh_id' => session('user.wh_id'),
					'location_id' => $inventory_detail['location_id'],
					'pro_code' => $inventory_detail['pro_code'],
					'type' => 'move',
					'refer_code' => $inventory_info['code'],
					'direction' => 'IN',
					'move_qty' => $inventory_detail['pro_qty'],
					'old_qty' => 0,
					'new_qty' => $inventory_detail['pro_qty'],
					'batch' => $batch['batch'],
					'status' => 'qualified',
					);
				$stock_move = D('StockMoveDetail');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();
				unset($log_qty);
				unset($log_old_qty);
				unset($log_new_qty);
				unset($stock_move_data);
			}
			
		}else{
			//有库存记录的用最早批次
			$map['pro_code'] = $inventory_detail['pro_code'];
			$map['location_id'] = $inventory_detail['location_id'];
			$map['batch'] = $stock_info['batch'];
			$map['status'] = $stock_info['status'];
			//更新库存记录
			$profit_qty = $inventory_detail['pro_qty'] - $inventory_detail['theoretical_qty'];
			M('stock')->where($map)->setInc('stock_qty',$profit_qty);
			unset($map);
			unset($data);

			//写入库存交易日志
			$stock_move_data = array(
				'wh_id' => session('user.wh_id'),
				'location_id' => $inventory_detail['location_id'],
				'pro_code' => $inventory_detail['pro_code'],
				'type' => 'move',
				'refer_code' => $inventory_info['code'],
				'direction' => 'IN',
				'move_qty' => $profit_qty,
				'old_qty' => $inventory_detail['theoretical_qty'],
				'new_qty' => $inventory_detail['pro_qty'],
				'batch' => $stock_info['batch'],
				'status' => $stock_info['status'],
				);
			$stock_move = D('StockMoveDetail');
			$stock_move_data = $stock_move->create($stock_move_data);
			$stock_move->data($stock_move_data)->add();
			unset($stock_move_data);
		}
	}

	//盘亏处理
	public function loss_todo($inventory_detail){
		//根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
		$map['pro_code'] = $inventory_detail['pro_code'];
		$map['location_id'] = $inventory_detail['location_id'];
		$stock_list = M('Stock')->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')->where($map)->order('stock_batch.product_date')->field('stock.*,stock_batch.product_date')->select();
		unset($map);

		$diff_qty = $inventory_detail['theoretical_qty'] - $inventory_detail['pro_qty'];
		//按照现进先出原则 减去最早的批次量
		foreach($stock_list as $stock){
			if($diff_qty > 0){
				//如果库存量小于等于差异量 则删除该条库存记录 然后减去差异量diff_qty
				if($stock['stock_qty'] <= $diff_qty){
					$map['id'] = $stock['id'];
					M('Stock')->where($map)->delete();
					unset($map);

					$diff_qty = $diff_qty - $stock['stock_qty'];
					$log_qty = $stock['stock_qty'];
					$log_old_qty = $stock['stock_qty'];
					$log_new_qty = 0;
				}else{
					//根据id 更新库存表
					$map['id'] = $stock['id'];
					$log_qty = $diff_qty;
					$log_old_qty = $stock['stock_qty'];
					$data['stock_qty'] = $stock['stock_qty'] - $diff_qty;
					$log_new_qty = $data['stock_qty'];
					M('stock')->where($map)->data($data)->save();
					unset($map);
					unset($data);

					//跳出循环
					$diff_qty = 0;
				}

				//写入库存交易日志
				$stock_move_data = array(
					'wh_id' => session('user.wh_id'),
					'location_id' => $stock['location_id'],
					'pro_code' => $stock['pro_code'],
					'type' => 'move',
					'refer_code' => $inventory_info['code'],
					'direction' => 'OUT',
					'move_qty' => $log_qty,
					'old_qty' => $log_old_qty,
					'new_qty' => $log_new_qty,
					'batch' => $stock['batch'],
					'status' => $stock['status'],
					);
				$stock_move = D('StockMoveDetail');
				$stock_move_data = $stock_move->create($stock_move_data);
				$stock_move->data($stock_move_data)->add();
				unset($log_qty);
				unset($log_old_qty);
				unset($log_new_qty);
				unset($stock_move_data);
			}
		}
	}

	//关闭盘点单 库存不变化
	public function closed(){
		if(IS_AJAX){
			$ids = I('ids');
			$map = array('id' => array('in',$ids));
			//根据盘点单ids 查询inventory_info
			$inventory_infos = M('stock_inventory')->where($map)->select();
			unset($map);
			
			//将对应盘点单置为closed
			foreach($inventory_infos as $inventory_info){
				$map['id'] = $inventory_info['id'];
				M('stock_inventory')->where($map)->data(array('status'=>'closed'))->save();
				unset($map);

				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$map['inventory_code'] = $inventory_info['code'];
				$inventory_details = M('stock_inventory_detail')->where($map)->select();
				unset($map);
				//根据stock_inventory_detail_id 更新对应的status为done
				foreach($inventory_details as $inventory_detail){
					$map['id'] = $inventory_detail['id'];
					M('stock_inventory_detail')->where($map)->data(array('status'=>'done'))->save();
					unset($map);
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
			unset($map);

			//检查是否存在 已经有差异的盘点单，如果有，则提示错误
			foreach($inventory_infos as $inventory_info){
				if($inventory_info['status'] == 'closed'){
					$this->msgReturn(0,'盘点单'.$inventory_info['code'].'已经经过差异确认，或者盘点单已经关闭');
				}
				if($inventory_info['status'] != 'confirm'){
					$this->msgReturn(0,'盘点单'.$inventory_info['code'].'的状态不是待确认，请操作完毕再进行确认');
				}
			}

			foreach($inventory_infos as $inventory_info){
				$inventory_is_diff = false;
				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$map['inventory_code'] = $inventory_info['code'];
				$inventory_details = M('stock_inventory_detail')->where($map)->select();
				unset($map);
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
						'type' => 'fast',
						'status' => 'noinventory',
						'wh_id' => session('user.wh_id'),
						);
					$stock_inventory = D('Inventory');
					$inventory_data = $stock_inventory->create($inventory_data);
					$stock_inventory->data($inventory_data)->add();
					unset($stock_inventory);
					unset($inventory_data);
					
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
							$stock_inventory_detail = D('InventoryDetail');
							$inventory_detail_data = $stock_inventory_detail->create($inventory_detail_data);
							$stock_inventory_detail->data($inventory_detail_data)->add();
							unset($stock_inventory_detail);
							unset($inventory_detail_data);
						}
					}
					unset($inventory_data);
					unset($inventory_code);
					unset($inventory_details);
				}

				//将原盘点单状态置为closed
				$map['id'] = $inventory_info['id'];
				M('stock_inventory')->where($map)->data(array('status'=>'closed'))->save();
				unset($map);

				//根据盘点单号inventory_code 查询盘点详情信息 stock_inventory_detail
				$map['inventory_code'] = $inventory_info['code'];
				$inventory_details = M('stock_inventory_detail')->where($map)->select();
				unset($map);
				//根据stock_inventory_detail_id 更新对应的status为done
				foreach($inventory_details as $inventory_detail){
					$map['id'] = $inventory_detail['id'];
					M('stock_inventory_detail')->where($map)->data(array('status'=>'done'))->save();
					unset($map);
				}
			}
			$this->msgReturn(1);
		}
	}

	public function printpage(){
		$id = I('id');
		$map['stock_inventory.id'] = $id;
		$inventory_info = M('stock_inventory')
		->join('warehouse on warehouse.id = stock_inventory.wh_id')
		->join('user on user.id = stock_inventory.created_user')
		->where($map)
		->field('stock_inventory.*, user.nickname as created_name, warehouse.name as wh_name')
		->find();
		unset($map);

		//根据inventory_code查询inventory_detail
		$map['inventory_code'] = $inventory_info['code'];
		$inventory_detail = M('stock_inventory_detail')
		->join('location on location.id = stock_inventory_detail.location_id')
		->where($map)
		->field('stock_inventory_detail.*,location.code as location_code')
		->order('stock_inventory_detail.id')
		->select();

		$inventory_detail = A('Pms','Logic')->add_fields($inventory_detail,'pro_name');

		$data['wh_name'] = $inventory_info['wh_name'];
		$data['inventory_code'] = $inventory_info['code'];
		$data['inventory_time'] = $inventory_info['created_time'];
		$data['print_time'] = date('Y-m-d H:i:s');
		$data['status'] = $this->filter['status'][$inventory_info['status']];
		$data['created_name'] = $inventory_info['created_name'];
		$data['inventory_detail'] = $inventory_detail;

		layout(false);
    	$this->assign($data);
    	$this->display('Inventory:print');
	}

	//手持设备扫描盘点 根据inventory_code返回对应详情
	/*public function getInvDetailByInvCode(){
		$inventory_code = I('inventory_code');
		$map['inventory_code'] = $inventory_code;
		$inventory_detail_infos = M('stock_inventory_detail')->where($map)->select();
		unset($map);

		$data['status'] = 1;
		$data['data'] = $inventory_detail_infos;

		$this->ajaxReturn($data);
	}*/

	//
}