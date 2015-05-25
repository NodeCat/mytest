<?php
namespace Wms\Controller;
use Think\Controller;
class StockInController extends CommonController {
	protected $filter = array(
		'type' => array(
			'purchase' => '采购入库'
		),
		'state' => array(
			'0'	=> '草稿',
			'21'=>'待收货',
			'31'=>'待上架',
			'33'=>'已上架',
			'04'=>'已关闭'
		),
	);
	protected $columns = array (   
		'code' => '到货单号',   
		'refer_code' => '采购单号',  
		'company_name' => '所属系统',  
		'warehouse_name' => '目的仓库', 
		//'type' => '单据类型',   
		'partner_name' => '供货商',
		'qty_total' =>'预计到货件数',
		'cat_total' =>'SKU种数',
		'sp_created_user_name' => '采购人',
  		'sp_created_time' => '采购时间',
		'state' => '状态', 
	);
	protected $query = array (   
		 'stock_bill_in.code' =>    array (     
			'title' => '到货单号',     
			'query_type' => 'like',     
			'control_type' => 'text',     
			'value' => 'name',   
		),  
		'stock_bill_in.refer_code' =>    array (     
			'title' => '采购单据',     
			'query_type' => 'like',     
			'control_type' => 'text',     
			'value' => 'Company.id,name',   
		),   
		
		'stock_bill_in.wh_id' =>    array (     
			'title' => '仓库',     
			'query_type' => 'eq',     
			'control_type' => 'getField',     
			'value' => 'Warehouse.id,name',   
		),
		'stock_bill_in.company_id' =>    array (     
			'title' => '所属系统',     
			'query_type' => 'eq',     
			'control_type' => 'getField',     
			'value' => 'Company.id,name',   
		),   
		
		'stock_bill_in.partner_id' =>    array (     
			'title' => '供货商',     
			'query_type' => 'eq',     
			'control_type' => 'refer',     
			'value' => 'stock_bill_in-partner_id-partner-id,id,name,Partner/refer',   
			),
		'stock_purchase.created_user' =>    array (     
			'title' => '采购人',     
			'query_type' => 'eq',     
			'control_type' => 'refer',     
			'value' => 'stock_purchase-created_user-user-id,id,nickname,User/refer',   
		),
		'stock_bill_in.created_time' =>    array (     
			'title' => '采购时间',     
			'query_type' => 'between',     
			'control_type' => 'datetime',     
			'value' => 'stock_bill_in-partner_id-partner-id,id,name,Partner/refer',   
		), 
	);
	public function on($t='scan_incode'){
		$this->cur = '上架';
		if(IS_GET) {
			C('LAYOUT_NAME','pda');
			switch ($t) {
				case 'scan_incode':
					$this->title = '扫描入库单';
					$tmpl = 'StockIn:scan-incode';
					break;
			}
			$this->display($tmpl);
		}
		elseif(IS_POST) {
			$code = I('post.code');
			$id = I('post.id');
			$type = I('post.t');
			if($type == 'scan_procode') {
				$A = A('StockIn','Logic');
				$res = $A->getOnQty($id,$code);
				if($res['res'] == true) {
					$this->assign($res['data']);
					layout(false);
					$this->msg = '查询成功。';
					$this->title = '录入上架量';
					$data = $this->fetch('StockIn:on-qty');
					$this->msgReturn(1,'查询成功。',$data);
				}
				else {
					$this->msgReturn(0,'查询失败。'.$res['msg']);
				}
			}
		
			if($type == 'input_qty') {
				$qty = I('post.qty');
				$location = I('post.location');
				$status = I('post.status');

				$res = A('StockIn','Logic')->on($id,$code,$qty,$location,$status);
				if($res['res'] == true) {
					$data['msg'] = '上架成功。'.$res['msg'];
					$res = M('stock_bill_in')->field('id,code')->find($id);
					$data['id'] = $res['id'];
					$data['code'] = $res['code'];
					$this->assign($data);
					$this->title = '扫描货品号';
					$data = $this->fetch('StockIn:scan-procode');
					$this->msgReturn(1,'上架成功。',$data);
				}
				else {
					$this->msgReturn(0,'上架失败。'.$res['msg']);
				}
			}
			if($type == 'scan_incode') {
				$map['is_deleted'] = 0;
				$map['code'] = $code;
				$res = M('stock_bill_in')->where($map)->find();
				if(!empty($res)) {
					if(true){
						if($res['status'] =='31' || $res['status'] =='32' || $res['status'] == '21') {
							$data['id'] = $res['id'];
							$data['code'] = $res['code'];
							$data['title'] = '扫描货品';
							$this->assign($data);
							layout(false);
							$this->msg = '查询成功。';
							$this->title = '扫描货品';
							$data = $this->fetch('StockIn:scan-procode');
							$this->msgReturn(1,'查询成功。',$data);
						}
						if($res['status'] =='33') {
							$this->msgReturn(0,'查询失败，该单据已上架。');
						}
						if($res['status'] == '53'){
							$this->msgReturn(0,'查询失败，该单据已完成。');
						}
						$this->msgReturn(0,'查询失败，该单据状态异常。');
					}
					else {
						$this->msgReturn(0,'查询失败，您没有权限。');
					}
				}
				else {
					$this->msgReturn(0,'查询失败，未找到该单据。');
				}
			}
		}
	}
	public function in($t='scan_incode'){
		$this->cur = '收货';
		if(IS_GET) {
			C('LAYOUT_NAME','pda');
			switch ($t) {
				case 'scan_incode':
					$this->title = '扫描入库单';
					$tmpl = 'StockIn:scan-incode';
					break;
			}
			$this->display($tmpl);
		}
		else if(IS_POST){
			$code = I('post.code');
			$id = I('post.id');
			$type = I('post.t');
			if($type == 'scan_procode') {
				$A = A('StockIn','Logic');
				$res = $A->getInQty($id,$code);
				if($res['res'] == true) {
					$this->assign($res['data']);
					layout(false);
					$this->msg = '查询成功。';
					$this->title = '录入到货量';
					$data = $this->fetch('StockIn:input-qty');
					$this->msgReturn(1,'查询成功。',$data);
				}
				else {
					$this->msgReturn(0,'查询失败。'.$res['msg']);
				}
				
			}
			if($type == 'input_qty') {
				$qty = I('post.qty');
				$res = A('StockIn','Logic')->in($id,$code,$qty);

				if($res['res'] == true) {
					$data['msg'] = '收货成功。'.$res['msg'];
					$res = M('stock_bill_in')->field('id,code')->find($id);
					$data['id'] = $res['id'];
					$data['code'] = $res['code'];
					$this->assign($data);
					$this->title = '扫描货品号';
					$data = $this->fetch('StockIn:scan-procode');
					$this->msgReturn(1,'验收成功。',$data);
				}
				else {
					$this->msgReturn(0,'验收失败。'.$res['msg']);
				}
			}
			if($type == 'scan_incode') {
				$map['is_deleted'] = 0;
				$map['code'] = $code;
				$res = M('stock_bill_in')->where($map)->find();
				if(!empty($res)) {
					if(true){
						if($res['status'] =='21' || $res['status'] =='22') {
							$data['id'] = $res['id'];
							$data['code'] = $res['code'];
							$data['title'] = '扫描货品';
							$this->assign($data);
							layout(false);
							$this->msg = '查询成功。';
							$this->title = '扫描货品';
							$data = $this->fetch('StockIn:scan-procode');
							$this->msgReturn(1,'查询成功。',$data);
						}
						if($res['status'] == '31' || $res['status'] =='32') {
							$this->msgReturn(0,'查询失败，该单据已入库。');
						}
						if($res['status'] == '53'){
							$this->msgReturn(0,'查询失败，该单据已完成。');
						}
						$this->msgReturn(0,'查询失败，该单据状态异常。');
					}
					else {
						$this->msgReturn(0,'查询失败，您没有权限。');
					}
				}
				else {
					$this->msgReturn(0,'查询失败，未找到该单据。');
				}
			}
		}
		
	}
	
