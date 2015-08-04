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
namespace Erp\Logic;

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
            $where['b.wh_id'] = $wh_id;
        }
        if($delivery_date){

            $where['b.delivery_date'] = $delivery_date;
        }
        if($delivery_ampm){

            $where['b.delivery_ampm'] = $delivery_ampm; 
        }
        $page_size = C('PAGE_SIZE');
        $where['b.status'] = 1;//待生产
        $where['b.type'] = 1;//销售订单
        $returnRes = array();
        $total = count($pro_codeArr);
        $totalPage = ceil($total/$page_size);
        if(intval($total)>0){
            $m = M();
            for($j=1; $j<=$totalPage;$j++){
                
                $pro_code = array_splice($pro_codeArr, 0, $page_size);
                $where['d.pro_code'] = array('in',$pro_code);
                $where['r.is_deleted'] = 0;
                //$query = ""
                $subQuery = $m->table('stock_bill_out_detail as d')
                            ->join('left join stock_bill_out as b on b.id=d.pid
                                    left join stock as s on d.pro_code=s.pro_code 
                                    join erp_process_sku_relation as r on d.pro_code = r.p_pro_code ')
                            ->field("r.ratio,b.delivery_ampm,b.delivery_date,b.wh_id,d.pro_code,r.c_pro_code,CASE WHEN s.status is null THEN 'undefined' ELSE s.status END as types")
                            ->where($where)
                            ->group('b.wh_id,d.pro_code,r.c_pro_code')
                            ->buildSql();

                //$map['a.types'] = array('not in',array('unqualified','freeze'));
                $result = $m->table($subQuery.' a')->where($map)->select();
                
                if($result){
                    $returnRes = array_merge($returnRes,$result);
                }

            }
        }
        //dump($returnRes);die;
        return $returnRes;

    }

    //采购需求没有选择分类时做逻辑操作
    public function getSkuInfoByWhIdUp($wh_id,$delivery_date='', $delivery_ampm='', $offset='',$limit=''){
        $m               = M();
        $where           = array();
        $where['b.status'] = 1;//待生产
        $where['b.type'] = 1;//销售订单
      
        if($wh_id){
            $where['b.wh_id'] = $wh_id;
        }
        if($delivery_date){

            $where['b.delivery_date'] = $delivery_date;
        }
        if($delivery_ampm){

            $where['b.delivery_ampm'] = $delivery_ampm; 
        }
        $result = array();
        $where['r.is_deleted'] = 0;
        $subQuery = $m->table('stock_bill_out_detail as d')
        ->join('left join stock_bill_out as b on b.id=d.pid
                left join stock as s on d.pro_code=s.pro_code 
                join erp_process_sku_relation as r on d.pro_code = r.p_pro_code ')
        ->field("r.ratio,b.delivery_ampm,b.delivery_date,b.wh_id,d.pro_code,r.c_pro_code,CASE WHEN s.status is null THEN 'undefined' ELSE s.status END as types")
        ->where($where)
        ->group('b.wh_id,d.pro_code,r.c_pro_code')->buildSql();

        //$map['a.types'] = array('not in',array('unqualified','freeze'));
        $m->table($subQuery.' a')->where($map);


        if($limit){
            $m2 = clone $m;//深度拷贝，m2用来统计数量, m 用来select数据。
            $count = count($m->select());
            $res = $m2->limit($offset,$limit)->select();
            //echo $m2->getLastSql();die;

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