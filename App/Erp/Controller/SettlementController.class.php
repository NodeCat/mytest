<?php
/**
 * 结算单业务处理
 * User: san77
 * Date: 15/7/14
 * Time: 上午10:32
 */

namespace Erp\Controller;

use Think\Controller;

class SettlementController extends CommonController
{
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
            '0'  => '待审核',
            '1'  => '已生效',
            '2'  => '已结算',
            '11' => '已作废',
        )
    );

    protected $columns = array (
        'id'                => '',
        'code'              => '结算单号',
        'partner_name'      => '供应商',
        'created_time'      =>'创建时间',
        'stock_in_time'     => '入库时间',
        'created_user'      => '创建人',
        'settlement_time'   => '结算时间',
        'settlement_user'   => '结算人',
        'total_amount'      => '结算金额',
        'status'            => '单据状态'
    );

    protected $query = array (
        'erp_settlement.code' => array (
            'title'         => '结算单号',
            'query_type'    => 'like',
            'control_type'  => 'text',
            'value'         => '',
        ),
        'erp_settlement.created_user' => array (
            'title'         => '创建人',
            'query_type'    => 'eq',
            'control_type'  => 'refer',
            'value'         => 'stock_purchase-created_user-user-id,id,nickname,User/refer',
        ),
        'erp_settlement.created_time' => array (
            'title'         => '创建时间',
            'query_type'    => 'between',
            'control_type'  => 'datetime',
            'value'         => '',
        ),
        'erp_settlement.purchase_code' => array (
            'title'         => '采购单号',
            'query_type'    => 'like',
            'control_type'  => 'text',
            'value'         => '',
        ),
        'erp_settlement.settlement_user' => array (
            'title' => '结算人',
            'query_type' => 'eq',
            'control_type' => 'refer',
            'value' => 'stock_purchase-created_user-user-id-1,id,nickname,User/refer',
        ),
        'erp_settlement.settlement_time' =>    array (
            'title' => '结算时间',
            'query_type' => 'between',
            'control_type' => 'datetime',
            'value' => '',
        ),
        'erp_settlement.partner_id' =>    array (
            'title' => '供应商',
            'query_type' => 'eq',
            'control_type' => 'refer',
            'value' => 'stock_purchase-partner_id-partner-id,id,name,Partner/refer',
        )
    );

    protected $code_type = array(
        1 => '采购单',
        2 => '入库单',
        3 => '冲红单',
        4 => '退货单',
    );

    public function match_partner()
    {
        $code           = I('q');
        $partnerMode    = M('Partner');
        $where['name']  = array('like', '%'.$code.'%');
        $where['is_deleted'] = 0;
        $res            = $partnerMode->field('id, name')->where($where)->select();

        if (empty($res)) {
            $res['']='';
        }
        echo json_encode($res);exit;
    }

    public function view()
    {
        $this->_before_index();
        $this->edit();
    }

    public function _before_index()
    {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false,
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr = array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
            'print'=>array('name'=>'print','link'=>'printpage','icon'=>'print','title'=>'打印', 'show'=>isset($this->auth['printpage']),'new'=>'true','target'=>'_blank')
        );

        $this->toolbar =array(
            array('name'=>'add', 'show' =>isset($this->auth['add']),'new'=>'true'),
        );

        $this->search_addon = true;
    }

    protected function before_lists()
    {
        // 0审核 1已生效 2已结算 11已作废
        $pill = array(
            'status'=> array(
                '0'=> array('value'=>'0','title'=>'待审核','class'=>'default'),
                '1'=> array('value'=>'1','title'=>'已生效','class'=>'info'),
                '2'=> array('value'=>'2','title'=>'已结算','class'=>'success'),
                '11'=> array('value'=>'11','title'=>'已作废','class'=>'warning')
            )
        );

        //统计每个结算单状态的数量
        $M                 = M('erp_settlement');
        $map['is_deleted'] = 0;
        $res               = $M->field('status,count(status) as qty')->where($map)->group('status')->select();

        foreach ($res as $key => $val) {
            if(array_key_exists($val['status'], $pill['status'])){
                $pill['status'][$val['status']]['count'] = $val['qty'];
                $pill['status']['total'] += $val['qty'];
            }
        }

        foreach ($pill['status'] as $k => $val) {
            if(empty($val['count'])){
                $pill['status'][$k]['count'] = 0;
            }
        }
        $this->pill = $pill;
    }

    protected function before_edit($data)
    {
        $logic   = D('Settlement','Logic');
        $result1 = $logic->getStockInListDetail($data['code']);     //采购单明细
        $result2 = $logic->getPurchaseListDetail($data['code']);    //入库单明细
        $result3 = $logic->getRefundListDetail($data['code']);      //冲红单明细
        $result4 = $logic->getPurchaseOutListDetail($data['code']); //退款单明细

        $pros = array_merge($result1, $result2, $result3, $result4);
        $this->pros = $pros;
    }

    //添加结算单
    public function add()
    {
        if (IS_AJAX && IS_POST) {
            $post         = I('post.data');             //选择的结算订单
            $total_amount = I('post.paid_amount');      //总金额
            $partner_id   = I('post.partner_id/d');     //供应商ID
            $bill_number  = I('post.bill_number');      //发票号
            $bill_amount  = I('post.bill_amount');      //发票金额
            
            if (empty($partner_id)) {
                $this->ajaxReturn(array('code'=>'-1', 'message'=>'请选择供应商！'));
            }

            if (empty($post) || count($post) <= 0) {
                $this->ajaxReturn(array('code'=>'-1', 'message'=>'请选择需要结算的订单！'));
            }

            if (empty($total_amount)) {
                $this->ajaxReturn(array('code'=>'-1', 'message'=>'结算金额不能为0'));
            }

            $paid   = array();      //采购单数据
            $stock  = array();      //入库单数据
            $refund = array();      //冲红单数据
            $purchaseOut = array(); //退货单数据

            foreach($post as $val) {
                //0=>ID, 1=>订单类型('norefund'-冲红单), 2=>付款类型(0—预付款，1—货到付款)
                $postArray =  explode('_',$val);

                if ($postArray[1] == 'norefund') {
                    $refund[] = $postArray[0];
                } else if ($postArray[1] == 'wait') {
                    $purchaseOut[] = $postArray[0];
                }else if ($postArray[2] == 0) {
                    $paid[] = $postArray[0];
                } else {
                    $stock[] = $postArray[0];
                }
            }

            //根据erp_purchase_in_detail 的id，查询与之相匹配的采购单号和到货单号一样的一组数据
            $stock_new = array();
            foreach($stock as $k => $val){
                $map['id'] = $val;
                $purchase_in_detail_info = M('erp_purchase_in_detail')->field('purchase_code,stock_in_code')->where($map)->find();
                unset($map);

                $purchase_in_detail_group_info = M('erp_purchase_in_detail')->field('id')->where($purchase_in_detail_info)->select();
                unset($purchase_in_detail_info);

                foreach($purchase_in_detail_group_info as $key => $value){
                    $stock_new[] = $value['id'];
                }
            }

            $stock = $stock_new;

            $sn = get_sn('settlement');
            $logic  = D('Settlement', 'Logic');
            $result = $logic->saveData($partner_id, $total_amount, $bill_number, $bill_amount, $sn, $paid, $stock, $refund, $purchaseOut);

            if (!$result) {
                $this->ajaxReturn(array('code'=>'-1', 'message'=>$logic->error));
            }
            $this->ajaxReturn($result);
        } else {
            $wareHouse       = A('Warehouse');
            $wareHouseList   = $wareHouse->get_list('Warehouse','id,name');

            $columns = array(
                'id' => '',
                'code'          => '单据号',
                'partner_name'  => '供应商',
                'warehouse'     => '仓库',
                'order_type'    => '订单类型',
                'payment_type'  => '付款类型',
                'total_amount'  => '结算金额',
                'created_time'  => '创建人'
            );

            $this->assign('code_type', $this->code_type);
            $this->assign('columns', $columns);
            $this->assign('wareHouseList', $wareHouseList);
            $this->display();
        }
    }

    /**
     * 修改发票信息
     */
    public function editInvoice()
    {
        if (IS_AJAX && IS_POST) {
            $id      = I('post.id/d');
            $invoice = I('post.invoice');
            $amount  = I('post.amount');

            if (empty($id) || ( empty($invoice) && empty($amount) )) {
                $this->ajaxReturn(array('code'=>-1, 'message'=>'信息填写不完整'));
            }

            $data = array(
                'id'             => $id,
                'invoice'        => $invoice,
                'invoice_amount' => $amount
            );

            $Model   = D('Settlement');
            if ($Model->create($data)) {
                if($Model->save()){
                    $this->ajaxReturn(array('code'=>1, 'message'=>'更新成功'));
                }
            }
            $this->ajaxReturn(array('code'=>-1, 'message'=>'数据更新失败！'));
        }
    }

    /**
     * 获取商户可结算的单据信息
     * return @array
     */
    public function getOrderList()
    {
        $partner_id = I('post.partner_id/d');
        $wh_id      = I('post.wh_id/d');
        $code_type  = I('post.code_type/d');

        if (IS_AJAX) {
            //商户ID
            if (!empty($partner_id)) {
                $map['partner_id'] = $partner_id;
            }
            //仓库ID
            if (!empty($wh_id)) {
                $map['wh_id'] = $wh_id;
            }

            $logic = D('Settlement','Logic');
            $returnArray = array();
            if (!empty($code_type)) {
                switch ($code_type) {
                    case 1: //采购单
                        $returnArray = $logic->getPurchaseList($map);
                        break;
                    case 2: //入库单
                        $returnArray = $logic->getStockInList($map);
                        break;
                    case 3: //冲红单
                        $returnArray = $logic->getRefundList($map);
                        break;
                    case 4: //退货单
                        $returnArray = $logic->getPurchaseOutList($map);
                }
            } else {
                //获取所有类型
                $result  = $logic->getPurchaseList($map);
                $result1 = $logic->getStockInList($map);
                $result2 = $logic->getRefundList($map);
                $result3 = $logic->getPurchaseOutList($map);

                //入库单需要过滤重复的code字段
                $tmp = array();
                $result1_new = array();
                foreach($result1 as $key => $val){
                    if(!isset($tmp[$val['code']])){
                        $tmp[$val['code']] = true;
                        $result1_new[$val['code']] = $val;
                    }else{
                        unset($returnArray[$key]);
                        $result1_new[$val['code']]['paid_amount'] = bcadd($result1_new[$val['code']]['paid_amount'], $val['paid_amount'], 2);
                    }
                }

                $result1 = $result1_new;

                $returnArray = array_merge($result, $result1, $result2, $result3);
            }

            //如果取出的单据数据，已经在其它结算单中，并且结算单不为作废状态，单据就不再显示
            $code = array();
            foreach ($returnArray as $key => $val) {
                $code[] = $val['code'];
            }
            $code_array = array_unique($code);

            if(empty($code_array)){
                $this->ajaxReturn(array());
            }

            $where['erp_settlement_detail.order_code'] = array('in',  $code_array);
            $model  = M('erp_settlement_detail');
            $join   = array('INNER JOIN erp_settlement ON erp_settlement.code=erp_settlement_detail.code AND erp_settlement.status!=11');
            $field  = 'erp_settlement_detail.order_code';
            $array = $model->field($field)->join($join)->where($where)->group('erp_settlement_detail.order_code')->getField('erp_settlement_detail.order_code,erp_settlement_detail.id');
            foreach ($returnArray as $keys => $value) {
                if ( !empty($array[$value['code']]) ) {
                    unset($returnArray[$keys]);         //剔除数组
                }
            }

            $this->ajaxReturn($returnArray);
        }
    }

    public function pass()
    {
        $id     = I('get.id/d');

        if (empty($id)) {
            $this->error('非法操作');
        }

        $data['id']     = $id;
        $data['status'] = 1;
        $data['audited_user'] = UID;
        $data['audited_time'] = get_time();

        $Model  = D('Settlement');

        if ($Model->create($data)) {
            if ($Model->save()) {
                $this->success('审核成功','/Settlement/index');
            } else {
                $this->error($Model->getError());
            }
        } else {
            $this->error($Model->getError());
        }
    }

    //更新结算单为作废状态
    public function close()
    {
        $id = I('get.id/d');

        if (empty($id)) {
            $this->error('非法操作');
        }

        $data['id']     = $id;
        $data['status'] = 11;       //作废状态标记
        $data['audited_user'] = UID;
        $data['audited_time'] = get_time();

        $Model  = D('Settlement');
        if ($Model->create($data)) {
            if ($Model->save()) {
                $this->success('审核成功','/Settlement/index');
            } else {
                $this->error($Model->getError());
            }
        } else {
            $this->error($Model->getError());
        }
    }

    //付款
    public function pay()
    {
        $ids = I('ids');

        if (empty($ids)) {
            $rsg['status'] = 0;
            $rsg['msg']    = '请选择一个付款的单据';
            $this->ajaxReturn($rsg);
        }

        //根据ids 查询采购入库单信息
        $map['id']  = array('in',$ids);
        $settlement = M('erp_settlement')->where($map)->select();

        foreach ($settlement as $settlementInfo) {
            if ($settlementInfo['status'] != '1') {
                $rsg['status'] = 0;
                $rsg['msg'] = '请选择已生效的单据付款';
                $this->ajaxReturn($rsg);
            }
        }

        //更新结算单为已结算状态
        $data['status'] = '2';
        $data['settlement_time'] = get_time();
        $data['settlement_user'] = UID;
        if (!M('erp_settlement')->where($map)->data($data)->save()) {
            $rsg['status'] = 0;
            $rsg['msg'] = '更新单据失败';
            $this->ajaxReturn($rsg);
        }
        unset($data);

        //查询结算单对应的单据详细信息，用于更新对应单据状态使用
        $subQuery          = M('erp_settlement')->field('code')->where($map)->buildSql();
        $settlement_detail = M('erp_settlement_detail')->where(' code in '.$subQuery)->select();
        unset($map);

        $purchase = array();        //采购单
        $stock    = array();        //入库单
        $refund   = array();        //退款单
        $stockOut = array();        //退货单
        $stock_amount    = 0;       //记录入库单已结算金额

        foreach($settlement_detail as $key => $val){
            if( $val['order_type'] == 1 ){
                $purchase[]       = $val['order_code'];
            } else if ( $val['order_type'] == 2 ){
                $stock[]          = $val['stock_id'];               //入库单ID，入库单有多个，可选择性的去对某个入库单付款，需记录入库单ID
                $stock_purchase[] = $val['order_code'];             //采购单code
                $stock_amount    += $val['total_amount'];
            } else if ($val['order_type'] == 3) {
                $refund[]         = $val['order_code'];
            } else if ($val['order_type'] == 4) {
                $stockOut[]       = $val['order_code'];
            }
        }

        //更新采购单为已付款状态
        if ( !empty($purchase) ) {
            $map['code']    = array('in', implode(',', $purchase));
            $data['status'] = '43';
            M('stock_purchase')->where($map)->data($data)->save();
            unset($map);
            unset($data);
        }

        //更新入库单为已支付状态，更新采购单为已结算状态
        if ( !empty($stock) ) {
            $map['id']      = array('in', implode(',', $stock));
            $data['status'] = 'paid';
            M('erp_purchase_in_detail')->where($map)->data($data)->save();
            unset($map);

            //更新采购单状态为已结算状态
            $map['code']    = array('in', implode(',', $stock_purchase));
            $data['status'] = '43';
            M('stock_purchase')->where($map)->data($data)->save();

            //更新采购单已结算金额
            M('stock_purchase')->where($map)->setInc('paid_amount', $stock_amount);
            unset($map);
            unset($data);
        }

        //更新退款单为已收款状态
        if ( !empty($refund) ) {
            $map['code']    = array('in', implode(',', $refund));
            $data['status'] = 'refund';
            M('erp_purchase_refund')->where($map)->data($data)->save();
            unset($map);
            unset($data);
        }

        //更新退款单为已收款状态
        if ( !empty($stockOut) ) {
            $map['rtsg_code']          = array('in', implode(',', $stockOut));
            $data['receivables_state'] = 'ok';

            M('stock_purchase_out')->where($map)->data($data)->save();
            unset($map);
            unset($data);
        }

        $data['status'] = 1;

        $this->ajaxReturn($data);
    }

    public function printPage()
    {
        $id = I('get.id/d');


        //查询结算单对应的单据详细信息，用于更新对应单据状态使用
        $join = array(
            "inner join partner on erp_settlement.partner_id=partner.id ",
            "left join user on erp_settlement.settlement_user = user.id ",
        );

        $model = M('erp_settlement');

        $settlement = $model->field('erp_settlement.code as code,erp_settlement.settlement_time as settlement_time, user.nickname as settlement_user, partner.name as partner_name, erp_settlement.total_amount')->join($join)->where('erp_settlement.id=%d', $id)->find();
        $settlement['print_time'] = get_time();
        $this->assign('settlement', $settlement);

        $code  = $model->where('id=%d', $id)->getField('code');

        $logic   = D('Settlement','Logic');
        $result1 = $logic->getStockInListDetail($code);     //采购单明细
        $result2 = $logic->getPurchaseListDetail($code);    //入库单明细
        $result3 = $logic->getRefundListDetail($code);      //冲红单明细
        $result4 = $logic->getPurchaseOutListDetail($code); //退款单明细

        $list = array_merge($result1, $result2, $result3, $result4);

        layout(false);
        $this->assign('list', $list);
        $this->display('Settlement:print');
    }

    //在search方法执行后 执行该方法
    protected function after_search(&$map)
    {
        //获得页面提交过来的采购单号
        if(array_key_exists('erp_settlement.purchase_code', $map)){
            //根据采购单号 查询结算单号
            $purchase_map['order_code'] = $map['erp_settlement.purchase_code'];

            $map_list = M('erp_settlement_detail')->where($purchase_map)->field('code')->select();
            unset($map['erp_settlement.purchase_code']);

            $map_list_arr = array();
            foreach($map_list as $val){
                $map_list_arr[] = $val['code'];
            }

            if(!empty($map_list_arr)){
                $map['erp_settlement.code'] = array('in',$map_list_arr);
            }else{
                $map['erp_settlement.code'] = '-1';
            }
        }
    }
}