<?php
namespace Wms\Controller;
use Think\Controller;
class StockInController extends CommonController {
	protected $filter = array(
		'type' => array(
			'purchase' => '采购入库'
		),
		'status' => array(
			'0'	=> '草稿',
			'21'=>'待入库',
			'31'=>'待上架',
			'23'=>'已入库',
			'53'=>'已完成',
			'00'=>'已关闭'
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
				$data = $A->getOnQty($id,$code);
				if(!empty($data)) {
					$this->assign($data);
					layout(false);
					$this->msg = '查询成功。';
					$this->title = '录入上架量';
					$data = $this->fetch('StockIn:on-qty');
					$this->msgReturn(1,'查询成功。',$data);
				}
				else {
					$this->msgReturn(0,'查询失败。');
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
				$data = $A->getInQty($id,$code);
				if(!empty($data)) {
					$this->assign($data);
					layout(false);
					$this->msg = '查询成功。';
					$this->title = '录入到货量';
					$data = $this->fetch('StockIn:input-qty');
					$this->msgReturn(1,'查询成功。',$data);
				}
				else {
					$this->msgReturn(0,'查询失败。');
				}
				
			}
			if($type == 'input_qty') {
				$qty = I('post.qty');
				$res = A('StockIn','Logic')->in($id,$code,$qty);

				if(!empty($res)) {
					$data['msg'] = '收货成功。'.$res;
					$res = M('stock_bill_in')->field('id,code')->find($id);
					$data['id'] = $res['id'];
					$data['code'] = $res['code'];
					$this->assign($data);
					$this->title = '扫描货品号';
					$data = $this->fetch('StockIn:scan-procode');
					$this->msgReturn(1,'验收成功。',$data);
				}
				else {
					$this->msgReturn(0,'验收失败。');
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
		foreach ($pros as $key => $val) {
			$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		}
		$this->pros = $pros;
	}
	public function _before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'true'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $pill = array(
			'status'=> array(
				array('value'=>'0','title'=>'草稿','class'=>'warning'),
				array('value'=>'21','title'=>'待入库','class'=>'primary'),
				array('value'=>'31','title'=>'待上架','class'=>'info'),
				array('value'=>'53','title'=>'已完成','class'=>'success'),
				array('value'=>'00','title'=>'已关闭','class'=>''),
			)
		);
    }
}