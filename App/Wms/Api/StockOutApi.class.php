<?php
namespace Wms\Api;
class StockOutApi {
   public function test() {
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
            $post = json_decode(file_get_contents("php://input"),true);
        }
        else{
            $post = I('post.');
        }
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
       
        $total = 0;
        
        $pro_codes = array_column($post['product_list'],'product_code');
        $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        //var_dump($pms); exit;
        foreach($post['product_list'] as $val) {
            $detail['pid'] = $stock_out_id;
            $detail['pro_code'] = $val['product_code'];
            $detail['order_qty'] = $val['qty'];
            $detail['pro_name'] = $pms[$val['product_code']]['name'];  
            $detail['pro_attrs'] = $pms[$val['product_code']]['pro_attrs'][0]['name'] . "：" . $pms[$val['product_code']]['pro_attrs'][0]['val'] . "，" . $pms[$val['product_code']]['pro_attrs'][1]['name'] . "：" . $pms[$val['product_code']]['pro_attrs'][1]['val'];

            $total += $val['qty'];
            $stock_detail->add($detail);
        }
        
        unset($map);
        $data['total_qty'] = $total;
        $map['id'] = $stock_out_id;
        $stock_out->where($map)->save($data);

        dump(json_encode($stock_out_id));exit;
    } 
}
