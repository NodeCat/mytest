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
   		if(empty($params['wh_id'])){
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
        //查找仓库名
        unset($map);

        $map['code'] = get_sn($stock_out_type, $post['wh_id']);
        $map['wh_id'] = $wh_id;
        $map['line_id'] = isset($post['line_id'])? $post['line_id']:'';
        $map['op_date'] = isset($post['delivery_date'])? date('Y-m-d',strtotime($post['delivery_date'])) : '';
        $map['op_time'] = isset($post['delivery_time'])? $post['delivery_time'] : '';
        $map['type'] = $type;
        $map['status'] = 1;//创建出库单的初始状态默认是待出库
        $map['process_type'] = 1;//出库单处理类型默认是正常单
        $map['refused_type'] = 1;//出库单拒绝类型默认是空
        $map['refer_code'] = isset($post['refer_code'])? $post['refer_code'] : '';
        $map['created_time'] = get_time();
        $map['updated_time'] = get_time();       
        $map['created_user'] = $user_id;
        $map['updated_user'] = $user_id;
        //请求pms数据
        $pro_codes = array_column($post['product_list'],'product_code');
        $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        if(empty($pms)) {
            if(isset($post['return_type'])) {
                return false;
            }else {
                $return = array('error_code' => '501', 'error_message' => 'pms infomation error', 'data' => '' );
                $this->ajaxReturn($return);
                exit;
            }
        }
        //添加出库单
        $stock_out_id = $stock_out->add($map);
        if(empty($stock_out_id)) {
            if(isset($post['return_type'])) {
                return false;
            }else {
                $return = array('error_code' => '401', 'error_message' => 'created stockout bill error', 'data' => '' );
                $this->ajaxReturn($return);
                exit;
            }
        }

        $total = 0;//计算一个出库单的总出库数量

        //添加明细
        foreach($post['product_list'] as $val) {
            $detail['pid'] = $stock_out_id;
            $detail['pro_code'] = $val['product_code'];
            $detail['order_qty'] = $val['qty'];
            //如果是加工出库单，则默认的实际发货量为0，其余类型出库单默认发货量等于订单量
            if($stock_out_type == 'MNO') {
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
            if(empty($res)) {
                if(isset($post['return_type'])){
                    return false;
                }else {
                    $return = array('error_code' => '501', 'error_message' => 'created detail error', 'data' => '' );
                    $this->ajaxReturn($return);
                    exit;
                }
            }
        }
        
        unset($map);
        $data['total_qty'] = $total;
        $map['id'] = $stock_out_id;
        $stock_out->where($map)->save($data);
       
        if(isset($post['return_type'])) {
            return true;
        }else {
            $return = array('error_code' => '0', 'error_message' => 'success', 'data' => '' );
            $this->ajaxReturn($return);
            exit;
        }
    } 
}