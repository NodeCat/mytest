<?php
namespace Wms\Controller;
use Think\Controller;
use Boris\ExportInspector;
class DistributionController extends CommonController {
    
    protected $filter = array(
    	    'status' => array(
    	        '1' => '未发运',
    	        '2' => '已发运',
    	        '3' => '已配送',
    	        '4' => '已结算',
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
            'end_time' => '发运时间',
            'status' => '状态',
    );
    protected $query   = array (
            'stock_wave_distribution.dist_code' => array(
                    'title' => '配送单号',
                    'query_type' => 'eq',
                    'control_type' => 'text',
                    'value' => ''
            ),
            'stock_wave_distribution.order_type' => array(
                    'title' => '订单类型',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
            	            '1' => '普通订单',
                        '3' => '水果爆款订单',
                        '4' => '水果订单',
                        '5' => '蔬菜订单',
                    ),
            ),
            'stock_wave_distribution.status' => array(
                    'title' => '状态',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
                        '1' => '未发运',
                        '2' => '已发运',
                        '3' => '已配送',
                        '4' => '已结算',
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
        $this->query['stock_wave_distribution.order_type']['value'] = D('Distribution', 'Logic')->getOrderTypeByTms();
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
        
        //SKU信息
        $D = D('Distribution', 'Logic');
        $result = $D->replace_sku_info($get);
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
            //execl头
            if (isset($value['id'])) {
                $ids[$key] = $value['id'];
            }
            //创建数据
            $data[$key] = $D->format_export_data($value);
        }
        $i = 0;
        foreach ($data as $k => $value){
           $sheet = $Excel->createSheet($i);
           if (!empty($ids)) {
               foreach ($ids as $index => $id) {
                   if ($k == $index) {
                       $sheet->setTitle($id);
                   }
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
        
        //SKU信息
        $D = D('Distribution', 'Logic');
        $result = $D->replace_sku_info($get);
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
        $ware = M('warehouse');
        $D = D('Distribution', 'Logic');
        foreach ($idarr as $get) {
            $map['id'] = $get;
            $dis = $M->where($map)->find();
            //SKU信息
            $result = $D->replace_sku_info($get);
            
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
            //纪录总价格
            $items['price_amount'] = $dis['total_price'];
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
            //汇总信息 
            foreach ($result as $val) {
                $merge = array_merge($merge, $val['detail']);
            }
            foreach ($merge as $key=>$v) {
                //叠加相同sku信息
                if (!isset($merge[$v['pro_code']])) {
                    $merge[$v['pro_code']] = $v;
                } else {
                    $merge[$v['pro_code']]['former_qty'] += $v['former_qty'];
                    $merge[$v['pro_code']]['delivery_qty'] += $v['delivery_qty'];
                }
                $items['delivery_qty'] += $v['delivery_qty']; //总发货量
                $items['delivery_amount'] += $v['delivery_qty'] * $v['price']; //总发货金额
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
            //unset($items['price_amount']);
        }
        $this->assign('list', $list);
        
        $this->display();
    }
    
    /**
     * 线路订单量统计
     */
    public function line_order_num() {
        //出库单类型
        $params['stype'] = I('stype');
        //订单类型
        $params['type'] = I('type');
        //线路
        $params['line'] = I('line');
        //日期
        $params['date'] = I('date');
        //时段
        $params['time'] = I('time');
        $D = D('Distribution', 'Logic');
        $list = $D->get_all_orders($params);
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
            $this->cur = '配送完成';
            C('LAYOUT_NAME','pda');
            $this->title = '请输入配送单号';
            $tmpl = 'Distribution:over';
            $this->display($tmpl);
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
        if (empty($pass_ids) && empty($reduce_ids)) {
            $this->msgReturn(false, '没有待复核的出库单');
        }

        if (!empty($wavein_ids) || !empty($pick_ids)) {
            //波次中或带分拣出库单
            $unpass_ids = implode(',', $wavein_ids) . '|' . implode(',', $pick_ids);
            $this->msgReturn(true, '请确认', '', U('unpass?ids=' . $unpass_ids . '&type=wave'));
        } elseif (!empty($make_ids)) {
            //弹出没有分拣的出库单
            if ($confirm == 'confirm') {
                //继续操作
                //删除此配送单下的这些出库单
                $merge = $make_ids;
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
                $data['status']   = 1; //状态 1 带生产
                $data['dis_mark'] = 0; //未加入出库单
                $data['wave_id']  = 0; //踢出波次
                if ($stock->create($data)) {
                    $stock->where($map)->save();
                }
                unset($map);
                unset($data);
                //将待生产订单从波次中踢出
                $affected = $D->updateStockWaveDetailByOutIds($merge);
                
                //更新配送单中总件数 总条数 总行数 总金额
                $D->updDistInfoByIds(array($result['id']));

            } else {
                $unpass_ids .= implode(',', $make_ids) . '|' . $post;
                $this->msgReturn(true, '请确认', '', U('unpass?ids=' . $unpass_ids . '&type=make'));
            }
        }
        if (!empty($reduce_ids)) {
            if ($confirm != 'confirm_reduce') {
                //弹出提示框
                $unpass_ids .= implode(',', $reduce_ids) . '|' . $post;
                $this->msgReturn(true, '请确认', '', U('unpass?ids=' . $unpass_ids . '&type=reduce'));
            }
        }

        //liuguangping 20150808 以前是整个车单加入明细，现在要改后的结果 ：一个出库单进入
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

        if ($pass_ids && is_array($pass_ids)) {
            //循环数组
            foreach($pass_ids as $pass_val){
                $pass_arr = array($pass_val);
                $pass_sku_detail = $D->get_out_detail($pass_arr);

                //查询出库单
                $maos = array();
                $maos['id'] = $pass_val;
                $out_code_name = M('stock_bill_out')->where($maos)->getField('code');
                //整理库存充足的出库详情
                foreach ($pass_sku_detail as $v) {
                    $stockOut->outStockBySkuFIFO(
                        array('wh_id'=>session('user.wh_id'), 
                        'pro_code'=>$v['pro_code'], 
                        'pro_qty'=>$v['order_qty'], 
                        'refer_code'=>$out_code_name, 
                        'location_ids'=>array($location_id['id']))
                    );
                }

            }
        }

        //整理库存不足的出库详情
        if ($reduce_ids && is_array($reduce_ids)) {
            //循环数组
            foreach($reduce_ids as $reduce_val){
                $reduce_arr = array($reduce_val);
                $reduce_sku_detail = $D->get_out_detail($reduce_arr);
                //统计
                //查询出库单
                $maos = array();
                $maos['id'] = $reduce_val;
                $out_code_name = M('stock_bill_out')->where($maos)->getField('code');
                foreach ($reduce_sku_detail as $v) {
                    $total_stock_qty = 0;
                    //查询PACK区内所有该sku的库存量
                    $stock_infos = A('Stock','Logic')->getStockInfosByCondition(
                        array(
                            'pro_code'=>$v['pro_code'],
                            'location_code'=>'PACK',
                            'stock_status'=>'qualified')
                    );
                    //累加库存量集合
                    foreach($stock_infos as $stock_info){
                        $total_stock_qty += $stock_info['stock_qty'] - $stock_info['assign_qty'];
                    }
                    //如果库存不足 记录下实际发货量
                    if(intval($total_stock_qty) < intval($v['order_qty'])){
                        $v['order_qty'] = $total_stock_qty;
                        $reduce_delivery_qty_list[$v['id']] = $total_stock_qty;
                    } else {
                        $reduce_delivery_qty_list[$v['id']] = $v['order_qty'];
                    }

                    //扣减库存
                    $stockOut->outStockBySkuFIFO(
                        array('wh_id'=>session('user.wh_id'), 
                        'pro_code'=>$v['pro_code'], 
                        'pro_qty'=>$v['order_qty'], 
                        'refer_code'=>$out_code_name, 
                        'location_ids'=>array($location_id['id']))
                    );  

                }

            }

        }


        $map['dist_code'] = $post;
        $data['status'] = 2; //已发运
        $data['end_time'] = date('Y-m-d H:i:s',time()); //发运时间
        //更新配送状态为已完成
        if ($M->create($data)) {
            $M->where($map)->save();
        }
        unset($map);
        unset($data);
        //更新配送详情状态为已完成
        $map['pid'] = $result['id'];
        $map['is_deleted'] = 0;
        $data['status'] = 5; //已发运
        if ($det->create($data)) {
            $det->where($map)->save();
        }
        unset($map);
        unset($data);

        if(!empty($pass_ids)){
            //更新库存充足的出库单状态 AND 出库单出库量
            $map['id'] = array('in', $pass_ids);
            $data['status'] = 2; //已出库
            $data['dis_mark'] = 1; //已分拨
            $data['act_delivery_date'] = date('Y-m-d H:i:s'); //实际发货时间
            if ($stock->create($data)) {
                $stock->where($map)->save();
            }
            unset($map);
            unset($data);

            //更新库存充足的发货量
            $pass_ids_string = implode(',', $pass_ids);
            $act_delivery_date = date('Y-m-d H:i:s');
            $sql = "UPDATE stock_bill_out_detail stock SET stock.delivery_qty = stock.order_qty,act_delivery_date = '{$act_delivery_date}' WHERE pid IN (" . $pass_ids_string . ")";
            M()->execute($sql);
        }
        
        if(!empty($reduce_ids)){
            //更新库存不足的出库单状态 AND 出库单出库量
            $map['id'] = array('in', $reduce_ids);
            $data['status'] = 2; //已出库
            $data['dis_mark'] = 1; //已分拨
            $data['refused_type'] = 2; //缺货
            $data['act_delivery_date'] = date('Y-m-d H:i:s'); //实际发货时间
            if ($stock->create($data)) {
                $stock->where($map)->save();
            }
            unset($map);
            unset($data);

            //更新库存不充足的发货量
            foreach($reduce_delivery_qty_list as $key_id => $reduce_delivery_qty){
                $map['id'] = $key_id;
                $data['delivery_qty'] = $reduce_delivery_qty;
                $data['act_delivery_date'] = date('Y-m-d H:i:s');
                M('stock_bill_out_detail')->where($map)->save($data);
                unset($map);
                unset($data);
            }
        }

        //通知实时库存接口 需要遍历出库单详情
        $synch_hop_bill_out_ids = array();
        $pass_reduce_ids = array_merge($pass_ids,$reduce_ids);
        $map['id'] = array('in', $pass_reduce_ids);
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
        if (!empty($synch_hop_bill_out_ids)) {
            $map['pid'] = array('in', $synch_hop_bill_out_ids);
            $bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();
            foreach($bill_out_detail_infos as $bill_out_detail_info){
                $notice_params['wh_id'] = session('user.wh_id');
                $notice_params['pro_code'] = $bill_out_detail_info['pro_code'];
                $notice_params['type'] = 'outgoing';
                $notice_params['qty'] = $bill_out_detail_info['delivery_qty'];
                A('Dachuwang','Logic')->notice_stock_update($notice_params);
                unset($notice_params);
            }
            unset($map);
        }

        
        //查询采购正品退货单或erp调拨单
        $map_refer = array();
        $map_refer['id'] = array('in',$pass_reduce_ids);
        $refer_out_res = $stock->where($map_refer)->select();
        //关联单据单号
        $refer_code_Arr = array_column($refer_out_res,'refer_code');
        $erp_map = array();
        $where_out = array();
        $erp_map['trf_code'] = array('in',$refer_code_Arr);
        $transfer_re = M('erp_transfer')->where($erp_map)->select();
        $where_out['rtsg_code'] = array('in',$refer_code_Arr);
        $purchase_out = M('stock_purchase_out')->where($where_out)->select();
        if ($transfer_re || $purchase_out) {
            //修改采购退货已收货状态和实际收货量 liuguangping        
            $distribution_logic = A('PurchaseOut','Logic');        
            $distribution_logic->upPurchaseOutStatus($pass_reduce_ids);
            //加入wms入库单 liuguangping
            $stockin_logic = A('StockIn','Logic');        
            $stockin_logic->addWmsIn($pass_reduce_ids);

            //加入erp调拨入库单
            $erp_stockin_logic = A('TransferIn', 'Logic');
            $erp_stockin_logic->addErpIn($pass_reduce_ids);
        }

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
        if ($type == 'make' || $type == 'reduce') {
            $get = explode('|', $get);
            $ids = array();
            $data = array();
            $dist_code = array_pop($get);
            //获取发运异常ID
            $ids = explode(',', array_pop($get));
            //获取出库单号码
            $stock = M('stock_bill_out');
            if ($type == 'make') {
                $map['id'] = array('in', $ids);
                $res = $stock->where($map)->select();
                foreach ($res as $value) {
                    $data['make']['out_code'][] = $value['code'];
                }
                $data['make']['count'] = count($res);
                $data['make']['type'] = 'make';
            } else {
                $map['id'] = array('in', $ids);
                $res = $stock->where($map)->select();
                foreach ($res as $value) {
                    $data['reduce']['out_code'][] = $value['code'];
                }
                $data['reduce']['count'] = count($res);
                $data['reduce']['type'] = 'reduce';
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
        $this->assign('type', $type);
        $this->cur = '发运异常';
        C('LAYOUT_NAME','pda');
        $this->title = '发运异常';
        $tmpl = 'Distribution:unpass';
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
        	unset($map);
        	//获取出库单ID
        	$map['pid'] = $id;
        	$res = $detail->where($map)->select();
        	$bill_out_id = array();
        	foreach ($res as $value) {
        	    $bill_out_id[] = $value['bill_out_id'];
        	}
        	unset($map);
        	//判断配送单下出库单是否全部时待生产
        	$map['id'] = array('in', $bill_out_id);
        	$result = $stock->where($map)->select();
        	foreach ($result as $val) {
        	    if ($val['status'] != 1) {
        	        $this->msgReturn(false, '此配送单下有非待生产出库单，不能删除');
        	    }
        	}
        	unset($map);
        	//删除配送单
        	$map['id'] = $id;
        	$data['is_deleted'] = 1;
        	if ($M->create($data)) {
        	    $M->where($map)->save();
        	}
        unset($map);
        unset($data);
        	//删除配送单详情
        	$map['pid'] = $id;
        	$data['is_deleted'] = 1;
        	if ($detail->create($data)) {
        	    $detail->where($map)->save();
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
        $stock_out = M('stock_bill_out');
        $D = D('Distribution', 'Logic');
        //获取配送单信息
        $map['id'] = array('in', $idarr);
        $map['is_deleted'] = 0;
        $res = M('stock_wave_distribution')->where($map)->select();
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
        //是否已加入波此
        //订单库存是否充足
        //查找你选择的出库单无缺货出库单数据id
        $idsArr = $D->checkout_stock_by_type($idarr);
        if (empty($idsArr)) {
            $this->msgReturn(false, '所有出库单已全部加入波次不可重复创建');
        }
        //去除空元素liuguangping
        $ids = array_filter($idsArr['trueResult']);
        $unids = array_filter($idsArr['falseResult']);

        //对缺货订单不做处理
        $ids = array_merge($ids,$unids);
        $cancel_order_notes = '因订单被取消而造成的出库单取消的单号：';
        $cancel_order_flag = false;
        //调用hop接口查看订单是否被取消，如果被取消，则不加入波次
        foreach($ids as $k => $bill_out_id){
            $map['id'] = $bill_out_id;
            $bill_out_info = $stock_out->where($map)->find();
            unset($map);
            //销售订单
            if($bill_out_info['type'] == 1){
                $order_id_list = array($bill_out_info['refer_code']);
                $map = array('order_ids' => $order_id_list, 'itemsPerPage' => 1);
                $order_lists = A('Common/Order','Logic')->order($map);
                unset($map);
                if(!empty($order_lists[0]['order_number']) && $order_lists[0]['status'] == 0){
                    //订单被取消了，不能拉进波次
                    unset($ids[$k]);
                    //同时将出库单逻辑删除
                    $map['id'] = $bill_out_id;
                    $data['is_deleted'] = 1;
                    $stock_out->where($map)->save($data);
                    unset($map);
                    unset($data);
                    //同时在对应的车单上删除掉
                    $map['bill_out_id'] = $bill_out_id;
                    $data['is_deleted'] = 1;
                    M('stock_wave_distribution_detail')->where($map)->save($data);
                    unset($map);
                    unset($data);
                    $cancel_order_notes .= $bill_out_info['code'].',';
                    $cancel_order_flag = true;
                }
            }
        }
        //更新配送单的数据
        $D->updDistInfoByIds($idarr);

        $count = count($ids);
        if(empty($ids)){
            $this->msgReturn(false, '所有订单都不满足要求，无法创建波次');
        }
        //$count = count($ids) + count($unids); 
        //库存不足的订单数量
        /*if (count($unids) > 0) {
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
                        //查询具体哪些sku缺货
                        $not_enough_info = A('Stock','Logic')->checkStockIsEnoughByOrderId($val['id']);
                        $out_info[$val['code']] = implode(',',$not_enough_info['data']['not_enough_pro_code']);
                    }
                }

                $msg['pup_count'] = count($unids);
                $msg['order_count'] = $count;
                $msg['dist_id'] = $get;
                $msg['out_info'] = $out_info;
                $this->msgReturn(true, '', $msg);
                return;
            }
        }
        unset($map);*/
        //获取配送单详情中符合条件的出库单
        $map['bill_out_id'] = array('in', $ids);
        $map['is_deleted'] = 0;
        $detail = M('stock_wave_distribution_detail')->where($map)->select();
        /*if (empty($detail)) {
            $this->msgReturn(false, '库存不足');
        }*/
        unset($map);
        //获取库存充足的订单详情
        $map['pid'] = array('in', $ids);
        $stock_detail = M('stock_bill_out_detail')->where($map)->select();
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
        $affectedWave = $D->updateStockInfoByIds($ids, $back);
        if (!$affectedWave) {
            $this->msgReturn(false, '更新出库单失败');
        }

        //更新库存不足的出库单状态为缺货 并修改其备注
        /*if (!empty($unids)) {
            $affectedReduce = $D->getReduceSkuCodesAndUpdate($unids);
            if (!$affectedReduce) {
                $this->msgReturn(false, '更新出库单失败');
            }
        }*/
        //通知hop订单状态改变为波次中
        foreach($ids as $bill_out_id){
            $map['id'] = $bill_out_id;
            $bill_out_info = $stock_out->where($map)->find();
            unset($map);

            //如果是销售订单则通知hop接口
            if($bill_out_info['order_type'] == 1){
                $map['suborder_id'] = $bill_out_info['refer_code'];
                $map['status'] = '11';
                $map['cur']['name'] = session('user.username');
                A('Common/Order','Logic')->set_status($map);
                unset($map);
            }
            
            unset($bill_out_info);
        }
        $msg = array();
        $msg['order_count'] = count($ids);
        $msg['type'] = 'success';
        $msg['wave_id'] = $back;
        if($cancel_order_flag){
            $msg['cancel_order_notes'] = $cancel_order_notes;
        }
        $this->msgReturn(true, '创建波次成功', $msg, U('index'));
    }
}
