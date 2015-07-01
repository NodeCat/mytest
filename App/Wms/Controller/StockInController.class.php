<?php
namespace Wms\Controller;
use Think\Controller;
class StockInController extends CommonController {
    public function __construct(){
        parent::__construct();
        if(IS_GET && ACTION_NAME == 'add'){
            $stock_type = M('stock_bill_in_type');
            $map['type'] = array('not in',array('ASN','MNI'));
            $this->stock_in_type = $stock_type->where($map)->select();
        }
    }
	protected $filter = array(
		'type' => array(
			'purchase' => '采购入库'
		),
		'status' => array(
			'0'	=> '草稿',
			'21'=>'待收货',
			'31'=>'待上架',
			'33'=>'已上架',
			'04'=>'已作废'
		),
	);
	protected $columns = array (   
		'code' => '到货单号',   
		//'refer_code' => '关联单号', 
	    'type' => '入库类型',
		//'company_name' => '所属系统',  
		'warehouse_name' => '目的仓库', 
		'partner_name' => '供货商',
		'qty_total' =>'预计到货件数',
		'cat_total' =>'SKU种数',
		'sp_created_user_name' => '创建人',
  		'sp_created_time' => '创建时间',
		'status' => '状态', 
	);
	protected $query = array (   
		 'stock_bill_in.code' =>    array (     
			'title' => '到货单号',     
			'query_type' => 'like',     
			'control_type' => 'text',     
			'value' => 'name',   
		),  
		'stock_bill_in.refer_code' =>    array (     
			'title' => '关联单据',     
			'query_type' => 'like',     
			'control_type' => 'text',     
			'value' => 'Company.id,name',   
		),  
		
		'warehouse.id' =>    array (     
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
	    'stock_bill_in.type' => array(
			'title' => '入库类型',
	        'query_type' => 'eq',
	        'control_type' => 'getField',
	        'value' => 'stock_bill_in_type.id,name',
		),
		
	   'stock_bill_in.partner_id' =>    array (     
			'title' => '供货商',     
			'query_type' => 'eq',     
			'control_type' => 'refer',     
			'value' => 'stock_bill_in-partner_id-partner-id,id,name,Partner/refer',   
			),
		/*'stock_purchase.created_user' =>    array (     
			'title' => '创建人',     
			'query_type' => 'eq',     
			'control_type' => 'refer',     
			'value' => 'stock_purchase-created_user-user-id,id,nickname,User/refer',   
		),*/
		'stock_bill_in.created_user' =>    array (     
			'title' => '创建人',     
			'query_type' => 'eq',     
			'control_type' => 'getField',     
			'value' => 'User.id,nickname',   
		),
		'stock_bill_in.created_time' =>    array (     
			'title' => '时间',     
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
					$tmpl = 'StockIn:on-scan-incode';
					break;
			}
			$this->display($tmpl);
		}
		elseif(IS_POST) {
			$code = I('post.code');
			$id = I('post.id');
			$type = I('post.t');
			//扫描SKU编号
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
		
			//上架数量 库位
			if($type == 'input_qty') {
				$qty = I('post.qty');
				$location = I('post.location');
				$status = I('post.status');

				//上架逻辑
				$res = A('StockIn','Logic')->on($id,$code,$qty,$location,$status);
				if($res['res'] == true) {
					//判断是否是采购入库
					$map['id'] = $id;
					$bill_in_info = M('stock_bill_in')->where($map)->find();
					unset($map);
					//如果是采购入库 更新采购入库单详情 erp_purchase_in_detail
					if($bill_in_info['type'] == 1){
						//写入采购入库单 erp_in_detail
						//根据stock_bill_in id 查询相关数据
						$map['stock_bill_in_detail.pid'] = $id;
						$map['stock_bill_in_detail.pro_code'] = $code;
						$bill_in_detail_info = M('stock_bill_in_detail')
						->join('stock_bill_in on stock_bill_in.id = stock_bill_in_detail.pid' )
						->join('stock_purchase on stock_purchase.code = stock_bill_in.refer_code')
						->where($map)
						->field('stock_bill_in.code,stock_bill_in.refer_code,stock_bill_in_detail.price_unit,stock_purchase.invoice_method')
						->find();
						unset($map);

						$data['price_unit'] = $bill_in_detail_info['price_unit'];
						$data['pro_code'] = $code;
						$data['pro_qty'] = $qty;
						$data['stock_in_code'] = $bill_in_detail_info['code'];
						$data['purchase_code'] = $bill_in_detail_info['refer_code'];
						$data['pro_status'] = $status;
						$data['price_subtotal'] = $bill_in_detail_info['price_unit'] * $qty;

						if($bill_in_detail_info['invoice_method'] == 0){
							$data['status'] = 'paid';
						}else{
							$data['status'] = 'nopaid';
						}

						$purchase_in_detail = D('PurchaseInDetail');
						$data = $purchase_in_detail->create($data);
						$purchase_in_detail->data($data)->add();
					}

					//有一件商品上架 更新到货单状态为 已上架
					$upd_map['id'] = $id;
					$upd_data['status'] = '33';
					M('stock_bill_in')->where($upd_map)->data($upd_data)->save();
					unset($upd_map);
					unset($upd_data);

					$data['msg'] = '上架成功。'.$res['msg'];
					$res = M('stock_bill_in')->field('id,code')->find($id);
					$data['id'] = $res['id'];
					$data['code'] = $res['code'];
					$this->assign($data);
					$this->title = '扫描货品号';
					$data = $this->fetch('StockIn:on-scan-procode');
					$this->msgReturn(1,'上架成功。',$data);
				}
				else {
					$this->msgReturn(0,'上架失败。'.$res['msg']);
				}
			}
			//扫描入库单号
			if($type == 'scan_incode') {
				$map['is_deleted'] = 0;
				$map['code'] = $code;
				$res = M('stock_bill_in')->where($map)->find();
				if(!empty($res)) {
					if(true){
						if($res['status'] =='31' || $res['status'] =='32' || $res['status'] == '21' || $res['status'] =='33') {
							$data['id'] = $res['id'];
							$data['code'] = $res['code'];
							$data['title'] = '扫描货品';
							$this->assign($data);
							layout(false);
							$this->msg = '查询成功。';
							$this->title = '扫描货品';
							$data = $this->fetch('StockIn:on-scan-procode');
							$this->msgReturn(1,'查询成功。',$data);
						}
						/*if($res['status'] =='33') {
							$this->msgReturn(0,'查询失败，该单据待上架数量为0。');
						}*/
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
					$tmpl = 'StockIn:in-scan-incode';
					break;
			}
			$this->display($tmpl);
		}
		else if(IS_POST){
			$code = I('post.code');
			$id = I('post.id');
			$type = I('post.t');
			if($type == 'scan_procode') {
				$a = A('Code','Logic');
				$ac = $a->getProCodeByEna13code($code);
				dump($ac);die;
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
					//有一件商品入库 更新到货单状态为 待上架
					$upd_map['id'] = $id;
					$upd_data['status'] = '31';
					M('stock_bill_in')->where($upd_map)->data($upd_data)->save();
					unset($upd_map);
					unset($upd_data);

					$data['msg'] = '收货成功。'.$res['msg'];
					$res = M('stock_bill_in')->field('id,code')->find($id);
					$data['id'] = $res['id'];
					$data['code'] = $res['code'];
					$this->assign($data);
					$this->title = '扫描货品号';
					$data = $this->fetch('StockIn:in-scan-procode');
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
						if($res['status'] =='21' || $res['status'] =='22' || $res['status'] == '31' || $res['status'] =='32' || $res['status'] =='33') {
							$data['id'] = $res['id'];
							$data['code'] = $res['code'];
							$data['title'] = '扫描货品';
							$this->assign($data);
							layout(false);
							$this->msg = '查询成功。';
							$this->title = '扫描货品';
							$data = $this->fetch('StockIn:in-scan-procode');
							$this->msgReturn(1,'查询成功。',$data);
						}
						/*if($res['status'] == '31' || $res['status'] =='32') {
							$this->msgReturn(0,'查询失败，该单据已入库。');
						}*/
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
		if (!empty($row['refer_code'])) {
		    //自动创建的入库单
		    $map['stock_purchase.code'] = $row['refer_code'];
		    $purchase = D('Purchase')->default()->where($map)->find();
		    $data['created_user_name'] = $purchase['created_user_name'];
		    $data['created_user_mobile'] = $purchase['created_user_mobile'];
		    $data['created_time'] = $purchase['created_time'];
		    $data['partner_name'] = $purchase['partner_name'];
		    $data['cat_total'] = $purchase['cat_total'];
		    $data['qty_total'] = $purchase['qty_total'];
		} else {
		    //手动创建的入库单
		    $stock_detail = M('stock_bill_in_detail');
		    $detail = $stock_detail->field('count(*) as cat_total, sum(expected_qty) as qty_total')
		                ->where(array('pid' => $id))
		                ->find();
		    $data['sp_created_time'] = $row['created_time'];
		    $data['cat_total'] = $detail['cat_total'];
		    $data['qty_total'] = $detail['qty_total'];
		    $data['refer_code'] = '无';
		    $data['partner_name'] = '无';
		}
		unset($map);
		//$map['pid'] = $purchase['id'];
		//$pros = M('stock_purchase_detail')->where($map)->select();
		$map['pid'] = $id;
		$pros = M('stock_bill_in_detail')->where($map)->select();
		unset($map);
		$A = A('StockIn','Logic');
		$qtyForPrepare = 0;
		foreach ($pros as $key => $val) {
			$qtyPrepare = $A->getQtyForIn($id,$val['pro_code']);
			//$qtyOn = $A->getQtyForOn($id,$val['pro_code']);
			$getQtyForReceipt = $A->getQtyForReceipt($id,$val['pro_code']);
			
			$qtyForPrepare += $qtyPrepare;
			//$qtyForOn += $qtyOn;
			//$pros[$key]['moved_qty'] = $val['pro_qty'] - $qtyIn;
			//$pros[$key]['moved_qty'] = $qtyIn;
			//$moved_qty_total += $qtyIn;
			//预计收获量
			$expected_qty_total += $val['pro_qty'];
			//已收总量
			$receipt_qty_total += $getQtyForReceipt;
			//$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
		}

		//根据pid 查询对应stock_bill_in_detail
		$map['pid'] = $id;
		$bill_in_detail_list = M('stock_bill_in_detail')->where($map)->select();
		
		$this->pros = $bill_in_detail_list;
		//已上架量
		foreach($this->pros as $pro){
			$data['qtyForIn'] += $pro['done_qty'];
		}
		//$data['qtyForIn'] = $expected_qty_total - $moved_qty_total;

		$data['qtyForPrepare'] = $qtyForPrepare;
		//预计收获量
		$data['expected_qty_total'] = $expected_qty_total;
		//已收总量
		$data['receipt_qty_total'] = $receipt_qty_total;

		//$data['qtyForOn'] =$qtyForIn;
	}
	protected function before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr =array(
            array('name'=>'view','link'=>'view','icon'=>'zoom-in','title'=>'查看', 'show' => isset($this->auth['view']),'new'=>'true'), 
        	array('name'=>'print','link'=>'printpage','icon'=>'print','title'=>'打印', 'show'=>isset($this->auth['printpage']),'new'=>'true','target'=>'_blank'),
        );
        if(ACTION_NAME == 'pindex') {
        	$show =false;
        }
        else {
        	$show = true;
        }
        $this->toolbar = array(
        	    array('name' => 'add', 'show' => $show && isset($this->auth['add']), 'new' => 'true'),
        );
        
    }
    public function pview() {
        $this->edit();
    }
    public function pindex() {
    	$this->_before_index();
    	$this->before_index();
    	$this->toolbar_tr =array(
            array('name'=>'pview','link'=>'pview','icon'=>'zoom-in','title'=>'查看', 'show' => isset($this->auth['pview']),'new'=>'true'), 
        	array('name'=>'print','link'=>'printpage','icon'=>'print','title'=>'打印', 'show'=>isset($this->auth['printpage']),'new'=>'true','target'=>'_blank'),
        );
        $this->columns = array (   
			'code' => '到货单号',   
			'refer_code' => '关联单号', 
		    //'type' => '入库类型',
			'company_name' => '所属系统',  
			'warehouse_name' => '目的仓库', 
			'partner_name' => '供货商',
			'qty_total' =>'预计到货件数',
			'cat_total' =>'SKU种数',
			'sp_created_user_name' => '创建人',
	  		'sp_created_time' => '创建时间',
			'status' => '状态', 
		);
    	//$tmpl = IS_AJAX ? 'Table:list':'index';
        $this->lists();
    }
    protected function before_lists(&$M){
    	$pill = array(
			'status'=> array(
				//'0'=>array('value'=>'0','title'=>'草稿','class'=>'warning'),
				'21'=>array('value'=>'21','title'=>'待收货','class'=>'primary'),
				'31'=>array('value'=>'31','title'=>'待上架','class'=>'info'),
				'33'=>array('value'=>'33','title'=>'已上架','class'=>'success'),
				'04'=>array('value'=>'04','title'=>'已作废','class'=>'danger')
			)
		);
		$M_bill_in = M('stock_bill_in');
		$map['is_deleted'] = 0;
		$map['wh_id'] = session('user.wh_id');
		if(ACTION_NAME == 'pindex'){
			//如果是采购到货单，只显示采购相关的到货单据
			$map['stock_bill_in.type'] = 1;
		}
		$res = $M_bill_in->field('status,count(status) as qty')->where($map)->group('status')->select();

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

		if(ACTION_NAME == 'pindex'){
			//如果是采购到货单，只显示采购相关的到货单据
			$M->where(array('stock_bill_in.type'=>'1'));
			//删除入库类型查找
			unset($this->query['stock_bill_in.type']);
			$this->assign('query',$this->query);
		}
    }
    
    /**
     * 列表数据后期处理
     * @param array $data 列表数据
     */
    protected function after_lists(&$data) {
        if (empty($data) || !is_array($data)) {
            //参数有误
            return;
        }
        $M = M('stock_bill_in_detail');
        //$value使用了变量引用 如增加代码不可使用
        foreach ($data as &$value) {
            //dump($data);exit;
            //检索手动创建的入库单
            if (empty($value['cat_total']) && empty($value['qty_total'])) {
                $detail = $M->field('count(*) as cat_total, sum(expected_qty) as qty_total')
                            ->where(array('pid' => $value['id']))
                            ->find();
                $value['cat_total'] = $detail['cat_total']; //SKU总数
                $value['qty_total'] = $detail['qty_total']; //总数量
                $value['sp_created_time'] = $value['created_time']; //创建时间
            }
        }
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
    	->join('left join stock_purchase on stock_purchase.code = stock_bill_in.refer_code')
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

    	$bill_in_detail_list = A('Pms','Logic')->add_fields($bill_in_detail_list,'pro_name');
        //如果没有对应的条码号则使用内部货号作为条码号
        foreach($bill_in_detail_list as &$val) {
            if(empty($val['barcode'])) {
               $val['barcode'] = $val['pro_code']; 
            }
        }
       
    	$data['bill_in_detail_list'] = $bill_in_detail_list;

    	layout(false);
    	$this->assign($data);
    	$this->display('StockIn:print');
    }
    
    /**
     * 手动创建入库单
     * (初始化入库数据 写入操作由父类add方法完成)
     * @param object $M stockin模型（操作数据表stock_bill_in）
     */
    protected function before_add(&$M) {
        $pros = I('post.pros');
        if (count($pros) < 2) {
            //没有商品被添加
            $this->msgReturn(0, '没有商品被添加');
            return;
        }
        unset($pros[0]);
        if (empty($pros)) {
            //没有商品被添加
            $this->msgReturn(0, '没有商品被添加');
            return;
        }
        
        //获取入库类型 生成对应的入库单号
        $type = I('post.type');
        if (empty($type)) {
            //没有选择入库类型
            $this->msgReturn(0, '请选择入库类型');
            return;
        }
        $stock_type = M('stock_bill_in_type');
        $type_name = $stock_type->field('type')->where(array('id' => $type))->find();
        $numbs = M('numbs');
        $name = $numbs->field('name')->where(array('prefix' => $type_name['type']))->find();
        
        foreach ($pros as $value) {
            if (empty($value['pro_name'])) {
                $this->msgReturn(0, '没有商品被添加');
                return;
            }
            //sku数量为0
            if ($value['pro_qty'] <= 0) {
                $this->msgReturn(0, '数量不可为0');
                return;
            }
        }
        $M->code = get_sn($name['name']); //入库单号
        $M->batch_code = get_batch(); //批次
        $M->updated_time = date('Y-m-d H:i:s', time()); //更新时间
        $M->created_user = session()['user']['uid']; //创建管理员
        $M->updated_user = session()['user']['uid']; //更新管理员
        $M->status = 21; //状态 21待入库
        $M->partner_id = 1; //手动添加供货商默认为1 
    }
    
    /**
     * 手动创建入库单（自动生成入库详情单）
     * @param int $id 入库单id
     * (操作数据表 stock_bill_in_detail)
     */
    protected function after_add($id) {
        $pros = I('pros'); //入库单详情数据
        //去除隐藏域
        unset($pros[0]);
        if (empty($pros)) {
            //没有产品被添加
            $this->msgReturn(0, '没有添加产品');
        }
        //获取采购单id
        $stock_in = D('stock_bill_in');
        $where = array('id' => $id);
        $stock_info = $stock_in->field('code, wh_id')->where($where)->find();
        //叠加相同产品
        $new_pros = array();
        foreach ($pros as $key => $value) {
            if (!isset($new_pros[$value['pro_code']])) {
                $new_pros[$value['pro_code']] = $value;
            } else {
                $new_pros[$value['pro_code']]['pro_qty'] = $value['pro_qty'] + $new_pros[$value['pro_code']]['pro_qty'];
            }
        }
        
        //生成入库详情单
        $stock_detail = D('StockBillInDetail');
        $detail = array();
        foreach ($new_pros as $val) {
            $detail['wh_id'] = $stock_info['wh_id'];
            $detail['pid'] = $id;
            $detail['refer_code'] = $stock_info['code'];
            $detail['pro_code'] = $val['pro_code'];
            $detail['pro_name'] = $val['pro_name'];
            $detail['pro_attrs'] = $val['pro_attrs'];
            $detail['expected_qty'] = $val['pro_qty'];
            $detail['pro_uom'] = $val['pro_uom'];
            $detail['prepare_qty'] = 0;
            $detail['done_qty'] = 0;
            $detail['receipt'] = 0;
            $detail['created_user'] = session()['user']['uid'];
            $detail['updated_user'] = session()['user']['uid'];
            $detail['created_time'] = date('Y-m-d H:i:s', time());
            $detail['updated_time'] = date('Y-m-d H:i:s', time());
            
            if ($stock_detail->create($detail)) {
                $stock_detail->add();
            }
        }
        $this->msgReturn(1, '', '', U('view', 'id='.$id));
    }

    //按照pro_code模糊匹配sku
    public function match_code() {
        $code=I('q');
        $A = A('Pms',"Logic");
        $data = $A->get_SKU_by_pro_codes_fuzzy_return_data($code);

        if(empty($data))$data['']='';
        echo json_encode($data);
    }
}
