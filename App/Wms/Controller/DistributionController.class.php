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
                'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), //查看按钮
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
        $tem = IS_AJAX ? 'Table:list' : 'index';
        $this->lists($tem);
    }
    
    public function after_search(&$map) {
        if (key_exists('stock_wave_distribution.order_id', $map)) {
            //订单ID搜索处理
            $order_id = $map['stock_wave_distribution.order_id'][1];
            $M = M('stock_wave_distribution_detail');
            if (!intval($order_id)) {
                $map['stock_wave_distribution.id'] = array('eq', null);
                unset($map['stock_wave_distribution.order_id']);
                return;
            }
            $where['bill_out_id'] = $order_id;
            $result = $M->field('pid')->where($where)->select();
            if (empty($result)) {
                $map['stock_wave_distribution.id'] = array('eq', null);
                unset($map['stock_wave_distribution.order_id']);
                return;
            } 
            $pids = array();
            foreach ($result as $value) {
                $pids[] = $value['pid'];
            }
            $map['stock_wave_distribution.id'] = array('in', $pids);
            unset($map['stock_wave_distribution.order_id']);
        }
    }
    
    /**
     * leibiao
     * @param unknown $data
     */
    public function after_lists(&$data) {
        $M = M('user');
        foreach ($data as &$value) {
            //格式化创建者昵称
            $map['id'] = $value['created_user'];
            $result = $M->field('nickname')->where($map)->find();
            $value['created_user'] = $result['nickname'];
        }
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
     * 配送单导出(三联单)
     */
    public function exportdis() {
        
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();
        
        $ary  =  array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        
        $get = I('get.id');
        if (empty($get)) {
            $this->msgReturn(false, '请选择配送单');
        }
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id($get);
        $order = D('Common/Order', 'Logic');
        $result = $order->getOrderInfoByOrderIdArr($order_ids);
        if (empty($result)) {
            $this->msgReturn(false, '获取订单失败');
        }
        $result = $result['list'];
        $data = array();
        foreach ($result as $value) {
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
            //筛选sku
            foreach ($order_ids as $sku_id => $order_id) {
                $sku_info = $D->get_out_detail_by_pids($sku_id);
                $sku_info = $sku_info['list'];
                foreach ($sku_info as $sku) {
                    $skucodearr[] = $sku['pro_code'];
                }
                foreach ($value as $kk => &$vv) {
                    foreach ($vv['detail'] as $key => $detail_info) {
                        if (!in_array($detail_info['sku_number'], $skucodearr)) {
                            unset($vv['detail'][$key]);
                        }
                    } 
                }
            }
            //创建数据
            $data[] = $D->format_export_data($value);
        }
        $i = 0;
        foreach ($data as $value){
           $sheet = $Excel->createSheet($i);
           $j = 0;
    	       foreach ($value as $val){
    	           $k = 0;
    	           foreach ($val as $v) {
    	               $sheet->setCellValue($ary[$k%27].($j+1), $v);
    	               $k ++;
    	           }
    	           $j ++;
    	       }
        	   $i++;
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
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.id');
        if (empty($get)) {
            $this->msgReturn(false, '参数有误');
        }
        
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();
        
        $ary  =  array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $ids = explode(',', $get);
        $D = D('Distribution', 'Logic');
        $data = $D->format_distribution($ids);
        
        $i = 0;
        foreach ($data['xls_list'] as $value){
           $sheet = $Excel->createSheet($i);
           $j = 0;
    	       foreach ($value as $val){
    	           $k = 0;
    	           foreach ($val as $v) {
    	               $sheet->setCellValue($ary[$k%27].($j+1), $v);
    	               $k ++;
    	           }
    	           $j ++;
    	       }
        	   $i++;
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
        $Order = D('Common/Order', 'Logic');
        $result = $Order->getOrderInfoByOrderIdArr($order_ids);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
        $result = $result['list'];
        //替换sku
        $result = $D->replace_sku_info($result, $get);
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
        $Order = D('Common/Order', 'Logic');
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
            //筛选sku
            foreach ($out_ids as $sku_id => $order_id) {
                $sku_info = $D->get_out_detail_by_pids($sku_id);
                $sku_info = $sku_info['list'];
                foreach ($sku_info as $sku) {
                    $skucodearr[] = $sku['pro_code'];
                }
                foreach ($result as $kk => &$vv) {
                    foreach ($vv['detail'] as $key => $detail_info) {
                        if (!in_array($detail_info['sku_number'], $skucodearr)) {
                            unset($vv['detail'][$key]);
                        }
                    } 
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
            $items['barcode'] = 'http://api.pda.dachuwang.com/barcode/get?text=' . $dis['dist_code']; //条码
            
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
        $confirm = I('post.confirm'); //是否是确认操作
        if (empty($post)) {
            $this->msgReturn(false, '请输入配送单号');
        }
        $M = M('stock_wave_distribution');
        $det = M('stock_wave_distribution_detail');
        $stock = M('stock_bill_out');
        $D = D('Distribution', 'Logic');
        $stockOut = D('Stock', 'Logic');
        //获取配送单
        $map['dist_code'] = $post;
        $result = $M->where($map)->find();
        if (empty($result)) {
            $this->msgReturn(false, '配送单不存在');
        }
        if ($result['status'] == 2) {
            $this->msgReturn(false, '配送单已经完成');
        }
        unset($map);
        //获取配送单详情
        $map['pid'] = $result['id'];
        $map['is_deleted'] = 0;
        $order_ids = $det->where($map)->select();
        if (empty($order_ids)) {
            $this->msgReturn(false, '空配送单');
        }
        unset($map);
        //获取所有出库单 IDS
        $bill_out_idarr = array();
        foreach ($order_ids as $value) {
            $bill_out_idarr[] = $value['bill_out_id'];
        }
        //判断出库单状态是否为分拣完成
        $map['id'] = array('in', $bill_out_idarr);
        $map['is_deleted'] = 0;
        $bill_out_status = $stock->where($map)->select();
        unset($map);
        if (empty($bill_out_status)) {
            $this->msgReturn(false, '不存在的出库单');
        }
        $unpass_ids = ''; //未分拣出库单号
        $pass_ids = array();  //分拣出库单id
        $unpass_code = array();
        foreach ($bill_out_status as $key => $val) {
            if ($val['status'] != 5) { //状态5 待复核 分拣完成
                $unpass_ids .= $val['id'] . ',';
                $unpass_code[] = $val['id'];
            } else {
                $pass_ids[] = $val['id'];
            }
        }
        if (empty($pass_ids)) {
            $this->msgReturn(false, '没有待复核的出库单');
        }
        $unpass_ids = rtrim($unpass_ids, ',');
        if (!empty($unpass_ids)) {
            //弹出没有分拣的出库单
            if (!empty($confirm)) {
                //继续操作
                //删除此配送单下的这些出库单
                $map['pid'] = $result['id'];
                $data['is_deleted'] = 1; //已删除
                if ($det->create($data)) {
                    $det->where($map)->save();
                }
                unset($map);    
                unset($data);

                //驳回不符合条件的订单
                $map['id'] = array('in', $unpass_code);
                $data['dis_mark'] = 0; //未加入出库单
                if ($stock->create($data)) {
                    $stock->where($map)->save();
                }
                unset($map);
                unset($data);
            } else {
                $unpass_ids .= ',' . $post;
                $this->msgReturn(true, '请确认', '', U('unpass?ids=' . $unpass_ids));
            }
        }
        //统计SKU数量扣减库存
        //获取出库详情
        $sku_detail = $D->get_out_detail($pass_ids);
        //$sku_detail = $sku_detail['list'];

        //统计
        $merg = array(); //sku统计结果
        foreach ($sku_detail as $v) {
            if (!isset($merg[$v['pro_code']])) {
                $merg[$v['pro_code']] = $v;
            } else {
                $merg['order_qty'] += $v['order_qty']; 
            }
        }
        //获取去拣货区库位
        $loc = M('location');
        $map['code'] = 'PACK';
        $map['wh_id'] = session('user.wh_id');
        $location = $loc->field('id')->where($map)->find();
        unset($map);
        $map['pid'] = $location['id'];
        $location_id = $loc->field('id')->where($map)->find();
        if (empty($location_id)) {
            $this->msgReturn(false, '还没创建分拣区库位');
        }
        unset($map);
        //扣减库存
        foreach ($merg as $sku) {
            $stockOut->outStockBySkuFIFO(array('wh_id'=>session('user.wh_id'), 'pro_code'=>$sku['pro_code'], 'pro_qty'=>$sku['order_qty'], 'refer_code'=>$post, 'location_ids'=>$location_id['id']));
        }

        $map['dist_code'] = $post;
        $data['status'] = 2; //已发运
        //更新配送状态为已完成
        if ($M->create($data)) {
            $M->where($map)->save();
        }
        unset($map);
        unset($data);
        //更新配送详情状态为已完成
        $map['pid'] = $result['id'];
        $map['is_deleted'] = 0;
        $data['status'] = 1; //已完成
        if ($det->create($data)) {
            $det->where($map)->save();
        }
        unset($map);
        unset($data);
        //更新出库单状态
        $map['id'] = array('in', $pass_ids);
        $data['status'] = 2; //已出库
        $data['dis_mark'] = 1; //已分拨
        if ($stock->create($data)) {
            $stock->where($map)->save();
        }
        unset($map);

        //通知实时库存接口 需要遍历出库单详情
        $synch_hop_bill_out_ids = array();
        $map['id'] = array('in', $pass_ids);
        $bill_out_infos = M('stock_bill_out')->where($map)->select();
        foreach($bill_out_infos as $bill_out_info){
            if(is_numeric($bill_out_info['refer_code']) && $bill_out_info['refer_code'] > 0){
                $synch_hop_bill_out_ids[] = $bill_out_info['id'];
                //通知hop更改订单状态
                $order_map['suborder_id'] = $bill_out_info['refer_code'];
                $order_map['status'] = '5';
                $order_map['cur']['name'] = session('user.username');
                A('Common/Order','Logic')->set_status($order_map);
                unset($order_map);
            }
        }
        unset($map);

        $map['pid'] = array('in', $synch_hop_bill_out_ids);
        $bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();
        foreach($bill_out_detail_infos as $bill_out_detail_info){
            $notice_params['wh_id'] = session('user.wh_id');
            $notice_params['pro_code'] = $bill_out_detail_info['pro_code'];
            $notice_params['type'] = 'outgoing';
            $notice_params['qty'] = $bill_out_detail_info['order_qty'];
            A('Dachuwang','Logic')->notice_stock_update($notice_params);
            unset($notice_params);
        }
        unset($map);

        $this->msgReturn(true, '已完成', '', '', U('over'));
    }
    
    
    public function unpass() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.ids');
        $get = explode(',', $get);
        if (empty($get)) {
            $this->msgReturn(false, '参数有误');
        }
        $data['dist_code'] = array_pop($get);
        //获取出库单号码
        $stock = M('stock_bill_out');
        $map['id'] = array('in', $get);
        $res = $stock->where($map)->select();
        foreach ($res as $value) {
            $data['out_code'][] = $value['code'];
        }
        $data['count'] = count($res);
        $this->assign('data', $data);
        $this->display();
    }
    
    /**
     * 删除配送单
     */
    public function delete_dist() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $ids = I('get.id');
        $idarr = explode(',', $ids);
        if (count($idarr) >= 2 || count($idarr) <= 0) {
            $this->msgReturn(false, '请选择一个配送单');
        }
        //配送单id
        	$id = array_shift($idarr);
        
        	$M = M('stock_wave_distribution');
        	$detail = M('stock_wave_distribution_detail');
        	$stock = M('stock_bill_out');
        	
        //判断是否发运
        	$map['id'] = $id;
        	$result = $M->where($map)->find();
        	if ($result['status'] != 1) {
        	    $this->msgReturn(false, '不能删除已经发运的配送单');
        	}
        	//删除出库单
        	$data['is_deleted'] = 1;
        	if ($M->create($data)) {
        	    $M->where($map)->save();
        	}
        unset($map);
        unset($data);
        	//删除出库单详情
        	$map['pid'] = $id;
        	$data['is_deleted'] = 1;
        	if ($detail->create($data)) {
        	    $detail->where($map)->save();
        	}
        	//获取出库单ID
        	$res = $detail->where($map)->select();
        	$bill_out_id = array();
        	foreach ($res as $value) {
        	    $bill_out_id[] = $value['bill_out_id'];
        	}
        	unset($map);
        	unset($data);
        	//将出库单驳回
        	$map['id'] = array('in', $bill_out_id);
        	$data['dis_mark'] = 0;
        	if ($stock->create($data)) {
        	    $stock->where($map)->save();
        	}
        	$this->msgReturn(true, '已删除', '', U('index'));
    }
}