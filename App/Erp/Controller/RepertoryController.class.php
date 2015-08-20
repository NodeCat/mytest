<?php
/**
 * User: san77
 * Date: 15/7/27
 * Time: 上午11:10
 */

namespace Erp\Controller;

use Think\Controller;

class RepertoryController extends CommonController
{
    #税率
    protected $price_rate = array(
        '1'   => 1.13,  //'米面粮油',
        '6'   => 1,     //'蛋',
        '43'  => 1.13,  //'水果',
        '130' => 1.17,  //'调味品',
        '168' => 1.17,  //'日用品',
        '198' => 1.13,  //'水产冻品',
        '269' => 1,     //'蔬菜',
        '326' => 1,     //'肉类',
        '303' => 1.17,  //'酒水饮料',
    );

    protected $columns = array (
        'id'                => '',
        'pro_code'          => '产品编号',
        'pro_name'          => '产品名称',
        'category_name1'    => '一级分类',
        'category_name2'    => '二级分类',
        'category_name3'    => '三级分类',
        'pro_uom'           => '单位',
        'pro_attrs'         => '规格',
        'wh_name'             => '仓库',
        'first_nums'        => '期初数量',
        'first_amount'      => '期初成本(含税)',
        'first_amounts'     => '期初成本(未含税)',
        'instock_num'       => '入库数量',
        'instock_amount'    => '入库金额（含税）',
        'instock_amounts'   => '入库金额（未含税）',
        'insotck_cost'      => '入库加权平均成本',
        'purchase_nums'     => '采购正品入库数量',
        'purchase_amount'   => '采购正品入库金额（含税）',
        'purchase_in_amount'=> '采购正品入库金额（未含税）',
        'process_nums'      => '加工入库数量',
        'process_in_amount' => '加工入库金额（含税）',
        'process_in_amounts'=> '加工入库金额（未含税）',
        /*'profit_nums'       => '盘盈数量',
        'profit_amount'     => '盘盈金额（含税）',
        'profit_amounts'    => '盘盈金额（未含税）',*/
        'stock_out_nums'    => '出库数量',
        'stock_out_amount'  => '出库金额（含税）',
        'stock_out_amounts' => '出库金额（未含税）',
        'stock_out_cost'    => '出库加权平均成本',
        'process_out_num'   => '加工出库数量',
        'process_out_amount'=> '加工出库金额（含税）',
        'process_out_amounts'=>'加工出库金额（未含税）',
        'purchase_return_nums'  => '采购正品退货数量',
        'purchase_return_amount'=> '采购正品退货金额（含税）',
        'purchase_return_amounts'=> '采购正品退货金额（未含税）',
        'sale_cost_nums'    => '销售数量',
        'sale_cost_amount'  => '销售成本（含税）',
        'sale_cost_amounts' => '销售成本（未含税）',
        'sale_income'       => '销售收入',
        'last_nums'         => '期末数量',
        'last_amount'       => '期末成本（含税）',
        'last_amounts'      => '期末成本（未含税）'
    );

    protected $query = array (
        'stock_snap.category1' => array (
            'title' => '一级分类',
            'query_type' => 'eq',
            'control_type' => 'select',
            'value' => ''
        ),

        'stock_snap.category2' => array (
            'title'            => '二级分类',
            'query_type'       => 'eq',
            'control_type'     => 'select',
            'value'            => ''
        ),
        'stock_snap.category3' => array (
            'title'            => '三级分类',
            'query_type'       => 'eq',
            'control_type'     => 'select',
            'value'            => ''
        ),
        'stock_snap.wh_id' =>    array (
            'title' => '仓库',
            'query_type' => 'eq',
            'control_type' => 'getField',
            'value' => 'Warehouse.id,name',
        ),
        'stock_snap.pro_code' => array (
            'title'         => '产品编号',
            'query_type'    => 'eq',
            'control_type'  => 'text',
            'value'         => '',
        ),
        'stock_snap.snap_time' => array (
            'title'         => '创建时间',
            'query_type'    => 'between',
            'control_type'  => 'datetime',
            'value'         => '',
        )
    );

