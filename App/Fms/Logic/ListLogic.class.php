<?php
namespace Fms\Logic;
class ListLogic {
	/*根据配送单id或者配送单号dist_code获得配送单信息
    *@param array(id,dist_code)
    *@return $dist结果集
    */
    public function distInfo($map){
        if(empty($map)){
            return null;
        }
        $map['is_deleted'] = 0;
        $dist = M('stock_wave_distribution')->where($map)->find();
        if (!empty($dist)) {
            unset($map);
            //查询条件为配送单id
            $map['pid'] = $dist['id'];
            $map['is_deleted'] = 0;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $dist_detail = M('stock_wave_distribution_detail')->where($map)->select();
            $dist['detail'] = $dist_detail;
        }
        return $dist;
    }
    /*根据出库单id获得出库单信息
    *@param id出库单id
    *@return $info结果集
    */
    public function bill_out_Info($id){
        if(empty($id)){
            return null;
        }
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $m = M('stock_bill_out');
        $bill_out = $m->where($map)->find();
        if (!empty($bill_out)) {
            unset($map);
            //查询条件为出库单id
            $map['pid'] = $id;
            $map['is_deleted'] = 0;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $bill_out_detail = M('stock_bill_out_detail')->where($map)->select();
            $bill_out['detail'] = $bill_out_detail;
        }
        return $bill_out;
    }
    public function get_status($status = 0){
        switch ($status) {
            case 0:
                $s = '已分拨';
                break;
            case 1:
                $s = '已装车';
                break;
            case 2:
                $s = '已签收';
                break;
            case 3:
                $s = '已拒收';
                break;
            case 4:
                $s = '已完成';
                break;
            default: 
                $s = '未处理'; 
                break;
        } 
        return $s;   
    }
    /**
     * [can_pay 查询配送单是否有退货，并且已创建拒收入库单]
     * @param  [int]    $dist_id [配送单id]
     * @return [int]            [若没有退货返回1;若有退货并且已经创建拒收入库单返回2,有退货没有创建拒收入库单返回3]
     */
    public function can_pay($dist_id) {
        $flag = false;
        $list_logic = A('Tms/List','Logic');
        //获得出库单列表
        unset($map); 
        $map['dist_id'] = $dist_id;
        $result = A('Wms/StockOut', 'Logic')->bill_out_list($map);
        if($result['status'] === 0) {
            $bill_outs = $result['list'];
        }
        
        //若查出的签收信息非空
        if (!empty($bill_outs)) { 
            for ($n = 0; $n < count($bill_outs); $n++) { 
                switch ($bill_outs[$n]['sign_status']) {
                    case '4':
                    case '2':
                        $sign_orders++; //已签收订单数加1
                        foreach ($bill_outs[$n]['detail'] as $value) {
                            unset($map);
                            $map['bill_out_detail_id'] = $value['id'];
                            $map['is_deleted'] = 0;
                            $sign_in_detail = M('tms_sign_in_detail')->where($map)->find();
                            $sign_qty = $sign_in_detail['real_sign_qty']; //签收数量
                            $delivery_qty = $value['delivery_qty']; //配送数量
                            $quantity = $delivery_qty - $sign_qty; //回仓数量
                            if($quantity > 0){
                                $flag = true;
                            }
                        } 
                        break;

                    case '3':
                        $flag = true;
                        break;
                    
                    default:
                        # code...
                        break;
                }
                 
            }
            //有拒收
            if ($flag) {
                //是否已创建拒收入库单
                $codes = array_column($bill_outs,'code');
                unset($map);
                $map['refer_code'] = array('in',$codes); 
                $map['type']       = 7;//拒收入库单
                $map['is_deleted'] = 0;
                $back_in = M('stock_bill_in')->where($map)->select();
                if (!empty($back_in)) {
                    return 2;
                } else {
                    return 3;
                }
            } else {
                //没有拒收
                return 1;
            }
        }
              
    }
    /**
     * [can_replace     查询订单是否有退货，并且已创建拒收入库单]
     * @param  [int]    $bill_out_id [出库单id]
     * @return [int]            [若没有退货返回1;若有退货并且已经创建拒收入库单返回2,有退货没有创建拒收入库单返回3]
     */
    public function can_replace($bill_out_id) {
        $flag = false;
        //获得出库单列表
        $bill_out = $this->bill_out_Info($bill_out_id);
        if (!empty($bill_out)) { 
            unset($map);
            $map['bill_out_id'] = $bill_out_id;
            $map['is_deleted']  = 0;
            $sign_data = M('stock_wave_distribution_detail')->where($map)->find();
            
            switch ($sign_data['status']) {
                case '2':
                    $sign_orders++; //已签收订单数加1
                    foreach ($bill_out['detail'] as $value) {
                        unset($map);
                        $map['bill_out_detail_id'] = $value['id'];
                        $map['is_deleted'] = 0;
                        $sign_in_detail = M('tms_sign_in_detail')->where($map)->find();
                        $sign_qty = $sign_in_detail['real_sign_qty']; //签收数量
                        $delivery_qty = $value['delivery_qty']; //配送数量
                        $quantity = $delivery_qty - $sign_qty; //回仓数量
                        if($quantity > 0){
                            $flag = true;
                        }
                    } 
                    break;

                case '3':
                    $flag = true;
                    break;
                
                default:
                    # code...
                    break;
            }
            //有拒收
            if ($flag) {
                //是否已创建拒收入库单
                unset($map);
                $map['refer_code'] = $bill_out['code']; 
                $map['is_deleted'] = 0;
                $back_in = M('stock_bill_in')->where($map)->select();
                if(!empty($back_in)) {
                    return 2;
                }else{      //如果没有查到相应的拒收入库单，直接返回3
                    return 3;
                }
            } else {
                //没有拒收
                return 1;
            }
        }
              
    }

