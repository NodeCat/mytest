<?php
/**
* 前端商城订单 进入到wms 转换成出库单
* @author liang 2015-6-12
*/
namespace Wms\Api;
use Think\Controller;
class OrderApi extends CommApi{
    //根据订单 创建出库单
    public function addBillOut($order_ids=''){
        if(empty($order_ids)) {
            $order_ids = I('orderIds');
        }
        if(empty($order_ids)){
            $return = array('error_code' => '101', 'error_message' => 'param is empty' );
            $this->ajaxReturn($return);
        }

        $order_id_list = explode(',', $order_ids);
        $map = array('order_ids' => $order_id_list, 'itemsPerPage' => count($order_id_list));
        $order_lists = A('Common/Order','Logic')->order($map);
        //是否有订单创建了出库单指针 zhangchaoge
        $break = false;
        foreach($order_lists as $order){
            $order_info['info'] = $order;
            if(empty($order_info['info'])){
                $return = array('error_code' => '201', 'error_message' => 'order info is empty' );
                $this->ajaxReturn($return);
            }
            if(empty($order_info['info']['detail'])){
                $return = array('error_code' => '202', 'error_message' => 'detail is empty' );
                $this->ajaxReturn($return);
            }
            if(empty($order_info['info']['warehouse_id'])){
                $return = array('error_code' => '203', 'error_message' => 'warehouse_id is empty' );
                $this->ajaxReturn($return);
            }
            //首先判断此订单是否已经创建了出库单 zhangchaoge
            $map['refer_code'] = $order_info['info']['id'];
            $map['code']       = $order_info['info']['order_number'];
            $map['is_deleted'] = 0;
            $stockBillOutInfo = M('stock_bill_out')->where($map)->find();
            unset($map);
            if (!empty($stockBillOutInfo)) {
                continue;
            }
            //根据warehouse_id查询对应的仓库是否存在 如果不存在 不写入出库表
            $map['id'] = $order_info['info']['warehouse_id'];
            $warehouse = M('warehouse')->where($map)->find();
            if(empty($warehouse)){
                $return = array('error_code' => '204', 'error_message' => 'warehouse is not exsist' );
                $this->ajaxReturn($return);
            }
            //写入出库单
            $params['code'] = $order_info['info']['order_number'];
            $params['wh_id'] = $order_info['info']['warehouse_id'];
            $params['type'] = 'SO';
            $params['line_id'] = $order_info['info']['line_id'];
            $params['refer_code'] = $order_info['info']['id'];
            $params['delivery_date'] = str_replace('/', '-', $order_info['info']['deliver_date']);
            $params['delivery_time'] = $order_info['info']['deliver_time'];
            if (empty($order_info['info']['deliver_time_real'])) {
                $deliver_time_real = '';
            } elseif ($order_info['info']['deliver_time_real'] == 1) {
                $deliver_time_real = 'am';
            } else {
                $deliver_time_real = 'pm';
            }
            $params['delivery_ampm'] = $deliver_time_real;
            $params['customer_realname'] = $order_info['info']['realname'];
            $params['customer_id'] = $order_info['info']['user_id'];
            $params['delivery_address'] = $order_info['info']['deliver_addr'];
            $params['company_id'] = $order_info['info']['site_src'];
            $params['order_type'] = $order_info['info']['order_type'];
            $params['op_date'] = str_replace('/', '-', $order_info['info']['created_time']);
            $params['customer_phone'] = $order_info['info']['mobile'];
            $params['pay_type'] = $order_info['info']['pay_type'];
            $params['pay_status'] = $order_info['info']['pay_status'];

            foreach($order_info['info']['detail'] as $order_detail){
                $detail[] = array(
                    'pro_code' => $order_detail['sku_number'],
                    'order_qty' => $order_detail['quantity'],
                    'price' => $order_detail['price'],
                    'name' => $order_detail['name'],
                    'spec' => $order_detail['spec'],
                    'unit_id' => $order_detail['unit_id'],
                    'close_unit' => $order_detail['close_unit'],
                    );
            }

            $params['detail'] = $detail;
            A('StockOut','Logic')->addStockOut($params);
            unset($params);
            unset($order_info);
            unset($detail);
            $break = true;
        }
        
        if ($break == true) {
            $return = array('error_code' => '0', 'error_message' => 'succ' );
        } else {
            $return = array('error_code' => '205', 'error_message' => 'Have Not Make Stock Bill Out');
        }
        $this->ajaxReturn($return);
    }

    //客退入库单
    public function guestBackStorage()
    {
        $order_infos = I('json.');
        $return = array();
        if (!is_array($order_infos) || empty($order_infos)) {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = '请合法传参';
            $this->ajaxReturn($return);
        }

        $order_number = $order_infos['order_number'];
        $sku_info   = $order_infos['sku_info'];
        $pro_code_arr = array_column($sku_info, 'code');

        if (!$order_number || !$sku_info) {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = '请合法传参';
            $this->ajaxReturn($return);
        }

        //判断同一次退货退相同的商品
        if (count($pro_code_arr) != count(array_unique($pro_code_arr))) {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = '请不要退相同的商品！';
            $this->ajaxReturn($return);
        }
        //判断商品是否属于这个订单
        $is_set = $this->judgeCode($pro_code_arr, $order_number);
        if ($is_set['status'] === -1) {
            $intersection = $is_set['data'];
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = '该订单' . $order_number . '的'.implode(',', $intersection).'商品没有出库量，不能退货';
            $this->ajaxReturn($return);
        }

        //该订单是否存在对应的出库单并且状态为出库
        $bill_out = M('stock_bill_out');
        $map = array();
        $map['code'] = array('in',$order_number);
        $map['status'] = 2;
        $map['is_deleted'] = 0;
        $bill_out_code_res = $bill_out->where($map)->field('code,refer_code')->find();
        if (!$bill_out_code_res) {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = '该订单' . $order_number . '没有出库或不正常单';
            $this->ajaxReturn($return);
        }

        $order_code = $bill_out_code_res['code'];
        //判断订单退货量是否合法（退货量是否大于出库量）
        $order_code_qty = $this->judgeOutQty($sku_info, $order_code);
        if ($order_code_qty['status'] === -1) {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = $order_code_qty['msg'];
            $this->ajaxReturn($return);
        }

        //创建客退入库单
        //加入wms入库单 liuguangping
        $stockin_logic = A('Wms/StockIn','Logic');    
        $is_created = $stockin_logic->addWmsInOfGuestBack($sku_info, $order_code);
        if ($is_created) {
            $return['status'] = 0;
            $return['data']   = $is_created;
            $return['msg']    = '创建客退入库单成功';
            $this->ajaxReturn($return);
        } else {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = '创建客退入库单失败';
            $this->ajaxReturn($return);
        }      

    }

