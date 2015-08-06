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
}