    //获取此数字的中文大写
    public function cny($ns) 
    { 
        static $cnums=array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖"), 
            $cnyunits=array("圆","角","分"), 
            $grees=array("拾","佰","仟","万","拾","佰","仟","亿"); 
        list($ns1,$ns2)=explode(".",$ns,2); 
        $ns2=array_filter(array($ns2[1],$ns2[0])); 
        $ret=array_merge($ns2,array(implode("",$this->_cny_map_unit(str_split($ns1),$grees)),"")); 
        $ret=implode("",array_reverse($this->_cny_map_unit($ret,$cnyunits))); 
        return str_replace(array_keys($cnums),$cnums,$ret); 
    }
    public function _cny_map_unit($list,$units) 
    { 
        $ul=count($units); 
        $xs=array(); 
        foreach (array_reverse($list) as $x) { 
            $l=count($xs); 
            if ($x!="0" || !($l%4)) $n=($x=='0'?'':$x).($units[($l-1)%$ul]); 
            else $n=is_numeric($xs[0][0])?$x:''; 
            array_unshift($xs,$n); 
        } 
        return $xs; 
    }
    /**
     * [createRefund    创建退款单]
     * @param  [int]    $suborder_id [子订单id]
     * @return [int]    [成功返回status=0,失败返回status=－1]
     */
    public function createRefund($suborder_id = 0)
    {
        if ($suborder_id == 0) {
            $res = array(
                'status' => -1,
                'msg'    => '请提供有效的子订单id。',
            );
            return $res;
        }
        
        $map['refer_code'] = $suborder_id;
        $map['is_deleted'] = 0;
        $map['type']       = 1;//销售出库单
        $bill_out = M('stock_bill_out')->where($map)->find();
        if ($bill_out) {
            unset($map);
            $map['pid']        = $bill_out['id'];
            $map['is_deleted'] = 0;
            $detail = M('stock_bill_out_detail')->where($map)->select();
            if ($detail) {
                $bill_out['detail'] = $detail;
            } else {
                $res = array(
                    'status' => -1,
                    'msg'    => '找不到该订单对应的出库单详情。',
                );
                return $res;
            }
        } else {
            $res = array(
                'status' => -1,
                'msg'    => '找不到该订单对应的出库单。',
            );
            return $res;
        }
        $A = A('Common/Order','Logic');
        unset($map);
        $map['suborder_id'] = $suborder_id;
        $order_info = $A->oneOrder($map);
        if (!$order_info) {
            $res = array(
                'status' => -1,
                'msg'    => '找不到该订单。',
            );
            return $res;
        }
        unset($map);
        $map['bill_out_id'] = $bill_out['id'];
        $map['is_deleted']  = 0;
        $sign_data = M('stock_wave_distribution_detail')->where($map)->find();
        if ($sign_data) {
            unset($map);
            $map['pid'] = $sign_data['id'];
            $map['is_deleted'] = 0;
            $sign_detail = M('tms_sign_in_detail')->where($map)->select();
            if ($sign_detail) {
                $sign_data['detail'] = $sign_detail;
            } else {
                $res = array(
                    'status' => -1,
                    'msg'    => '找不到该订单对应的签收详情。',
                );
                return $res;
            }
        } else {
            $res = array(
                'status' => -1,
                'msg'    => '找不到该订单对应的签收信息。',
            );
            return $res;
        }
        $refund_model = D('Fms/Refund');
        //如果是已完成状态并且是已付款的才创建退款单
        if ($sign_data['status'] == 4 && $sign_data['pay_status'] == 1) {
            $data['reject_code']    = '';
            $data['suborder_id'] = $bill_out['refer_code'];
            $data['order_id']    = $order_info['order_id'];
            $data['reject_reason'] = $sign_data['reject_reason'];
            $data['refer_code']    = $bill_out['code']; //关联出库单号
            $data['pid']           = $bill_out['id'];   //关联出库单id
            $data['pay_type']      = 0;//微信退款
            $data['city_id']            = $order_info['city_id'];
            $data['city_name']          = $order_info['city_name'];
            $data['shop_name']     = $order_info['shop_name'];
            $data['customer_id']   = $order_info['user_id'];
            $data['customer_name'] = $order_info['username'];
            $data['customer_mobile'] = $order_info['mobile'];
            $data['created_user']    = session('user.uid');
            $data['created_time']    = get_time();
            $data['update_user']     = session('user.uid');
            $data['update_time']     = get_time();
            $data['wh_id']           = $bill_out['wh_id'];

            foreach ($bill_out['detail'] as $key => $value) {
                $lack = $value['order_qty'] - $value['delivery_qty'];
                if ($lack > 0) {
                    unset($det);
                    unset($map);
                    $map['where'] = array (
                        'sku_number' => $value['pro_code'],
                    );
                    $category = $A->getCategoryBySku($map);
                    $catename = explode('-->',$category['cate_name']);
                    $data['type'] = '2';    //缺货退款单
                    $det['pid'] = $data['id'];
                    $det['primary_category']    = $category['path'][0];
                    $det['primary_category_cn'] = $catename[0];
                    $det['pro_code']            = $value['pro_code'];
                    $det['pro_name']            = $value['pro_name'];
                    $det['price']               = $value['price'];
                    $det['reject_qty']          = $lack;
                    $det['reject_price']        = bcmul($value['price'],$lack,2);
                    $data['sum_reject_price']  += $det['reject_price'];
                    $det['created_user']    = session('user.uid');
                    $det['created_time']    = get_time();
                    $det['update_user']     = session('user.uid');
                    $det['update_time']     = get_time();
                    $data['detail'][] = $det;
                }
            }
            if (!empty($data['detail'])) {
                //判断是否创建过退款单
                unset($map);
                $map['refer_code'] = $data['refer_code'];
                $map['type']       = '2';//缺货退款单
                $map['is_deleted'] = 0;
                $ishave = $refund_model->where($map)->find();
                if (!$ishave) {
                    $res1 = $refund_model->relation('detail')->add($data);
                    logs($res1,'未处理，创建退款单','fms_refund');
                }
            }
            unset($data['detail']);
            unset($data['sum_reject_price']);
            //拒收入库单号
            unset($map);
            $map['refer_code']      = $bill_out['code']; //关联出库单号
            $map['is_deleted']      = 0;
            $map['type']            = '7';//拒收入库单
            $bill_in = M('stock_bill_in')->where($map)->find();
            if ($bill_in) {
                $data['reject_code']    = $bill_in['code'];
            }
            foreach ($bill_out['detail'] as $key => $value) {
                
                $sign_qty = 0;
                foreach ($sign_data['detail'] as $k => $val) {
                    if ($val['bill_out_detail_id'] == $value['id']) {
                        $sign_qty = $val['real_sign_qty'];
                    }
                }
                $rej_qty = $value['delivery_qty'] - $sign_qty;
                if ($rej_qty <= 0) {
                    continue;
                }
                unset($det);
                unset($map);
                $map['where'] = array (
                    'sku_number' => $value['pro_code'],
                );
                $category = $A->getCategoryBySku($map);
                $catename = explode('-->',$category['cate_name']);
                $data['type'] = '1';    //拒收退款单
                $det['pid'] = $data['id'];
                $det['primary_category']    = $category['path'][0];
                $det['primary_category_cn'] = $catename[0];
                $det['pro_code']            = $value['pro_code'];
                $det['pro_name']            = $value['pro_name'];
                $det['price']               = $value['price'];
                $det['reject_qty']          = $rej_qty;
                $det['reject_price']        = bcmul($value['price'],$rej_qty,2);
                $data['sum_reject_price']  += $det['reject_price'];
                $det['created_user']    = session('user.uid');
                $det['created_time']    = get_time();
                $det['update_user']     = session('user.uid');
                $det['update_time']     = get_time();
                $data['detail'][] = $det;
            }
            if (!empty($data['detail'])) {
                //判断是否创建过退款单
                unset($map);
                $map['refer_code'] = $data['refer_code'];
                $map['type']       = '1';//拒收退款单
                $map['is_deleted'] = 0;
                $ishave = $refund_model->where($map)->find();
                if (!$ishave) {
                    $res2 = $refund_model->relation('detail')->add($data);
                    logs($res2,'未处理，创建退款单','fms_refund');
                }
            }

            if ($res1 || $res2) {
                $res = array(
                    'status' => 0,
                    'msg'    => '创建退款单成功。',
                );
                return $res;
            } else {
                $res = array(
                    'status' => -1,
                    'msg'    => '创建退款单失败。',
                );
                return $res;
            }

            $res = array(
                'status' => -1,
                'msg'    => '没有退货，无需创建退款单。',
            );
            return $res;
        } else {
            $res = array(
                'status' => -1,
                'msg'    => '该订单不是已完成状态或者不是已付款的订单。',
            );
            return $res;
        }
        
    }
}