<?php
namespace Wms\Api;
use Think\Controller;
class StockOutApi extends Controller{
   public function stockout() {
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
            $post = json_decode(file_get_contents("php://input"),true);
        }
        else{
            $post = I('post.');
        }
        
        $stock_out = M('stock_bill_out');
        $stock_detail = M('stock_bill_out_detail');
        $warehouse = M('warehouse');
        $stock_type = M('stock_bill_out_type');
        $user = M('user');
        //查找出库单类型
        $stock_out_type = isset($post['stock_out_type'])? $post['stock_out_type'] : 'SO';
        $map['type'] = $stock_out_type;
        $type = $stock_type->where($map)->getField('id');
        //查找仓库名
        unset($map);
        $map['code'] = $post['picking_type_id'];
        $wh_id = $warehouse->where($map)->getField('id');
        //查找用户id（默认用户名是api）
        unset($map);
        $map['username'] = 'api';
        $user_id = $user->where($map)->getField('id');

        unset($map);
        $map['code'] = get_sn($stock_out_type, $post['wh_id']);
        $map['wh_id'] = $wh_id;
        $map['line_name'] = isset($post['line_name'])? $post['line_name']:'';
        $map['op_date'] = isset($post['delivery_date'])? date('Y-m-d',strtotime($post['delivery_date'])) : '';
        $map['op_time'] = isset($post['delivery_time'])? $post['delivery_time'] : '';
        $map['type'] = $type;
        $map['status'] = 1;
        $map['process_type'] = 1;
        $map['refused_type'] = 1;
        $map['created_time'] = get_time();
        $map['updated_time'] = get_time();       
        $map['created_user'] = $user_id;
        $map['updated_user'] = $user_id;

        $stock_out_id = $stock_out->add($map);
        if(empty($stock_out_id)) {
            if(isset($post['return_type'])) {
                return false;
            }else {
                $return = array('error_code' => '401', 'error_message' => 'created stockout bill error', 'data' => '' );
                $this->ajaxReturn($return);
            }
        }
        $total = 0;
        $pro_codes = array_column($post['product_list'],'product_code');
        $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        if(empty($pms)) {
            if(isset($post['return_type'])) {
                return false;
            }else {
                $return = array('error_code' => '501', 'error_message' => 'pms infomation error', 'data' => '' );
                $this->ajaxReturn($return);
            }
        }
        foreach($post['product_list'] as $val) {
            $detail['pid'] = $stock_out_id;
            $detail['pro_code'] = $val['product_code'];
            $detail['order_qty'] = $val['qty'];
            $detail['delivery_qty'] = $val['qty'];
            $detail['pro_name'] = $pms[$val['product_code']]['name'];  
            $detail['pro_attrs'] = $pms[$val['product_code']]['pro_attrs'][0]['name'] . ":" . $pms[$val['product_code']]['pro_attrs'][0]['val'] . "," . $pms[$val['product_code']]['pro_attrs'][1]['name'] . ":" . $pms[$val['product_code']]['pro_attrs'][1]['val'];
            $detail['status'] = 1;

            $total += $val['qty'];
            $res = $stock_detail->add($detail);
            if(empty($res)) {
                if(isset($post['return_type'])){
                    return false;
                }else {
                    $return = array('error_code' => '501', 'error_message' => 'created detail error', 'data' => '' );
                    $this->ajaxReturn($return);
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
        }
    } 
}
