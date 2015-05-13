<?php
namespace Wms\Controller;
use Think\Controller;
class StockInController extends CommonController {
	protected $filter = array(
		'type' => array(
			'purchase' => '采购入库'
		),
	);
	public function test($t="index"){
		C('LAYOUT_NAME','pda');
		$this->display('Pda:'.$t);
	}
	public function in($t='scan_incode'){
		if(IS_GET) {
			C('LAYOUT_NAME','pda');
			switch ($t) {
				case 'scan_incode':
					$this->title = '扫描到货单';
					$tmpl = 'StockIn:scan-incode';
					break;
				case 'scan_procode':
					$id = I('post.id');
					$code = I('post.code');
					$res = M('stock_bill_in')->where($map)->find();
					$tmpl = 'StockIn:scan-procode';
					break;
				default:
					# code...
					break;
			}
			$this->display($tmpl);
		}
		else if(IS_POST){
			$inCode = I('post.code');
			$id = I('post.id');
			$type = I('post.t');
			if($type == 'scan_procode') {
				//get_pro_name_qty_by_code()//根据采购单ID和sku获取货品名称和预计量和已验收量

			}
			$map['is_deleted'] = 0;
			$map['code'] = $inCode;
			$res = M('stock_bill_in')->where($map)->find();
			if(!empty($res)) {
				if(true){
					if($res['status'] =='21' || $res['status'] =='22') {
						$data['id'] = $res['id'];
						$data['code'] = $res['code'];
						$data['title'] = '扫描货品';
						$this->assign($data);
						layout(false);
						$data = $this->fetch('StockIn:scan-procode');
						$this->msgReturn(1,'查询成功。',$data);
					}
					if($res['status'] == '31' || $res['status'] =='32') {
						$this->msgReturn(0,'查询失败，该单据已入库。');
					}
					if($res['status'] == '53'){
						$this->msgReturn(0,'查询失败，该单据已完成。');
					}
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
	public function testin(){
		C('LAYOUT_NAME','pda');
		$this->msgReturn(1,'操作成功咯','',U('next'));
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
    }
}