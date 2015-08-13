<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangping@dachuwang.com>
// +----------------------------------------------------------------------
namespace Erp\Logic;

class TransferLogic
{
    
    public function create_transfer($parma = array(), $detail = array())
    {
        $transfer_detail_model = M('erp_transfer_detail');
        $transfer_model = M('erp_transfer');
        if (!$parma || !$detail) {
            return false;
        }
        if ($pid = $transfer_model->add($parma)) {
            foreach ($detail as $key => $value) {
                $detail[$key]['pid'] = $pid;
            }
            if ($transfer_detail_model->addAll($detail)) {
                return $pid;
            }else{
                $transfer_model->delete($pid);
                return false;
            }
        } else {
            return false;
        }
    }

    public function get_transfer_all_sku_detail(&$data, $table = 'erp_transfer_detail')
    {  
        if (empty($data)) {
            $data['detail'] = '';
        } else {
            $pid = $data['id'];
            $where = array();
            $where['pid'] = $pid;
            $where['is_deleted'] = 0;
            $m = M($table);
            $data['detail'] = $m->where($where)->select();
            foreach ($data['detail'] as $key => $val) {
                $data['detail'][$key]['pro_names'] = '['.$val['pro_code'].'] '.$val['pro_name'] .'（'. $val['pro_attrs'].'）';
            }
        }

        return $data;
    }

