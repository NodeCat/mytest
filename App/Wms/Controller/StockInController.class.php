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
            '0' => '草稿',
            '21'=>'待收货',
            '31'=>'待上架',
            '33'=>'已上架',
            //'04'=>'已作废'
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
        'stock_bill_in.pro_code' => array(
            'title' => '货号',
            'query_type' => 'eq',
            'control_type' => 'text',
                'value' => '',
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
    public function after_search(&$map) {
        if (array_key_exists('stock_bill_in.pro_code',$map)) {
            $where['pro_code'] = $map['stock_bill_in.pro_code'][1];
            $result = M('stock_bill_in_detail')->where($where)->select();
            if (empty($result)) {
                unset($map['stock_bill_in.pro_code']);
            }
            $ids = array();
            foreach ($result as $value) {
                $ids[] = $value['pid'];
            }
            unset($map['stock_bill_in.pro_code']);
            $map['stock_bill_in.id'] = array('in', $ids);
        }
    }
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
            //ena13 to pro_code
            $codeLogic = A('Code','Logic');
            $code = $codeLogic->getProCodeByEna13code($code);
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
                //生产日期
                $product_date = I('post.product_date');

                //上架逻辑
                $res = A('StockIn','Logic')->on($id,$code,$qty,$location,$status,$product_date);
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
                        $data['price_subtotal'] = formatMoney(intval($bill_in_detail_info['price_unit'] * 100 * $qty) / 100,2);

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
            //ena13 to pro_code
            $codeLogic = A('Code','Logic');
            $code = $codeLogic->getProCodeByEna13code($code);

            $id = I('post.id');
            $type = I('post.t');
            if($type == 'scan_procode') {
                $A = A('StockIn','Logic');
                $res = $A->getInQty($id,$code,1);
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
                unset($map);
                if(!empty($res)) {
                    if(!empty($res['refer_code'])){
                        //如果是销售到货单，货到付款，判断结算金额是否大于0，如果大于0则证明已经收过货，不让继续收货
                        $map['code'] = $res['refer_code'];
                        $purchase_info = M('stock_purchase')->where($map)->find();

                        if($purchase_info['invoice_method'] == 1 && floatval($purchase_info['paid_amount']) > 0){
                            $this->msgReturn(0,'采购单已结算，无法收货');
                        }
                    }
                    
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
            //$qtyPrepare = $A->getQtyForIn($id,$val['pro_code']);
            //$qtyOn = $A->getQtyForOn($id,$val['pro_code']);
            $getQtyForReceipt = $A->getQtyForReceipt($id,$val['pro_code']);
            
            $qtyForPrepare += $val['prepare_qty'];
            //$qtyForOn += $qtyOn;
            //$pros[$key]['moved_qty'] = $val['pro_qty'] - $qtyIn;
            //$pros[$key]['moved_qty'] = $qtyIn;
            //$moved_qty_total += $qtyIn;
            //预计收获量
            $expected_qty_total += $val['expected_qty'];
            //已收总量
            $receipt_qty_total += $getQtyForReceipt;
            //$pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
        }

        //根据pid 查询对应stock_bill_in_detail
        $map['pid'] = $id;
        $bill_in_detail_list = M('stock_bill_in_detail')->where($map)->select();
        
        $this->pros = A('Pms','Logic')->add_fields($bill_in_detail_list,'pro_name');
        //已上架量
        foreach($this->pros as $pro){
            $data['qtyForIn'] += $pro['done_qty'];
        }
        //$data['qtyForIn'] = $expected_qty_total - $moved_qty_total;

        $data['qtyForPrepare'] = $qtyForPrepare;
        //预计收获量
        $data['expected_qty_total'] = formatMoney($expected_qty_total, 2);
        //已收总量
        $data['receipt_qty_total'] = formatMoney($receipt_qty_total, 2);

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
        $this->search_addon = true;
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
                //'04'=>array('value'=>'04','title'=>'已作废','class'=>'danger')
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
                $value['qty_total'] = formatMoney($detail['qty_total'], 2); //总数量
                $value['sp_created_time'] = $value['created_time']; //创建时间
            }
        }
    }
    
    //打印
    public function printpage(){
        $id = I('get.id');

        //根据id 查询对应入库单
        $map['stock_bill_in.id'] = array('in',$id);
        $bill_in = M('stock_bill_in')
        ->join('left join partner on partner.id = stock_bill_in.partner_id' )
        ->join('user on user.id = stock_bill_in.created_user')
        ->join('warehouse on warehouse.id = stock_bill_in.wh_id')
        ->join('left join stock_purchase on stock_purchase.code = stock_bill_in.refer_code')
        ->where($map)->field('stock_bill_in.id, stock_purchase.expecting_date, stock_bill_in.code, stock_purchase.remark, partner.name as partner_name, user.nickname as created_user_name, warehouse.name as dest_wh_name')
        ->select();
        unset($map);

        foreach($bill_in as $key => $value){
            //根据pid 查询对应入库单详情
            $map['stock_bill_in_detail.pid'] = $value['id'];
            $bill_in_detail_list = M('stock_bill_in_detail')
            ->join('left join product_barcode on product_barcode.pro_code = stock_bill_in_detail.pro_code')
            ->where($map)->field('stock_bill_in_detail.pro_code,product_barcode.barcode,stock_bill_in_detail.expected_qty,stock_bill_in_detail.receipt_qty')
            ->select();
            
            $data[$key]['refer_code'] = $value['code'];
            $data[$key]['remark'] = $value['remark'];
            $data[$key]['print_time'] = get_time();
            $data[$key]['partner_name'] = $value['partner_name'];
            $data[$key]['expecting_date'] = $value['expecting_date'];
            $data[$key]['created_user_name'] = $value['created_user_name'];
            $data[$key]['session_user_name'] = session('user.username');
            $data[$key]['dest_wh_name'] = $value['dest_wh_name'];

            $bill_in_detail_list = A('Pms','Logic')->add_fields($bill_in_detail_list,'pro_name');
            //如果没有对应的条码号则使用内部货号作为条码号
            foreach($bill_in_detail_list as &$val) {
                if(empty($val['barcode'])) {
                   $val['barcode'] = $val['pro_code']; 
                }
            }

            $data[$key]['bill_in_detail_list'] = $bill_in_detail_list;
        }
       
        layout(false);
        $this->assign('result',$data);
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

        if(count($pros) > 150){
            $this->msgReturn(0, '一次提交的产品不能超过150个');
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

            if (strlen(formatMoney($value['pro_qty'], 2, 1))>2) {
                $mes = '数量只能精确到两位小数点';
                $this->msgReturn(0,$mes);
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
            $in_qty[$pro_info_arr[0]] = $pro_info_arr[1];
            //$purchase_infos[$pro_info_arr[0]]['pro_qty'] = $pro_info_arr[1];
            //$purchase_infos[$pro_info_arr[0]]['price_unit'] = $pro_info_arr[2];
        }

        $sku_list = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes,150);

        //拼接模板
        foreach($pro_codes as $key => $pro_code){
            $key++;
            $result .= '<tr class="tr-cur tr-active">
                <td style="width:50%;">
                    <input type="hidden" value="'.$pro_code.'" name="pros['.$key.'][pro_code]" class="pro_code form-control input-sm">
                    <input type="hidden" value="'.$sku_list[$pro_code]['name'].'" name="pros['.$key.'][pro_name]" class="pro_name form-control input-sm">
                    <input type="hidden" value="'.$sku_list[$pro_code]['pro_attrs_str'].'" name="pros['.$key.'][pro_attrs]" class="pro_attrs form-control input-sm">
                    <input type="text" id="typeahead" placeholder="编号" class="pro_names typeahead form-control input-sm" autocomplete="off" value="['.$pro_code.']'.$sku_list[$pro_code]['wms_name'].'">
                </td>
                <td style="width:10%;">
                    <input type="text" name="pros['.$key.'][pro_qty]" placeholder="数量" value="'.$in_qty[$pro_code].'" class="form-control input-sm text-left" autocomplete="off">
                </td>
                <td style="width:10%;">
                    <select name="pros['.$key.'][pro_uom]" class="form-control input-sm">
                        <!--<option value="箱">箱</option>-->
                            <option value="件">件</option>
                    </select>
                </td>
                <!--<td style="width:10%;">
                    <input type="text" id="price_unit" name="pros[0][price_unit]" placeholder="单价"  value="0.00" class="pro_unit form-control input-sm text-left">
                </td>-->
                
                <td style="width:10%;" class="text-center">
                    <a data-href="/Category/delete.htm" data-value="67" class="btn btn-xs btn-delete" data-title="删除" rel="tooltip" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-original-title="" title=""><i class="glyphicon glyphicon-trash"></i> </a>
                </td>
              </tr>';
        }

        $this->msgReturn(1,'',array('html'=>$result));
    }

    //一键上架
    public function onall(){
        $ids = I('id');
        if(empty($ids)){
            $this->msgReturn(0,'请选择到货单');
        }

        //查询收货区库位
        $map['code'] = 'WORK-01';
        $map['wh_id'] = session('user.wh_id');
        $rev_location_info = M('location')->where($map)->find();
        unset($map);

        if(empty($rev_location_info['id'])){
                $this->msgReturn(0,'请添加库位WORK-01');
        }

        //查询到货单信息
        $map['id'] = array('in',$ids);
        $stock_bill_in_infos = M('stock_bill_in')->where($map)->select();
        foreach($stock_bill_in_infos as $stock_bill_in_info){
                if($stock_bill_in_info['status'] == 33){
                    $this->msgReturn(0,'含有已上架的出库单，不能重复上架，请重新选择');
                }
        }
        unset($map);
        unset($stock_bill_in_info);
        foreach ($stock_bill_in_infos as $stock_bill_in_info) {
            //根据到货单查询相关SKU信息
            $map['stock_bill_in_detail.pid'] = $stock_bill_in_info['id'];
            $stock_bill_in_detail = M('stock_bill_in_detail')
            ->join('join stock_bill_in on stock_bill_in.id = stock_bill_in_detail.pid')
            ->field('stock_bill_in_detail.*,stock_bill_in.code')
            ->where($map)
            ->select();
            unset($map);
            foreach($stock_bill_in_detail as $stock_bill_in_detail_info){
                //先模拟收货 更新prepare_qty的值为expected_qty
                $map['id'] = $stock_bill_in_detail_info['id'];
                $data['prepare_qty'] = $stock_bill_in_detail_info['expected_qty'];
                M('stock_bill_in_detail')->where($map)->data($data)->save();
                unset($map);
                unset($data);
                $refer_code = $stock_bill_in_detail_info['code'];
                //liugunagping
                //扣库存操作
                //有批次走分批次走，没有则按照原来的走
                $batch = $stock_bill_in_detail_info['batch'];
                if($batch){
                    $has_source_batch=true;
                }
                else{
                    $has_source_batch=false;
                    $batch = $refer_code;
                }
                $pro_code = $stock_bill_in_detail_info['pro_code'];
                $pro_qty = $stock_bill_in_detail_info['expected_qty'];
                $pro_uom = $stock_bill_in_detail_info['pro_uom'];
                $status = 'qualified';
                $product_date = date('Y-m-d');
                $wh_id = session('user.wh_id');
                $location_id = $rev_location_info['id'];
                //直接上架
                A('Stock','Logic')->adjustStockByShelves($wh_id,$location_id,$refer_code,$batch,$pro_code,$pro_qty,$pro_uom,$status,$product_date,$stock_bill_in_detail_info['pid'],$has_source_batch);
                //如果时采购到货单 则创建采购入库单（ERP）
                if($stock_bill_in_info['type'] == 1){
                    //写入采购入库单 erp_in_detail
                    //根据stock_bill_in id 查询相关数据
                    $map['stock_bill_in_detail.pid'] = $stock_bill_in_info['id'];
                    $map['stock_bill_in_detail.pro_code'] = $stock_bill_in_detail_info['pro_code'];
                    $bill_in_detail_info_from_purchase = M('stock_bill_in_detail')
                    ->join('stock_bill_in on stock_bill_in.id = stock_bill_in_detail.pid' )
                    ->join('stock_purchase on stock_purchase.code = stock_bill_in.refer_code')
                    ->where($map)
                    ->field('stock_bill_in.code,stock_bill_in.refer_code,stock_bill_in_detail.price_unit,stock_purchase.invoice_method')
                    ->find();
                    unset($map);
                    $data['price_unit'] = $bill_in_detail_info_from_purchase['price_unit'];
                    $data['pro_code'] = $stock_bill_in_detail_info['pro_code'];
                    $data['pro_qty'] = $stock_bill_in_detail_info['expected_qty'];
                    $data['stock_in_code'] = $bill_in_detail_info_from_purchase['code'];
                    $data['purchase_code'] = $bill_in_detail_info_from_purchase['refer_code'];
                    $data['pro_status'] = $status;
                    $data['price_subtotal'] = formatMoney(intval($bill_in_detail_info_from_purchase['price_unit'] * 100 * $stock_bill_in_detail_info['expected_qty']) / 100,2);
                    if($bill_in_detail_info_from_purchase['invoice_method'] == 0){
                        $data['status'] = 'paid';
                    }else{
                        $data['status'] = 'nopaid';
                    }
                    $purchase_in_detail = D('PurchaseInDetail');
                    $data = $purchase_in_detail->create($data);
                    $purchase_in_detail->data($data)->add();
                    unset($data);
                } elseif ($stock_bill_in_info['type'] == 4){
                    //加入调拨类型liuguangping
                    //收货=》erp_到货量调拨入库单详细待入库量和实际收货量 erp状态 待上架状态@因需求变更这步取消
                    //@refer_code 入库单code $pro_code 产品编码 $batch 批次 $pro_qty 出库量
                    A('TransferIn','Logic')->updateStockInQty($refer_code, $pro_code, $batch, $pro_qty);
                    //A('TransferIn','Logic')->updateTransferInStatus($refer_code);
                    //上架=》待入库量减去 已上架量增加
                    //$is_up = up 上架量 waiting 待上架
                    A('TransferIn','Logic')->updateStockInQty($refer_code, $pro_code, $batch,$pro_qty,$status,'up');
                    A('TransferIn','Logic')->updateTransferInStatus($refer_code,'up');

                }
            }
        }
        unset($map);
        
        //更新到货单状态为已上架
        $map['wh_id'] = session('user.wh_id');
        $map['id'] = array('in',$ids);
        $data['status'] = 33;
        M('stock_bill_in')->where($map)->save($data);
        unset($map);
        unset($data);

        $this->msgReturn(1);
    }
}
