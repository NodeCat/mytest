<?php
namespace Wms\Api;
class StockOutApi {
   public function stockout() {
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
            $post = json_decode(file_get_contents("php://input"),true);
        }
        else{
            $post = I('post.');
        }
       /*if(empty($post) || !array_key_exists('biz_type', $post) || !array_key_exists('line_name', $post) || !array_key_exists('product_list', $post) || !array_key_exists('delivery_date', $post)) {
            $data = array('error_code' => '201', 'error_message' => 'parameter error', 'data' => '' );
            $this->_return_json($data);
        }*/
        
        $stock_out = M('stock_bill_out');
        $stock_detail = M('stock_bill_out_detail');

        $map['code'] = get_sn('out',$post['wh_id']);
        $map['wh_id'] = $post['picking_type_id'];
        $map['line_name'] = $post['line_name'];
        $map['op_date'] = $post['delivery_date'];
        $map['type'] = 1;//$post['type'];
        $map['status'] = 1;
        $map['process_type'] = 1;
        $map['refused_type'] = 1;
        $stock_out_id = $stock_out->add($map);
        if(empty($stock_out_id)) {
            $return = array('error_code' => '401', 'error_message' => 'created stockout bill error', 'data' => '' );
            echo json_encode($return);exit;
        }
        $total = 0;
        
        $pro_codes = array_column($post['product_list'],'product_code');
        $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        if(empty($pms)) {
            $return = array('error_code' => '501', 'error_message' => 'pms infomation error', 'data' => '' );
            echo json_encode($return);exit;
        }
        foreach($post['product_list'] as $val) {
            $detail['pid'] = $stock_out_id;
            $detail['pro_code'] = $val['product_code'];
            $detail['order_qty'] = $val['qty'];
            $detail['pro_name'] = $pms[$val['product_code']]['name'];  
            $detail['pro_attrs'] = $pms[$val['product_code']]['pro_attrs'][0]['name'] . ":" . $pms[$val['product_code']]['pro_attrs'][0]['val'] . "," . $pms[$val['product_code']]['pro_attrs'][1]['name'] . ":" . $pms[$val['product_code']]['pro_attrs'][1]['val'];
            $detail['status'] = 1;

            $total += $val['qty'];
            $res = $stock_detail->add($detail);
            if(empty($res)) {
                $return = array('error_code' => '501', 'error_message' => 'created detail error', 'data' => '' );
                echo json_encode($return);exit;
            }
        }
        
        unset($map);
        $data['total_qty'] = $total;
        $map['id'] = $stock_out_id;
        $stock_out->where($map)->save($data);
        
        $return = array('error_code' => '0', 'error_message' => 'success', 'data' => '' );
        echo json_encode($return);exit;
        
    } 
}