    //判断订单中的商品合法性
    public function judgeCode($pro_code_arr = array(), $order_number = '')
    {
        $return = array('status'=>-1,'data'=>'','msg'=>'出错！');
        $map = array();
        $map['a.refer_code'] = $order_number;
        $map['a.pro_code']   = array('in', $pro_code_arr);
        $map['a.is_deleted']   = 0;
        $map['b.is_deleted']   = 0;
        $stock_bill_out_container = M('stock_bill_out_container');
        $join = array('as a join stock_bill_out as b on b.code = a.refer_code');
        $res = $stock_bill_out_container->field('a.pro_code')->join($join)->where($map)->group('a.pro_code')->select();
        if (!$res) {
            $return = array('status'=>-1,'data'=>'','msg'=>'查询出库报错！');
        }
        $bill_pro_code_arr = array_column($res, 'pro_code');
        //以第一个数组为基础去差集
        $intersection = array_diff($pro_code_arr, $bill_pro_code_arr);
        if ($intersection){
            $return = array('status'=>-1,'data'=>$intersection,'msg'=>'ERO');
        } else {
            $return = array('status'=>0,'data'=>'','msg'=>'SUC');
        }
        return $return;

    }

    //判断订单退货量是否合法（退货量是否大于出库量） $order_infos 客退详细信息 order_code 订单单号 订单单号==出库单单
    public function judgeOutQty($order_infos = array(),$order_code)
    {
        $return = array('status'=>-1,'data'=>'','msg'=>'');
        if ($order_infos) {
            $pro_code_arr = array_column($order_infos, 'code');
            if ($pro_code_arr && $order_code) {
                $stock_bill_out_container = M('stock_bill_out_container');
                $map = array();
                $map['a.refer_code'] = array('in', $order_code);
                $map['a.pro_code']   = array('in', $pro_code_arr);
                $map['a.is_deleted']   = 0;
                $map['b.is_deleted']   = 0;

                $join = array('as a join stock_bill_out as b on b.code = a.refer_code');
                $res = $stock_bill_out_container->field('a.pro_code,a.refer_code,sum(a.qty) as qty,b.code as order_code')->join($join)->where($map)->group('a.pro_code,b.code')->select();
                if ($res) {
                    //查询入库单入库量
                    $bill_in_detail_m = M('stock_bill_in_detail');
                    $where = array();
                    $where['a.pro_code'] = array('in',$pro_code_arr);
                    $where['b.refer_code'] = array('in',$order_code);
                    $where['b.is_deleted'] = 0;
                    $where['a.is_deleted'] = 0;
                    $joins = array('as a join stock_bill_in as b on a.pid = b.id');
                    $bill_in_res = $bill_in_detail_m->field('a.pro_code,b.code,sum(a.expected_qty) as qty,b.refer_code as order_code')->join($joins)->where($where)->group('a.pro_code,b.refer_code')->select();
                    $expected_qty_arr = array();
                    foreach ($bill_in_res  as $index => $vals) {
                        $expected_qty_arr[$vals['order_code'].'_qty_'.$vals['pro_code']] = $vals['qty'];
                    }
                    //判断退货量是否大于出库量
                    //客退量大与出库量数据
                    $unqulify = array();
                    $error_unqulify = array();
                    foreach($res as $val){
                        foreach ($order_infos as $value) {
                            if ($value['code'] == $val['pro_code']){
                                //出库单的量是 出库量-入库量 等于这次该入库的量
                                $bill_in_qty = isset($expected_qty_arr[$val['order_code'].'_qty_'.$val['pro_code']])?$expected_qty_arr[$val['order_code'].'_qty_'.$val['pro_code']]:0;
                                $pro_qty = bcsub($val['qty'], $bill_in_qty, 2);
                                if (bccomp($value['qty'], $pro_qty, 2) == 1) {
                                    array_push($unqulify, $val['order_code']);
                                    $mes = "订单号" . $val['order_code'] . '中的商品编号为' . $val['pro_code'] ;
                                    array_push($error_unqulify,$mes);
                                }
                                //array_push($qulify, $val['order_code']);
                            }
                        }
                    }

                    if ($unqulify) {
                        $return = array('status'=>-1,'data'=>$unqulify,'msg'=>implode(',', $error_unqulify).'客退量大与客退量');
                    } else {
                        $return = array('status'=>0,'data'=>'','msg'=>'SUC');
                    }
                    
                } else {
                    $return = array('status'=>-1,'data'=>'','msg'=>'请选择正确的合法商品和订单');
                }
            } else {
                $return = array('status'=>-1,'data'=>'','msg'=>'请选择正确的合法商品和订单');
            }
        }
        return $return;
    }
    
}
