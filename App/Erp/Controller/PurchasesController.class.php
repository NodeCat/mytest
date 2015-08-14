<?php
/**
* @author liang
* @version 2015-6-25
* 采购需求报表
*/
namespace Erp\Controller;
use Think\Controller;
class PurchasesController extends CommonController {
    
    //显示数据列表
    protected function lists() {
        //获得仓库信息
        $this->warehouse = M('warehouse')->field('id,name')->select();

        $p                  = I("p",1);
        $page_size          = C('PAGE_SIZE');
        $offset             = ($p-1)*$page_size;
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
        $array = array();

        //优化代码如果没选择分类则查本地库
        if(!$param['top'] && !$param['second'] && !$param['second_child']){
            $pro_codeArr = $purchasesLogic->getSkuInfoByWhIdUp($wh_id, $delivery_date, $delivery_ampm, $offset, $page_size);

            if($pro_codeArr){

                $array = $pro_codeArr['res'];
            }

            $count          = $pro_codeArr['count'];
            $data           = $array;

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

            $count          = count($array);
            $data           = array_splice($array, $offset, $page_size);

        }

        $maps                   = array();
        $maps['cat_1']          = $param['top'];
        $maps['cat_2']          = $param['second'];
        $maps['cat_3']          = $param['second_child'];
        $maps['wh_id']          = $wh_id;
        $maps['delivery_date']  = $delivery_date;
        $maps['delivery_ampm']  = $delivery_ampm;
        $p_sku  = array();
        $c_sku  = array();
        foreach ($data as $key => $value) {
            $p_sku[] = $value['pro_code'];
            if ($value['c_pro_code']) {
                $c_sku[] = $value['c_pro_code'];
            }
            //$data[$key]['purchase_num'] = getPurchaseNum($value['pro_code'], $delivery_date, $delivery_ampm, $value['wh_id']);
            //解决不选日期和时间段是应该是根据空汇总
            $data[$key]['delivery_date'] = $delivery_date;
            $data[$key]['delivery_ampm'] = $delivery_ampm;
            //父下单量
            $down_qty = getDownOrderNum($value['pro_code'],$delivery_date,$delivery_ampm,$value['wh_id']);
            $data[$key]['p_down_qty'] = $down_qty;
            //父在在库量
            $p_qty = getStockQtyByWpcode($value['pro_code'], $value['wh_id']);
            $data[$key]['p_in_qty'] = $p_qty;
            //需要生产子的量 = 父在在库量 x 生产比例;
            $c_qty_count = f_mul($p_qty,$value['ratio']);
            //父采购量
            $data[$key]['purchase_num'] = f_sub($down_qty, $p_qty);
            //子Sku在库量
            $c_qty = getStockQtyByWpcode($value['c_pro_code'], $value['wh_id']);
            $data[$key]['c_in_qty'] = $c_qty;
            //子sku总可用量 = 父在在库量 x 生产比例 + 子Sku在库量;
            $available_qty = f_add($c_qty_count, $c_qty);
            $data[$key]['available_qty'] = $available_qty;
            //子Sku总需求量 = 父下单量 x 生产比例; 
            $requirement_qty = f_mul($down_qty,$value['ratio']);
            $data[$key]['requirement_qty'] = $requirement_qty;
            //子Sku采购量 = 子SKU总需求量 - 子SKU总可用量;
            $data[$key]['c_purchase_qty'] = f_sub($requirement_qty,$available_qty);
            /*if ($data[$key]['purchase_num'] < 0) {
                unset($data[$key]);
            }*/
        }
        $model = M('stock_bill_in_detail');
        //获取父SKU名称和规格
        $p_sku_info = getSkuInfoByCodeArray($p_sku);
        //获取子SKU名称和规格
        $c_sku_info = getSkuInfoByCodeArray($c_sku);

        foreach ($data as &$d_data) {
            $d_data['p_pro_name'] = $p_sku_info[$d_data['pro_code']]['name'];
            $d_data['p_pro_attrs'] = $p_sku_info[$d_data['pro_code']]['pro_attrs_str'];
            $d_data['c_pro_name'] = $c_sku_info[$d_data['c_pro_code']]['name'];
            $d_data['c_pro_attrs'] = $c_sku_info[$d_data['c_pro_code']]['pro_attrs_str'];
        }
        $this->data = $data;
        $template= IS_AJAX ? 'list':'index';
        $this->page($count,$maps,$template);

    }