    public function view()
    {
        $this->_before_index();
        $this->edit();
    }

    public function edit() {
        if (IS_POST) {
            $this->save();
        } else {
            $M = D(CONTROLLER_NAME);
            $pk = $M->getPk();
            $table = $M->tableName;
            if(empty($table)) {
                $table = strtolower(CONTROLLER_NAME);
            }
            $id=I($pk);
            $_params     = I('get.');
            $_time       = explode(',', $_params['snap_time']);
            $start_time  = $_time[0];
            $end_time    = date("Y-m-d",strtotime($_time[1])-86400);

            if (empty($id) || empty($start_time) || empty($end_time)) {
                $this->msgReturn(0,'param_error');
            }


            $map[$table.'.'.'is_deleted'] = 0; //预置条件
            $map[$table.'.'.$pk] = $id;
            $res = $M->scope('default')->field('*')->where($map)->find();
            unset($map);

            if (!empty($res) && is_array($res)) {//如果查询成功

                if (ACTION_NAME == 'view') {
                    $this->filter_list($res);//如果是查看，需要filter
                }
                preg_match('/规格.*/',$res['pro_attrs'],$result);
                $attr_name = explode(',', $result[0]);
                $res['attr_name'] = str_replace('规格:', '', $attr_name['0']);
                $this->data = $res;

                //获取入库出库明细信息
                $logic      = D('Repertory', 'Logic');
                //采购入库单
                $purchaseDetail = $logic->getPurchaseDetail($start_time, $end_time, $res['pro_code']);
                //加工入库单
                $processDetail = $logic->getProcessDetail($start_time, $end_time, $res['pro_code']);
                //销售出库
                $stockOutDetail = $logic->getStockOutDetail($start_time, $end_time, $res['pro_code']);
                //加工出库
                $processOutDetail = $logic->getProcessOutDetail($start_time, $end_time, $res['pro_code']);
                //采购退货
                $refundDetail = $logic->getRefundDetail($start_time, $end_time, $res['pro_code']);
                //合并数组
                $arrays = array_merge_recursive($purchaseDetail, $processDetail, $stockOutDetail, $processOutDetail, $refundDetail);
                ksort($arrays);
                $list   = array();

                $map['pro_code'] = $res['pro_code'];
                $map['snap_time'] = $start_time;
                $stock_info = $M->where($map)->find();

                $start_num    = !empty($stock_info) ? $stock_info['stock_qty'] : 0 ;
                $start_amount = $this->numbers_format_2($res['stock_qty'] * $res['price_unit']);
                $last_num     = 0;      //结余数
                $last_amount  = 0;      //结余金额(含税)
                $last_amounts = 0;      //结余金额(不含税)
                $equally_amount = 0;    //加权平均成本

                foreach ($arrays as $array) {
                    foreach($array as $key => $val){
                        if ($val['type'] == 'in') {
                            $val['total_amounts'] = $val['total_amount'] / $this->price_rate[$res['category1']];
                            //计算结余数
                            if ($last_num == 0) {
                                $last_num       = $start_num + $val['pro_qty'];
                                $last_amount    = $start_amount + $val['total_amount'];
                                $equally_amount = $this->numbers_format_2($last_amount / $last_num);
                                $last_amounts   = $this->numbers_format_2($last_amount / $this->price_rate[$res['category1']]);
                            } else {
                                $last_num       += $val['pro_qty'];
                                $last_amount    += $val['total_amount'];
                                $equally_amount = $this->numbers_format_2($last_amount / $last_num);
                                $last_amounts   = $this->numbers_format_2($last_amount / $this->price_rate[$res['category1']]);
                            }

                            //计算结余金额含税 AND 不含税
                            $val['last_num']      = $last_num;
                            $val['equally_amount']= $equally_amount;
                            $val['last_amount']   = $last_amount;
                            $val['last_amounts']  = $last_amounts;
                        } else {
                            //计算结余数
                            if($last_num == 0){
                                $last_num       = $start_num - $val['pro_qty'];
                                $last_amount    = $start_amount - $val['total_amount'];
                                $equally_amount = $this->numbers_format_2($last_amount / $last_num);
                                $last_amounts   = $this->numbers_format_2($last_amount / $this->price_rate[$res['category1']]);
                            } else {
                                $last_num       -= $val['pro_qty'];
                                $last_amount    -= $val['total_amount'];
                                $equally_amount = $this->numbers_format_2($last_amount / $last_num);
                                $last_amounts   = $this->numbers_format_2($last_amount / $this->price_rate[$res['category1']]);
                            }

                            $val['total_amounts'] = $val['total_amount'] / $val['pro_qty'];
                            //计算结余金额含税 AND 不含税
                            $val['last_num']      = $last_num;
                            $val['equally_amount']= $equally_amount;
                            $val['last_amount']   = $last_amount;
                            $val['last_amounts']  = $last_amounts;
                        }
                        $val['total_amount'] = $this->numbers_format_2($val['total_amount']);
                        $val['total_amounts'] = $this->numbers_format_2($val['total_amounts']);

                        $list[] = $val;
                    }
                }

                $this->assign('list', $list);
            } else {
                $msg = ' '.$M->getError().' '.$M->_sql();
                $this->msgReturn(0,'没有找到该记录，请检查表关联或者纪录状态'.$msg);
            }
            $this->pk = $pk;
            $this->display(ACTION_NAME);
        }
    }

