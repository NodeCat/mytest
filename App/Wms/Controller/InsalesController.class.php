<?php
/**
* @author liang
* @version 2015-6-25
* 进销存分析
*/
namespace Wms\Controller;
use Think\Controller;
class InsalesController extends CommonController {
	protected function before_index(){
		$this->table = array(
            'searchbar' => true, 
            'toolbar_tr'=> true,
        );
	}
	//显示数据列表
    protected function lists() {
        //获得仓库信息
        $this->warehouse = M('warehouse')->field('id,name')->select();
    	//获得分类
        $pmsLogic = A('Pms','Logic');
    	$cats = $pmsLogic->get_SKU_category();
    	//一级分类
    	$this->cat_1 = $cats['list']['top'];
    	$this->cat_2 = $cats['list']['second'];
    	$this->cat_3 = $cats['list']['second_child'];

        $p           = I("p",1);
        $page_size   = C('PAGE_SIZE');
        $wh_id       = (I('wh_id')== '全部')?'':I('wh_id');
        $cat_1       = I('cat_1');
        $cat_2       = I('cat_2');
        $cat_3       = I('cat_3');

        //获取sku 第三级分类id
        $param = array();
        $param['top'] = ($cat_1 == '全部')?'':$cat_1;
        $param['second'] = ($cat_2 == '全部')?'':$cat_2;
        $param['second_child'] = ($cat_3 == '全部')?'':$cat_3;

        $categoryLogic = A('Category', 'Logic');
        $categoryChild = $categoryLogic->getPidBySecondChild($param);
        $pro_codeArr   = array();
        if($categoryChild){
            //获取sku_code
            $insalesLogic = A('Insales','Logic');
            $result = $insalesLogic->getSkuInfoByCategory($categoryChild);
            //帅选sku_code
            if($result){
                $pro_codeArr = $insalesLogic->getSkuInfoByWhId($result,$wh_id);
            }
        }
        $array = array();

        if($pro_codeArr){

            $array = $pro_codeArr;
        }

        $count          = count($array);
        $data           = array_splice($array, ($p-1)*$page_size, $page_size);
        $maps           = array();

        $maps           = $param;
        $maps['wh_id']  = $wh_id;

        $this->data = $data;

        $template= IS_AJAX ? 'list':'index';
        $this->page($count,$maps,$template);

        //$this->display('Insales:index');
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

        //获取sku 第三级分类id
        $param = array();
        $param['top'] = ($cat_1 == '全部')?'':$cat_1;
        $param['second'] = ($cat_2 == '全部')?'':$cat_2;
        $param['second_child'] = ($cat_3 == '全部')?'':$cat_3;

        $categoryLogic = A('Category', 'Logic');
        $categoryChild = $categoryLogic->getPidBySecondChild($param);
        $pro_codeArr   = array();
        if($categoryChild){
            //获取sku_code
            $insalesLogic = A('Insales','Logic');
            $result = $insalesLogic->getSkuInfoByCategory($categoryChild);
            //帅选sku_code
            if($result){
                $pro_codeArr = $insalesLogic->getSkuInfoByWhId($result,$wh_id);
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