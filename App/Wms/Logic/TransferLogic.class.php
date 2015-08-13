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
namespace Wms\Logic;

class TransferLogic
{
    
    public function create_transfer($parma = array(), $detail = array())
    {
        $transfer_detail_model = M('erp_transfer_detail');
        $transfer_model = M('erp_transfer');
        if (!$parma || !$detail) {
            return false;
        }
        if ($pid = $transfer_model->add($parma)) {//echo $transfer_model->getLastSql();die;
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
                    $stock_bill_out['op_date'] = get_time();
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
                            $stock_bill_out_detail[$key]['former_qty'] = $value['plan_transfer_qty'];
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



}
/* End of file TransferLogic.class.php */
/* Location: ./Application/Logic/TransferLogic.class.php */