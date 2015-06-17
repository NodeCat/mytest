<?php
namespace Wms\Controller;
use Think\Controller;
class PurchaseController extends CommonController {
	protected $filter = array(
		'invoice_method' =>  array(
			'0' => '预付款',
			'1' => '货到付款',
		),
		'invoice_status' => array(
			'0' => '未付款', 
		),
		'picking_status' => array(
			'0' => '未入库', 
		),
		'status' => array(
			'0' => '草稿',
			'11'=>'待审核',
			'13' => '已生效',
			'23' => '已完成',
			'04' => '已作废',
			'14' => '已驳回'
		)
	);
	
	protected $columns = array (   
		'id' => '',   
		'code' => '采购单号',   
		//'in_code' =>'采购到货单号',
		'warehouse_name' =>'仓库',
		'partner_name' => '供应商',
		'invoice_method' =>'付款方式',
		'company_name' => '所属系统',  
		'user_nickname' => '采购人',   
		'created_time' => '采购时间', 
		'status' => '单据状态',    
		//'cat_total' => 'sku种数',  
		//'qty_total' => '采购总数',   
		'price_total' => '采购总金额',
		'paid_amount' => '已结算金额',  
	);
	protected $query = array (
		'stock_purchase.code' => array (
			'title' => '采购单号',
			'query_type' => 'like',
			'control_type' => 'text',
			'value' => '',
		),
		'warehouse.id' =>    array (     
			'title' => '仓库',     
			'query_type' => 'eq',     
			'control_type' => 'getField',     
			'value' => 'Warehouse.id,name',   
		),
		'stock_purchase.company_id' =>    array (     
			'title' => '所属系统',     
			'query_type' => 'eq',    
			 'control_type' => 'getField',     
			 'value' => 'Company.id,name',   
		),   
		'stock_purchase.partner_id' =>    array (     
			'title' => '供应商',    
			 'query_type' => 'eq',     
			 'control_type' => 'refer',     
			 'value' => 'stock_purchase-partner_id-partner-id,id,name,Partner/refer',   
		),
		'stock_purchase_detail.pro_code' =>    array (     
			'title' => '货品编号',    
			 'query_type' => 'eq',     
			 'control_type' => 'text',     
			 'value' => '',   
		),   
		'stock_purchase.created_user' =>    array (     
			'title' => '采购人',     
			'query_type' => 'eq',     
			'control_type' => 'refer',     
			'value' => 'stock_purchase-created_user-user-id,id,nickname,User/refer',   
		),
		'stock_purchase.created_time' =>    array (    
			'title' => '采购时间',     
			'query_type' => 'between',     
			'control_type' => 'datetime',     
			'value' => 'stock_purchase-created_user-user-id,id,nickname,User/refer',   
		),
		'stock_purchase.invoice_method' => array(
			'title'=> '付款方式',
			'query_type'=>'eq',
			'control_type' => 'select',     
			 'value' => array(
			 	'0' => '预付款',
				'1' => '货到付款',
			 ), 
		),   

	);
	public function match_code() {
        $code=I('q');
        $A = A('Pms',"Logic");
        $data = $A->get_SKU_by_pro_codes_fuzzy_return_data($code);
        if(empty($data))$data['']='';
        echo json_encode($data);
    }
	public function view() {
        $this->_before_index();
        $this->edit();
    }
	
