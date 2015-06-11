<?php
namespace Wms\Controller;
use Think\Controller;

class WaveController extends Controller {
	
	public function lists(){
		$line_ids = $this->line();
		$map['status'] = L('订单状态待生产');
		//$map['line_ids'] = $line_ids;
		$A = A('Order','Logic');
		$orders = $A->order($map);
		foreach ($orders as $key => $val) {
			$order_ids[] = $val['id'];
			foreach ($val['detail'] as $k => $v) {
				$pros[$v['sku_number']] += $v['quantity'];
			}
		}
		$order_count = count($order_ids);
		$line_count = count($pros);
		$qty_total = array_sum($pros);
		//dump($order_count);
		//dump($line_count);
		//dump($qty_total);
		dump($orders);
		return $order_ids;

	}
	//创建波次
	public function create($order_ids='') {
		//创建波次，获取波次ID
		$data['code'] = get_sn('wave');
		$wave_id = M('stock_wave')->add($data);

		//获取勾选的订单列表
		$order_ids = $this->lists();
		$order_ids = implode(',', $order_ids);

		$map['wave_id'] = $wave_id;
		$map['order_ids'] = $order_ids;
		$map['status'] = L('订单状态波次中');
		$A = A('Order','Logic');
		$res = $A->operate($map);
		dump($res);
		
	}
	//释放订单
	public function release() {
		//将订单状态改为待生产，波次号改为0
		$map['status'] = L('订单状态待生产');
		$map['order_ids'] = $order_ids;
		$map['wave_id'] = 0;
		$res = A('Order','Logic')->operate($map);
	}

	//删除波次
	public function delete() {
		//删除波次

		$where['id'] = $wave_id;
		$data['is_deleted'] = 1;
		M('stock_wave')->where($where)->save($data);

		//将订单状态改为待生产，波次号改为0
		$map['status'] = L('订单状态待生产');
		$map['order_ids'] = $order_ids;
		$map['wave_id'] = 0;
		$res = A('Order','Logic')->operate($map);
	}
	public function picking() {
		//获取提交的波次ID
		$wave_id = I('post.wave_id');
		$wave_id = 71;
		$map['status'] = L('订单状态待生产');
		//$map['wave_id'] = $wave_id;
		$A = A('Order','Logic');
		$orders = $A->order($map);
		foreach ($orders as $key => &$val) {
			$line_orders[$val['line_id']]['order_ids'][] = $val['id'];
			foreach ($val['detail'] as $k => $v) {
				$line_orders[$val['line_id']]['pros'][$v['sku_number']] += $v['quantity'];
			}
		}
		
		foreach ($line_orders as $key => $val) {
			$order_count = count($val['order_ids']);
			$line_count = count($val['pros']);
			$qty_total = array_sum($val['pros']);
			
		}
		
		dump($line_orders);

	}
	
	public function order_detail() {

	}

	public function view(){
		echo L('订单');
	}
	
	//根据仓库ID获取线路列表
	public function line(){
		$map['warehouse_id'] = 2;
		$map['status'] = '1';
		$A = A('Order','Logic');
		$lines = $A->line($map);
		$citys = $A->city();
		foreach ($lines as $key => $val) {
			$line_ids[] = $val['id'];
			$lines[$key]['city'] = $citys[$val['location_id']];
		}
		$line_ids = implode(',',$line_ids);
		return $line_ids;
	}

	//获取待生产的订单列表
	public function order($map=''){
		$A = A('Order','Logic');
		$res = $A->order($map);
		return $res;
		$this->columns = array(
			'订单号' => 'order_number',
			'波次号' => 'wave_id',
			'总件数' => '',
			'状态' => 'status_cn',
			'' => '',
		);
	}
	//释放波次，创建分拣任务
	public function create_pick_task(){

	}
	//完成分拣
	public function finish_task(){

	}
	public function deliever(){

	}


}