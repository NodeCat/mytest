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
			'23'=>'已入库',
			'53'=>'已完成',
			'00'=>'已关闭'
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
						if($res['status'] =='31' || $res['status'] =='32') {
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
		$A = A('StockIn','Logic');
		$qtyForIn = 0;
		foreach ($pros as $key => $val) {
			$qtyIn = $A->getQtyForIn($id,$val['pro_code']);
			$qtyOn = $A->getQtyForOn($id,$val['pro_code']);
			
			$qtyForIn += $qtyIn;
			$qtyForOn += $qtyOn;
			//$pros[$key]['moved_qty'] = $val['pro_qty'] - $qtyIn;
			$pros[$key]['moved_qty'] = $qtyIn;
			$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		}
		$this->pros = $pros;
		$data['qtyForIn'] = $qtyForIn;
		$data['qtyForOn'] =$qtyForOn;
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
				'0'=>array('value'=>'0','title'=>'草稿','class'=>'warning'),
				'21'=>array('value'=>'21','title'=>'待收货','class'=>'primary'),
				'31'=>array('value'=>'31','title'=>'待上架','class'=>'info'),
				'53'=>array('value'=>'53','title'=>'已完成','class'=>'success'),
				'04'=>array('value'=>'04','title'=>'已关闭','class'=>'danger'),
			)
		);
		$M = M('stock_bill_in_detail');
		$map['is_deleted'] = 0;
		$res = $M->field('status,count(status) as qty')->where($map)->group('status')->select();
		foreach ($res as $key => $val) {
			$pill['status'][$val['status']]['count'] = $val['qty'];
		}
		$this->pill = $pill;
    }
}