	public function _before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr =array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            //'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"0,11,04,14"), 
            //'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"0,11"),
            //'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"0,11"),
            //'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"0,11,13"),
            //'refund'=>array('name'=>'refund' ,'icon'=>'repeat','title'=>'生成红冲单', 'show' => isset($this->auth['refund']),'new'=>'true','domain'=>"13"),
            'edit'=>array('name'=>'edit', 'show' => false,'new'=>'true'), 
            'pass'=>array('name'=>'pass' ,'show' => false,'new'=>'true'),
            'reject'=>array('name'=>'reject' ,'show' => false,'new'=>'true'),
            'close'=>array('name'=>'close' ,'show' => false,'new'=>'true'),
            'refund'=>array('name'=>'refund' ,'icon'=>'repeat','title'=>'生成红冲单', 'show' => false,'new'=>'true'),
            'print'=>array('name'=>'print','link'=>'printpage','icon'=>'print','title'=>'打印', 'show'=>isset($this->auth['printpage']),'new'=>'true','target'=>'_blank')
        );
        
        $this->toolbar =array(
            array('name'=>'add', 'show' =>isset($this->auth['add']),'new'=>'true'),
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
            ),
        );
    }
	protected function before_add(&$M) {
		$pros = I('pros');

		//检查采购记录数
		if(count($pros['pro_code']) == 1){
			$this->msgReturn(0,'请至少采购一个产品');
		}
		//检查采购数量
		foreach($pros['pro_qty'] as $pro_qty){
			if($pro_qty == 0){
				$this->msgReturn(0,'采购数量不能为0');
			}
		}
		$M->type = 'purchase';
		$M->code = get_sn('purchase');
		$M->price_total = 0;
		$M->qty_total = 0;
		$M->cat_total = 0;
		$M->invoice_status = '0';
		$M->picking_status = '0';
	}
	
	protected function before_save(&$M){
		$M->status = '11';

		if(ACTION_NAME == 'edit'){
			$pros = I('pros');
			//检查采购记录数
			if(count($pros['pro_code']) == 1){
				$this->msgReturn(0,'请至少采购一个产品');
			}
			//检查采购数量
			foreach($pros['pro_qty'] as $pro_qty){
				if($pro_qty == 0){
					$this->msgReturn(0,'采购数量不能为0');
				}
			}
		}
	}

	protected function after_save($pid){
		$pros = I('pros');
		if(ACTION_NAME=='edit'){
			$pid = I('id');

			//如果是edit 根据pid 删除所有相关的puchase_detail记录
			$map['pid'] = $pid;
			M('stock_purchase_detail')->where($map)->delete();
			unset($map);
		}
		$n = count($pros['pro_code']);
		if($n <2) {
			$this->msgReturn(1,'','',U('view','id='.$pid));
		}
		$M = D('PurchaseDetail');
		for ($i = $n-1,$j=$i;$i>0;$i--,$j--) {
			$row['pid'] = $pid ;
			$row['pro_code'] = $pros['pro_code'][$j];
			if(empty($row['pro_code'])) {
				continue;
			}
			$row['pro_name'] = $pros['pro_name'][$j];
			$row['pro_attrs'] = $pros['pro_attrs'][$j];
			$row['pro_qty'] = $pros['pro_qty'][$j];
			$row['pro_uom'] = $pros['pro_uom'][$j];
			$row['price_unit'] = $pros['price_unit'][$j];
			$row['price_subtotal'] = $row['price_unit'] * $row['pro_qty'];
			$data = $M->create($row);
			//if(!empty($pros['id'][$j])) {
				//$map['id'] = $pros['id'][$j];
				//$res = $M->where($map)->save($data);
			//}
			//else {
			$res = $M->add($data);
			//}
			if($res==false){
				dump($pros);
				dump($M->getError());
				dump($M->_sql());
				exit();
			}
		}
		unset($map);
		$field="count(*) as cat_total,sum(pro_qty) as qty_total,sum(price_subtotal) as price_total";
		$map['pid'] = $pid;
		$data = $M->field($field)->where($map)->group('pid')->find();
		unset($map);
		$where['id'] = $pid;
		$M = D(CONTROLLER_NAME);
		$M->where($where)->save($data);
		unset($data);

		$this->msgReturn(1,'','',U('view','id='.$pid));
	}
	protected function before_edit() {
		$M = D('Purchase');
		$id = I($M->getPk());
		$map['pid'] = $id;
		$pros = M('stock_purchase_detail')->where($map)->order('id desc')->select();
		foreach ($pros as $key => $val) {
			$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		}
		$this->pros = $pros;

		//view上方按钮显示权限
		$this->toolbar_tr =array(
			'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"0,11,04,14"), 
            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"0,11"),
            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"0,11"),
            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"0,11,13"),
            'refund'=>array('name'=>'refund' ,'icon'=>'repeat','title'=>'生成红冲单', 'show' => isset($this->auth['refund']),'new'=>'true','domain'=>"13"),
		);
	}
	protected function before_lists(){
		$pill = array(
			'status'=> array(
				array('value'=>'0','title'=>'草稿','class'=>'warning'),
				array('value'=>'21','title'=>'待入库','class'=>'primary'),
				array('value'=>'31','title'=>'待上架','class'=>'info'),
				//array('value'=>'53','title'=>'已完成','class'=>'success'),
				array('value'=>'04','title'=>'已作废','class'=>''),
			)
		);
		//0 草稿 1审核 2入库 3上架 4付款 5完成
		// 1待 2部分 3完成 4否
		$pill = array(
			'status'=> array(
				//'0'=> array('value'=>'0','title'=>'草稿','class'=>'default'),
				//array('value'=>'03','title'=>'已发送','class'=>'default'),
				'11'=>array('value'=>'11','title'=>'待审核','class'=>'default'),
				'13'=> array('value'=>'13','title'=>'已生效','class'=>'info'),//已审核
				//array('value'=>'21','title'=>'待入库','class'=>'info'),
				//'23'=> array('value'=>'23','title'=>'已完成','class'=>'success'),//已入库
				//array('value'=>'20','title'=>'已拒收','class'=>'success'),
				//array('value'=>'31','title'=>'待上架','class'=>'info'),
				//array('value'=>'33','title'=>'已上架','class'=>'success'),
				//array('value'=>'30','title'=>'未上架','class'=>'success'),
				//array('value'=>'41','title'=>'待付款','class'=>'success'),
				//array('value'=>'43','title'=>'已结算','class'=>'success'),
				//array('value'=>'40','title'=>'未付款','class'=>'success'),
				//array('value'=>'53','title'=>'已完成','class'=>'success'),
				'14'=> array('value'=>'14','title'=>'已驳回','class'=>'danger'),
				'04'=> array('value'=>'04','title'=>'已作废','class'=>'warning')
			)
		);
		$M = M('stock_purchase');
		$map['is_deleted'] = 0;
		$map['wh_id'] = session('user.wh_id');
		$res = $M->field('status,count(status) as qty')->where($map)->group('status')->select();

		foreach ($res as $key => $val) {
			if(array_key_exists($val['status'], $pill['status'])){
				$pill['status'][$val['status']]['count'] = $val['qty'];
				$pill['status']['total'] += $val['qty'];
			}
		}

		foreach($pill['status'] as $k => $val){
			if(empty($val['count'])){
				$pill['status'][$k]['count'] = 0;
			}
		}
		$this->pill = $pill;
		
	}
	public function reject(){
		$M = D(CONTROLLER_NAME);
		$pk = $M->getPk();
		$id = I('get.'.$pk);
		$map[$M->tableName.'.'.$pk] = $id;
		$res = $M->field('code,status')->where($map)->find();
		
		if(empty($res) || ($res['status']!='0' && $res['status']!='11')) {
			$this->msgReturn(0);
		}
		$data['status'] = '14';
		$res = $M->where($map)->save($data);

		$this->msgReturn($res);
	}
	public function close(){
		$M = D(CONTROLLER_NAME);
		$pk = $M->getPk();
		$id = I('get.'.$pk);
		$map[$M->tableName.'.'.$pk] = $id;
		$res = $M->field('code,status')->where($map)->find();
		
		if(empty($res) || ($res['status']!='0' && $res['status']!='11' && $res['status']!='13')) {
			$this->msgReturn(0);
		}
		else {
			if($res['status'] == '11'){
				$data['status'] = '04';		
			}
			else{
				$where['refer_code'] = $res['code'];
				$in = M('stock_bill_in');
				$res = $in->field('id')->where($where)->find();
				
				$A = A('StockIn','Logic');
				$res = $A->haveCheckIn($res['id']);

				//没有收货
				if($res == false) {
					$data['status'] = '04';
					//$data['is_deleted'] = 1;
					$data = M('stock_purchase')->create($data);
					$res = M('stock_purchase')->where($map)->save($data);
					unset($map);
					unset($data);

					//关闭对应的到货单
					$data['status'] = '04';
					$data['is_deleted'] = 1;
					$data = M('stock_bill_in')->create($data);
					$map['refer_code'] = $where['refer_code'];
					M('stock_bill_in')->where($map)->save($data);
					unset($map);
					unset($data);

				}
				//已经收获
				else {
					$this->msgReturn(0,'操作失败，采购单对应的到货单已收货。');
					//$A->finishByPurchase($id);
				}
				$this->msgReturn($res);
			}
		}
		$res = $M->where($map)->save($data);
	
		$this->msgReturn($res);
	}

	public function refund() {
		//通过采购id获取采购单，复制一份改变编号后存到红冲单
		$id = I($pk);
		$map['id'] = $id['id'];
		$purchase_info = M('stock_purchase')->where($map)->find();
		unset($map);

		//根据采购单号查询是否已经建立了冲红采购单 冲红单的状态不是已作废
		$map['refer_code'] = $purchase_info['code'];
		$map['status'] = array('neq','cancel');
		$purchase_refund_info = M('erp_purchase_refund')->where($map)->find();
		unset($map);
		if(!empty($purchase_refund_info)){
			$this->msgReturn(0,'已经建立了冲红单，不能重复建立，请到冲红单列表查看','',U('view','id='.$id['id']));
		}

		//通过采购单号获取到货单id 生成红冲采购单
		$refund_purchase_data = $purchase_info;
		$refund_purchase_data['refer_code'] = $purchase_info['code'];
		$refund_purchase_data['code'] = get_sn('rpo');
		$refund_purchase_data['status'] = 'norefund';
		unset($refund_purchase_data['id']);

		$M_rep_purchase_refund = D('PurchaseRefund');
		$refund_purchase_data = $M_rep_purchase_refund->create($refund_purchase_data);

		//根据到货单id获取到货详情
		$map['refer_code'] = $refund_purchase_data['refer_code'];
		$map['type'] = 1;//采购入库单类型id
		$map['is_deleted'] = 0;
		$stock_bill_in = M('stock_bill_in')->field('id')->where($map)->select();
		$pid = $stock_bill_in[0]['id'];
		unset($map);

		//把到货详情拷贝到红冲单详情
		$map['pid'] = $pid;
		$stock_bill_in_detail = M('stock_bill_in_detail')->where($map)->select();
		unset($map);
		$sum = 0;
		foreach ($stock_bill_in_detail as $key => $val) {
			//如果sku已经全部收到，则不计入冲红单中
			if($val['expected_qty'] - $val['done_qty'] == 0){
				continue;
			}
			$v = $val;
			unset($v['id']);
			unset($v['pid']);
			$v = D('PurchaseRefundDetail')->create($v);
			$refund_purchase_data['detail'][] = $v;
			$sum +=  $val['price_unit'] * $val['qualified_qty'];
		}
		if(empty($refund_purchase_data['detail'])){
			$this->msgReturn(0,'已经全部收货成功，没有差异，不能生成冲红单');
		}
		$refund_purchase_data['for_paid_amount'] = $refund_purchase_data['price_total'] - $sum;

		$res = $M_rep_purchase_refund->relation(true)->add($refund_purchase_data);
		
		$this->msgReturn(1,'','',U('view','id='.$id['id']));
	}

	public function pass(){
		$M = D(CONTROLLER_NAME);
		$pk = $M->getPk();
		$id = I($pk);
		$map[$M->tableName.'.'.$pk] = $id;
		$res = $M->relation(true)->where($map)->find();
		unset($map);
		if($res['status']!='11') {
			$this->msgReturn(0);
		}
		$data['refer_code'] = $res['code'];
		$data['wh_id'] = $res['wh_id'];
		$data['company_id'] = $res['company_id'];
		$data['partner_id'] = $res['partner_id'];
		$data['type'] = 1;
		$Min = D('StockIn');
		
		$bill = $Min->create($data);
		$bill['code'] = get_sn('in');
		$bill['type'] = '1';
		$bill['status'] = '21';
		$bill['batch_code'] = get_batch($bill['code']);

		foreach ($res['detail'] as $key => $val) {
			$v['pro_code'] = $val['pro_code'];
			$v['pro_name'] = $val['pro_name'];
			$v['pro_attrs'] = $val['pro_attrs'];
			$v['pro_uom'] = $val['pro_uom'];
			$v['expected_qty'] = $val['pro_qty'];
			$v['prepare_qty'] = 0;
			$v['done_qty'] = 0;
			$v['wh_id'] = $data['wh_id'];
			//$v['type'] = 'in';
			$v['refer_code'] = $bill['code'];
			$v['pid'] = $val['pid'];
			$v['price_unit'] = $val['price_unit'];
			$bill['detail'][] = $v;
		}

		$res = $Min->relation('detail')->add($bill);

		//如果是预付款 更新结算金额为采购总金额
		$map['id'] = $id;
		$purchase_info = M('stock_purchase')->where($map)->find();

		if($purchase_info['invoice_method'] == 0){
			$data['paid_amount'] = $purchase_info['price_total'];
			M('stock_purchase')->where($map)->save($data);
		}

		if($res == true){
			$purchase['status'] = '13';
			$M->where($map)->save($purchase);
			$this->msgReturn($res,'','',U('view','id='.$id));
		}
		else{
			dump($Min->getError);
			dump($Min->_sql());
		}
		$this->msgReturn($res);
	}

	//在search方法执行后 执行该方法
	protected function after_search(&$map){
		//获得页面提交过来的货品编号
		if(array_key_exists('stock_purchase_detail.pro_code', $map)){
			$pro_code = $map['stock_purchase_detail.pro_code'][1];
			unset($map['stock_purchase_detail.pro_code']);

			//根据pro_code 查询stock_purchase_detail的pid
			$purchase_detail_map['pro_code'] = array('like','%'.$pro_code.'%');
			$pid_list = M('stock_purchase_detail')->where($purchase_detail_map)->field('pid')->group('pid')->select();
			unset($purchase_detail_map);

			$pid_arr = array();
			foreach($pid_list as $pid){
				$pid_arr[] = $pid['pid'];
			}

			if(!empty($pid_arr)){
				$map['stock_purchase.id'] = array('in',$pid_arr);
			}
		}
	}

    public function printpage() {
        $id = I('get.id');

        $purchase = M('stock_purchase');
        $map['stock_purchase.id'] = $id;
        $data = $purchase
        ->join('partner on partner.id = stock_purchase.partner_id' )
        ->join('warehouse on warehouse.id = stock_purchase.wh_id')
        ->join('user on user.id = stock_purchase.created_user')
        ->where($map)
        ->field('stock_purchase.*, partner.name as partner_name, user.nickname as created_name, warehouse.name as wh_name')
        ->find(); 
 
        $purchase_detail = M('stock_purchase_detail');
        unset($map);
        $map['pid'] = $id;
        $list = $purchase_detail->where($map)->select();
       
        $column['purchase_code'] = $data['code'];
        $column['purchase_time'] = $data['created_time'];
        $column['print_time'] = get_time();
        $column['partner'] = $data['partner_name'];
        $column['purchase_pay'] = $this->filter['invoice_method'][$data['invoice_method']];
        $column['purchase_qty'] = $data['cat_total'] . '种' . '/' . $data['qty_total'] . '件';
        $column['purchase_amount'] = $data['price_total'];
        $column['purchaser'] = $data['created_name'];
        $column['warehouse'] = $data['wh_name'];
        $column['remark'] = $data['remark'];
        $column['purchase_detail'] = $list;
        
    	layout(false);
    	$this->assign($column);
    	$this->display('Purchase:print');
    }
}
