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

            foreach($order_info['info']['detail'] as $order_detail){
                $detail[] = array(
                    'pro_code' => $order_detail['sku_number'],
                    'order_qty' => $order_detail['quantity'],
                    'price' => $order_detail['price'],
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
    public function guestBackStorage(){
        $order_infos = I('back_storage_infos')?I('back_storage_infos'):I('json.back_storage_infos');
        $return = array();
        if (!$order_infos) {
            $return['status'] = -1;
            $return['data']   = '';
            $return['msg']    = '请合法传参';
            $this->ajaxReturn($return);
        }
        
        $order_code_arr = array_column($order_infos, 'order_code');
        $pro_code_arr = array_column($order_infos, 'pro_code');
        $order_code_num = count($order_code_arr);

        if ($order_code_num != count(array_unique($pro_code_arr))) {
            $return['status'] = -1;
            $return['data']   = '';
            $return['msg']    = '请不要重复退相同的商品！';
            $this->ajaxReturn($return);            
        }

        //得到订单单号查询出库id
        $bill_out = M('stock_bill_out');
        $map = array();
        $map['code'] = array('in',$order_code_arr);
        $map['status'] = 2;
        $bill_out_code_res = $bill_out->where($map)->field('code,refer_code')->select();
        //合法出库单号|订单号
        $bill_out_code_arr = array_column($bill_out_code_res, 'code');

        //判断订单合法性
        $order_code_res = $this->judgeOrder($order_code_arr,$bill_out_code_arr);
        if ($order_code_res['status'] === -1) {
            $return['status'] = -1;
            $return['data']   = '订单为' . implode(',', $order_code_res['data']) . '有问题，原因是订单还没有出库或不正常单';
            $return['msg']    = '订单为' . implode(',', $order_code_res['data']) . '有问题，原因是订单还没有出库或不正常单';
            $this->ajaxReturn($return);
        }

        //判断订单退货量是否合法（退货量是否大于出库量）
        $order_code_qty = $this->judgeOutQty($order_infos, $bill_out_code_arr);

        if ($order_code_qty['status'] === -1) {
            $return['status'] = -1;
            $return['data']   = $order_code_qty['msg'];
            $return['msg']    = $order_code_qty['msg'];
            $this->ajaxReturn($return);
        }

        //创建客退入库单
        //加入wms入库单 liuguangping
        $stockin_logic = A('Wms/StockIn','Logic');    
        $is_created = $stockin_logic->addWmsInOfGuestBack($order_infos);
        if ($is_created) {
            $return['status'] = 0;
            $return['data']   = '客退成功';
            $return['msg']    = '客退成功';
            $this->ajaxReturn($return);
        } else {
            $return['status'] = -1;
            $return['data']   = '客退失败';
            $return['msg']    = '客退失败';
            $this->ajaxReturn($return);
        }      
    }

    //判断订单合法性 @order_code_arr 订单号集合 bill_out_code_arr 合法的订单集合
    public function judgeOrder($order_code_arr = array(), $bill_out_code_arr = array()){
        $return = array('status'=>-1,'data'=>'','msg'=>'');
        if ($order_code_arr) {
            
            //以第一个数组为基础去差集
            $intersection = array_diff($order_code_arr, $bill_out_code_arr);
            if ($intersection){
                $return = array('status'=>-1,'data'=>$intersection,'msg'=>'ERO');
            } else {
                $return = array('status'=>0,'data'=>'','msg'=>'SUC');
            }

        }

        return $return;
    }

    //判断订单退货量是否合法（退货量是否大于出库量） $order_infos 客退详细信息 $bill_out_code_arr 合法的出库单号
    public function judgeOutQty($order_infos = array(), $bill_out_code_arr = array()){
        $return = array('status'=>-1,'data'=>'','msg'=>'');
        if ($order_infos) {
            $pro_code_arr = array_column($order_infos, 'pro_code');
            $order_code_arr = array_column($order_infos, 'order_code');
            if ($pro_code_arr && $bill_out_code_arr) {
                $stock_bill_out_container = M('stock_bill_out_container');
                $map = array();
                $map['a.refer_code'] = array('in', $bill_out_code_arr);
                $map['a.pro_code']   = array('in', $pro_code_arr);
                $map['a.is_deleted']   = 0;

                $join = array('as a join stock_bill_out as b on b.code = a.refer_code');
                $res = $stock_bill_out_container->field('a.pro_code,a.refer_code,sum(a.qty) as qty,b.code as order_code')->join($join)->where($map)->group('a.pro_code,b.code')->select();
                if ($res) {
                    //查询入库单入库量
                    $bill_in_detail_m = M('stock_bill_in_detail');
                    $where = array();
                    $where['a.pro_code'] = array('in',$pro_code_arr);
                    $where['b.refer_code'] = array('in',$order_code_arr);
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
                    //合法的订单
                    $qulify   = array();
                    foreach($res as $val){
                        foreach ($order_infos as $value) {
                            if ( $value['order_code'] == $val['order_code'] && $value['pro_code'] == $val['pro_code']){
                                //出库单的量是 出库量-入库量 等于这次该入库的量
                                $bill_in_qty = isset($expected_qty_arr[$val['order_code'].'_qty_'.$val['pro_code']])?$expected_qty_arr[$val['order_code'].'_qty_'.$val['pro_code']]:0;
                                $pro_qty = bcsub($val['qty'], $bill_in_qty, 2);
                                if (bccomp($value['pro_qty'], $pro_qty, 2) == 1) {
                                    array_push($unqulify, $val['order_code']);
                                    $mes = "订单号" . $val['order_code'] . '中的商品编号为' . $val['pro_code'] ;
                                    array_push($error_unqulify,$mes);
                                }
                                array_push($qulify, $val['order_code']);
                            }
                        }
                    }

                    if ($unqulify) {
                        $return = array('status'=>-1,'data'=>$unqulify,'msg'=>implode(',', $error_unqulify).'客退量大与出库量');
                    } else {
                        if ($qulify) {
                            
                            $judge_code = $this->judgeOrder($order_code_arr, $qulify);
                            if ($judge_code['status'] === -1) {
                                $return = array('status'=>-1,'data'=>$judge_code['data'],'msg'=>'订单为' . implode(',', $judge_code['data']) . '有问题，原因是订单还没有出库或不正常单');
                            } else {
                                $return = array('status'=>0,'data'=>'','msg'=>'SUC');
                            }
                        } else {
                            $return = array('status'=>-1,'data'=>'','msg'=>'请选择正确的合法商品和订单');
                        }
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