    /**
     * 采购需求数据存导出
     */
    public function exportPurchases() {
        
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
        $p_sku  = array();
        $c_sku  = array();
        foreach ($pro_codeArr as $key => $value) {
            $p_sku[] = $value['pro_code'];
            if ($value['c_pro_code']) {
                $c_sku[] = $value['c_pro_code'];
            }
            //解决不选日期和时间段是应该是根据空汇总
            $pro_codeArr[$key]['delivery_date'] = $delivery_date;
            $pro_codeArr[$key]['delivery_ampm'] = $delivery_ampm;
            //父下单量
            $down_qty = getDownOrderNum($value['pro_code'],$delivery_date,$delivery_ampm,$value['wh_id']);
            $pro_codeArr[$key]['down_qty'] =$down_qty;
            //父在在库量
            $p_qty = getStockQtyByWpcode($value['pro_code'], $value['wh_id']);
            $pro_codeArr[$key]['p_qty']=$p_qty;
            //父采购量
            $pro_codeArr[$key]['purchase_num'] = f_sub($down_qty, $p_qty);
            //需要生产子的量 = 父在在库量 x 生产比例;
            $c_qty_count = f_mul($p_qty,$value['ratio']);
            //子Sku在库量
            $c_qty = getStockQtyByWpcode($value['c_pro_code'], $value['wh_id']);
            $pro_codeArr[$key]['c_qty']=$c_qty;
            //子sku总可用量 = 父在在库量 x 生产比例 + 子Sku在库量;
            $available_qty = f_add($c_qty_count, $c_qty);
            $pro_codeArr[$key]['available_qty'] = $available_qty;
            //子Sku总需求量 = 父下单量 x 生产比例; 
            $requirement_qty = f_mul($down_qty,$value['ratio']);
            $pro_codeArr[$key]['requirement_qty'] = $requirement_qty;
            //子Sku采购量 = 子SKU总需求量 - 子SKU总可用量;
            $pro_codeArr[$key]['c_purchase_qty'] = f_sub($requirement_qty,$available_qty);
            /*if ($pro_codeArr[$key]['purchase_num'] < 0) {
                unset($pro_codeArr[$key]);
            }*/
            
        }
        $p_sku_info = getSkuInfoByCodeArray($p_sku);
        //获取子SKU名称和规格
        $c_sku_info = getSkuInfoByCodeArray($c_sku);

        foreach ($pro_codeArr as &$d_data) {
            $d_data['p_pro_name'] = $p_sku_info[$d_data['pro_code']]['name'];
            $d_data['p_pro_attrs'] = $p_sku_info[$d_data['pro_code']]['pro_attrs_str'];
            $d_data['c_pro_name'] = $c_sku_info[$d_data['c_pro_code']]['name'];
            $d_data['c_pro_attrs'] = $c_sku_info[$d_data['c_pro_code']]['pro_attrs_str'];
        }
        
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
        foreach ($pro_codeArr as $value){
            $i++;
            $sheet->setCellValue('A'.$i, $value['pro_code']);
            $sheet->setCellValue('B'.$i, $value['p_pro_name']);
            $sheet->setCellValue('C'.$i, $value['p_pro_attrs']);
            $sheet->setCellValue('D'.$i, $value['wh_name']);
            $sheet->setCellValue('E'.$i, $value['p_qty']);
            $sheet->setCellValue('F'.$i, $value['down_qty']);
            $sheet->setCellValue('G'.$i, $value['purchase_num']);
            $sheet->setCellValue('H'.$i, $value['ratio']);
            $sheet->setCellValue('I'.$i, $value['c_pro_code']);
            $sheet->setCellValue('J'.$i, $value['c_pro_name']);
            $sheet->setCellValue('K'.$i, $value['c_qty']);
            $sheet->setCellValue('L'.$i, $value['available_qty']);
            $sheet->setCellValue('M'.$i, $value['requirement_qty']);
            //$sheet->setCellValue('L'.$i, getProcessByCode($value['pro_code'], $value['wh_id'],$value['delivery_date'], $value['delivery_ampm'], $value['c_pro_code']));
            $sheet->setCellValue('N'.$i, $value['c_purchase_qty']);
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
}