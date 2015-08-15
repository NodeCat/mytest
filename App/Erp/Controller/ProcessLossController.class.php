<?php
/**
 * Created by PhpStorm.
 * User: san77
 * Date: 15/8/6
 * Time: 上午11:08
 */
namespace Erp\Controller;

use Think\Controller;

class ProcessLossController extends CommonController
{
    protected $columns = array (
        'id' => '',
        'p_pro_code' => '父SKU',
        'p_pro_name' => '父SKU名称',
        'c_pro_code' => '子SKU',
        'ratio' => '比例',
        'c_pro_num' => '原料加工数',
        'p_pro_num' => '成品加工数',
        'loss_ratio' => '损耗率',
        'loss_number' => '原料损耗数',
        'y_loss_amount' => '原料损耗成本(元/斤)',
        'c_loss_amount' => '成品损耗成本(元/袋)',
    );
    protected $query   = array (
        'erp_process.wh_id' =>    array (
            'title' => '仓库',
            'query_type' => 'eq',
            'control_type' => 'getField',
            'value' => 'Warehouse.id,name',
        ),
        'erp_process.created_time' => array (
            'title' => '加工时间',
            'query_type' => 'between',
            'control_type' => 'datetime',
            'value' => '',
        )
    );
    //设置列表页选项
    protected function before_index() {
        $this->table = array(
            'searchbar' => true,
            'checkbox'  => true,
            'status'    => false,
        );
    }