	protected function before_edit(&$data) {
		$M = D('StockIn');
		$id = I($M->getPk());
		$row = $M->find($id);
		$map['stock_purchase.code'] = $row['refer_code'];
		$purchase = D('Purchase')->default()->where($map)->find();
		$data['created_user_name'] = $purchase['created_user_name'];
		$data['created_user_mobile'] = $purchase['created_user_mobile'];
		$data['created_time'] = $purchase['created_time'];
		$data['partner_name'] = $purchase['partner_name'];
		$data['cat_total'] = $purchase['cat_total'];
		$data['qty_total'] = $purchase['qty_total'];
		unset($map);
		$map['pid'] = $purchase['id'];
		$pros = M('stock_purchase_detail')->where($map)->select();
		unset($map);
		$A = A('StockIn','Logic');
		$qtyForIn = 0;
		foreach ($pros as $key => $val) {
			$qtyIn = $A->getQtyForIn($id,$val['pro_code']);
			$qtyOn = $A->getQtyForOn($id,$val['pro_code']);
			
			$qtyForIn += $qtyIn;
			$qtyForOn += $qtyOn;
			//$pros[$key]['moved_qty'] = $val['pro_qty'] - $qtyIn;
			//$pros[$key]['moved_qty'] = $qtyIn;
			$moved_qty_total += $qtyIn;
			$expected_qty_total += $val['pro_qty'];
			//$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		}

		//根据pid 查询对应stock_bill_in_detail
		$map['pid'] = $id;
		$bill_in_detail_list = M('stock_bill_in_detail')->where($map)->select();
		
		$this->pros = $bill_in_detail_list;
		$data['qtyForIn'] = $expected_qty_total - $moved_qty_total;
		$data['qtyForOn'] =$qtyForIn;
	}
	public function before_index() {
        $this->table = array(
            'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr =array(
            array('name'=>'view','link'=>'view','icon'=>'zoom-in','title'=>'查看', 'show' => true,'new'=>'true'), 
        );
        
    }
    public function pview() {
        $this->edit();
    }
    public function pindex() {
    	$this->_before_index();
    	$this->before_index();
    	$this->toolbar_tr =array(
            array('name'=>'pview','link'=>'pview','icon'=>'zoom-in','title'=>'查看', 'show' => true,'new'=>'true'), 
        );
    	//$tmpl = IS_AJAX ? 'Table:list':'index';
        $this->lists();
    }
    public function before_lists(){
    	$pill = array(
			'status'=> array(
				//'0'=>array('value'=>'0','title'=>'草稿','class'=>'warning'),
				'21'=>array('value'=>'21','title'=>'待收货','class'=>'primary'),
				'31'=>array('value'=>'31','title'=>'待上架','class'=>'info'),
				'33'=>array('value'=>'33','title'=>'已上架','class'=>'success'),
				'04'=>array('value'=>'04','title'=>'已关闭','class'=>'danger'),
			)
		);
		$M = M('stock_bill_in');
		$map['is_deleted'] = 0;
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
    //打印
    public function printpage(){
    	$id = I('get.id');

    	//根据id 查询对应入库单
    	$map['stock_bill_in.id'] = $id;
    	$bill_in = M('stock_bill_in')
    	->join('partner on partner.id = stock_bill_in.partner_id' )
    	->join('user on user.id = stock_bill_in.created_user')
    	->join('warehouse on warehouse.id = stock_bill_in.wh_id')
    	->join('stock_purchase on stock_purchase.code = stock_bill_in.refer_code')
    	->where($map)->field('stock_purchase.expecting_date, stock_bill_in.code, stock_purchase.remark, partner.name as partner_name, user.nickname as created_user_name, warehouse.name as dest_wh_name')->find();
    	unset($map);

    	//根据pid 查询对应入库单详情
    	$map['stock_bill_in_detail.pid'] = $id;
    	$bill_in_detail_list = M('stock_bill_in_detail')
    	->join('left join product_barcode on product_barcode.pro_code = stock_bill_in_detail.pro_code')
    	->where($map)->field('stock_bill_in_detail.pro_code,product_barcode.barcode,stock_bill_in_detail.expected_qty,stock_bill_in_detail.receipt_qty')->select();

    	$data['refer_code'] = $bill_in['code'];
    	$data['remark'] = $bill_in['remark'];
    	$data['print_time'] = get_time();
    	$data['partner_name'] = $bill_in['partner_name'];
    	$data['expecting_date'] = $bill_in['expecting_date'];
    	$data['created_user_name'] = $bill_in['created_user_name'];
    	$data['session_user_name'] = session('user.username');
    	$data['dest_wh_name'] = $bill_in['dest_wh_name'];

    	//$bill_in_detail_list = A('Pms','Logic')->add_fields($bill_in_detail_list,'pro_name');
    	$data['bill_in_detail_list'] = $bill_in_detail_list;

    	layout(false);
    	$this->assign($data);
    	$this->display('StockIn:print');
    }
}