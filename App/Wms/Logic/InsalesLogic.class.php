<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Wms\Logic;

class InsalesLogic{
    /**
     * 根据分类获获取sku
     * getSkuInfoByCategory
     *  
     * @param Array $categoryIds 分类id
     * @author liuguangping@dachuwang.com
     * @return Array $returnRes;
     * 
     */
    public function getSkuInfoByCategory($categoryIds = array()) {
        
        $returnRes = array();
        $pmsLogic = A('Pms','Logic');
        $page_size   = C('PAGE_SIZE');
        if($categoryIds){
            $result = $pmsLogic->get_SKU_by_category_id($categoryIds, 1, $page_size);
            if($result){
                if($result['status'] == 0 && $result['total'] && $result['list']){
                    //查出第一条
                    $list = $result['list'];
                    $skurs = getSubByKey($list, 'sku_number');
                    $returnRes = $skurs;
                    $total = $result['total'];
                    $totalPage = ceil($total/$page_size); 
                    
                    if(intval($totalPage)>=2){
                        for($i=2; $i<=$totalPage; $i++){
                            $result = $pmsLogic->get_SKU_by_category_id($categoryIds, $i, $page_size);
                            if($result['status'] == 0 &&  $result['list']){
                                $list = $result['list'];
                                $skurs = getSubByKey($list, 'sku_number');
                                $returnRes = array_merge($returnRes, $skurs);
                            }
                        }
                    }


                }
            }
        }
        return array_unique($returnRes);
        
    }

    /**
     * 根据条件获取要求的sku
     * getSkuInfoByWhId
     *  
     * @param String $wh_id 仓库id
     * @param Array $pro_codeArr sku 码数组
     * @author liuguangping@dachuwang.com
     * @return Array $returnRes;
     * 
     */
    public function getSkuInfoByWhId($pro_codeArr = array(),$wh_id){

        if($wh_id){
            $where['wh_id'] = $wh_id;
        }
        $page_size = C('PAGE_SIZE');
        $where['status'] = 'qualified';
        $returnRes = array();
        $total = count($pro_codeArr);
        $totalPage = ceil($total/$page_size);
        if(intval($total)>0){
            $m = M('stock');
            for($j=1; $j<=$totalPage;$j++){
                
                $pro_code = array_splice($pro_codeArr, 0, $page_size);
                $where['pro_code'] = array('in',$pro_code);
                $result = $m->where($where)->select();
                if($result){
                    //$pro_codes = getSubByKey($result, 'pro_code');
                    $returnRes = array_merge($returnRes,$result);
                }

            }
        }
        //sku 按照库位维度分组 包含 pro_code,pro_name,pro_attrs_str,wh_id,
        if($returnRes){
            $set = array();
            foreach ($returnRes as $key => $value) {
                if(isset($set[$value['wh_id'].$value['pro_code']]['pro_qty'])){
                    $set[$value['wh_id'].$value['pro_code']]['pro_qty'] += $value['stock_qty'];
                }else{
                    $set[$value['wh_id'].$value['pro_code']]['pro_qty'] = $value['stock_qty'];
                    $set[$value['wh_id'].$value['pro_code']]['wh_id'] = $value['wh_id'];
                    $set[$value['wh_id'].$value['pro_code']]['pro_code'] = $value['pro_code'];
                }
            }
            $returnRes = $set;
        }
        //return array_unique($returnRes);
        return $returnRes;

    }

    //进销存没有选择分类时做逻辑操作
    public function getSkuInfoByWhIdUp($wh_id,$offset='',$limit=''){
        $m               = M('stock');
        $where           = array();
        $where['status'] = 'qualified';
        if($wh_id){
            $where['wh_id'] = $wh_id;
        }
        $result = array();
        $m->field('wh_id,pro_code,sum(stock_qty) as pro_qty')->where($where)->group('wh_id,pro_code');
        if($limit){
            $m2 = clone $m;//深度拷贝，m2用来统计数量, m 用来select数据。
            $count = count($m->select());
            $res = $m2->limit($offset,$limit)->select();
            $result['count'] = $count;
            $result['res']   = $res;
        }else{
            $result = $m->select();
        }
        
        return $result;
    }
}
/* End of file InsalesLogic.class.php */
/* Location: ./Application/Logic/InsalesLogic.class.php */