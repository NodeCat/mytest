<?php
namespace Erp\Controller;
use Think\Controller;
class PurchaseInDetailController extends CommonController {
	protected $filter = array(
			'status' => array('paid' => '已支付', 'nopaid' => '待支付'),
            'invoice_method' =>  array(
                '0' => '预付款',
                '1' => '货到付款'
            ),
		);
	protected $columns = array(
        'id' => '',
        'code' => '入库单号',
        'partner_name' => '供应商',
		'purchase_code' => '采购单号',
		'stock_in_code' => '到货单号',
		'pro_name' => '货品名称',
        'pro_code' => '货品号',
		'pro_qty' => '入库数量',
		'price_unit' => '单价',
		'price_subtotal' => '小计',
		'status' => '支付状态',
        'updated_time' => '付款时间',
        'created_time' => '入库时间',
    );
    protected $query   = array (
        'erp_purchase_in_detail.partner_name' => array (
            'title' => '供应商',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
		'erp_purchase_in_detail.purchase_code' => array (
		    'title' => '采购单号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
		'erp_purchase_in_detail.stock_in_code' => array (
		    'title' => '到货单号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
		'erp_purchase_in_detail.pro_code' => array (
		    'title' => '货品号',
		    'query_type' => 'like',
		    'control_type' => 'text',
		    'value' => '',
		),
        'erp_purchase_in_detail.status' => array (
            'title' => '支付状态',
            'query_type' => 'eq',     
            'control_type' => 'select',     
            'value' => array(
                'paid'=>'已支付',
                'nopaid'=>'待支付'
            ),   
        ),
        'erp_purchase_in_detail.id' => array (
            'title' => '入库单号',
            'query_type' => 'eq',
            'control_type' => 'text',
            'value' => '',
        ),
        'erp_purchase_in_detail.created_time' =>    array (    
            'title' => '下单时间',     
            'query_type' => 'between',     
            'control_type' => 'datetime',     
            'value' => '',   
        ),
	);

    //设置列表页选项
	protected function before_index() {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => false,'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
        $this->search_addon = true;
    }

    protected function after_search(&$map){
        if(IS_AJAX){
            //按照供应商查询
            if(!empty($map['erp_purchase_in_detail.partner_name'])){
                $purchase_codes = $this->getPurchaseCodeMapByPartnerName($map['erp_purchase_in_detail.partner_name'][1]);
                if($purchase_codes){
                    $map['purchase_code'] = array('in',$purchase_codes);
                }else{
                    $map['purchase_code'] = '';
                }
                unset($map['erp_purchase_in_detail.partner_name']);
            }
        }
    }

    //付款
    protected function after_lists(&$data){
        //过滤所有不合格
        foreach($data as $k => $val){
            if($val['pro_status'] != 'qualified'){
                unset($data[$k]);
            }
        }
    }

    //支付
    public function pay(){
    	$ids = I('ids');

        if(empty($ids)){
            $data['status'] = 0;
            $data['msg'] = '请选择一个未收款的单据';
            $this->ajaxReturn($data);
        }

    	//根据ids 查询采购入库单信息
    	$map['id'] = array('in',$ids);
    	$purchase_in_details = M('erp_purchase_in_detail')->where($map)->select();
    	unset($map);

    	$paid_amount = 0;
    	foreach($purchase_in_details as $purchase_in_detail){
    		if($purchase_in_detail['status'] == 'paid'){
    			$data['status'] = 0;
    			$data['msg'] = '所选单据中有已支付状态的单据，请选择未支付的单据';
    			$this->ajaxReturn($data);
    		}
    	}

    	//更新为支付状态
    	$map['id'] = array('in',$ids);
    	$data['status'] = 'paid';
    	M('erp_purchase_in_detail')->where($map)->data($data)->save();
    	unset($map);
    	unset($data);

        //根据ids 查询采购入库单信息 按照采购单号分组
        $map['id'] = array('in',$ids);
        $purchase_in_details = M('erp_purchase_in_detail')->where($map)->field('id,purchase_code,sum(price_subtotal) as price_total')->group('purchase_code')->select();
        unset($map);

        foreach($purchase_in_details as $purchase_in_detail){
            $map['code'] = $purchase_in_detail['purchase_code'];
            //更新采购单 paid_amount
            M('stock_purchase')->where($map)->setInc('paid_amount',$purchase_in_detail['price_total']);
            unset($map);
        }        

    	$data['status'] = 1;

		$this->ajaxReturn($data);
    }

    //导出
    public function export(){
        $id = I('id');
        $created_time_start = I('created_time_start');
        $created_time_end = I('created_time_end');
        $purchase_code = I('purchase_code');
        $stock_in_code = I('stock_in_code');
        $pro_code = I('pro_code');
        $status = I('status');
        //按照供应商查询
        $partner_name = I('partner');
        if(!empty($partner_name)){
            $purchase_codes = $this->getPurchaseCodeMapByPartnerName($partner_name);
            if($purchase_codes){
                $map['purchase_code'] = array('in',$purchase_codes);
            }else{
                $map['purchase_code'] = '';
            }
        }

        if(!empty($purchase_code)){
            $map['purchase_code'] = array(array('like','%'.$purchase_code.'%'));
        }
        if(!empty($stock_in_code)){
            $map['stock_in_code'] = array(array('like','%'.$stock_in_code.'%'));
        }
        if(!empty($pro_code)){
            $map['pro_code'] = array(array('like','%'.$pro_code.'%'));
        }
        if(!empty($status)){
            $map['status'] = $status;
        }
        if(!empty($id)){
            $map['erp_purchase_in_detail.id'] = $id;
        }
        if(!empty($created_time_start) || !empty($created_time_end)){
            $created_time_start = (empty($created_time_start)) ? '1900-01-01' : $created_time_start;
            $created_time_end = (empty($created_time_end)) ? '9999-12-31' : $created_time_end;
            $created_time_start = $created_time_start.' 00:00:00';
            $created_time_end = $created_time_end.' 23:59:59';
            $map['erp_purchase_in_detail.created_time'] = array('between',$created_time_start.','.$created_time_end);
        }

        //查询符合条件的采购入库单
        $purchase_in_details = M('erp_purchase_in_detail')
        ->join("inner join stock_purchase on stock_purchase.code=erp_purchase_in_detail.purchase_code ")
        ->join("inner join partner on stock_purchase.partner_id=partner.id ")
        ->field('erp_purchase_in_detail.*,partner.name as partner_name')
        ->where($map)->order('id DESC')->select();

        if(empty($purchase_in_details)){
            echo '没有符合条件的数据';
            return false;
        }

        //$purchase_in_details = A('Pms','Logic')->add_fields($purchase_in_details,'pro_name');

        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();

        $Excel->getActiveSheet()->setCellValue('A1', '入库单号'); 
        $Excel->getActiveSheet()->setCellValue('B1', '供应商'); 
        $Excel->getActiveSheet()->setCellValue('C1', '采购单号'); 
        $Excel->getActiveSheet()->setCellValue('D1', '到货单号');
        $Excel->getActiveSheet()->setCellValue('E1', '货品号');
        $Excel->getActiveSheet()->setCellValue('F1', '入库数量');
        $Excel->getActiveSheet()->setCellValue('G1', '单价');
        $Excel->getActiveSheet()->setCellValue('H1', '小计');
        $Excel->getActiveSheet()->setCellValue('I1', '支付状态');
        $Excel->getActiveSheet()->setCellValue('J1', '付款时间');

        $i = 2; 
        foreach($purchase_in_details as $purchase_in_detail){
            $Excel->getActiveSheet()->setCellValue('A' . $i, $purchase_in_detail['id']); 
            $Excel->getActiveSheet()->setCellValue('B' . $i, $purchase_in_detail['partner_name']); 
            $Excel->getActiveSheet()->setCellValue('C' . $i, $purchase_in_detail['purchase_code']); 
            $Excel->getActiveSheet()->setCellValue('D' . $i, $purchase_in_detail['stock_in_code']);
            $Excel->getActiveSheet()->setCellValue('E' . $i, $purchase_in_detail['pro_code']); 
            $Excel->getActiveSheet()->setCellValue('F' . $i, $purchase_in_detail['pro_qty']); 
            $Excel->getActiveSheet()->setCellValue('G' . $i, $purchase_in_detail['price_unit']); 
            $Excel->getActiveSheet()->setCellValue('H' . $i, $purchase_in_detail['price_subtotal']); 
            $Excel->getActiveSheet()->setCellValue('I' . $i, $this->filter['status'][$purchase_in_detail['status']]);
            $Excel->getActiveSheet()->setCellValue('J' . $i, $purchase_in_detail['updated_time']); 
            $i ++;
        }

        date_default_timezone_set("Asia/Shanghai");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download;charset=utf-8");
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

    //根据
    public function getPurchaseCodeMapByPartnerName($partner_name){
        //按照供应商查询
        if(!empty($partner_name)){
            //根据供应商名称查询对应的id
            $partner_map['name'] = array('like','%'.$partner_name.'%');
            $partner_info = M('partner')->where($partner_map)->field('id')->select();
            unset($partner_map);
            foreach($partner_info as $partner){
                $partner_ids[] = $partner['id'];
            }
            if(empty($partner_ids)){
                return false;
            }

            //根据供应商id 查询对应的采购单号
            $purchase_map['partner_id'] = array('in',$partner_ids);
            $purchase_info = M('stock_purchase')->where($purchase_map)->field('code')->select();
            unset($purchase_map);
            foreach($purchase_info as $purchase){
                $purchase_codes[] = $purchase['code'];
            }

            if(!empty($purchase_codes)){
                return $purchase_codes;
            }else{
                return false;
            }
        }
        return false;
    }

    public function printpage() {
        $id = I('get.id');

        $purchase = M('erp_purchase_in_detail');
        $field    = 'erp_purchase_in_detail.*, sum(erp_purchase_in_detail.pro_qty) as s_pro_qty, erp_purchase_in_detail.id as code,partner.name as partner_name, user.nickname as created_name, stock_purchase.invoice_method, stock_purchase.remark,  warehouse.name as wh_name';
        $where['erp_purchase_in_detail.id'] = array('in', $id );
        $join = array(
            'inner join stock_purchase on stock_purchase.code=erp_purchase_in_detail.purchase_code',
            'inner join partner on stock_purchase.partner_id=partner.id',
            'user on user.id = stock_purchase.created_user',
            'warehouse on warehouse.id = stock_purchase.wh_id'
        );
        $data = $purchase->field($field)->join($join)->where($where)->group('erp_purchase_in_detail.purchase_code, erp_purchase_in_detail.stock_in_code, erp_purchase_in_detail.pro_code')->select();
        $purchase_detail    = M('stock_bill_in_detail');

        foreach($data as $key => $value){
            $map = array();
            $map['refer_code']  = $value['stock_in_code'];
            $map['pro_code']    = $value['pro_code'];
            $list = $purchase_detail->where($map)->select();
            $result[$key]['id']              = $value['id'];
            $result[$key]['purchase_code']   = $value['purchase_code'];
            $result[$key]['purchase_time']   = $value['created_time'];
            $result[$key]['print_time']      = get_time();
            $result[$key]['partner']         = $value['partner_name'];
            $result[$key]['purchase_pay']    = $this->filter['invoice_method'][$value['invoice_method']];
            $result[$key]['purchase_qty']    = '1种' . '/' . $value['s_pro_qty'] . '件';
            $result[$key]['purchase_amount'] = $value['price_subtotal'];
            $result[$key]['purchaser']       = $value['created_name'];
            $result[$key]['warehouse']       = $value['wh_name'];
            $result[$key]['remark']          = $value['remark'];
            $result[$key]['purchase_detail'] = $list;
        }
        unset($data);

        layout(false);
        $this->assign('result',$result);
        $this->display('PurchaseInDetail:print');
    }
}
