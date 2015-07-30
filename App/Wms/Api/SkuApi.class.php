<?php
namespace Wms\Api;
use Think\Controller;

/**
 * SKU统计相关
 */
class SkuApi extends CommApi 
{
    /**
     * SKU统计接口
     * @param int post.category_id 品类ID
     * @param int post.stime 开始时间戳
     * @param int post.etime 结束时间戳
     * @param int post.warehouse_id 仓库ID
     * @return array(
     *      status 0成功 -1 失败
     *      list[ ]SKU 列表
     *      total[ ] 总计
     *  } 
     */
    public function skuStatistics()
    {
        $returnSucess = array('status' => 0, 'list' => array(), 'total' => array());
        $returnError  = array('code' => -1, 'msg' => 'Failed to get the data');
        
        //接受POST参数
        $condition = I('post.');
        if (empty($condition)) {
            $this->ajaxReturn($returnError);
        }    
        //参数判断 分类
        if (empty($condition['category_id1'])) {
            $returnError['msg'] = 'The catgory_id1 Is Not Have';
            $this->ajaxReturn($returnError);
        }
        //开始时间 结束时间
        if (empty($condition['stime']) || empty($condition['etime'])) {
            $returnError['msg'] = 'The stime Or etime Is Not Have';
            $this->ajaxReturn($returnError);
        }
        //仓库ID
        if (empty($condition['warehouse_id'])) {
            $condition['warehouse_id'] = 0;
        }
        //分页
        if (empty($condition['itemspages'])) {
            $condition['itemspages'] = C('PAGE_SIZE');
        }
        
        $CategoryLogic = A('Category', 'Logic');
        $InsalesLogic  = A('Insales', 'Logic');
        $SkuInfo       = A('SkuInfo', 'Logic');
        
        //根据分类ID获取分类下所有三级分类ID 调用CategoryLogic下的getPidBySecondChild方法
        //请求条件
        
        $newSkuCodeArr = array(); //待查询的SKU编号数组
        if (empty($condition['sku_number'])) {
            //BI没有传递SKU编号 按照分类查询
            $param = array(
            	   'top' => $condition['category_id1'],
               'second' => 0,
               'second_child' => 0,
            ); 
            if (!empty($condition['category_id2'])) {
                $param['second'] = $condition['category_id2'];
            }
            if (!empty($condition['category_id3'])) {
                $param['second_child'] = $condition['category_id3'];
            }
            //所有三级分类ID
            $thirdCatIdArr = $CategoryLogic->getPidBySecondChild($param);
            if (empty($thirdCatIdArr)) {
                $returnError['msg'] = 'The Cat Have Not Data Info';
                $this->ajaxReturn($returnError);
            }
            
            //根据所有三级分类ID获取所有SKU编号 调用InsalesLogic下的getSkuInfoByCategory方法
            $skuCodeArr = $InsalesLogic->getSkuInfoByCategory($thirdCatIdArr);
            if (empty($skuCodeArr)) {
                $returnError['msg'] = 'The Cat Have Not SKU Code Data Info';
                $this->ajaxReturn($returnError);
            }
            //根据仓库ID获取SKU访问记录
            $visitRecord = $SkuInfo->getSkuVisitRecord($condition['warehouse_id']);
            
            //采购价格依照分类下的SKU统计
            $sellSkuArr    = array_chunk($skuCodeArr, $condition['itemspages']);
            //去仓库下SKU和分类下SKU交集
            $mergeCodeArr  = array_intersect($skuCodeArr, $visitRecord);
            //将SKU分组 
            $newSkuCodeArr = array_chunk($mergeCodeArr, $condition['itemspages']);
        } else {
            //BI传递SKU编号 则优先使用SKU编号 （SKU数据格式保持统一）
            $skuCodeArr    = array($condition['sku_number']);
            $sellSkuArr    = array($condition['sku_number']);
            $newSkuCodeArr = array(array($condition['sku_number']));
        }
        //根据SKU编号获取 实际销售额 实际销售件数 平均采购价 拒收SKU数 SKU出库总数
        
        
        //分组计算SKU信息
        $stockSellQtyArr = array(); //SKU出库总数
        $salePriceArr    = array(); //平均采购价
        $tmsInfo         = array(); //TMS数据 实际销售额 实际销售件数 拒收SKU数
        foreach ($newSkuCodeArr as $skuCodes) {
            //平均采购价
            $salePrice       = $SkuInfo->calculatePrice($skuCodes, $condition['warehouse_id'], $condition['stime'], $condition['etime']);
            $salePriceArr    = array_merge($salePriceArr, $salePrice);
        }
        foreach ($sellSkuArr as $skuCodesMerge) {
            //SKU出库总数
            $stockSellQty    = $SkuInfo->stockSellQty($skuCodes, $condition['warehouse_id'], $condition['stime'], $condition['etime']);
            $stockSellQtyArr = array_merge($stockSellQtyArr, $stockSellQty);
        }
        //组合最终返回的SKU数组
        $reutrnSkuCodeArr = array();
        $salseSkuIndexArr = array(); //采购
        $stockSkuIndexArr = array(); //出库
        foreach ($salePriceArr as $salseSkuIndex => $value) {
            $salseSkuIndexArr[] = $salseSkuIndex;
        }
        foreach ($stockSellQtyArr as $stockSkuIndex => $value) {
            $stockSkuIndexArr[] = $stockSkuIndex;
        }
        $reutrnSkuCodeArr = array_merge($salseSkuIndexArr, $stockSkuIndexArr);
        //TMS数据 实际销售额 实际销售件数 拒收SKU数
        $tmsInfo = $SkuInfo->getTmsInfo($condition['stime'], $condition['etime'], $condition['warehouse_id']);
        $avearage_buy_price       = 0; //平均采购价
        $out_warehouse_sku_counts = 0; //SKU出库量
        $reject_sku_counts        = 0; //拒收SKU数量
        $actual_sale_amount       = 0; //实际销售额
        $actual_sale_count        = 0; //实际销售件数
        //遍历所有SKU 组合信息
        foreach ($reutrnSkuCodeArr as $key => $skuCode) {
            $index = rtrim($skuCode, '#');
            $returnSucess['list'][$key]['sku_number']               = $index;
            $returnSucess['list'][$key]['avearage_buy_price']       = 0;
            $returnSucess['list'][$key]['out_warehouse_sku_counts'] = 0;
            $returnSucess['list'][$key]['reject_sku_counts']        = 0;
            $returnSucess['list'][$key]['actual_sale_amount']       = 0;
            $returnSucess['list'][$key]['actual_sale_count']        = 0;
            
            //SKU出库量
            if (isset($stockSellQtyArr[$skuCode])) {
                $returnSucess['list'][$key]['out_warehouse_sku_counts'] = $stockSellQtyArr[$skuCode];
            }
            //平均采购价
            if (isset($salePriceArr[$skuCode])) {
                $returnSucess['list'][$key]['avearage_buy_price'] = $salePriceArr[$skuCode];
            }
            
            if (isset($tmsInfo[$index])) {
                //拒收SKU数量
                $returnSucess['list'][$key]['reject_sku_counts']  = $tmsInfo[$index]['reject_sku_counts'];
                //实际销售额
                $returnSucess['list'][$key]['actual_sale_amount'] = $tmsInfo[$index]['actual_sale_amount'];
                //实际销售件数
                $returnSucess['list'][$key]['actual_sale_count']  = $tmsInfo[$index]['actual_sale_count'];
            }
            //汇总
            $avearage_buy_price       += $returnSucess['list'][$key]['avearage_buy_price'];        //平均采购价
            $out_warehouse_sku_counts += $returnSucess['list'][$key]['out_warehouse_sku_counts']; //SKU出库量
            $reject_sku_counts        += $returnSucess['list'][$key]['reject_sku_counts'];        //拒收SKU数量
            $actual_sale_amount       += $returnSucess['list'][$key]['actual_sale_amount'];       //实际销售额
            $actual_sale_count        += $returnSucess['list'][$key]['actual_sale_count'];        //实际销售件数            
        }
        //汇总
        $returnSucess['total']['average_buy_price']        = $avearage_buy_price;
        $returnSucess['total']['out_warehouse_sku_counts'] = $out_warehouse_sku_counts;
        $returnSucess['total']['reject_sku_counts']        = $reject_sku_counts;
        $returnSucess['total']['actual_sale_amount']       = $actual_sale_amount;
        $returnSucess['total']['actual_sale_count']        = $actual_sale_count;
        
        $this->ajaxReturn($returnSucess);
    } 
}