<?php
/**
* @author liang
* @version 2015-6-25
* 进销存分析
*/
namespace Erp\Controller;
use Think\Controller;
class InsalesController extends CommonController {
	protected function before_index(){

	}
	//显示数据列表
    protected function lists() {
        //获得仓库信息
        $this->warehouse = M('warehouse')->field('id,name')->select();

        $p           = I("p",1);
        $page_size   = C('PAGE_SIZE');
        $offset      = ($p-1)*$page_size;
        $wh_id       = (I('wh_id')== '全部')?'':I('wh_id');
        $cat_1       = I('cat_1');
        $cat_2       = I('cat_2');
        $cat_3       = I('cat_3');
        $pro_code    = I('pro_code');

        //获取sku 第三级分类id
        $param = array();
        $param['top'] = ($cat_1 == '全部')?'':$cat_1;
        $param['second'] = ($cat_2 == '全部')?'':$cat_2;
        $param['second_child'] = ($cat_3 == '全部')?'':$cat_3;
        $insalesLogic = A('Insales','Logic');
        $array = array();

        //优化代码如果没选择分类则查本地库
        if(!$param['top'] && !$param['second'] && !$param['second_child']){
            $pro_codeArr = $insalesLogic->getSkuInfoByWhIdUp($wh_id,$pro_code, $offset, $page_size);

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
                    $pro_codeArr = $insalesLogic->getSkuInfoByWhId($result,$wh_id,$pro_code);
                }
            }
            
            if($pro_codeArr){

                $array = $pro_codeArr;
            }

            $count          = count($array);
            $data           = array_splice($array, $offset, $page_size);
        }

        $sku            = array();
        foreach ($data as $value) {
            $sku[] = $value['pro_code'];
        }
        $where['pro_code'] = array('in', implode(',', array_unique($sku)));
        $model = M('stock_bill_in_detail');
        $list  = $model->field('pro_code, pro_name, pro_attrs')->where($where)->group('pro_code')->select();
        $sku_info = array();
        foreach ($list as $value) {
            $sku_info[$value['pro_code']] = $value;
        }
        unset($list);
        foreach ($data as $key => $val) {
            $data[$key]['pro_name'] = $sku_info[$val['pro_code']]['pro_name'];
            $data[$key]['pro_attrs'] = $sku_info[$val['pro_code']]['pro_attrs'];
        }

        $maps           = array();
        $maps['cat_1']  = $param['top'];
        $maps['cat_2']  = $param['second'];
        $maps['cat_3']  = $param['second_child'];
        $maps['wh_id']  = $wh_id;

        $this->data = $data;
        $template= IS_AJAX ? 'list':'index';
        $this->page($count,$maps,$template);

	}

    /**
     * 进销存导出
     */
    public function exportInsales() {
        
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        
        $wh_id       = (I('wh_id')== '全部')?'':I('wh_id');
        $cat_1       = I('cat_1');
        $cat_2       = I('cat_2');
        $cat_3       = I('cat_3');
        $pro_code    = I('pro_code');

        //获取sku 第三级分类id
        $param = array();
        $param['top'] = ($cat_1 == '全部')?'':$cat_1;
        $param['second'] = ($cat_2 == '全部')?'':$cat_2;
        $param['second_child'] = ($cat_3 == '全部')?'':$cat_3;

        $insalesLogic = A('Insales','Logic');
        if(!$param['top'] && !$param['second'] && !$param['second_child']){
            $pro_codeArr = $insalesLogic->getSkuInfoByWhIdUp($wh_id,$pro_code);
        }else{
            $categoryLogic = A('Category', 'Logic');
            $categoryChild = $categoryLogic->getPidBySecondChild($param);
            $pro_codeArr   = array();
            if($categoryChild){
                //获取sku_code
                $result = $insalesLogic->getSkuInfoByCategory($categoryChild);
                //帅选sku_code
                if($result){
                    $pro_codeArr = $insalesLogic->getSkuInfoByWhId($result,$wh_id,$pro_code);
                }
            }
        }
        


        if(!$pro_codeArr){
            $this->msgReturn(false, '导出数据为空！');
        }

        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel();
        
        $ary  =  array("A", "B", "C", "D", "E");
        $sheet = $Excel->createSheet('0');
        $sheet->setCellValue('A1', '产品编号');
        $sheet->setCellValue('B1', '产品名称');
        $sheet->setCellValue('C1', '规格');
        $sheet->setCellValue('D1', '仓库');
        $sheet->setCellValue('E1', '在库存量');
        $i = 1;
        
        foreach ($pro_codeArr as $value){
            $i++;
            $sheet->setCellValue('A'.$i, $value['pro_code']);
            $sheet->setCellValue('B'.$i, getPronameByCode('name',$value['pro_code']));
            $sheet->setCellValue('C'.$i, getSkuInfoByCode('pro_attrs_str',$value['pro_code']));
            $sheet->setCellValue('D'.$i, getTableFieldById('warehouse','name',$value['wh_id']));
            $sheet->setCellValue('E'.$i, $value['pro_qty']);
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