    public function _before_index()
    {
        $this->table = array(
            #'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false,
            'toolbar_tr'=> true,
            'statusbar' => true
        );

        $this->toolbar_tr = array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
        );

        $this->search_addon = true;
    }

    /**
     * 进销存导出
     */
    public function exportData() {

        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }

        $wh_id = I('get.wh_id');
        $ids         = I('get.ids');
        $start_time  = I('get.start_time');
        $end_time    = I('get.end_time');

        if (empty($start_time) || empty($end_time)){
            $this->msgReturn(false, '参数错误');
        }

        if (!empty($ids)) {
            $where['stock_snap.id']    = array('in', $ids);
        } else {
            $where['DATE_FORMAT(stock_snap.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        }

        $where['stock_snap.is_deleted'] = 0;
        $join = array(
            "inner join warehouse on warehouse.id=stock_snap.wh_id ",
        );
        $field = "stock_snap.pro_code, stock_snap.pro_name, stock_snap.category1, stock_snap.category_name1, stock_snap.category_name2, stock_snap.category_name3, stock_snap.pro_uom, stock_snap.pro_attrs, warehouse.name as wh_name";
        $model = M('stock_snap');

        $data = $model->field($field)->join($join)->where($where)->group('stock_snap.pro_code')->select();

        if (!$data) {
            $this->msgReturn(false, '导出数据为空！');
        }

        $start_sku = array();
        foreach ($data as $val) {
            $start_sku[] = $val['pro_code'];
        }

        //sku查询条件
        $pro_codes  = array('in', implode(',', $start_sku));

        //获取列表
        $price_rate = $this->price_rate[$data[0]['category1']];
        $logic      = D('Repertory', 'Logic');
        $logic->getDataList($start_time, $end_time, $pro_codes, $wh_id, $data, $price_rate);

        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();

        $sheet = $Excel->createSheet('0');
        $sheet->setCellValue('A1', '产品编号');
        $sheet->setCellValue('B1', '产品名称');
        $sheet->setCellValue('C1', '一级分类');
        $sheet->setCellValue('D1', '二级分类');
        $sheet->setCellValue('E1', '三级分类');
        $sheet->setCellValue('F1', '单位');
        $sheet->setCellValue('G1', '规格');
        $sheet->setCellValue('H1', '仓库');
        $sheet->setCellValue('I1', '期初数量');
        $sheet->setCellValue('J1', '期初成本(含税)');
        $sheet->setCellValue('K1', '期初成本(未含税)');
        $sheet->setCellValue('L1', '入库数量');
        $sheet->setCellValue('M1', '入库金额（含税）');
        $sheet->setCellValue('N1', '入库金额（未含税）');
        $sheet->setCellValue('O1', '入库加权平均成本');
        $sheet->setCellValue('P1', '采购正品入库数量');
        $sheet->setCellValue('Q1', '采购正品入库金额（含税）');
        $sheet->setCellValue('R1', '采购正品入库金额（未含税）');
        $sheet->setCellValue('S1', '加工入库数量');
        $sheet->setCellValue('T1', '加工入库金额（含税）');
        $sheet->setCellValue('U1', '加工入库金额（未含税）');
        $sheet->setCellValue('V1', '盘盈数量');
        $sheet->setCellValue('W1', '盘盈金额（含税）');
        $sheet->setCellValue('X1', '盘盈金额（未含税）');
        $sheet->setCellValue('Y1', '出库数量');
        $sheet->setCellValue('Z1', '出库金额（含税）');
        $sheet->setCellValue('AA1', '出库金额（未含税）');
        $sheet->setCellValue('AB1', '出库加权平均成本');
        $sheet->setCellValue('AC1', '加工出库数量');
        $sheet->setCellValue('AD1', '加工出库金额（含税）');
        $sheet->setCellValue('AE1', '加工出库金额（未含税）');
        $sheet->setCellValue('AF1', '采购正品退货数量');
        $sheet->setCellValue('AG1', '采购正品退货金额（含税）');
        $sheet->setCellValue('AH1', '采购正品退货金额（未含税）');
        $sheet->setCellValue('AI1', '销售数量');
        $sheet->setCellValue('AJ1', '销售成本（含税）');
        $sheet->setCellValue('AK1', '销售成本（未含税）');
        $sheet->setCellValue('AL1', '销售收入');
        $sheet->setCellValue('AM1', '期末数量');
        $sheet->setCellValue('AN1', '期末成本（含税）');
        $sheet->setCellValue('AO1', '期末成本（未含税）');

        $i = 1;
        foreach ($data as $value){
            $i++;
            $sheet->setCellValue('A'.$i, $value['pro_code']);
            $sheet->setCellValue('B'.$i, $value['pro_name']);
            $sheet->setCellValue('C'.$i, $value['category_name1']);
            $sheet->setCellValue('D'.$i, $value['category_name2']);
            $sheet->setCellValue('E'.$i, $value['category_name3']);
            $sheet->setCellValue('F'.$i, $value['pro_uom']);
            $sheet->setCellValue('G'.$i, $value['pro_attrs']);
            $sheet->setCellValue('H'.$i, $value['wh_name']);
            $sheet->setCellValue('I'.$i, $value['first_nums']);
            $sheet->setCellValue('J'.$i, $value['first_amount']);
            $sheet->setCellValue('K'.$i, $value['first_amounts']);
            $sheet->setCellValue('L'.$i, $value['instock_num']);
            $sheet->setCellValue('M'.$i, $value['instock_amount']);
            $sheet->setCellValue('N'.$i, $value['instock_amounts']);
            $sheet->setCellValue('O'.$i, $value['insotck_cost']);
            $sheet->setCellValue('P'.$i, $value['purchase_nums']);
            $sheet->setCellValue('Q'.$i, $value['purchase_amount']);
            $sheet->setCellValue('R'.$i, $value['purchase_in_amount']);
            $sheet->setCellValue('S'.$i, $value['process_nums']);
            $sheet->setCellValue('T'.$i, $value['process_in_amount']);
            $sheet->setCellValue('U'.$i, $value['process_in_amounts']);
            $sheet->setCellValue('V'.$i, '');
            $sheet->setCellValue('W'.$i, '');
            $sheet->setCellValue('X'.$i, '');
            $sheet->setCellValue('Y'.$i, $value['stock_out_nums']);
            $sheet->setCellValue('Z'.$i, $value['stock_out_amount']);
            $sheet->setCellValue('AA'.$i, $value['stock_out_amounts']);
            $sheet->setCellValue('AB'.$i, $value['stock_out_cost']);
            $sheet->setCellValue('AC'.$i, $value['process_out_num']);
            $sheet->setCellValue('AD'.$i, $value['process_out_amount']);
            $sheet->setCellValue('AE'.$i, $value['process_out_amounts']);
            $sheet->setCellValue('AF'.$i, $value['purchase_return_nums']);
            $sheet->setCellValue('AG'.$i, $value['purchase_return_amount']);
            $sheet->setCellValue('AH'.$i, $value['purchase_return_amounts']);
            $sheet->setCellValue('AI'.$i, $value['sale_cost_nums']);
            $sheet->setCellValue('AJ'.$i, $value['sale_cost_amount']);
            $sheet->setCellValue('AK'.$i, $value['sale_cost_amounts']);
            $sheet->setCellValue('AL'.$i, $value['sale_income']);
            $sheet->setCellValue('AM'.$i, $value['last_nums']);
            $sheet->setCellValue('AN'.$i, $value['last_amount']);
            $sheet->setCellValue('AO'.$i, $value['last_amounts']);
        }

        date_default_timezone_set("Asia/Shanghai");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = Insales-".date('Y-m-d-H-i-s',time()).".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
        $objWriter->save('php://output');

    }

    protected function after_lists(&$data)
    {
        if (empty($data)) {
            return false;
        }

        $wh_id = $_POST['query']['stock_snap.wh_id'];

        $start_time = $_POST['query']['stock_snap.snap_time'];
        $end_time = $_POST['query']['stock_snap.snap_time_1'];

        if (empty($start_time)) {
            $start_time = I('get.snap_time');
            $end_time   = I('get.snap_time_1');
        }

        $start_sku = array();
        foreach ($data as $val) {
            $start_sku[] = $val['pro_code'];
        }
        //sku查询条件
        $pro_codes  = array('in', implode(',', $start_sku));

        //获取列表
        $price_rate = $this->price_rate[$data[0]['category1']];
        $logic      = D('Repertory', 'Logic');
        $logic->getDataList($start_time, $end_time, $pro_codes, $wh_id, $data, $price_rate);
    }

    //在search方法执行后 执行该方法
    protected function after_search(&$map)
    {
        if (IS_AJAX) {
            if ($map['stock_snap.snap_time'][0] != 'between') {
                $this->msgReturn(0, '请填写开始和结束时间');
            }

            /*
            $time_str = explode(',', $map['stock_snap.snap_time'][1]);

            $model = M('stock_snap');
            $where['snap_time'] = date("Y-m-d", strtotime($time_str[0])-86400);
            $start_count = $model->where($where)->count();


            if (empty($start_count)) {
                $this->msgReturn(0, '开始时间没有数据');
            }

            $where['snap_time'] = date("Y-m-d", strtotime($time_str[1])-86400);
            $end_count = $model->where($where)->count();

            if (empty($end_count)) {
                $this->msgReturn(0, '结束时间没有数据');
            }
            */
        }
        //默认页面进来，不显示报表数据，将时间赋值成当天时间，取不出数据
        if (empty($map['stock_snap.snap_time'])) {
            $map['stock_snap.snap_time'] = array('gt', date('Ymd'));
        }
        $where = '';
        foreach ($map as  $key => $val) {
            $where[] = $key.'/'.$val[1];
        }

        $url_param = implode('/', $where);
        $this->toolbar_tr = array(
            'view'=>array('name'=>'view', 'link' => 'Repertory/view/'.$url_param, 'show' => isset($this->auth['view']),'new'=>'true'),
        );
    }

    //计算分页条数
    protected function after_count($param)
    {
        if (!empty($param['map']['stock_snap.snap_time']) && $param['map']['stock_snap.snap_time'][0] != 'gt') {
            $_time_str = explode(',', $param['map']['stock_snap.snap_time'][1]);
            $time      = date('Y-m-d', (strtotime($_time_str[0])-86400));
            $time_1    = date('Y-m-d', (strtotime($_time_str[1])-86400));
            $param['map']['stock_snap.snap_time'][1] = $time.','.$time_1;
            $param['count'] = $param['model']->where($param['map'])->count(' DISTINCT pro_code');
        }
    }

    //格式化金额，截取2位小数
    private function numbers_format_2($number)
    {
        if ($number == 0) {
            return sprintf("%.2f",0);
        }
        $p= stripos($number, '.');
        if ($p) {
            return sprintf("%.2f", substr($number,0,$p+3));
        } else {
            return sprintf("%.2f",$number);
        }
    }
}