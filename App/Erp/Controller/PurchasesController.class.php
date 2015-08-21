<?php
/**
* @author liang
* @version 2015-6-25
* 采购需求报表
*/
namespace Erp\Controller;
use Think\Controller;
class PurchasesController extends CommonController
{
    
    //显示数据列表
    protected function lists()
    {
        //获得仓库信息
        $warehouse = M('warehouse')->field('id,name')->select();
        //当前所在的仓库
        $wh_id_now = session('user.wh_id');
        foreach ($warehouse as &$wh_val) {
            if ($wh_val['id'] == $wh_id_now) {
                $wh_val['action'] = 'selected';
            }
        }
        $this->warehouse = $warehouse;

        $p                  = I("p",1);
        $page_size          = C('PAGE_SIZE');
        $offset             = ($p-1)*$page_size;
        $wh_id              = (I('wh_id')== '全部')?'':I('wh_id');
        $cat_1              = I('cat_1');
        $cat_2              = I('cat_2');
        $cat_3              = I('cat_3');
        $delivery_date      = I('delivery_date');
        $delivery_ampm      = (I('delivery_ampm') == '全天')?'':I('delivery_ampm');

        if (!IS_GET || I('p')) {
            if (!$delivery_date) {
                $this->msgReturn(false, '请选择配送日期');
            }
        }
        //获取sku 第三级分类id
        $param = array();
        $param['top'] = ($cat_1 == '全部')?'':$cat_1;
        $param['second'] = ($cat_2 == '全部')?'':$cat_2;
        $param['second_child'] = ($cat_3 == '全部')?'':$cat_3;
        $insalesLogic = A('Insales','Logic');
        $purchasesLogic = A('Purchases','Logic');
        $array = array();
        //优化代码如果没选择分类则查本地库
        if(!$param['top'] && !$param['second'] && !$param['second_child']){
            if (!IS_GET || I('p')) {
                $pro_codeArr = $purchasesLogic->getSkuInfoByWhIdUp($wh_id, $delivery_date, $delivery_ampm);
                if($pro_codeArr){
                    $array = $pro_codeArr;
                }
                $data           = $array;
            }
        }else{
            $categoryLogic = A('Category', 'Logic');
            $categoryChild = $categoryLogic->getPidBySecondChild($param);
            $pro_codeArr   = array();
            if($categoryChild){
                //获取sku_code
                $result = $insalesLogic->getSkuInfoByCategory($categoryChild);
                //帅选sku_code
                if($result){
                    $pro_codeArr = $purchasesLogic->getSkuInfoByWhId($result, $wh_id, $delivery_date, $delivery_ampm);
                }
            }
            if($pro_codeArr){
                $array = $pro_codeArr;
            }
            $data = $array;

        }

        $maps                   = array();
        $maps['cat_1']          = $param['top'];
        $maps['cat_2']          = $param['second'];
        $maps['cat_3']          = $param['second_child'];
        $maps['wh_id']          = $wh_id;
        $maps['delivery_date']  = $delivery_date;
        $maps['delivery_ampm']  = $delivery_ampm;
        /****刘广平优化20150820****/
        $result_arr = array();
        $result_arr = $this->dataHandle($data, $delivery_date,$delivery_ampm);
        $data           = array();
        $count          = count($result_arr);
        $data           = array_splice($result_arr, $offset, $page_size);

        $p_sku  = array();
        $c_sku  = array();
        foreach ($data as $c_val) {
            if ($c_val['sub']['c_pro_code']) {
                $c_sku[] = $c_val['sub']['c_pro_code'];
            }
            foreach ($c_val['detail'] as $p_val) {
                $p_sku[] = $p_val['pro_code'];
            }
        }
        //获取父SKU名称和规格
        $p_sku_info = getSkuInfoByCodeArray($p_sku);
        //获取子SKU名称和规格
        $c_sku_info = getSkuInfoByCodeArray($c_sku);

        foreach ($data as &$d_data) {
            $d_data['sub']['c_pro_name']         = $c_sku_info[$d_data['sub']['c_pro_code']]['name'];
            $d_data['sub']['c_pro_attrs']        = $c_sku_info[$d_data['sub']['c_pro_code']]['pro_attrs_str'];
            foreach ($d_data['detail'] as &$c_val) {
                $c_val['p_pro_name']    = $p_sku_info[$c_val['pro_code']]['name'];
                $c_val['p_pro_attrs']   = $p_sku_info[$c_val['pro_code']]['pro_attrs_str'];
            }
        }
        $this->data = $data;
        $template= IS_AJAX ? 'list':'index';
        $this->page($count,$maps,$template);

    }
    /**
     * 采购需求数据存导出
     */
    public function exportPurchases()
    {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $wh_id              = (I('wh_id')== '全部')?'':I('wh_id');
        $cat_1              = I('cat_1');
        $cat_2              = I('cat_2');
        $cat_3              = I('cat_3');
        $delivery_date      = I('delivery_date');
        $delivery_ampm      = (I('delivery_ampm') == '全天')?'':I('delivery_ampm');

        //获取sku 第三级分类id
        $param = array();
        $param['top'] = ($cat_1 == '全部')?'':$cat_1;
        $param['second'] = ($cat_2 == '全部')?'':$cat_2;
        $param['second_child'] = ($cat_3 == '全部')?'':$cat_3;

        $insalesLogic = A('Insales','Logic');
        $purchasesLogic = A('Purchases','Logic');
        if(!$param['top'] && !$param['second'] && !$param['second_child']){
            $pro_codeArr = $purchasesLogic->getSkuInfoByWhIdUp($wh_id, $delivery_date, $delivery_ampm);
        }else{
            $categoryLogic = A('Category', 'Logic');
            $categoryChild = $categoryLogic->getPidBySecondChild($param);
            $pro_codeArr   = array();
            if($categoryChild){
                //获取sku_code
                $result = $insalesLogic->getSkuInfoByCategory($categoryChild);
                //帅选sku_code
                if($result){
                    $pro_codeArr = $purchasesLogic->getSkuInfoByWhId($result, $wh_id, $delivery_date, $delivery_ampm);
                }
            }
        }

        if(!$pro_codeArr){
            $this->msgReturn(false, '导出数据为空！');
        }
        /****刘广平优化20150820****/
        $result_arr = array();
        $result_arr = $this->dataHandle($pro_codeArr, $delivery_date,$delivery_ampm);

        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();
        
        $sheet = $Excel->createSheet('0');
        $sheet->setCellValue('A1', '父SKU货号');
        $sheet->setCellValue('B1', '父SKU名称');
        $sheet->setCellValue('C1', '父SKU规格');
        $sheet->setCellValue('D1', '仓库');
        $sheet->setCellValue('E1', '父SKU在库存量');
        $sheet->setCellValue('F1', '父SKU下单量');
        $sheet->setCellValue('G1', '父SKU采购量');
        $sheet->setCellValue('H1', '比例关系');
        $sheet->setCellValue('I1', '子SKU货号');
        $sheet->setCellValue('J1', '子SKU名称');
        $sheet->setCellValue('K1', '子SKU在库存量');
        $sheet->setCellValue('L1', '子SKU总可用量');
        $sheet->setCellValue('M1', '子SKU总需求量');
        $sheet->setCellValue('N1', '子SKU采购量');
        $i = 1;
        $j = 1;
        $style_center = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        foreach ($result_arr as $value){
            $j = $i+1;
            $j_end = $i+$value['sub']['rowspan'];
            foreach ($value['detail'] as $index => $vo) {
                $i++;
                $sheet->setCellValue('A'.$i, $vo['pro_code']);
                $sheet->setCellValue('B'.$i, getPronameByCode('name',$vo['pro_code']));
                $sheet->setCellValue('C'.$i, getSkuInfoByCode('pro_attrs_str',$vo['pro_code']));
                $sheet->setCellValue('D'.$i, $vo['wh_name']);
                $sheet->setCellValue('E'.$i, $vo['p_in_qty']);
                $sheet->setCellValue('F'.$i, $vo['p_down_qty']);
                $sheet->setCellValue('G'.$i, $vo['purchase_num']);
                $sheet->setCellValue('H'.$i, $vo['ratio']);

            }
            $sheet->mergeCells('I' . $j .':I'. $j_end);
            $sheet->mergeCells('J' . $j .':J'. $j_end);
            $sheet->mergeCells('K' . $j .':K'. $j_end); 
            $sheet->mergeCells('L' . $j .':L'. $j_end);
            $sheet->mergeCells('M' . $j .':M'. $j_end);
            $sheet->mergeCells('N' . $j .':N'. $j_end);
                 
            $sheet->setCellValue('I'.$j, $value['sub']['c_pro_code']);
            $sheet->getStyle('I'.$j)->getAlignment()->setVertical($style_center);
            $sheet->setCellValue('J'.$j, getPronameByCode('name',$value['sub']['c_pro_code']));
            $sheet->getStyle('J'.$j)->getAlignment()->setVertical($style_center);
            $sheet->setCellValue('K'.$j, $value['sub']['c_in_qty']);
            $sheet->getStyle('K'.$j)->getAlignment()->setVertical($style_center);
            $sheet->setCellValue('L'.$j, $value['sub']['available_qty']);
            $sheet->getStyle('L'.$j)->getAlignment()->setVertical($style_center);
            $sheet->setCellValue('M'.$j, $value['sub']['requirement_qty']);
            $sheet->getStyle('M'.$j)->getAlignment()->setVertical($style_center);
            $sheet->setCellValue('N'.$j, $value['sub']['c_purchase_qty']);
            $sheet->getStyle('N'.$j)->getAlignment()->setVertical($style_center);
        }
        date_default_timezone_set("Asia/Shanghai");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = PurchaseQty_".date('YmdHis',time()).".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
        $objWriter->save('php://output');
        
    }
    //数据处理
    public function dataHandle($data, $delivery_date, $delivery_ampm)
    {
        /****刘广平优化20150820****/
        $tmp_arr    = array();
        $result_arr = array();
        foreach ($data as $index => $val) {
            $keys = $val['c_pro_code'] .'_join_'.$val['wh_id'];
            //子Sku在库量
            $c_qty = getStockQtyByWpcode($val['c_pro_code'], $val['wh_id']);
            //父在在库量
            $p_qty = getStockQtyByWpcode($val['pro_code'], $val['wh_id']);
            //需要生产子的量 = 父在在库量 x 生产比例;
            $c_qty_count = f_mul($p_qty,$val['ratio']);
            //父下单量
            $down_qty = getDownOrderNum($val['pro_code'],$delivery_date,$delivery_ampm,$val['wh_id']);
            //子Sku总需求量 = 父下单量 x 生产比例; 
            $requirement_qty = f_mul($down_qty,$val['ratio']);
            if (!$val['c_pro_code']) {
                $tmp_arr[$keys]['key_num']                    = 1;
                $tmp_arr[$keys]['index']                      = $index;
                $result_arr[$index]['sub']['c_pro_code']      = $val['c_pro_code'];
                $result_arr[$index]['sub']['rowspan']         = 1;
                $result_arr[$index]['sub']['c_in_qty']        = 0.00;
                //子sku总可用量 = (父在在库量 x 生产比例)*n + 子Sku在库量;
                $result_arr[$index]['sub']['available_qty']   = 0.00;
                //子Sku采购量 = 子SKU总需求量 - 子SKU总可用量;
                $result_arr[$index]['sub']['c_purchase_qty']  = 0.00;
            } elseif (!isset($tmp_arr[$keys])) {
                $tmp_arr[$keys]['key_num']                    = 1;
                $tmp_arr[$keys]['index']                      = $index;
                $result_arr[$index]['sub']['c_pro_code']      = $val['c_pro_code'];
                $result_arr[$index]['sub']['rowspan']         = 1;
                $result_arr[$index]['sub']['c_in_qty']        = $c_qty;
                //子sku总可用量 = (父在在库量 x 生产比例)*n + 子Sku在库量;
                $tmp_arr[$keys]['available_qty']              = f_add($c_qty_count, $c_qty);
                $result_arr[$index]['sub']['available_qty']   = $tmp_arr[$keys]['available_qty'];
                //子Sku采购量 = 子SKU总需求量 - 子SKU总可用量;
                $tmp_arr[$keys]['c_purchase_qty']             = f_sub($requirement_qty,$tmp_arr[$keys]['available_qty']);
                $result_arr[$index]['sub']['c_purchase_qty']  = $tmp_arr[$keys]['c_purchase_qty'];
            }else {
                $tmp_arr[$keys]['key_num'] ++;
                $result_arr[$tmp_arr[$keys]['index']]['sub']['rowspan']           = $tmp_arr[$keys]['key_num'];
                $tmp_arr[$keys]['available_qty'] = f_add($tmp_arr[$keys]['available_qty'], $c_qty_count);
                //子sku总可用量 = (父在在库量 x 生产比例)*n + 子Sku在库量;
                $result_arr[$tmp_arr[$keys]['index']]['sub']['available_qty']     = $tmp_arr[$keys]['available_qty'];
                //子Sku采购量 = 子SKU总需求量 - 子SKU总可用量;
                $c_purchase_qty = f_sub($requirement_qty,$c_qty_count);
                $tmp_arr[$keys]['c_purchase_qty'] = f_add($tmp_arr[$keys]['c_purchase_qty'], $c_purchase_qty);
                $result_arr[$tmp_arr[$keys]['index']]['sub']['c_purchase_qty']    = $tmp_arr[$keys]['c_purchase_qty'];
            }
            //父sku详细赋值
            $result_arr[$tmp_arr[$keys]['index']]['detail'][$index]['ratio']      = $val['ratio'];
            $result_arr[$tmp_arr[$keys]['index']]['detail'][$index]['wh_name']    = $val['wh_name'];
            $result_arr[$tmp_arr[$keys]['index']]['detail'][$index]['pro_code']   = $val['pro_code'];
            $result_arr[$tmp_arr[$keys]['index']]['detail'][$index]['p_in_qty']   = $p_qty;
            $result_arr[$tmp_arr[$keys]['index']]['detail'][$index]['p_down_qty'] = $down_qty;
            //父采购量
            $result_arr[$tmp_arr[$keys]['index']]['detail'][$index]['purchase_num'] = f_sub($down_qty, $p_qty);
            //子Sku总需求量 = 父下单量 x 生产比例;
            $result_arr[$tmp_arr[$keys]['index']]['sub']['requirement_qty']       = f_add($requirement_qty,$result_arr[$tmp_arr[$keys]['index']]['sub']['requirement_qty']);
        }
        return $result_arr;
    }
}