    //生产插入出库表逻辑
    public function insertErpWmsTransferout($id)
    {
        if (!$id) {
            return false;
        }
        //插入erp调拨出库单
        $transfer = M('erp_transfer');
        $transfer_detail = M('erp_transfer_detail');
        $erp_transfer_out = M('erp_transfer_out');
        $erp_transfer_out_detail = M('erp_transfer_out_detail');
        $stock_bill_out_m = M('stock_bill_out');
        $stock_bill_out_detail_m = M('stock_bill_out_detail');

        $erp_out_arr = array();
        $stock_bill_out = array();
        $stock_bill_out_detail = array();
        $erp_out_arr_detail = array();
        $erp_transfer_where = array();
        $erp_transfer_where['id'] = $id;
        $transfer_re = $transfer->where($erp_transfer_where)->find();
        if (!$transfer_re) {
            return false;
        } else {
            //加入erp调拨出库单
            $erp_out_arr['code'] = get_sn('STO');
            $erp_out_arr['refer_code'] = $transfer_re['trf_code'];
            $erp_out_arr['wh_id_out'] = $transfer_re['wh_id_out'];
            $erp_out_arr['wh_id_in'] = $transfer_re['wh_id_in'];
            $erp_out_arr['cat_total'] = $transfer_re['plan_cat_total'];
            $erp_out_arr['qty_tobal'] = $transfer_re['plan_qty_tobal'];
            $erp_out_arr['created_time'] = get_time();
            $erp_out_arr['status'] = 'tbr';
            $erp_out_arr['created_user'] = UID;
            $erp_out_arr['updated_time'] = get_time();
            $erp_out_arr['updated_user'] = UID;
            if ($erp_out_pid = $erp_transfer_out->add($erp_out_arr)) {
                //调拨出库单详细
                $erp_transfer_detail_where = array();
                $erp_transfer_detail_where['pid'] = $id;
                $transferdetail = $transfer_detail->where($erp_transfer_detail_where)->select();
                foreach ($transferdetail as $key => $value) {
                    $erp_out_arr_detail[$key]['pid'] = $erp_out_pid;
                    $erp_out_arr_detail[$key]['pro_code'] = $value['pro_code'];
                    $erp_out_arr_detail[$key]['pro_name'] = $value['pro_name'];
                    $erp_out_arr_detail[$key]['pro_attrs'] = $value['pro_attrs'];
                    $erp_out_arr_detail[$key]['pro_uom'] = $value['pro_uom'];
                    $erp_out_arr_detail[$key]['status'] = 'tbr';
                    $erp_out_arr_detail[$key]['created_time'] = get_time();
                    $erp_out_arr_detail[$key]['plan_transfer_qty'] = $value['plan_transfer_qty'];
                    $erp_out_arr_detail[$key]['created_user'] = UID;
                    $erp_out_arr_detail[$key]['updated_time'] = get_time();
                    $erp_out_arr_detail[$key]['updated_user'] = UID;
                }
                if ($erp_transfer_out_detail->addAll($erp_out_arr_detail)) {
                    //添加wms 出库单和详细
                    $stock_bill_out['code'] = $erp_out_arr['code'];
                    $stock_bill_out['wh_id'] = $transfer_re['wh_id_out'];
                    $stock_bill_out['type'] = 5;
                    $stock_bill_out['refer_code'] = $transfer_re['trf_code'];
                    $stock_bill_out['notes'] = $transfer_re['remark'];
                    $stock_bill_out['status'] = 1;
                    $stock_bill_out['created_time'] = get_time();
                    $stock_bill_out['created_user'] = UID;
                    $stock_bill_out['updated_user'] = UID;
                    $stock_bill_out['updated_time'] = get_time();

                    $stock_bill_out['total_qty'] = $transfer_re['plan_qty_tobal'];
                    if ($stock_pid = $stock_bill_out_m->add($stock_bill_out)) {
                        foreach ($transferdetail as $key => $value) {
                            $stock_bill_out_detail[$key]['pid'] = $stock_pid;
                            $stock_bill_out_detail[$key]['pro_code'] = $value['pro_code'];
                            $stock_bill_out_detail[$key]['wh_id'] = $transfer_re['wh_id_out'];
                            $stock_bill_out_detail[$key]['pro_name'] = $value['pro_name'];
                            $stock_bill_out_detail[$key]['pro_attrs'] = $value['pro_attrs'];
                            $stock_bill_out_detail[$key]['price'] = '0';
                            $stock_bill_out_detail[$key]['status'] = 1;
                            $stock_bill_out_detail[$key]['order_qty'] = $value['plan_transfer_qty'];
                            $stock_bill_out_detail[$key]['created_time'] = get_time();
                            $stock_bill_out_detail[$key]['measure_unit'] = $value['pro_uom'];
                            $stock_bill_out_detail[$key]['created_user'] = UID;
                            $stock_bill_out_detail[$key]['updated_time'] = get_time();
                            $stock_bill_out_detail[$key]['updated_user'] = UID;

                        }

                        if ($stock_bill_out_detail_m->addAll($stock_bill_out_detail)) {
                            return true;
                        }else{
                            //插入wms 出库单详细失败！
                            //插入wms出库失败
                            //删除erp 出库单 详细
                            $erp_transfer_out->delete($erp_out_pid);
                            $erp_out_arr_detail_where['pid'] = $erp_out_pid;
                            $erp_transfer_out_detail->where($erp_transfer_detail_where)->delete();
                            //wms 出库单
                            $stock_bill_out_m->delete($stock_pid);
                            return false;
                        }

                    }else{
                        //插入wms出库失败
                        //删除erp 出库单 详细
                        $erp_transfer_out->delete($erp_out_pid);
                        $erp_out_arr_detail_where['pid'] = $erp_out_pid;
                        $erp_transfer_out_detail->where($erp_transfer_detail_where)->delete();
                        return false;

                    }
                }else{
                    $erp_transfer_out->delete($erp_out_pid);
                    return false;
                }

            }else{
                return false;
            }
        }

    }

    //作废做的相应操作
    public function updateStatus($id)
    {
        if (!$id) {
            return false;
        }

        //修改状态 调拨出库单
        $data['status'] = 'cancelled';
        $data['updated_user'] = UID;
        $data['updated_time'] = get_time();
        $map = array();
        $map['id'] = $id;
        $erp_transfer_m = M('erp_transfer');
        $fist = $erp_transfer_m->where($map)->find();
        //修改调拨单状态 为作废
        $back = $erp_transfer_m->where($map)->save($data);
        if (!$back) {
            return false;
        }else{
            $map = array();
            $map['pid'] = $id;
            $data['status'] =  'cancelled';
            //修改调拨单详细状态 为作废
            $back = M('erp_transfer_detail')->where($map)->save($data);
            if (!$back) {
                $map = array();
                $map['id'] = $id;
                $data['status'] = $fist['status'];
                $back = M('erp_transfer')->where($map)->save($data);
                return false;
            } else {
                //驳回的调拨单作废走得逻辑
                if ($fist['status'] == 'rejected' || $fist['status'] == 'audit') {
                    return true;
                }
                //修改调拨单出库单erp详细状态 为作废
                $map = array();
                $map['refer_code'] = $fist['trf_code'];
                $send = M('erp_transfer_out')->where($map)->find();
                $data['status'] = 'cancelled';
                $back = M('erp_transfer_out')->where($map)->save($data);
                $maps['pid'] = $send['id'];
                $back2 = M('erp_transfer_out_detail')->where($maps)->save($data);
                if ($back && $back2) {
                    //删除调拨单出库单wms详细状态 为作废
                    $stock_bill_out_m = M('stock_bill_out');
                    $stock_bill_out_detail_m = M('stock_bill_out_detail');
                    $map = array();
                    $map['refer_code'] = $fist['trf_code'];
                    $thrid = $stock_bill_out_m->where($map)->find();
                    $stock_bill_out_m->where($map)->delete();
                    $map = array();
                    $map['pid'] = $thrid['id'];
                    M('erp_transfer_out_detail')->where($map)->delete();
                    return true;
                } else {
                    $data['status'] = $send['status'];
                    M('erp_transfer_out')->where($map)->save($data);
                    M('erp_transfer_out_detail')->where($maps)->save($data);
                    return false;
                }

            }
        }



    }

