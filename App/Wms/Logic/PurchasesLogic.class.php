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

class PurchasesLogic{

    /**
     * 根据条件获取要求的sku
     * getSkuInfoByWhId
     *  
     * @param String $wh_id 仓库id
     * @param Array $pro_codeArr sku 码数组
     * @param Date delivery_date 时间
     * @param String delivery_ampm am上午 pm下午 
     * @author liuguangping@dachuwang.com
     * @return Array $returnRes;
     * 
     */
    public function getSkuInfoByWhId($pro_codeArr = array(), $wh_id='', $delivery_date='', $delivery_ampm=''){

        if($wh_id){
            $where['s.wh_id'] = $wh_id;
        }
        if($delivery_date){

            $where['bo.delivery_date'] = $delivery_date;
        }
        if($delivery_ampm){

            $where['bo.delivery_ampm'] = $delivery_ampm; 
        }
        $page_size = C('PAGE_SIZE');
        $where['s.status'] = 'qualified';
        $returnRes = array();
        $total = count($pro_codeArr);
        $totalPage = ceil($total/$page_size);
        if(intval($total)>0){
            $m = M();
            for($j=1; $j<=$totalPage;$j++){
                
                $pro_code = array_splice($pro_codeArr, 0, $page_size);
                $where['s.pro_code'] = array('in',$pro_code);
                $result = $m->table('stock as s')
                            ->join('left join stock_bill_out_detail as b on s.pro_code=b.pro_code 
                                    left join stock_bill_out as bo on b.pid = bo.id 
                                    left join erp_process_sku_relation as r on s.pro_code = r.p_pro_code')
                            ->field('s.wh_id,s.pro_code,r.c_pro_code')
                            ->where($where)
                            ->group('s.wh_id,s.pro_code,r.c_pro_code')
                            ->select();
                if($result){
                    $returnRes = array_merge($returnRes,$result);
                }

            }
        }
        
        return $returnRes;

    }

    //采购需求没有选择分类时做逻辑操作
    public function getSkuInfoByWhIdUp($wh_id,$delivery_date='', $delivery_ampm='', $offset='',$limit=''){
        $m               = M();
        $where           = array();
        $where['s.status'] = 'qualified';
        if($wh_id){
            $where['s.wh_id'] = $wh_id;
        }
        if($delivery_date){

            $where['bo.delivery_date'] = $delivery_date;
        }
        if($delivery_ampm){

            $where['bo.delivery_ampm'] = $delivery_ampm; 
        }
        $result = array();
        $m->table('stock as s')
        ->join('left join stock_bill_out_detail as b on s.pro_code=b.pro_code 
                left join stock_bill_out as bo on b.pid = bo.id 
                left join erp_process_sku_relation as r on s.pro_code = r.p_pro_code')
        ->field('s.wh_id,s.pro_code,r.c_pro_code')
        ->where($where)
        ->group('s.wh_id,s.pro_code,r.c_pro_code');
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