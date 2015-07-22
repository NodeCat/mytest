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
            $returnError['msg'] = 'The warehouse_id Is Not Have';
            $this->ajaxReturn($returnError);
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
        
        //根据SKU编号获取 实时在库量 实时可售量 平均采购价 平均销售价 拒收SKU数 SKU出库总数
        
        //将SKU分组 
        $newSkuCodeArr = array();
        $i = $k = 1;
        foreach ($skuCodeArr as $value) {
            if ($i > $condition['itemspages'] * $k) {
                $k++;$i++;
                $newSkuCodeArr[$k][] = $value;
            } else {
                $i++;
                $newSkuCodeArr[$k][] = $value;
            }
        }
        //分组计算SKU信息
        $priceAndQtyArr = array(); //平均销售价和SKU出库总数
        $stockInArr     = array(); //实时在库量
        $stockSellInArr = array(); //实时可售量
        $salePriceArr   = array(); //平均采购价
        foreach ($newSkuCodeArr as $skuCodes) {
            //平均销售价和SKU出库总数
            $priceAndQty    = $SkuInfo->calculateSellPrice($skuCodes, $condition['warehouse_id'], $condition['stime'], $condition['etime']);
            $priceAndQtyArr = array_merge($priceAndQtyArr, $priceAndQty);
            //实时在库量
            $stockIn        = $SkuInfo->getActualStockBySkuCode($skuCodes, $condition['warehouse_id']);
            $stockInArr     = array_merge($stockInArr, $stockIn);
            //实时可售量
            $stockSellIn    = $SkuInfo->getActualSellBySkuCode($skuCodes, $condition['warehouse_id']);
            $stockSellInArr = array_merge($stockSellInArr, $stockSellIn);
            //平均采购价
            $salePrice      = $SkuInfo->calculatePrice($skuCodes, $condition['warehouse_id'], $condition['stime'], $condition['etime']);
            $salePriceArr   = array_merge($salePriceArr, $salePrice);
        }
        
        $quantity_inwarehouse     = 0; //实时在库量
        $quantity_sale            = 0; //实时可售量
        $average_buy_price        = 0; //平均采购价
        $average_sale_price       = 0; //平均销售价
        $out_warehouse_sku_counts = 0; //SKU出库量
        $reject_sku_counts        = 0; //拒收SKU数量
        //遍历所有SKU 组合信息
        foreach ($skuCodeArr as $key => $skuCode) {
            $returnSucess['list'][$key]['sku_number']               = $skuCode;
            $returnSucess['list'][$key]['quantity_inwarehouse']     = 0;
            $returnSucess['list'][$key]['quantity_sale']            = 0;
            $returnSucess['list'][$key]['average_buy_price']        = 0;
            $returnSucess['list'][$key]['average_sale_price']       = 0;
            $returnSucess['list'][$key]['out_warehouse_sku_counts'] = 0;
            
            foreach ($stockInArr as $indexIn => $stockInQty) {
                //实时在库量
                $trueIndexIn = intval(rtrim($indexIn, '#'));
                if ($trueIndexIn == $skuCode) {
                    $returnSucess['list'][$key]['quantity_inwarehouse'] = $stockInQty;
                    unset($stockInArr[$indexIn]);
                    break;
                }
            }
            foreach ($stockSellInArr as $indexSell => $stockSellQty) {
                //实时可售量
                $trueIndexSell = intval(rtrim($indexSell, '#'));
                if ($trueIndexSell == $skuCode) {
                    $returnSucess['list'][$key]['quantity_sale'] =  $stockSellQty;
                    unset($stockSellInArr[$indexSell]);
                    break;
                }
            }
            foreach ($salePriceArr as $indexSale => $saleQty) {
                //平均采购价
                $trueIndexSale = intval(rtrim($indexSale, '#'));
                if ($trueIndexSale == $skuCode) {
                    $returnSucess['list'][$key]['average_buy_price'] = $saleQty;
                    unset($salePriceArr[$indexSale]);
                    break;
                }
            }
            foreach ($priceAndQtyArr as $indexPrice => $priceAndQtyVal) {
                $trueIndexPrice = intval(rtrim($indexPrice, '#'));
                if ($trueIndexPrice == $skuCode) {
                    //平均销售价
                    $returnSucess['list'][$key]['average_sale_price'] = $priceAndQtyVal['price'];
                    //SKU出库量
                    $returnSucess['list'][$key]['out_warehouse_sku_counts'] = $priceAndQtyVal['sum'];
                    unset($priceAndQtyArr[$indexPrice]);
                    break;
                }
            }
            
            //拒收SKU数量
            $returnSucess['list'][$key]['reject_sku_counts'] = 0;
            //汇总
            $quantity_inwarehouse     += $returnSucess['list'][$key]['quantity_inwarehouse'];     //实时在库量
            $quantity_sale            += $returnSucess['list'][$key]['quantity_sale'];            //实时可售量
            $average_buy_price        += $returnSucess['list'][$key]['average_buy_price'];        //平均采购价
            $average_sale_price       += $returnSucess['list'][$key]['average_sale_price'];       //平均销售价
            $out_warehouse_sku_counts += $returnSucess['list'][$key]['out_warehouse_sku_counts']; //SKU出库量
            $reject_sku_counts        += $returnSucess['list'][$key]['reject_sku_counts'];        //拒收SKU数量
            
        }
        //汇总
        $returnSucess['total']['quantity_inwarehouse']     = $quantity_inwarehouse;
        $returnSucess['total']['quantity_sale']            = $quantity_sale;
        $returnSucess['total']['average_buy_price']        = $average_buy_price;
        $returnSucess['total']['average_sale_price']       = $average_sale_price;
        $returnSucess['total']['out_warehouse_sku_counts'] = $out_warehouse_sku_counts;
        $returnSucess['total']['reject_sku_counts']        = $reject_sku_counts;
        
        $this->ajaxReturn($returnSucess);
    } 
}