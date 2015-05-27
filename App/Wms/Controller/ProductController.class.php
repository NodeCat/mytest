<?php
namespace Wms\Controller;
use Think\Controller;
class ProductController extends AuthController {
    
    public function index(){
        
        layout(!IS_AJAX);
        $count =10;
        $page = I('p',1);
        $product_code = I('product_code');
        if($product_code) {
            $pms_info = A('Pms', 'Logic')->get_SKU_by_pro_codes_fuzzy($product_code, $page, $count);
        }else {
            $pms_info = A('Pms', 'Logic')->get_SKU_by_all($page, $count);
        }
        foreach($pms_info['list'] as $key=>&$val) {
            foreach($val['spec'] as $value){
               $val['attrs'] .= $value['name'] . ":" . $value['val'] . ',';
            }
            $val['attrs'] = substr($val['attrs'], 0, strlen($val['attrs'])-1);
        }

        $total_count = $pms_info['total']; 
        
        $target = "table-content";
        $pagesId = 'page';
        
        import("Common.Lib.Page");
        $Page = new \Common\Lib\Page($total_count, $count, $map,$target, $pagesId);
        $this->page     = $Page->show();
        $this->pageinfo = $Page->nowPage.'/'.$Page->totalPages;
        $this->jump_url = $Page->jump_url;

        $this->assign('product_info', $pms_info['list']);
        $this->assign('total', $pms_info['total']);
        $this->display('list');
    }
    
    public function view() {
        $pro_code = I('pro_code');
        $pro_codes = array($pro_code);
        $pro_info = A('Pms', 'Logic')->get_SKU_by_pro_codes($pro_codes);
        $data['pro_code'] = $pro_info['list'][0]['sku_number'];
        $data['pro_name'] = $pro_info['list'][0]['name'];
        
        foreach($pro_info['list'][0]['spec'] as $key=>$val) {
            $attrs[$key]['name'] = $val['name'];
            $attrs[$key]['val']  = $val['val'];
        }
        $this->assign('data', $data);
        $this->assign('attrs', $attrs);
        $this->display('view');
    }
}