    public function _before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
        );

        $this->search_addon = true;
    }

    /**
     * 列表字段处理
     * @param $data 加工区所有的sku数据
     */
    public function after_lists(&$data) {
        $pro_code = array();
        //格式化状态
        foreach ($data as $key => &$value) {
            $pro_code[] = $value['c_pro_code'];
            $value['c_pro_num'] = sprintf('%.2f',$value['p_pro_num']*$value['ratio']);
        }

        $start_time = $_POST['query']['erp_process.created_time'];
        $end_time   = $_POST['query']['erp_process.created_time_1'];

        if (empty($start_time)) {
            $start_time = I('get.created_time');
            $end_time   = I('get.created_time_1');
        }

        $code   = implode(',', $pro_code);
        $logic  = D('ProcessLoss', 'Logic');
        $result = $logic->getStockLoss($code, $start_time, $end_time);
        foreach ($data as $key => $val) {
            $data[$key]['loss_number']   = sprintf('%.2f', $result[$val['c_pro_code']]['stock_qty']);
            $loss_ratio                  = ($data[$key]['loss_number'] / ($val['c_pro_num'] + $data[$key]['loss_number']));       //损耗率
            $data[$key]['loss_ratio']    = sprintf('%.2f', $loss_ratio * 100).'%';
            $c_loss_amount               = ($result[$val['c_pro_code']]['total_amount'] / $result[$val['c_pro_code']]['stock_qty']) * $loss_ratio;    //原料损耗成本
            $data[$key]['y_loss_amount'] = sprintf('%.2f', $c_loss_amount);
            $data[$key]['c_loss_amount'] = sprintf('%.2f', $data[$key]['y_loss_amount'] * $val['ratio']);   //成品损耗成本
        }
    }

    //计算分页条数
    protected function after_count($param)
    {
        $param['count'] = $param['model']->scope('default')->where($param['map'])->group()->count('DISTINCT erp_process_detail.p_pro_code');
    }

    /**
     * 进销存导出
     */
    public function exportData()
    {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }

        $ids         = I('get.ids');
        $start_time  = I('get.start_time');
        $end_time    = I('get.end_time');
        $wh_id       = I('get.wh_id');
        $where       = array();

        if (!empty($start_time) && !empty($end_time)){
            $where['DATE_FORMAT(erp_process.`created_time`,\'%Y-%m-%d\')'] = array('between', "$start_time,$end_time");
        }

        if (!empty($wh_id)) {
            $where['erp_process.`wh_id`'] = $wh_id;
        }

        if (!empty($ids)) {
            $model = M('erp_process_detail');
            $field = 'erp_process_detail.id, erp_process.code, erp_process_detail.p_pro_name, erp_process_detail.p_pro_code, erp_process_sku_relation.c_pro_code, SUM(erp_process_detail.real_qty) as p_pro_num, erp_process_sku_relation.ratio';
            $join = array(
                'INNER JOIN erp_process ON erp_process.id=erp_process_detail.pid',
                'INNER JOIN erp_process_sku_relation ON erp_process_sku_relation.p_pro_code=erp_process_detail.p_pro_code'
            );
            $where['erp_process_detail.id']    = array('in', $ids);
            $data = $model->join($join)->field($field)->where($where)->group('erp_process_detail.p_pro_code')->select();
        } else {
            $model = D('ProcessLoss');
            $data = $model->scope('default')->where($where)->select();
        }


        if (!$data) {
            $this->msgReturn(false, '导出数据为空！');
        }
        $pro_code = array();
        foreach ($data as $key => &$values) {
            $pro_code[] = $values['c_pro_code'];
            $values['c_pro_num'] = sprintf('%.2f',$values['p_pro_num']*$values['ratio']);
        }

        $code   = implode(',', $pro_code);
        $logic  = D('ProcessLoss', 'Logic');
        $result = $logic->getStockLoss($code, $start_time, $end_time);

        foreach ($data as $key => $val) {
            $data[$key]['loss_number']   = sprintf('%.2f', $result[$val['c_pro_code']]['stock_qty']);
            $loss_ratio                  = ($data[$key]['loss_number'] / ($val['c_pro_num'] + $data[$key]['loss_number']));       //损耗率
            $data[$key]['loss_ratio']    = sprintf('%.2f', $loss_ratio * 100).'%';
            $c_loss_amount               = ($result[$val['c_pro_code']]['total_amount'] / $result[$val['c_pro_code']]['stock_qty']) * $loss_ratio;    //原料损耗成本
            $data[$key]['y_loss_amount'] = sprintf('%.2f', $c_loss_amount);
            $data[$key]['c_loss_amount'] = sprintf('%.2f', $data[$key]['y_loss_amount'] * $val['ratio']);   //成品损耗成本
        }

        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();

        $sheet = $Excel->createSheet('0');
        $sheet->setCellValue('A1', '父SKU');
        $sheet->setCellValue('B1', '父SKU名称');
        $sheet->setCellValue('C1', '子SKU');
        $sheet->setCellValue('D1', '比例');
        $sheet->setCellValue('E1', '原料加工数');
        $sheet->setCellValue('F1', '成品加工数');
        $sheet->setCellValue('G1', '损耗率');
        $sheet->setCellValue('H1', '原料损耗数');
        $sheet->setCellValue('I1', '原料损耗成本(斤)');
        $sheet->setCellValue('J1', '成品损耗成本(袋)');

        $i = 1;
        foreach ($data as $value){
            $i++;
            $sheet->setCellValue('A'.$i, $value['p_pro_code']);
            $sheet->setCellValue('B'.$i, $value['p_pro_name']);
            $sheet->setCellValue('C'.$i, $value['c_pro_code']);
            $sheet->setCellValue('D'.$i, $value['ratio']);
            $sheet->setCellValue('E'.$i, $value['c_pro_num']);
            $sheet->setCellValue('F'.$i, $value['p_pro_num']);
            $sheet->setCellValue('G'.$i, $value['loss_ratio']);
            $sheet->setCellValue('H'.$i, $value['loss_number']);
            $sheet->setCellValue('I'.$i, $value['y_loss_amount']);
            $sheet->setCellValue('J'.$i, $value['c_loss_amount']);
        }

        date_default_timezone_set("Asia/Shanghai");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = ProcessLoss-".date('Y-m-d-H-i-s',time()).".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
        $objWriter->save('php://output');

    }
}