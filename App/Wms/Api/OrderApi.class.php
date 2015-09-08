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
        $order_logic = A('Wms/Order','Logic');
        $is_set = $order_logic->judgeCode($pro_code_arr, $order_number);
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
        $order_code_qty = $order_logic->judgeOutQty($sku_info, $order_code);
        if ($order_code_qty['status'] === -1) {
            $return['status'] = 1;
            $return['data']   = '';
            $return['msg']    = $order_code_qty['msg'];
            $this->ajaxReturn($return);
        }

        //创建客退入库单
        //加入wms入库单 liuguangping
        $stockin_logic = A('Wms/StockIn','Logic');    
        $is_created = $stockin_logic->addWmsInOfBack($sku_info, $order_code, 3);
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
}
