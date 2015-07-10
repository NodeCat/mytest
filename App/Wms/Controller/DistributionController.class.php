<?php
namespace Wms\Controller;
use Think\Controller;
use Boris\ExportInspector;
class DistributionController extends CommonController {
    
    protected $filter = array(
    	    'status' => array(
    	        '1' => '未发运',
    	        '2' => '已发运',
        )
    );
    protected $columns = array (
            'dist_code' => '配送单号',
            'line_name' => '线路',
            'total_price' => '应收总金额',
            'order_count' => '总单数',
            'line_count' => '总行数',
            'sku_count' => '总件数',
            'created_user' => '创建者',
            'created_time' => '创建时间',
            'status' => '状态',
    );
    protected $query   = array (
            'stock_wave_distribution.order_type' => array(
                    'title' => '订单类型',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
            	            '1' => '普通订单',
                        '2' => '冻品订单',
                        '3' => '水果爆款订单',
                        '4' => '水果订单',
                    ),
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
        $M = M('stock_wave_distribution_detail');
        if (key_exists('stock_wave_distribution.order_id', $map)) {
            //订单ID搜索处理
            $order_id = $map['stock_wave_distribution.order_id'][1];
            if (!intval($order_id)) {
                $map['stock_wave_distribution.id'] = array('eq', null);
                unset($map['stock_wave_distribution.order_id']);
                return;
            }
            $stock_bill_out = M('stock_bill_out');
            $where['refer_code'] = $order_id;
            $bill_out_ids = $stock_bill_out->where($where)->select();
            $bill_out_id = array();
            foreach ($bill_out_ids as $value) {
                $bill_out_id[] = $value['id'];
            }
            unset($where);
            $where['bill_out_id'] = array('in', $bill_out_id);
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
        if (key_exists('stock_wave_distribution.order_type', $map)) {
            //订单类型搜索处理
            $order_type = $map['stock_wave_distribution.order_type'][1];
            $stock_detail = M('stock_bill_out');
            unset($where);
            $where['order_type'] = $order_type;
            $res = $stock_detail->where($where)->select();
            $pids = array();
            if (empty($res)) {
                $map['stock_wave_distribution.id'] = array('eq', null);
                unset($map['stock_wave_distribution.order_type']);
            }
            foreach ($res as $val) {
                $pids[] = $val['id'];
            }
            unset($where);
            $where['bill_out_id'] = array('in', $pids);
            $dist = $M->where($where)->select();
            if (empty($dist)) {
                $map['stock_wave_distribution.id'] = array('eq', null);
                unset($map['stock_wave_distribution.order_type']);
            }
            $dist_ids = array();
            foreach ($dist as $dist_id) {
                $dist_ids[] = $dist_id['pid'];
            }
            $dist_ids = array_unique($dist_ids);
            $map['stock_wave_distribution.id'] = array('in', $dist_ids);
            unset($map['stock_wave_distribution.order_type']);
        }
    }
    
    /**
     * 列表处理
     */
    public function after_lists(&$data) {
        $M = M('user');
        $D = D('Distribution', 'Logic');
        foreach ($data as &$value) {
            
            //格式化创建者昵称
            $map['id'] = $value['created_user'];
            $result = $M->field('nickname')->where($map)->find();
            $value['created_user'] = $result['nickname'];
            //组合线路片区
            $line_id = array();
            $line_id = explode(',', $value['line_id']);
            $line_id = array_unique($line_id);
            foreach ($line_id as $val) {
                $value['line_name'] .= $D->format_line($val) . '/';
            }
            $value['line_name'] = rtrim($value['line_name'], '/');
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
        if (count(explode(',', $get)) > 1) {
            $this->msgReturn(false, '只能选择一个配送单');
        }
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id(array($get));
        $order = D('Common/Order', 'Logic');
        $result = $order->getOrderInfoByOrderIdArr($order_ids);
        if (empty($result)) {
            $this->msgReturn(false, '获取订单失败');
        }
        $result = $result['list'];
        $data = array();
        $ids = array();
        foreach ($result as $key => $value) {
            switch ($value['pay_type']) {
            	    case 0:
            	        $value['pay_type_cn'] = '货到付款';
            	        break;
            	    case 1:
            	        $value['pay_status_cn'] = '微信支付';
            	        break;
            }
            switch ($value['pay_status']) {
            	    case -1:
            	        $value['pay_status_cn'] = '支付失败';
            	        break;
            	    case 0:
            	        $value['pay_status_cn'] = '未支付';
            	        break;
            	    case 1:
            	        $value['pay_status_cn'] = '支付成功';
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
            //execl头
            $ids[$key] = $value['id'];
            //创建数据
            $data[$key] = $D->format_export_data($value);
        }
        $i = 0;
        foreach ($data as $k => $value){
           $sheet = $Excel->createSheet($i);
           foreach ($ids as $index => $id) {
               if ($k == $index) {
                   $sheet->setTitle($id);
               }
           }
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
        header("Content-Disposition:attachment;filename = " . $dis['dist_code'] . ".xlsx");
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
        $order_ids = $D->get_order_ids_by_dis_id(array($get));
        //拉取订单
        $Order = D('Common/Order', 'Logic');
        $result = $Order->getOrderInfoByOrderIdArr($order_ids);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
        $result = $result['list'];
        //替换sku
        $result = $D->replace_sku_info($result, $get);
        $total_price = 0;
        foreach($result as $val){
            $total_price += $val['total_price'];
        }
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
        $data['total_price'] = $total_price; //总价格
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
            $stock_bill_out = M('stock_bill_out');
            $stock_out_code = array();
            foreach ($out_ids as $sku_id => $order_id) {
                //获取出库单号
                $map['id'] = $sku_id;
                $stock_out_code[] = $stock_bill_out->where($map)->find();
                $sku_info = $D->get_out_detail_by_pids($sku_id);
                $sku_info = $sku_info['list'];
                foreach ($sku_info as $sku) {
                    $skucodearr[] = $sku['pro_code'];
                }
            }
            foreach ($result as $kk => &$vv) {
                //获取出库单号
                foreach ($stock_out_code as $stock_bill_out_code) {
                    if ($stock_bill_out_code['refer_code'] == $vv['id']) {
                        $vv['stock_bill_out_code'] = $stock_bill_out_code['code'];
                    }
                }
                foreach ($vv['detail'] as $key => $detail_info) {
                    if (!in_array($detail_info['sku_number'], $skucodearr)) {
                        //去除不在出库单中的sku 
                        unset($vv['detail'][$key]);
                    }
                }
            }
            $items = array();
            $items['dist_code'] = $dis['dist_code'];
            $items['id'] = $dis['id'];
            //组合线路片区
            $dis['line_id'] = explode(',', $dis['line_id']);
            $dis['line_id'] = array_unique($dis['line_id']);
            foreach ($dis['line_id'] as $line_id) {
                $items['line_name'] .= $D->format_line($line_id) . '/';
            }
            $items['line_name'] = rtrim($items['line_name'], '/');
            
            $user_ids = array();
            $map_pos = array();
            foreach ($result as $value) {
                $user_ids[$value['user_id']] = null;
                //获取地图坐标
                $map_pos[] = json_decode($value['geo'], true);
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
            $items['map_pos'] = $map_pos;
            $items['barcode'] = C('BARCODE_PATH') . $dis['dist_code']; //条码
            $merge = array();
            foreach ($result as $val) {
                $merge = array_merge($merge, $val['detail']);
                $items['price_amount'] += $val['total_price'];
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
            unset($items['price_amount']);
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
        $stock_detail = M('stock_bill_out_detail');
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
        $wavein_ids = array(); //波次中的出库单ID
        $pick_ids = array(); //带分拣的出库单ID
        $make_ids = array(); //带生产的出库单ID
        $pass_ids = array();  //分拣出库单ID
        $reduce_ids = array(); //库存不足的出库单ID
        foreach ($bill_out_status as $key => $val) {
            if ($val['status'] == 3) { //状态3 波此中 
                $wavein_ids[] = $val['id'];
            } elseif ($val['status'] == 4) {
                //4待分拣
                $pick_ids[] = $val['id'];
            } elseif ($val['status'] == 1) {
                //待生产
                $make_ids[] = $val['id'];
            } elseif ($val['status'] == 5) {
                //分拣完成
                //判断分拣完成的库存够不够  防止发生损毁
                $result_detail = $D->checkout_stock_eg($val['id']);
                if ($result_detail) {
                    //库存充足
                    $pass_ids[] = $val['id'];
                } else {
                    //库存不足
                    $reduce_ids[] = $val['id'];
                }
            }
        }
        if (empty($pass_ids)) {
            $this->msgReturn(false, '没有待复核的出库单');
        }
        if (!empty($wavein_ids) || !empty($pick_ids)) {
            //波次中或带分拣出库单
            $unpass_ids = implode(',', $wavein_ids) . '|' . implode(',', $pick_ids);
            $this->msgReturn(true, '请确认', '', U('unpass?ids=' . $unpass_ids . '&type=wave'));
        } elseif (!empty($make_ids) || !empty($reduce_ids)) {
            //弹出没有分拣的出库单
            if (!empty($confirm)) {
                //继续操作
                //删除此配送单下的这些出库单
                $merge = array_merge($make_ids, $reduce_ids);
                $map['bill_out_id'] = array('in', $merge);
                $map['pid'] = $result['id'];
                $data['is_deleted'] = 1; //已删除
                if ($det->create($data)) {
                    $det->where($map)->save();
                }
                unset($map);
                unset($data);
            
                //驳回不符合条件的订单
                $map['id'] = array('in', $merge);
                $data['dis_mark'] = 0; //未加入出库单
                if ($stock->create($data)) {
                    $stock->where($map)->save();
                }
                unset($map);
                unset($data);
                //更新配送单中总件数 总条数 总行数 总金额
                $sur_detail = $D->get_out_detail($pass_ids); //通过审核的sku详情
                $total['order_count'] = count($pass_ids); //总单数
                $total['sku_count'] = 0; //总件数
                $total['line_count'] = 0; //总行数
                $total['total_price'] = 0; //总金额
                $det_merge = array();
                foreach ($sur_detail as $sur) {
                    $total['sku_count'] += $sur['order_qty'];
                    $total['total_price'] += $sur['order_qty'] * $sur['price'];
                    $det_merge[$sur['pro_code']] = null;
                }
                $total['line_count'] = count($det_merge);
                if ($M->create($total)) {
                    //更新操作
                    $map['id'] = $result['id'];
                    $M->where($map)->save();
                }
            } else {
                $unpass_ids .= implode(',', $make_ids) . '|' . implode(',', $reduce_ids) . '|' . $post;
                $this->msgReturn(true, '请确认', '', U('unpass?ids=' . $unpass_ids . '&type=make'));
            }
        }
        //统计SKU数量扣减库存
        //获取出库详情
        $sku_detail = $D->get_out_detail($pass_ids);

        //统计
        $merg = array(); //sku统计结果
        foreach ($sku_detail as $v) {
            if (!isset($merg[$v['pro_code']])) {
                $merg[$v['pro_code']] = $v;
            } else {
                $merg[$v['pro_code']]['order_qty'] += $v['order_qty']; 
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
            $stockOut->outStockBySkuFIFO(array('wh_id'=>session('user.wh_id'), 
                                               'pro_code'=>$sku['pro_code'], 
                                               'pro_qty'=>$sku['order_qty'], 
                                               'refer_code'=>$post, 
                                               'location_ids'=>array($location_id['id'])));
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
        //更新出库单状态 AND 出库单出库量
        $map['id'] = array('in', $pass_ids);
        $data['status'] = 2; //已出库
        $data['dis_mark'] = 1; //已分拨
        if ($stock->create($data)) {
            $stock->where($map)->save();
        }
        //更新发货量
        $pass_ids_string = implode(',', $pass_ids);
        $sql = "UPDATE stock_bill_out_detail stock SET stock.delivery_qty = stock.order_qty WHERE pid IN (" . $pass_ids_string . ")";
        M()->execute($sql);
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

        $this->msgReturn(true, '已完成', '', U('over'));
    }
    
    /**
     * 发运异常显示
     */
    public function unpass() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.ids');
        $type = I('get.type');
        if ($type == 'make') {
            $get = explode('|', $get);
            $ids = array();
            $data = array();
            $dist_code = array_pop($get);
            foreach ($get as $value) {
                $ids[] = explode(',', $value);
            }
            //获取出库单号码
            $stock = M('stock_bill_out');
            $map['id'] = array('in', $get);
            $i = 0;
            foreach ($ids as $key => $val) {
                $map['id'] = array('in', $val);
                $res = $stock->where($map)->select();
                foreach ($res as $value) {
                    $data[$key]['out_code'][] = $value['code'];
                }
                $data[$key]['count'] = count($res);
                if ($i <= 0) {
                    $data[$key]['type'] = 'make';
                } else {
                    $data[$key]['type'] = 'reduce';
                }
                $i++;
            }
            $confirm = 'yes'; //是否显示确认按钮 否
            $this->assign('dist_code', $dist_code);
        } else {
            $get = explode('|', $get);
            $ids = array();
            foreach ($get as $value) {
                $ids[] = explode(',', $value);
            }
            $stock = M('stock_bill_out');
            $data = array();
            $i = 0;
            foreach ($ids as $key => $val) {
                $map['id'] = array('in', $val);
                $res = $stock->where($map)->select();
                foreach ($res as $value) {
                    $data[$key]['out_code'][] = $value['code'];
                }
                $data['count'] = count($res);
                if (i <= 0) {
                    $data[$key]['type'] = 'wave';
                } else {
                    $data[$key]['type'] = 'pick';
                }
                $i++;
            }
            $confirm = 'not'; //是否显示确认按钮 否
        }
        $this->assign('confirm', $confirm);
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
    
    /**
     * 创建配送单波此
     */
    public function create_wave() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.id');
        $idarr = explode(',', $get);
        if (empty($idarr)) {
            $this->msgReturn(false, '请选择一个配送单');
        }
        //配送单ID
        $M = M('stock_wave_distribution');
        $det = M('stock_wave_distribution_detail');
        $wave_det = M('stock_wave_detail');
        $stock_out = M('stock_bill_out');
        $stock_out_detail = M('stock_bill_out_detail');
        $stockout_logic = D('StockOut', 'Logic');
        //获取配送单信息
        $map['id'] = array('in', $idarr);
        $map['is_deleted'] = 0;
        $res = $M->where($map)->select();
        //是否发运
        if (empty($res)) {
            $this->msgReturn(false, '不存在的配送单');
        }
        unset($map);
        foreach ($res as $val) {
            if ($val['status'] == 2) {
                $this->msgReturn(false, '请选择未发运的配送单');
            }
        }
        //获取详情
        $map['pid'] = array('in', $idarr);
        $map['is_deleted'] = 0;
        $detail = $det->where($map)->select();
        if (empty($detail)) {
            $this->msgReturn(false, '空的配送单');
        }
        unset($map);
        //是否已加入波此
        $D = D('Distribution', 'Logic');
        $bill_out_id = array(); //出库单id
        foreach ($detail as $value) {
            $map['bill_out_id'] = $value['bill_out_id'];
            $map['is_deleted'] = 0;
            $result = $wave_det->where($map)->find();
            if (empty($result)) {
                //只加入带生产状态的出库单（没有加入波次）
                $bill_out_id[] = $value['bill_out_id'];
            }
        }
        if (empty($bill_out_id)) {
            $this->msgReturn(false, '次配送单下所有出库单都已加入波次');
        }
        //订单库存是否充足
        //查找你选择的出库单无缺货出库单数据id
        $idsArr = $stockout_logic->enoughaResult(implode(',', $bill_out_id));
        $ids = $idsArr['tureResult'];
        $unids = $idsArr['falseResult'];
        if(!$ids){
            $this->msgReturn(false, '库存不足，无法创建波次');
        }
        $ids = explode(',', $ids);
        $count = count($bill_out_id) - count($ids); //库存不足的订单数量
        if ($count > 0) {
            //弹出确认框
            $confirm = I('get.confirm');
            //确认之后将继续向下执行
            if (empty($confirm)) {
                unset($map);
                //获取被驳回的出库单号
                $bill_out_code = '';
                if (!empty($unids)) {
                    $map['id'] = array('in', $unids);
                    $bill_out_codes = $stock_out->where($map)->select();
                    unset($map);
                    foreach ($bill_out_codes as $val) {
                        $bill_out_code .= $val['code'] . ',';
                    }
                }
                $msg['pup_count'] = $count;
                $msg['order_count'] = count($bill_out_id);
                $msg['dist_id'] = $get;
                $msg['out_code'] = $bill_out_code;
                $this->msgReturn(true, '', $msg);
                return;
            }
        }
        unset($map);
        //剔除库存不足的订单
        foreach ($detail as $key => $val) {
            if (!in_array($val['bill_out_id'], $ids)) {
                unset($detail[$key]);
            }
        }
        //获取库存充足的订单详情
        $map['pid'] = array('in', $ids);
        $stock_detail = $stock_out_detail->where($map)->select();
        //创建波次和配送单关联数据
        $wave_info = array();
        $assist = array();
        $wave_info['status'] = 200; //待运行
        $wave_info['wave_type'] = 2; //手动创建
        $wave_info['order_count'] = count($ids);
        foreach ($stock_detail as $sku_info) {
            $assist[$sku_info['pro_code']] = null; //统计sku种类
            $wave_info['sku_count'] += $sku_info['order_qty']; //总种类
        }
        $wave_info['line_count'] = count($assist); //总行数
        $i = 0;
        foreach ($res as $v) {
            if ($i <= 0) { 
                $wave_info['company_id'] = $v['company_id'];
                $wave_info['wh_id'] = $v['wh_id'];
            }
            //创建出库单好关联的配送单号数据
            foreach ($detail as &$det_info) {
                if ($det_info['pid'] == $v['id']) {
                    $det_info['refer_code'] = $v['dist_code'];
                }
            }
            $i++;
        }
        //创建波次
        $wave_info['detail'] = $detail;
        $back = $D->create_wave($wave_info);
        if (!$back) {
            $this->msgReturn(false, '创建波次失败');
        }
        //更新出库单状态为波次中
        $map['id'] = array('in', $ids);
        $data['status'] = 3; //波此中
        $data['wave_id'] = $back;
        $data['refused_type'] = 1;
        if ($stock_out->create($data)) {
           $affect = $stock_out->where($map)->save();
           if (!affect) {
               $this->msgReturn(false, '出库单状态更新失败');
           }
        }
        $msg = array();
        $msg['order_count'] = count($ids);
        $msg['type'] = 'success';
        $msg['wave_id'] = $back;
        $this->msgReturn(true, '创建波次成功', $msg, U('index'));
    }
}