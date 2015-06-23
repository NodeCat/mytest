<?php
namespace Wms\Controller;
use Think\Controller;
class DistributionController extends CommonController {
    
    protected $filter = array(
    	    'status' => array(
    	        '1' => '未发运',
    	        '2' => '已发运',
        )
    );
    protected $columns = array (
            'dist_code' => '配送单号',
            'total_price' => '应收总金额',
            'order_count' => '总单数',
            'line_count' => '总行数',
            'sku_count' => '总件数',
            'created_user' => '创建者',
            'created_time' => '创建时间',
            'status' => '状态',
    );
    protected $query   = array (
            'stock_wave_distribution.company_id' => array(
                    'title' => '所属系统',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'Company.id,name',
            ),
            'stock_wave_distribution.status' => array(
                    'title' => '状态',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
                        '1' => '未发运',
                        '2' => '已发运',
                    ),
            ),
            'stock_wave_distribution.order_id' => array(
                    'title' => '订单ID',
                    'query_type' => 'eq',
                    'control_type' => 'text',
                    'value' => '',
            ),
            'stock_wave_distribution.wh_id' => array(
                    'title' => '所属仓库',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'warehouse.id,name',
            ),
            'stock_wave_distribution.created_time' => array(
                    'title' => '创建时间',
                    'query_type' => 'between',
                    'control_type' => 'datetime',
                    'value' => 'created_time',
            ),
    );
    
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
                'view'=>array('name'=>'view', 'show' => true/*isset($this->auth['view'])*/,'new'=>'true'), //查看按钮
                'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true'), //编辑按钮
        );
        $this->status =array(
                array(
                        array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
                        array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
                ),
        );
        /*$this->toolbar = array(
        	         array('name'=>'add', 'show' => trueisset($auth['view']),'new'=>'true', 'link' => 'DistDetail/index'),
        );*/
    }
    
    public function index() {
        $this->before_index();
        $this->lists('index');
    }

    
    /**
     * 生成配送单
     */
    public function add() {
        if (!IS_POST) {
            $this->msgReturn(false, '未知错误');
        }
        $post = I('post.');
        if (empty($post)) {
            $this->msgReturn(false, '参数错误');
        }
        
        $D = D('Distribution', 'Logic');
        $result = $D->add_distributioin($post);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
       
        $this->msgReturn(true, '已创建配送单', '', '', U('index'));
    }
    
    /**
     * 配送单导出
     */
    public function exportdis() {
        
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();
        $i = 1;
        if(empty($this->columns)) {
            $table = get_tablename(CONTROLLER_NAME);
            $res = get_setting($table);
            $columns = $res['list'];
        }
        else {
            $columns = $this->columns;
        }
        
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $Sheet = $this->get_excel_sheet($Excel);
        foreach ($columns as $key  => $value) {
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }
        
        $get = I('get.id');
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id($get);
        $order = D('Order', 'Logic');
        $result = $order->getOrderInfoByOrderIdArr($order_ids);
        if (empty($result)) {
            $this->msgReturn(false, '获取订单失败');
        }
        $result = $result['list'];
        $data = array();
        foreach ($result as &$value) {
            switch ($value['pay_type']) {
            	   case 0:
            	       $value['pay_type'] = '货到付款';
            	       break;
            	   case 1:
            	       $value['pay_type'] = '微信支付';
            	       break;
            }
            switch ($value['pay_status']) {
            	   case -1:
            	       $value['pay_status'] = '支付失败';
            	       break;
            	   case 0:
            	       $value['pay_status'] = '未支付';
            	       break;
            	   case 1:
            	       $value['pay_status'] = '已支付';
            	       break;
            }
            //创建数据
            $data[] = $D->format_export_data($value);
        }
        $i = 0;
        foreach ($data as $v){
        	   
        	   for($j  = 0;$j<count($data) ; ++$j){
        	       $sheet = $Excel->createSheet($j);
        	       dump($sheet);exit;
        	       foreach ($data[$j] as $vv){
        	           $sheet->setCellValue($ary[$i/27].$ary[$i%27].($j+2), $data[$j]);
        	       }
        	   }
        	   $i++;
        }
        exit('tyyyyy');
        
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
    
    /**
     * 导出配送单
     */
    public function export_distribution() {
        foreach ($dist_list as $dist) {
            $dist_arr = [];
            $dist_arr[] = array('配送线路单号:' . $dist['dist_number'], '', '', '', '', '', '仓库:' . $dist['warehouse_name'], '', '', '', '', '', '', '');
            $dist_arr[] = array('线路（片区）:' . $dist['line_name'], '', '', '', '', '', '发车时间:' . $dist['deliver_date'] . ($dist['deliver_time'] == 1 ? '上午' : '下午'), '', '', '', '', '', '', '');
            $dist_arr[] = array('订单数:' . count($dist['orders']), '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('订单明细', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = $title_arr;
            foreach ($dist['orders'] as $order) {
                foreach ($order['detail'] as $detail) {
                    $specs = '';
                    foreach ($detail['spec'] as $spec) {
                        if($spec['name'] != '描述') {
                            $specs .= $spec['name'] . ':' . $spec['val'];
                        }
                    }
                    $dist_arr[] = array(
                            $order['id'],
                            $order['order_number'],
                            $order['shop_name'],
                            $order['deliver_addr'],
                            $order['mobile'],
                            $order['remarks'],
                            $detail['sku_number'],
                            $detail['name'],
                            $specs,
                            $detail['single_price'],
                            $detail['close_unit'],
                            $detail['quantity'],
                            $detail['unit_id'],
                            '',
                    );
                }
        
            }
        
            $dist_arr[] = array('汇总', '', '', '', '', '', '', '', '', '', '', $dist['sku_count'], '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('货品汇总', '', '', '', '', '', '', '', '', '', '', '', '', '');
            $dist_arr[] = array('货品号', '产品名称', '订货数量', '', '', '', '', '', '', '', '', '', '', '');
            foreach ($dist['sku_list'] as $sku) {
                $dist_arr[] = array(
                        $sku['sku_number'],
                        $sku['name'],
                        $sku['quantity'],
                );
            }
            $dist_arr[] = array('汇总', '', $dist['sku_count'], '', '', '', '', '', '', '', '', '', '', '');
        
            $xls_list[] = $dist_arr;
            $sheet_titles[] = $dist['dist_number'];
        }
    }
    /**
     * 配送单详情
     */
    public function view() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.id');
        if (empty($get)) {
            $this->msgReturn(false, '参数有误');
        }
        
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单id
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id($get);
        
        //拉取订单
        $Order = D('Order', 'Logic');
        $result = $Order->getOrderInfoByOrderIdArr($order_ids);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
        $result = $result['list'];
        $data = array();
        $data['dist_code'] = $dis['dist_code']; //编号
        $data['is_printed'] = $dis['is_printed']; //是否打印
        //创建者
        $user = M('user');
        $map['id'] = $dis['created_user'];
        $user_info = $user->field('nickname')->where($map)->find();
        $data['creator'] = $user_info['nickname'];
        $data['created_time'] = $dis['created_time']; //创建时间
        $data['order_count'] = $dis['order_count']; //总单数
        $data['sku_count'] = $dis['sku_count']; //sku总数
        $data['total_price'] = $dis['total_price']; //总价格
        $data['line_count'] = $dis['line_count']; //总行数
        $this->assign('data', $data);
        $this->assign('orderList', $result);
        $this->display();
    }
    
    /**
     * 配送单打印
     */
    public function printpage() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $ids = I('get.id');
        $idarr = explode(',', $ids);
        if (empty($idarr)) {
            $this->msgReturn(false, '参数有误');
        }
        
        $M = M('stock_wave_distribution');
        //$detail = M('stock_wave_distribution_detail');
        $ware = M('warehouse');
        $D = D('Distribution', 'Logic');
        //获取所有配送单下的订单id
        $order_ids = $D->get_order_ids_by_dis_id($idarr);
        //拉取订单
        $Order = D('Order', 'Logic');
        $res = $Order->getOrderInfoByOrderIdArr($order_ids);
        if ($res['status'] == false) {
            $this->msgReturn(false, $res['msg']);
        }
        $res = $res['list'];
        $list = array();
        foreach ($idarr as $get) {
            $map['id'] = $get;
            $dis = $M->where($map)->find();
            //获取此配送单下的订单ID
            $out_ids = $D->get_order_ids_by_dis_id(array($get));
            unset($map);
            //筛选此配送单下的订单
            $result = array();
            foreach ($res as $key => $value) {
                //获取此配送单下的订单
                if (in_array($value['id'], $out_ids)) {
                    $result[] = $value;
                    unset($res[$key]);
                }
            }
            $items = array();
            $items['dist_code'] = $dis['dist_code'];
            $items['line_name'] = $D->format_line($dis['line_id']);
            $user_ids = array();
            foreach ($result as $value) {
                $user_ids[$value['user_id']] = null;
            }
            $items['user_count'] = count($user_ids); //总客户数量
            $items['orders_length'] = $dis['order_count'];  //总订单数
            $items['sku_count'] = $dis['sku_count']; //sku总数
            //获取仓库名称
            
            $map['id'] = $dis['wh_id'];
            $ware_info = $ware->field('name')->where($map)->find();
            $items['warehouse_name'] = $ware_info['name'];
            $items['deliver_date'] = $dis['deliver_date']; //发车时间
            $items['deliver_time'] = $dis['deliver_time']; //时段
            $items['orders'] = $result; //订单列表
            $items['barcode'] = 'http://api.pda.dachuwang.com/barcode/get?text=PD1506080001'; //条码
            
            $merge = array();
            foreach ($result as $val) {
                $merge = array_merge($merge, $val['detail']);
            }
            foreach ($merge as $key=>$v) {
                if (!isset($merge[$v['product_id']])) {
                    $merge[$v['product_id']] = $v;
                } else {
                    $merge[$v['product_id']]['quantity'] += $v['quantity'];
                }
                unset($merge[$key]);
            }
            $items['sku_list'] = $merge;
            unset($map);
            //更新打印状态
            if ($dis['is_printed'] <= 0) {
                $data['is_printed'] = 1;
                $map['id'] = $get;
                if ($M->create($data)) {
                    $M->where($map)->save();
                }
            }
            $list[] = $items;
        }
        $this->assign('list', $list);
        
        $this->display();
    }
    
    /**
     * 线路订单量统计
     */
    public function line_order_num() {
        $D = D('Distribution', 'Logic');
        $list = $D->get_all_orders();
        if ($list['status'] == false || empty($list['list'])) {
            echo 0;
        } else {
            echo json_encode($list['list'], true);
        }
    }
    
    /**
     * pda端送货完成
     */
    public function over() {
        if (!IS_POST) {
            $this->title = '请输入配送单号';
            $this->display();
            return;
        }
        $post = I('post.dist_code');
        if (empty($post)) {
            $this->msgReturn(false, '请输入配送单号');
        }
        $M = M('stock_wave_distribution');
        $det = M('stock_wave_distribution_detail');
        $stock = M('stock_bill_out');
        $map['dist_code'] = $post;
        $result = $M->where($map)->find();
        if (empty($result)) {
            $this->msgReturn(false, '配送单不存在');
        }
        if ($result['status'] == 2) {
            $this->msgReturn(false, '配送单已经完成');
        }
        unset($map);
        $map['pid'] = $result['id'];
        $order_ids = $det->where($map)->select();
        if (empty($order_ids)) {
            $this->msgReturn(false, '空配送单');
        }
        unset($map);
        
        $map['dist_code'] = $post;
        $data['status'] = 2; //已完成
        //更新状态为已完成
        if ($M->create($data)) {
            $M->where($map)->save();
        }
        unset($map);
        unset($data);
        //更新订单状态
        $order_idarr = array();
        foreach ($order_ids as $value) {
            $order_idarr[] = $value['bill_out_id'];
        }
        $map['refer_code'] = array('in', $order_idarr);
        $data['status'] = 7; //已完成
        if ($stock->create($data)) {
            $stock->where($map)->save();
        }
        $this->msgReturn(true, '已完成', '', '', U('over'));
    }
}