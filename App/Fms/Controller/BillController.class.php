<?php
namespace Fms\Controller;

use Think\Controller;

class BillController extends \Wms\Controller\CommonController
{
	protected $query;
	protected $columns = array (
		
		'billing_num' => '账单号',
		'theory_start' => '帐期',
        'billing_cycle' => '帐期长度',
        'expire_time' => '结款日期',
		'shop_name' => '店铺名称',
		'mobile' => '客户手机号',
		'bd_name' => '所属BD',
		'bd_mobile' => 'BD手机号',
		'status' => '账单状态'
    );
    protected $excel_columns = array(
        'deliver_date' => '发货日期',
        'suborder_id' => '子单号(短)',
        'primary_category_cn' => '一级分类',
        'shui' => '税率',
        'name' => '末级明细',
        'val' => '规格',
        'unit_cn' => '单位',
        'actual_quantity' => '实收数量',
        'actual_sum_price' => '签收金额',
        'refuse_quantity' => '退货数量',
        'refuse_price' => '退货金额',
        'deliver_fee' => '运费',
        'minus_amount' => '优惠',
        'deposit' => '押金',
        'deal_price' => '实收金额',
    );

    protected $filter = array (
        'expire_status' => array(
            '0' => '',
            '1' => '逾期未付'
        )
    );
    

	public function before_index() {

        $this->table = array(
                'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
                'searchbar' => true, //是否显示搜索栏
                'checkbox'  => false, //是否显示表格中的浮选款
                'status'    => false,
                'toolbar_tr'=> true,
                'statusbar' => true
        );
        //$this->search_addon = true;

        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
        );

        $this->pill = array(
			'status'=> array(
				'11'=>array('value'=>'2','title'=>'未打款','class'=>'warning'),
				'13'=> array('value'=>'3','title'=>'对账中','class'=>'info'),//已审核
				'14'=> array('value'=>'4','title'=>'已打款','class'=>'danger'),
				'04'=> array('value'=>'5','title'=>'已收款','class'=>'success')
			)
		);

        //查询条件
        $A = A('Common/Order', 'Logic');
        $query = $A->billQuery();
        
        unset($query['area'][0]);
        $this->bd = json_encode($query['bd']);
        foreach ($query['bd'] as &$value) {
            $value = $value['name'];
        }

