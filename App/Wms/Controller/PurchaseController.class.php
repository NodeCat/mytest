<?php
namespace Wms\Controller;
use Think\Controller;
class PurchaseController extends CommonController {
	protected $filter = array(
		'invoice_method' =>  array(
			'0' => '先款后货',
			'1' => '先货后款',
		),
		'invoice_status' => array(
			'0' => '未付款', 
		),
		'picking_status' => array(
			'0' => '未入库', 
		),
		'company_id' => array(
			'1' => '大楚网',
			'2' => '大过往',
		),
		'status' => array(
			'0' => '待审核',
			'1' => '待入库',
			'2' => '待上架'
		)
	);
	public function index() {
		$tmpl = IS_AJAX ? 'Table:list':'index';
        $this->lists($tmpl);
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
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'true'), 
            array('name'=>'audit' ,'show' => !isset($auth['audit']),'new'=>'false'),
            array('name'=>'close' ,'show' => !isset($auth['close']),'new'=>'false')
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => !isset($auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => !isset($auth['resume']))
            ),
        );
        $this->status_type='0';
    }
	public function before_add(&$M) {
		$M->type = 'purchase';
		$M->code = get_sn('purchase');
		$M->price_total = 0;
		$M->qty_total = 0;
		$M->cat_total = 0;
		$M->invoice_status = '0';
		$M->picking_status = '0';
	}

	public function after_save($pid){
		$pros = I('pros');
		if(ACTION_NAME=='edit'){
			$pid = I('id');
		}
		$n = count($pros['pro_code']);
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
			if(!empty($pros['id'][$j])) {
				$map['id'] = $pros['id'][$j];
				$res = $M->where($map)->save($data);
			}
			else {
				$res = $M->add($data);
			}
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
		$where['id'] = $pid;
		$M = D(CONTROLLER_NAME);
		$M->where($where)->save($data);
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
	}
	public function before_lists(){
		$pill = array(
			'status'=> array(
				array('value'=>'0','title'=>'草稿','class'=>'warning'),
				array('value'=>'20','title'=>'待入库','class'=>'primary'),
				array('value'=>'30','title'=>'待上架','class'=>'info'),
				array('value'=>'51','title'=>'已完成','class'=>'success'),
				array('value'=>'01','title'=>'已关闭','class'=>''),
			)
		);
		//0 草稿 1审核 2入库 3上架 4付款 5完成
		//0 否 1待 2部分 3完成
		$pill = array(
			'status'=> array(
				array('value'=>'0','title'=>'草稿','class'=>'default'),
				//array('value'=>'01','title'=>'已发送','class'=>'default'),
				//array('value'=>'10','title'=>'待审核','class'=>'info'),
				array('value'=>'11','title'=>'已生效','class'=>'info'),//已审核
				//array('value'=>'20','title'=>'待入库','class'=>'info'),
				array('value'=>'21','title'=>'已完成','class'=>'success'),//已入库
				//array('value'=>'22','title'=>'已拒收','class'=>'success'),
				//array('value'=>'30','title'=>'待上架','class'=>'info'),
				//array('value'=>'31','title'=>'已上架','class'=>'success'),
				//array('value'=>'32','title'=>'未上架','class'=>'success'),
				//array('value'=>'40','title'=>'待付款','class'=>'success'),
				//array('value'=>'41','title'=>'已结算','class'=>'success'),
				//array('value'=>'42','title'=>'未付款','class'=>'success'),
				//array('value'=>'51','title'=>'已完成','class'=>'success'),
				array('value'=>'12','title'=>'已驳回','class'=>'danger'),
				array('value'=>'00','title'=>'已作废','class'=>'warning'),
			)
		);
		$M = M('stock_purchase');
		$map['is_deleted'] = 0;
		$res = $M->field('status,count(status) as qty')->where($map)->group('status')->select();
		foreach ($res as $key => $val) {
			$pill['status'][$key]['count'] = $val['qty'];
		}
		$this->pill = $pill;
		$query = $this->query;
		$query['stock_purchase.company_id']['value'] = array('1' => '大楚网' , '2'=>'大果王' );
		$this->query = $query;
	}

	public function pass(){
		$M = D(CONTROLLER_NAME);
		$pk = $M->getPk();
		$id = I($pk);
		$map[$M->tableName.'.'.$pk] = $id;
		$res = $M->relation(true)->where($map)->find();
		if($res['status']!='0') {
			$this->msgReturn(0);
		}
		$data['refer_code'] = $res['code'];
		$data['wh_id'] = $res['wh_id'];
		$data['company_id'] = $res['company_id'];
		$data['partner_id'] = $res['partner_id'];
		$Min = D('StockIn');
		$bill = $Min->relation(true)->create($data);
		$bill['code'] = get_sn('purchase');
		$bill['type'] = 'purchase';
		$bill['status'] = '1';
		$bill['batch_code'] = 'batch'.NOW_TIME;
		$res = $Min->add($bill);
		if($res == true){
			$purchase['status'] = '2';
			$M->where($map)->save($purchase);
			$this->msgReturn($res,'','',U('StockIn/view','id='.$res));
		}
		else{
			dump($Min->getError);
			dump($Min->_sql());
		}
		$this->msgReturn($res);
	}

}