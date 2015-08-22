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
        'c_loss_number' => '原料损耗数',
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
        if(!IS_GET || I('p')){
            $query = I('query');
            $start_time = $query['erp_process.created_time'];
            $end_time   = $query['erp_process.created_time_1'];

            if (empty($start_time)) {
                $start_time = I('get.created_time');
                $end_time   = I('get.created_time_1');
            }

            $wh_id = $query['erp_process.wh_id'];

            $data = $this->prepareData($data, $start_time, $end_time, $wh_id);
        }else{
            $data = '';
            $this->page(0);
            exit;
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

        $model = D('ProcessLoss');
        $data = $model->scope('default')->where($where)->select();

        if (!$data) {
            $this->msgReturn(false, '导出数据为空！');
        }

        $data = $this->prepareData($data, $start_time, $end_time, $wh_id);

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
            $sheet->setCellValue('H'.$i, $value['c_loss_number']);
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

    //整理数据
    private function prepareData($data, $start_time, $end_time, $wh_id){
        $c_pro_code = array();
        $p_pro_code = array();
        //格式化状态
        foreach ($data as $key => &$value) {
            $c_pro_code[] = $value['c_pro_code'];
            $p_pro_code[] = $value['p_pro_code'];
            $value['c_pro_num'] = bcmul($value['p_pro_num'], $value['ratio'], 2);
        }

        $c_code   = implode(',', $c_pro_code);
        $p_code   = implode(',', $p_pro_code);
        $logic  = D('ProcessLoss', 'Logic');
        //获取所有加工损耗区ID
        $location_ids = $logic->getLocationList('XA-001',$wh_id);
        $data = $logic->getStockLoss($c_code, $p_code, $start_time, $end_time, $location_ids, $wh_id);

        foreach ($data as $key => $val) {
            //损耗率
            $loss_ratio                  = bcdiv($data[$key]['c_loss_number'], bcadd($val['c_pro_num'], $data[$key]['c_loss_number'], 2), 2);
            $data[$key]['loss_ratio']    = bcmul($loss_ratio, 100, 2).'%';
            //原料损耗成本
            $c_loss_amount               = bcmul(bcdiv($data[$val['c_pro_code']]['total_amount'], $data[$val['c_pro_code']]['c_loss_number'], 2), $loss_ratio, 2);
            $data[$key]['y_loss_amount'] = $c_loss_amount;
            //成品损耗成本
            $data[$key]['c_loss_amount'] = bcmul($data[$key]['y_loss_amount'], $val['ratio'], 2);
        }

        return $data;
    }
}