        $this->query = array(
        	'billing_cycle' => array(
        		'title' => '账期长度',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['billing_cycle']
            ),
            'area' => array(
        		'title' => '地区',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['area']
            ),
            'bd' => array(
        		'title' => '所属BD',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['bd']
            ),
            'status' => array(
        		'title' => '账单状态',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['billing_status']
            ),
            'expire_status' => array(
                'title' => '是否逾期',
                'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['expire_status']
            ),
            'start_time' =>    array (    
    			'title' => '账单时间',     
    			'query_type' => 'between',     
    			'control_type' => 'datetime',     
    			'value' => '',   
    		  ),
            'keyword' =>    array (    
                'title' => '关键字搜索',     
                'query_type' => 'eq',     
                'control_type' => 'text',     
                'value' => '',  

            ),
        );
    }

    protected function search($query = '') {
        $this->before($query,'search');//查询前的处理函数
        $condition = I('query'); //列表页查询框都是动态生成的，名字都是query['abc']
        $condition = queryFilter($condition); //去空处理
        $get = I('path.');unset($get['p']);//获取链接中附加的查询条件，状态栏中的按钮url被附带了查询参数
        //将参数并入$condition
        $get_len = count($get);
        for ($i = 0;$i < $get_len;++$i) {
            if(array_key_exists($get[$i], $query) && !array_key_exists($get[$i], $condition)) {
                $condition[$get[$i]] = $get[++$i];
            }
        }
        $this->condition = $condition;
        !empty($condition) && $this->filter_list($condition, '1');//反向转义，反向转filter
        if(!empty($condition)){
            foreach ($query as $key => $v) {//query是查询条件生成的数组，从query中取出当前提交的查询条件。因此，如果提交了query定义之外的查询条件，是会被过滤掉的
                if(!array_key_exists($key, $condition) && !array_key_exists($key.'_1', $condition)) {
                    continue;
                }
                $map[$key] = $condition[$key];
                switch ($v['query_type']) {
                    case 'between'://区间匹配
                        $map['end_time'] = $condition[$key.'_1'];
                    break;
                }
                continue;
                //查询匹配方式
                switch ($v['query_type']) {
                    case 'eq'://相等
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'in':
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'like':
                        $map[$key]=array($v['query_type'],'%'.$condition[$key].'%');
                        break;
                    case 'between'://区间匹配
                        //边界值+1
                        if(check_data_is_valid($condition[$key]) || check_data_is_valid($condition[$key.'_1'])){
                            $condition[$key.'_1'] = date('Y-m-d',strtotime($condition[$key.'_1']) + 86400);
                        }elseif(is_numeric($condition[$key.'_1'])){
                            $condition[$key.'_1'] = $condition[$key.'_1'] + 1;
                        }
                        if(empty($condition[$key]) && !empty($condition[$key.'_1'])) {
                            $map[$key]=array('lt',$condition[$key.'_1']);
                        }
                        elseif(!empty($condition[$key]) && empty($condition[$key.'_1'])) {
                            $map[$key]=array('gt',$condition[$key]);
                        }
                        else {
                            $map[$key]=array($v['query_type'],$condition[$key].','.$condition[$key.'_1']);
                        }
                        break;
                }
            }
        }
        $condition = I('q');//对状态栏的特殊处理,状态栏中的各种状态按钮实际上是附加了各种status=1 这样的查询条件
         if(!empty($condition)){
            $para=explode('&', urldecode($condition));
            foreach ($para as $key => $v) {
                $cond=explode('=', $v);
                if(count($cond)===2)
                    $map[$cond[0]]=$cond[1];
            }
        }
        
        $this->after($map,'search');//查询条件生成以后，这里可以往$map中加入新的查询条件
        return $map;
    }

    public function view()
    {
    	$A = A('Common/Order', 'Logic');
    	$map['id'] = I('id');
    	$data = $A->billDetail($map);
        $store = $A->billStore($map);
        $data['state'] = $data['billing_info']['status_code'];
    	$this->data = $data;
        $this->store = $store;
    	$remarks = $A->billRemarkList($map);
    	$this->remarks = $remarks['list'];
    	$this->display();
    }

    public function detail()
    {
    	$A = A('Common/Order', 'Logic');
    	$map['id'] = I('id');
        $t = I('t');
        if(empty($t)) {
            $map['date'] = I('date');
            $this->date = $map['date'];
            $data = $A->billOrders($map);
        }
        else {
            $this->t = 'store';
            
            $map['customer_id'] = I('uid');
            $data = $A->billStoreOrders($map);
        }
        
        foreach ($data['list'] as &$order) {
            $deal_price= 0;
            foreach ($order['detail'] as &$vo) {
                if(empty($vo['net_weight'])) {
                    $vo['new_weight'] = 0;
                }
                $vo['pricePerW'] = round($vo['single_price'] / $vo['net_weight'],2);
                $vo['inW'] = $vo['quantity'] * $vo['net_weight'];
                $vo['shW'] = $vo['actual_quantity'] * $vo['net_weight'];
                $vo['juW'] = ($vo['quantity'] - $vo['actual_quantity']) * $vo['net_weight'];
                $vo['inW'] = $vo['inW'] ? $vo['inW'] : '/';
                $vo['shW'] = $vo['shW'] ? $vo['shW'] : '/';
                $vo['juW'] = $vo['juW'] ? $vo['juW'] : '/';
                $vo['pricePerW']  = $vo['pricePerW'] ? $vo['pricePerW'] : '/';
                $deal_price += $vo['actual_sum_price'];
            }
            if($deal_price > 0) {
                $order['actual_price'] = $deal_price + $order['deliver_fee'] - $order['minus_amount']- $order['pay_reduce'];
            } else {
                $order['actual_price'] = 0 ;
            }
        }
    	$this->data = $data['list'];
    	$this->display();
    }

    public function pay()
    {
        $A = A('Common/Order', 'Logic');
        $map['id'] = I('id');
        //{"id": xx, "author_id":xx, "author_name":xx, "role_name": xx, "role_id":xx,"payment":xx}
        $map['author_id'] = session('user.uid');
        $map['author_name'] = session('user.username');
        $map['role_id'] = session('user.role');
        $map['role_name'] = session('user.role');

        $data = $A->billDetail($map);
        if ($data['billing_info']['status_code'] == '4' ) {
            $payType = '结算';
            $map['payment'] = '1';
        } else {
            $payType = '一键结算';
            $map['payment'] = '0';
        }
        $res = $A->billPay($map);
        $url = $res['status'] == '0' ? U('view',array('id'=>$map['id'])) : '';
        $status = $res['status'] == 0 ? 1 : 0;
        if($status == 1) {
            $this->addRemark($map['id'], $payType);
        }
        $this->msgReturn($status, $res['msg'], '', $url);
    }

    protected function addRemark($id, $remark) {
        $map['author_id'] = session('user.uid');
        $map['author_name'] = session('user.username');
        $map['role_id'] = session('user.role');
        $map['role_name'] = session('user.role');
        $map['content'] = $remark;
        $map['id'] = $id;
        $res = A('Common/Order', 'Logic')->billAddRemark($map);
    }

    public function remark()
    {
        $A = A('Common/Order', 'Logic');

        if(IS_POST) {
            $remark = I('remark');
            $id = I('id');
            $this->addRemark($id, $remark);
        }
        
        $map['id'] = I('id');
        $data = $A->billDetail($map);
        $this->data = $data;
        $remarks = $A->billRemarkList($map);
        foreach ($remarks['lists'] as &$value) {
            $value['auth_name'] = $value['role_name'] . $value['auth_name'];
        }
        $data = $remarks['list'];
        $this->columns = array(
            'created_time' =>'日期',
            'content' => '操作',
            'role_name' => "名称"
        );
        $this->msgReturn('1',$res['msg'],$data);
    }

    //显示数据列表
    protected function lists($template='')
    {
        //先根据控制器名称获取对应的表名
        $M = M();

        //如果当前控制器中定义了字段，则优先采用控制器中的定义，为的是项目上线以后，这种配置在文件中生效，放在数据库中可能会丢
        if(empty($this->columns)) {
            $this->assign('columns',$setting['list']);
        }
        else {
            $this->assign('columns',$this->columns);
        }
        if(empty($this->query)){
            $this->assign('query',$setting['query']);
        }
        else {
            $this->assign('query',$this->query);
        }

        $maps = $this->search($this->query);
        $map = $maps;
        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        $map['currentPage'] = $p;
        $map['itemsPerPage'] = $page_size;

        if(!empty($map)) {
            $M->where($map);//用界面上的查询条件覆盖scope中定义的
        }
        $this->before($M,'lists');//列表显示前的业务处理

        $M2 = clone $M;//深度拷贝，M2用来统计数量, M 用来select数据。
        $this->after($data,'lists');//查询后的业务处理，传入了结果集
        $this->filter_list($data);//对结果集进行过滤转换
        $A = A('Common/Order', 'Logic');
        if(isset($map['start_time'])) {
            $map['start_time'] = strtotime($map['start_time']);
        }
        if(isset($map['end_time'])) {
            $map['end_time'] = strtotime($map['end_time']);
        }

        $data = $A->billList($map);
        foreach ($data['list'] as &$val) {
            $val['theory_start'] = $val['theory_start'] . ' － ' . $val['theory_end'];
            if ($val['expire_status'] == '1') {
                $val['expire_time'] = $val['expire_time'] . ' <span class="label label-danger">逾期未付</span>';
            }
        }
        $this->pk = 'id';
        $this->assign('data', $data['list']); 
        $maps = $this->condition;
        $template= IS_AJAX ? 'Common@Table/list':'Common@Table/index';
        $this->page($data['total'],$maps,$template);
    }
    
    //根据账单信息到excel表格
    public function export()
    {
        $id = I('id');
        if(empty($id)){
            $this->msgReturn('0','请选择账单');
        }
        $A = A('Common/Order', 'Logic');
        $List_Logic = A('Fms/List', 'Logic');
        $map['billing_ids'] = $id;
        //根据账单id获取账单信息
        $res = $A->billGetExcelData($map);
        if(!$res){
            $this->msgReturn('0','要导出数据为空！');
        }
        if($res['status'] != 0) {
            $this->msgReturn('0',$res['msg']);
        }
        $res = $res['list'];

        unset($map);
        $map['type'] = 'sku_type';
        $map['is_deleted'] = 0;
        //取出一级分类对应的税率
        $tax = M('category')->where($map)->getField('code as id, val');
        
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        
        $bill = $res[0];
        $Excel = new \PHPExcel(); 
        $Sheet = $this->get_excel_sheet($Excel);
        $Sheet->setTitle('对账单');
        $Sheet->mergeCells('A1:O1');
        $Sheet->setCellValue('A1', $bill['shop_name'].' 对账单'.$bill['start_time'].'~'.$bill['end_time']);
        $Sheet->getStyle('A1')->getFont()->setSize(16);
        $Sheet->getStyle('A1')->getFont()->setBold(true);
        $Sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $i = 1;
        $columns = $this->excel_columns;
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        foreach ($columns as $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'2', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'2')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'2')->getFont()->setBold(true);
            ++$i;
        }

        $bill_price = 0;
        $i = 2;
        //对所有订单按发货时间进行排序
        $date_deliver = array();
        foreach($bill['suborder_list'] as $order) {
            $date_deliver[] = $order['deliver_date'];
        }
        array_multisort($date_deliver,$bill['suborder_list']);
        foreach($bill['suborder_list'] as $key => $value) {
            //对sku根据一级分类进行分组
            $result = array();
            foreach ($value['order_details'] as $k => $val) {
                $result[$val['primary_category']][] = $val;
            }
            
            //对分组根据sku的实收金额降序排序
            $array_sum_price = array();
            foreach ($result as $k => &$val) {
                $sum_price = array();
                foreach ($val as &$v) {
                    $sum_price[] = $v['actual_sum_price'];
                    $val['sum_price'] += $v['actual_sum_price'];
                    $v['spec'] = json_decode($v['spec'],true);
                }
                array_multisort($sum_price,SORT_DESC,$val);
                $array_sum_price[] = $val['sum_price'];
            }
            array_multisort($array_sum_price,SORT_DESC,$result);
            foreach ($result as &$gp) {
                array_pop($gp);
            }
            
            $value['order_details'] = $result;
            foreach ($value['order_details'] as $ky => $grp) {
                //分组实收数量
                $grp_actual_qty = 0;
                //分组签收金额
                $grp_deal_price = 0;
                //分组退货数量
                $grp_refuse_qty = 0;
                //分组退货金额
                $grp_refuse_price = 0;
                foreach ($grp as $k => $sku) {
                    $i++;
                    $Sheet->setCellValue('A'.$i, $value['deliver_date']);
                    $Sheet->setCellValue('B'.$i, $value['id']);
                    $Sheet->setCellValue('C'.$i, $sku['primary_category_cn']);
                    $Sheet->setCellValue('D'.$i, $tax[$sku['primary_category']]);
                    $Sheet->setCellValue('E'.$i, $sku['name']);
                    $spec = '';
                    foreach ($sku['spec'] as $sp) {
                        if ($sp['name'] == '规格') {
                            $spec = $sp['val'];
                        }
                    }
                    $Sheet->setCellValue('F'.$i, $spec);
                    $Sheet->setCellValue('G'.$i, $sku['unit_cn']);
                    $sku_qty = $sku['actual_quantity'] - $sku['refuse_quantity'];
                    if ($sku_qty < 0) {
                        $sku_qty = 0;
                    }
                    //分组实收数量
                    $grp_actual_qty += $sku_qty;
                    $Sheet->setCellValue('H'.$i, $sku_qty);
                    //分组签收金额
                    $grp_deal_price += $sku['actual_sum_price'];
                    $Sheet->setCellValue('I'.$i, $sku['actual_sum_price']);
                    //分组退货数量
                    $grp_refuse_qty += $sku['refuse_quantity'];
                    $Sheet->setCellValue('J'.$i, $sku['refuse_quantity']);
                    //分组退货金额
                    $grp_refuse_price += $sku['refuse_price'];
                    $Sheet->setCellValue('K'.$i, $sku['refuse_price']);
                    if ($k == 0) {
                        $Sheet->setCellValue('L'.$i, $value['deliver_fee']);
                        $Sheet->setCellValue('M'.$i, $value['minus_amount']);
                        $Sheet->setCellValue('N'.$i, $value['deposit']);
                    }
                    $sku_price = $sku['actual_sum_price'] - $sku['refuse_price'];
                    if ($sku_price < 0) {
                        $sku_price = 0;
                    }
                    $Sheet->setCellValue('O'.$i, $sku_price);
                }
                $i += 1;
                $Sheet->mergeCells('A'.$i.':'.'G'.$i);
                $Sheet->setCellValue('A'.$i, '小计');
                $Sheet->getStyle('A'.$i)->getAlignment()->setHorizontal('center');
                $actual_qty = $grp_actual_qty - $grp_refuse_qty;
                if ($actual_qty < 0) {
                    $actual_qty = 0;
                }
                $Sheet->setCellValue('H'.$i, $actual_qty);
                $Sheet->setCellValue('I'.$i, $grp_deal_price);
                $Sheet->setCellValue('J'.$i, $grp_refuse_qty);
                $Sheet->setCellValue('K'.$i, $grp_refuse_price);
                if ($ky == 0) {
                    $Sheet->setCellValue('L'.$i, $value['deliver_fee']);
                    $Sheet->setCellValue('M'.$i, $value['minus_amount']);
                    $Sheet->setCellValue('N'.$i, $value['deposit']);
                }
                //分组实收金额 ＝ 分组签收金额 － 分组退货金额
                $grp_actual_price = $grp_deal_price - $grp_refuse_price;
                if ($grp_actual_price < 0) {
                    $grp_actual_price = 0;
                }
                $Sheet->setCellValue('O'.$i, $grp_actual_price);
            }
            $i += 1;
            $Sheet->mergeCells('A'.$i.':'.'G'.$i);
            $Sheet->setCellValue('A'.$i, '合计');
            $Sheet->getStyle('A'.$i)->getAlignment()->setHorizontal('center');
            $order_actual_qty = $value['sum_actual_quantity'] - $value['sum_refuse_quantity'];
            if ($order_actual_qty < 0) {
                $order_actual_qty = 0;
            }
            $Sheet->setCellValue('H'.$i, $order_actual_qty);
            $Sheet->setCellValue('I'.$i, $value['deal_price']);
            $Sheet->setCellValue('J'.$i, $value['sum_refuse_quantity']);
            $Sheet->setCellValue('K'.$i, $value['sum_refuse_price']);
            $Sheet->setCellValue('L'.$i, $value['deliver_fee']);
            $Sheet->setCellValue('M'.$i, $value['minus_amount']);
            $Sheet->setCellValue('N'.$i, $value['deposit']);
            $order_actual_price = $value['deal_price'] - $value['sum_refuse_price'];
            if ($order_actual_price < 0) {
                $order_actual_price = 0;
            }
            $bill_price += $order_actual_price;
            $Sheet->setCellValue('O'.$i, $order_actual_price);
        }
        $i += 1;
        $Sheet->mergeCells('A'.$i.':'.'B'.$i);
        $Sheet->setCellValue('A'.$i, '大写金额合计');
        $Sheet->mergeCells('C'.$i.':'.'O'.$i);
        $Sheet->setCellValue('C'.$i, $List_Logic->cny($bill_price));
        $Sheet->getStyle('C'.$i)->getAlignment()->setHorizontal('right');
        $i += 1;
        $Sheet->mergeCells('A'.$i.':'.'B'.$i);
        $Sheet->setCellValue('A'.$i, '经办人:');
        $Sheet->mergeCells('C'.$i.':'.'M'.$i);
        $Sheet->setCellValue('N'.$i, '财务部核对人:');
        //销售收入明细表
        $Sheet1 = $Excel->createSheet();
        $Sheet1->setTitle('销售收入明细表');
        $Excel->setActiveSheetIndex(1);
        $Sheet1->mergeCells('A1:F1');
        $Sheet1->setCellValue('A1', $bill['shop_name'].' 销售收入明细表'.$bill['start_time'].'~'.$bill['end_time']);
        $Sheet1->getStyle('A1')->getFont()->setSize(16);
        $Sheet1->getStyle('A1')->getFont()->setBold(true);
        $Sheet1->getStyle('A1')->getAlignment()->setHorizontal('center');
        $Sheet1->setCellValue('A2', '城市');
        $Sheet1->setCellValue('B2', '一级分类');
        $Sheet1->setCellValue('C2', '价格');
        $Sheet1->setCellValue('D2', '税率');
        $Sheet1->setCellValue('E2', '税额');
        $Sheet1->setCellValue('F2', '价税合计');
        $category_group = array();
        foreach($bill['suborder_list'] as $key => $value) {
            foreach ($value['order_details'] as $k => $val) {
                $category_group[$val['primary_category']][] = $val;
            }
        }
        $j = 2;
        //一级分类的实收金额总计
        $grp_sum_price = 0;
        //一级分类的税率总计
        $grp_sum_tax   = 0;
        //一级分类的价税总计
        $grp_sum_tax_price = 0;
        foreach ($category_group as $key => $value) {
            $j++;
            //单个分组的单价总计
            $sum_actual_price = 0;
            $category = '';
            foreach ($value as $k => $val) {
                $actual_price = $val['actual_sum_price'] - $val['refuse_price'];
                if ($actual_price < 0 ) {
                    $actual_price = 0;
                }
                $sum_actual_price += $actual_price;
                $category = $val['primary_category_cn'];
            }
            $Sheet1->setCellValue('B'.$j, $category);
            //一级分类的实收金额总计
            $grp_sum_price += $sum_actual_price;
            $Sheet1->setCellValue('C'.$j, $sum_actual_price);
            //一级分类的税率总计
            $grp_sum_tax += $tax[$key];
            $Sheet1->setCellValue('D'.$j, $tax[$key]);
            $tax_price = bcmul($sum_actual_price, $tax[$key], 2);
            //一级分类的价税总计
            $grp_sum_tax_price += $tax_price;
            $Sheet1->setCellValue('E'.$j, $tax_price);
            $Sheet1->setCellValue('F'.$j, $sum_actual_price + $tax_price);
        }
        $Sheet1->mergeCells('A3:A'.$j);
        $Sheet1->setCellValue('A3', $bill['city_cn']);
        $Sheet1->getStyle('A3')->getAlignment()->setHorizontal('center');
        $Sheet1->getStyle('A3')->getAlignment()->setVertical('center');
        $j += 1;
        $Sheet1->mergeCells('A'.$j.':B'.$j);
        $Sheet1->setCellValue('A'.$j, '合计');
        $Sheet1->getStyle('A'.$j)->getAlignment()->setHorizontal('center');
        $Sheet1->setCellValue('C'.$j, $grp_sum_price);
        $Sheet1->setCellValue('D'.$j, $grp_sum_tax);
        $Sheet1->setCellValue('E'.$j, $grp_sum_tax_price);
        $Sheet1->setCellValue('F'.$j, $grp_sum_price + $grp_sum_tax_price);
         
        date_default_timezone_set("Asia/Shanghai");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = 对账单-".date('Y-m-d-H-i-s',time())."-".$bill['id'].".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: "); 
        $objWriter  = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    protected function get_excel_sheet(&$Excel) 
    {
        $Excel->getProperties()
        ->setCreator("Dachuwang")
        ->setLastModifiedBy("Dachuwang")
        ->setTitle("Dachuwang")
        ->setSubject("Dachuwang")
        ->setDescription("Dachuwang")
        ->setKeywords("Dachuwang")
        ->setCategory("Dachuwang");
        $Excel->setActiveSheetIndex(0);
        $Sheet  =  $Excel->getActiveSheet();          
        $Sheet->getDefaultColumnDimension()->setAutoSize(true);
        $Sheet->getDefaultStyle()->getFont()->setName('Arial');
        $Sheet->getDefaultStyle()->getFont()->setSize(13);
        return $Sheet;
    }
}