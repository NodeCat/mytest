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
            '43' => '已结算',
            '04' => '已作废',
            '14' => '已驳回'
        )
    );

    protected $outremark = array(
                'quality'   =>"质量问题",
                'wrong'     =>"收错货物",
                'replace'   =>"替代销售",
                'unsalable' =>"滞销退货",
                'overdue'   =>"过期退货",
                'other'     =>"其他问题",
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
            array('name'=>'export' ,'show' => isset($this->auth['export']),'new'=>'false'),
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
                array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
            ),
        );
        $this->search_addon = true;
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
		//检查是否有重复的sku
		foreach($pros['pro_code'] as $pro_code){
			$pros_count[$pro_code] += 1;
			if($pros_count[$pro_code] > 1){
				$this->msgReturn(0,'不能输入两个重复的sku：'.$pro_code);
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
		
		$n = count($pros['pro_code']);
		if($n <2) {
			$this->msgReturn(1,'','',U('view','id='.$pid));
		}
		//验证小数点 liuguangping
		for ($i = $n-1,$j=$i;$i>0;$i--,$j--) {
			$mes = '';
			$pro_code = $pros['pro_code'][$j];
			if(empty($pro_code)) {
				$mes = '第' . $j . '产品不能为空！';
				continue;
			}
			if (strlen(formatMoney($pros['pro_qty'][$j], 2, 1))>2) {
				$mes = $pro_code . '采购数量只能精确到两位小数点';
				$this->msgReturn(0,$mes);
			}

			if (strlen(formatMoney($pros['price_unit'][$j], 2, 1))>2) {
				$mes = $pro_code . '采购单价只能精确到两位小数点';
				$this->msgReturn(0,$mes);
			}
		}

		//如果编辑时删除
		if (ACTION_NAME == 'edit') {
			$pid = I('id');
			//如果是edit 根据pid 删除所有相关的puchase_detail记录
			$map['pid'] = $pid;
			M('stock_purchase_detail')->where($map)->delete();
			unset($map);
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
			$row['pro_qty'] = formatMoney($pros['pro_qty'][$j],2);
			$row['pro_uom'] = $pros['pro_uom'][$j];
			$row['price_unit'] = formatMoney($pros['price_unit'][$j],2);
			$row['price_subtotal'] = formatMoney((intval($row['price_unit'] * 100 * $row['pro_qty'] )/ 100),2);
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

		//查找采购单是否有商品已经上架 liuguangping 20150709
		$p_code = $M->where(array('id'=>$id))->getField('code');
		//查找采购单和批次是否有东西上架
		$where 			 	 = array();
		$where['status'] 	 = '33';//上架
		$where['is_deleted'] = 0;
		$where['type']		 = 1;//采购到货单
		$where['refer_code'] = $p_code;
		$purchase_in_code    = M('stock_bill_in')->where($where)->getField('code');
		//有上架才能退货
		if($purchase_in_code){
			$this->purchase_in_code = TRUE;
		}


		//view上方按钮显示权限
		$this->toolbar_tr =array(
			'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"0,11,04,14"),
            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"0,11"),
            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"0,11"),
            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"0,11,13"),
            'refund'=>array('name'=>'refund' ,'icon'=>'repeat','title'=>'生成红冲单', 'show' => isset($this->auth['refund']),'new'=>'true','domain'=>"13"),
            'out'=>array('name'=>'out' ,'show' => isset($this->auth['out']),'new'=>'true','domain'=>array('13')),//退货已经生效的采购单，并且采购单已经上架的
        );

    }
    protected function before_lists(){
        $pill = array(
            'status'=> array(
                array('value'=>'0','title'=>'草稿','class'=>'warning'),
                array('value'=>'21','title'=>'待入库','class'=>'primary'),
                array('value'=>'31','title'=>'待上架','class'=>'info'),
                array('value'=>'43','title'=>'已结算','class'=>'success'),
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
                '43'=> array('value'=>'43','title'=>'已结算','class'=>'success'),
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
            $sum +=  (intval($val['price_unit'] * 100) * $val['qualified_qty'] / 100);
        }
        if(empty($refund_purchase_data['detail'])){
            $this->msgReturn(0,'已经全部收货成功，没有差异，不能生成冲红单');
        }
        //精确两位 liuguangping
        $refund_purchase_data['for_paid_amount'] = formatMoney($refund_purchase_data['price_total'] - $sum, 2);

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
        $map['stock_purchase.id'] = array('in',$id);
        $data = $purchase
        ->join('partner on partner.id = stock_purchase.partner_id' )
        ->join('warehouse on warehouse.id = stock_purchase.wh_id')
        ->join('user on user.id = stock_purchase.created_user')
        ->where($map)
        ->field('stock_purchase.*, partner.name as partner_name, user.nickname as created_name, warehouse.name as wh_name')
        ->select();

        foreach($data as $key => $value){
            $purchase_detail = M('stock_purchase_detail');
            unset($map);
            $map['pid'] = $value['id'];
            $list = $purchase_detail->where($map)->select();

            $result[$key]['purchase_code'] = $value['code'];
            $result[$key]['purchase_time'] = $value['created_time'];
            $result[$key]['print_time'] = get_time();
            $result[$key]['partner'] = $value['partner_name'];
            $result[$key]['purchase_pay'] = $this->filter['invoice_method'][$value['invoice_method']];
            $result[$key]['purchase_qty'] = $value['cat_total'] . '种' . '/' . $value['qty_total'] . '件';
            $result[$key]['purchase_amount'] = $value['price_total'];
            $result[$key]['purchaser'] = $value['created_name'];
            $result[$key]['warehouse'] = $value['wh_name'];
            $result[$key]['remark'] = $value['remark'];
            $result[$key]['purchase_detail'] = $list;
        }
        

        layout(false);
        $this->assign('result',$result);
        $this->display('Purchase:print');
    }

    //处理预览数据
    public function preview(){
        $pro_infos = I('pro_infos');
        if(empty($pro_infos)){
            $this->msgReturn(0,'请提交批量处理的信息');
        }
        $pro_infos_list = explode("\n", $pro_infos);

        $pro_codes = array();
        $purchase_infos = array();
        foreach($pro_infos_list as $pro_info){
            $pro_info_arr = explode("\t", $pro_info);
            $pro_codes[] = $pro_info_arr[0];
            $purchase_infos[$pro_info_arr[0]]['pro_qty'] = $pro_info_arr[1];
            $purchase_infos[$pro_info_arr[0]]['price_unit'] = $pro_info_arr[2];
        }

        $sku_list = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);

        //拼接模板
        foreach($pro_codes as $pro_code){
            $result .= '<tr class="tr-cur">
                <td style="width:50%;">
                    <input type="hidden" value="'.$pro_code.'" name="pros[pro_code][]" class="pro_code form-control input-sm"><input type="hidden" value="'.$sku_list[$pro_code]['name'].'" name="pros[pro_name][]" class="pro_name form-control input-sm"><input type="hidden" value="'.$sku_list[$pro_code]['pro_attrs_str'].'" name="pros[pro_attrs][]" class="pro_attrs form-control input-sm">
                    <input type="text" value="'.'['.$pro_code.'] '.$sku_list[$pro_code]['wms_name'].'" class="pro_names typeahead form-control input-sm" autocomplete="off">
                </td>
                <td style="width:10%;">
                    <input type="text" id="pro_qty" name="pros[pro_qty][]" placeholder="数量" value="'.$purchase_infos[$pro_code]['pro_qty'].'" class="pro_qty form-control input-sm text-left p_qty" autocomplete="off">
                </td>
                <td style="width:10%;">
                    <select name="pros[pro_uom][]" class="form-control input-sm">
                        <!--<option value="箱">箱</option>-->
                        <option value="件">件</option>
                    </select>
                </td>
                <td style="width:10%;">
                    <input type="text" id="price_unit" name="pros[price_unit][]" placeholder="单价" value="'.$purchase_infos[$pro_code]['price_unit'].'" class="form-control input-sm text-left p_price">
                </td>

                <td style="width:10%;">
                    <label type="text" class="text-left p_res">'.$purchase_infos[$pro_code]['price_unit'] * $purchase_infos[$pro_code]['pro_qty'].'</label>
                </td>

                <td style="width:10%;" class="text-center">
                    <a data-href="/Category/delete.htm" data-value="67" class="btn btn-xs btn-delete" data-title="删除" rel="tooltip" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-original-title="" title=""><i class="glyphicon glyphicon-trash"></i> </a>
                </td>
            </tr>';
        }

        $this->msgReturn(1,'',array('html'=>$result));
    }

    /**
     * 退货显示
     *
     * @author liuguangping@dachuwang.com
     * @since 2015-07-09
     */
    public function out(){
        $flg = I('flg');
        $id  = I('id');

        //查找采购单是否有商品已经上架 liuguangping 20150709
        $M = D('Purchase');
        $purchase_infos = $M->field('code,wh_id,partner_id')->where(array('id'=>$id))->find();
        if(!$purchase_infos){
            $this->msgReturn('0','请合法操作！');
        }
        $p_code = $purchase_infos['code'];
        $wh_id = $purchase_infos['wh_id'];
        $partner_id = $purchase_infos['partner_id'];
        //查找采购单和批次是否有东西上架
        $where                   = array();
        $where['status']      = '33';//上架
        $where['is_deleted'] = 0;
        $where['type']         = 1;//采购到货单
        $where['refer_code'] = $p_code;
        $purchase_in_code    = M('stock_bill_in')->where($where)->getField('code');
        if(!$purchase_in_code){
            $this->msgReturn('0','请选择已上架的采购单');
        }

        $purchaseOutLogic = A('PurchaseOut','Logic');
        $result = $purchaseOutLogic->getOutInfoByPurchaseCode($id, $p_code, $purchase_in_code , $flg);
        if(!$result){
            $this->msgReturn('0','没有满足要退货的货物！');
        }
        $stock_logic = A('Stock','Logic');
        foreach ($result as $key => $vo) {
            $parma = array();
            $parma['pro_code']   = $vo['pro_code'];
            $parma['wh_id']      = $wh_id;
            $parma['batch_code'] = $vo['batch_code'];
            $parma['pro_code']   = $vo['pro_code'];
            if($flg == 'success'){
                $parma['stock_status'] = 'qualified';
            }elseif($flg == 'error'){
                $parma['stock_status'] = 'unqualified';
               }
               $area_name = array('RECV','PACK','Downgrade','Loss','WORK','Breakage');
               $parma['no_in_location_area_code'] = $area_name;
            $pro_qty = $stock_logic->getStockInfosByCondition($parma,1);
            $result[$key]['stock_qty'] = formatMoney($pro_qty['sum'], 2);
        }
        $this->data = $result;
        $this->p_code = $p_code;
        $this->wh_id = $wh_id;
        $this->partner_id = $partner_id;
        $this->out_remark = $this->outremark;
        $this->flg = $flg;
        $this->display('out');
    }

    /**
     * 退货入库
     *
     * @author liuguangping@dachuwang.com
     * @since 2015-07-10
     */
    public function doOut(){
        if(IS_POST){
            $flg         = I('flg');
            $refer_code = I('refer_code');
            $wh_id         = I('wh_id');
            $partner_id = I('partner_id');
            $out_remark = I('out_remark');
            $remark     = I('remark');
            $pros         = I('pros');

            if($flg == 'success'){
                $flg = 'genuine';
            }elseif($flg == 'error'){
                $flg = 'defective';
            }

            if(!$pros){
                $this->msgReturn('1','请选择要退款的货品');
            }

            $plan_return_qtys = $pros['plan_return_qty'];
            $num = arraySum($plan_return_qtys);
            if($num<=0){
                $this->msgReturn('1','退货量为零不能退货');
            }

            foreach ($pros['plan_return_qty']  as $pank => $valp) {
                $mes = '';
                $pro_codemes = $pros['pro_code'][$pank];
                /*if($valp == ''){
                    $mes = $pro_codemes . '退货量数量不能为空';
                    $this->msgReturn(0,$mes);
                }*/
                if (strlen(formatMoney($valp, 2, 1))>2) {
                    $mes = $pro_codemes . '退货量只能精确到两位小数点';
                    $this->msgReturn(0,$mes);
                }

            }

            $purchaseout = array();
            $purchaseout['wh_id'] = $wh_id;
            $purchaseout['partner_id'] = $partner_id;
            $purchaseout['out_remark'] = $out_remark;
            $purchaseout['remark'] = $remark;
            $purchaseout['out_type'] = $flg;
            $purchaseout['rtsg_code'] = get_sn('RTSG',$wh_id);
            $purchaseout['status'] = 'audit';
            $purchaseout['refer_code'] = $refer_code;
            $purchaseoutM = D('PurchaseOut');

            if(!$purchaseoutM->create($purchaseout)){
                $mes = $purchaseoutM->getError();
                $this->msgReturn('1',$mes);
            }

            $result = $purchaseoutM->add();

            if($result){
                //在插入退货单详细表
                $purchaseOutLogic = A('PurchaseOut','Logic');
                $purchaseoutDetail = $purchaseOutLogic->getInserDate(I(),$result);
                $purchaseoutDetailM = D('PurchaseOutDetail');
                $addAll = array();
                foreach($purchaseoutDetail as $vals){
                    if(!$purchaseoutDetailM->create($vals)){
                        $mes = $purchaseoutDetailM->getError();
                        $this->msgReturn('1',$mes);exit;
                    }
                    array_push($addAll, $vals);
                }
                if(M('stock_purchase_out_detail')->addAll($addAll)){
                    //@todo插入出库单表 出库单详细表 已经迁了退货出库单的审核批准方法下面
                    $this->msgReturn('0','退货成功！','',U('PurchaseOut/view',array('id'=>$result)));

                }else{
                    //做处理如果详细插入失败，则删除退货单
                    $purchaseoutM->where(array('id'=>$result))->save(array('is_deleted'=>1));
                    $this->msgReturn('1','提货单创建失败');
                }

            }else{
                $this->msgReturn('1','提货单创建失败');
            }

        }else{
            $this->msgReturn('1','请合法提交！');
        }
    }

    /**
    * 导出采购单详情
    */
    public function exportdetail(){
        $ids = I('id');

        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel(); 
        $i = 1;
        $columns = array(
            'code'=>'采购单号',
            'warehouse_name'=>'仓库',
            'partner_name'=>'供应商',
            'nickname'=>'采购员',
            'invoice_method'=>'付款方式',
            'created_time'=>'采购时间',
            'stock_bill_in_code'=>'到货单号',
            'status'=>'单据状态',
            'pro_code'=>'货品号',
            'pro_name'=>'产品名称',
            'pro_qty'=>'采购数量',
            'pro_uom'=>'计量单位',
            'price_unit'=>'采购单价',
            'price_subtotal'=>'小计');
        
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $Sheet = $this->get_excel_sheet($Excel);
        foreach ($columns as $key  => $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }

        $map['stock_purchase_detail.pid'] = array('in',$ids);
        $result = M('stock_purchase_detail')
        ->join('join stock_purchase on stock_purchase.id = stock_purchase_detail.pid')
        ->join('join warehouse on warehouse.id = stock_purchase.wh_id')
        ->join('join partner on partner.id = stock_purchase.partner_id')
        ->join('join user on user.id = stock_purchase.created_user')
        ->join('left join stock_bill_in on stock_bill_in.refer_code = stock_purchase.code')
        ->where($map)
        ->field('stock_purchase.code,
            warehouse.name as warehouse_name,
            partner.name as partner_name,
            user.nickname,
            stock_purchase.invoice_method,
            stock_purchase.created_time,
            stock_bill_in.code as stock_bill_in_code,
            stock_purchase.status,
            stock_purchase_detail.pro_code,
            stock_purchase_detail.pro_name,
            stock_purchase_detail.pro_qty,
            stock_purchase_detail.pro_uom,
            stock_purchase_detail.price_unit,
            stock_purchase_detail.price_subtotal')
        ->select();

        for($j  = 0;$j<count($result) ; ++$j){
            $i  = 1;
            foreach ($columns as $key  => $value){
                if($key == 'invoice_method'){
                    if($result[$j][$key] == 0){
                        $result[$j][$key] = '预付款';
                    }
                    if($result[$j][$key] == 1){
                        $result[$j][$key] = '货到付款';
                    }
                }
                if($key == 'status'){
                    switch($result[$j][$key]){
                        case '11':
                            $result[$j][$key] = '待生产';
                            break;
                        case '13':
                            $result[$j][$key] = '已生效';
                            break;
                        case '23':
                            $result[$j][$key] = '已完成';
                            break;
                        case '43':
                            $result[$j][$key] = '已结算';
                            break;
                        case '04':
                            $result[$j][$key] = '已作废';
                            break;
                        case '14':
                            $result[$j][$key] = '已驳回';
                            break;
                        case '33':
                            $result[$j][$key] = '已上架';
                            break;
                        default:
                            break;
                    }
                }
                $Sheet->setCellValue($ary[$i/27].$ary[$i%27].($j+2), $result[$j][$key]);
                ++$i;
            }
        }
        
        if(ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        date_default_timezone_set("Asia/Shanghai"); 
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = ".time().".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007'); 
        $objWriter->save('php://output');
    }
}