    /**
     * 根据出库单id修改采购退货单状态
     * upPurchaseOutStatus
     *  
     * @param Int $out_id 出库单id
     * @author liuguangping@dachuwang.com
     * @return Boolture;
     * 
     */
    public function upPurchaseOutStatus($idArr = array()){
        if(!$idArr) return FALSE;
        $return = array();
        foreach ($idArr as $out_id) {
            if(!$out_id){
                continue;
            }
            $M = M('stock_bill_out');
            $stock_bill_out_detail = M('stock_bill_out_detail');
            $purchase_out_detail = M('stock_purchase_out_detail');
            $map = array();
            $where = array();
            $map['id'] = $out_id;
            $bill_out_result = $M->where($map)->find();
            //用于多种类型出库单库存判断扩展 liuguangping
            $distribution_logic = A('Wms/Distribution','Logic');
            $key = $bill_out_result['type'];
            $type = $distribution_logic->get_stock_bill_out_type($key);
            if($type[$key] == 'STO'){
                //调拨出库
                //修改调拨单
                $transfer_code = $bill_out_result['refer_code'];//调拨单
                //wms和erp的出库单和wms出库单详细的详细关联单号
                $bill_out_code = $bill_out_result['code'];
                //检查是否是调拨单
                $erp_map = array();
                $erp_map['trf_code'] = $transfer_code;
                if (!M('erp_transfer')->where($erp_map)->find()) {
                    continue;
                }
                if($this->updateTransfer($transfer_code, $out_id)){
                    //修改erp 出库单
                    if ($this->erpUpdateOut($bill_out_code, $out_id)) {
                        //添加erp出库单详细
                        $this->insertErpContainer($out_id);
                        $return = true;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

            }else{
                continue;
            }
        }
        return $return;

    }

        //修改调拨单@transfer_code 调拨单 ，出库单id
    public function updateTransfer($transfer_code, $out_id)
    {
        $data = array();
        $map = array();
        $return = false;
        $data['status'] = 'refunded';
        $data['updated_time'] = get_time();
        $data['updated_user'] = session('user.uid');
        $map['trf_code'] = $transfer_code;
        $transfer_m = M('erp_transfer');
        $wms_out_detail = M('stock_bill_out_detail');
        $erp_transfer_detail_m = M('erp_transfer_detail');
        if ($transfer_m->where($map)->save($data)) {
            $where = array();
            $where['trf_code'] = $transfer_code;
            $id = $transfer_m->where($where)->getField('id');

            //查找出库单详细表
            unset($map);
            $map['pid'] = $out_id;
            $map['is_deleted'] = 0;
            //修改采购退货单实际出库量
            $detail = $wms_out_detail->field('pro_code,delivery_qty')->where($map)->select();
            if($detail){
                foreach ($detail as $vals) {
                    unset($map);
                    //一个调拨单里面只有一个sku
                    $map['pro_code'] = $vals['pro_code'];
                    $map['pid'] = $id;
                    $saveDetail = array();
                    $saveDetail['updated_time'] = get_time();
                    $saveDetail['updated_user'] = session('user.uid');
                    $saveDetail['status'] = 'refunded';
                    $saveDetail['real_out_qty'] = $vals['delivery_qty'];
                    $erp_transfer_detail_m->where($map)->save($saveDetail);
                }
                return true;
            }else{
                $where = array();
                $purSave = array();
                $where['trf_code'] = $transfer_code;
                $purSave['status'] = 'tbr';
                $transfer_m->where($where)->save($purSave);
                return false;
            }

        } else {
            return false;
        }
    }

    //修改ERP 出库单 $bill_out_code erp出库单和wms 出库单 @out_id wms出库单id
    public function erpUpdateOut($bill_out_code,  $out_id){
        $transfer_out_m = M('erp_transfer_out');
        $transfer_out_detail_m = M('erp_transfer_out_detail');
        $wms_out_detail = M('stock_bill_out_detail');
        $map = array();
        $data = array();
        $map['code'] = $bill_out_code;
        $data['status'] = 'refunded';
        $data['updated_time'] = get_time();
        $data['updated_user'] = session('user.uid');
        if ($transfer_out_m->where($map)->save($data)) {
            $where = array();
            $where['code'] = $bill_out_code;
            $id = $transfer_out_m->where($where)->getField('id');

            //查找出库单详细表
            unset($map);
            $map['pid'] = $out_id;
            $map['is_deleted'] = 0;
            //修改采购退货单实际出库量
            $detail = $wms_out_detail->field('pro_code,delivery_qty')->where($map)->select();
            if($detail){
                foreach ($detail as $vals) {
                    unset($map);
                    //一个调拨单里面只有一个sku
                    $map['pro_code'] = $vals['pro_code'];
                    $map['pid'] = $id;
                    $saveDetail = array();
                    $saveDetail['updated_time'] = get_time();
                    $saveDetail['updated_user'] = session('user.uid');
                    $saveDetail['status'] = 'refunded';
                    $saveDetail['real_out_qty'] = $vals['delivery_qty'];
                    $transfer_out_detail_m->where($map)->save($saveDetail);
                }
                return true;
            }else{
                $where = array();
                $purSave = array();
                $where['code'] = $bill_out_code;
                $purSave['status'] = 'tbr';
                $transfer_out_m->where($where)->save($purSave);
                return false;
            }
        } else {
            return false;
        }
    }

    //写入ERP出库详细详细表
    public function insertErpContainer($out_id){
        $stock_bill_out_container = M('stock_bill_out_container');
        $erp_bill_out_container = M('erp_transfer_out_container');
        $map['o.id'] = $out_id;
        $map['o.status'] = 2;//已出库
        $map['o.type'] = 5;//调拨单
        $map['o.is_deleted'] = 0;
        $stock_container = $stock_bill_out_container->field('c.*,o.refer_code as code_refer')->join(' as c left join stock_bill_out as o on o.code = c.refer_code')->where($map)->select();

        if($stock_container){
            $data = array();
            $process_logic = A('Process', 'Logic');
            foreach ($stock_container as $key => $value) {
                $data[$key]['refer_code'] = $value['code_refer'];
                $data[$key]['pro_code'] = $value['pro_code'];
                $data[$key]['batch'] = get_batch($value['batch']);
                $data[$key]['price'] = $process_logic->get_price_by_sku($value['batch'], $value['pro_code']);//平均价
                $data[$key]['pro_qty'] = $value['qty'];
                $data[$key]['location_id'] = $value['location_id'];
                $data[$key]['wh_id'] = $value['wh_id'];
                $data[$key]['created_time'] = get_time();
                $data[$key]['created_user'] = session('user.uid');
                $data[$key]['updated_time'] = get_time();
                $data[$key]['updated_user'] = session('user.uid');
            }
            $erp_bill_out_container->addAll($data);
            return true;
        } else {
            return false;
        }
    }

    //处理调拨逻辑
    public function transferController($pass_reduce_ids){
        //处理erp调拨实际收货量和状态
        //$distribution_logic = A('Erp/Transfer','Logic');        
        $this->upPurchaseOutStatus($pass_reduce_ids);

        //加入wms入库单 liuguangping
        $stockin_logic = A('Wms/StockIn','Logic');        
        $stockin_logic->addWmsIn($pass_reduce_ids);

        //加入erp调拨入库单
        $erp_stockin_logic = A('Erp/TransferIn', 'Logic');
        $erp_stockin_logic->addErpIn($pass_reduce_ids);
    }


}
/* End of file TransferLogic.class.php */
/* Location: ./Application/Logic/TransferLogic.class.php */