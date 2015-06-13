<?php
namespace Wms\Logic;

class StockOutLogic{
	/**
	* 创建出库单
	*  
	* $params = array(
	* 	'wh_id'=>'',      //仓库id
    *   'type'=>'',       //出库单类型(具体查看配置中的出库单类型设置,默认是SO)
	*	'line_id'=>'',            //线路名称(目前存的就是线路的字符串)
    *   'refer_code'=>'',           //关联单据号
    *   'detail'=>array(array('pro_code'=>'','order_qty'=>''),
    *                         array('pro_code'=>'','order_qty'=>''),
    *                        ....
    *                           ),
	* )
	*
	*/
   public function addStockOut($params = array()) {
   		if(empty($params['wh_id']) || empty($params['detail'])){
   			return false;
   		}
        $stock_out = M('stock_bill_out');
        $stock_detail = M('stock_bill_out_detail');
        $stock_type = M('stock_bill_out_type');
        $user = M('user');

        //查找出库单类型
        $stock_out_type = isset($params['type'])? $params['type'] : 'SO';
        $map['type'] = $stock_out_type;
        $type = $stock_type->where($map)->getField('id');
        unset($map);

        $data['code'] = get_sn($stock_out_type, $params['wh_id']);
        $data['wh_id'] = $params['wh_id'];
        $data['line_id'] = isset($params['line_id'])? $params['line_id']:'';
        $data['type'] = $type;
        $data['status'] = 1;//创建出库单的初始状态默认是待出库
        $data['process_type'] = 1;//出库单处理类型默认是正常单
        $data['refused_type'] = 1;//出库单拒绝类型默认是空
        $data['refer_code'] = isset($params['refer_code'])? $params['refer_code'] : '';

        //请求pms数据
        $pro_codes = array_column($params['detail'],'pro_code');
        $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);

        //添加出库单
        $stock_out_id = $stock_out->add($data);

        $total = 0;//计算一个出库单的总出库数量

        //添加明细
        foreach($params['detail'] as $val) {
            $detail = array();
            $detail['pid'] = $stock_out_id;
            $detail['pro_code'] = $val['pro_code'];
            $detail['order_qty'] = $val['order_qty'];
            //如果是加工出库单 采购出库单，则默认的实际发货量为0，其余类型出库单默认发货量等于订单量
            if(in_array($stock_out_type , array('MNO','SO')) ) {
                $detail['delivery_qty'] = 0;
            }else {
                $detail['delivery_qty'] = $val['qty'];
            }
            $detail['pro_name'] = $pms[$val['product_code']]['name'];  
            //拼接货品的规格
            unset($detail['pro_attrs']);
            foreach($pms[$val['product_code']]['pro_attrs'] as $k=>$v) {
                $detail['pro_attrs'] .= $v['name'] . ":" . $v['val'] . ",";
            }
            $detail['pro_attrs'] = substr($detail['pro_attrs'], 0, strlen($detail['pro_attrs'])-1);
            $detail['status'] = 1;

            $total += $val['qty'];
            $res = $stock_detail->add($detail);
        }
        
        $stock_out_data['total_qty'] = $total;
        $map['id'] = $stock_out_id;
        $stock_out->where($map)->save($stock_out_data);
       
        return ture;
    } 
}