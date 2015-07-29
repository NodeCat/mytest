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

class TransferInLogic
{
    //加入调拨入库单
    public function addErpIn($pass_reduce_ids)
    {
        if (empty($pass_reduce_ids)) {
            return false;
        }
        //根据出库单号获取调拨单
        $stock_out_m = M('stock_bill_out');
        $stock_in_m = M('stock_bill_in');
        $stock_in_detail = M('stock_bill_in_detail');
        $erp_transfer_m = M('erp_transfer');
        $erp_transfer_in = M('erp_transfer_in');
        $erp_transfer_in_detail = M('erp_transfer_in_detail');

        $map = array();

        //查到出库单
        $map['is_deleted'] = 0;
        $map['id'] = array('in',$pass_reduce_ids);
        $stock_out_r = $stock_out_m->field('refer_code')->where($map)->select();

        if (!$stock_out_r) {
            return false;
        }
        $transfer_codes = array_unique(array_column($stock_out_r, 'refer_code'));
        if (empty($transfer_codes)) {
            return false;
        }

        //根据调拨单获取入库单数据
        unset($map);
        $map['is_deleted'] = 0;
        $map['refer_code'] = array('in',$transfer_codes);

        $wms_stock_in_r = $stock_in_m->where($map)->select();
        if (!$wms_stock_in_r) {
            return false;
        }
        $data = array();
        $detail = array();
        foreach ($wms_stock_in_r as $key => $value) {

            //根据调拨单获取获取出库仓库id
            $wh_id_in_m['trf_code'] = $value['refer_code'];
            $erp_transfer_wout = $erp_transfer_m->where($wh_id_in_m)->getField('wh_id_out');

            //查找本次入库的catgory 种类数
            $array_m = array();
            $array_m['pid'] = $value['id'];
            $sumCateR = $stock_in_detail->where($array_m)->group('pro_code')->select();
            $sumCate['qty_tobal'] = $stock_in_detail->where($array_m)->sum('expected_qty');
            $sumCate['cat_total'] = count($sumCateR);
            //插入erp_transfer_in 调拨入库单
            $data['code'] = $value['code'];
            $data['wh_id_out'] = $erp_transfer_wout;
            $data['wh_id_in'] = $value['wh_id'];
            $data['cat_total'] = $sumCate['cat_total'];
            $data['qty_tobal'] = $sumCate['qty_tobal'];
            $data['status'] = 'waiting';
            $data['created_time'] = get_time();
            $data['updated_user'] = session('user.uid');
            $data['updated_time'] = get_time();
            $data['created_user'] = session('user.uid');
            if ($pid = $erp_transfer_in->add($data)) {
                $detal_in = $stock_in_detail->where($array_m)->select();
                if ($detal_in) {
                    $detail = array();
                    foreach ($detal_in as $ky => $val) {
                        //生产日期未加入
                        $detail[$ky]['pid'] = $pid;
                        $detail[$ky]['pro_code'] = $val['pro_code'];
                        $detail[$ky]['pro_name'] = $val['pro_name'];
                        $detail[$ky]['pro_attrs'] = $val['pro_attrs'];
                        $detail[$ky]['batch_code'] = $val['batch'];
                        $detail[$ky]['pro_uom'] = $val['pro_uom'];
                        $detail[$ky]['price_unit'] = $val['price_unit'];
                        $detail[$ky]['plan_in_qty'] = $val['expected_qty'];
                        $detail[$ky]['created_time'] = get_time();
                        $detail[$ky]['created_user'] = session('user.uid');
                        $detail[$ky]['updated_time'] = get_time();
                        $detail[$ky]['updated_user'] = session('user.uid');
                    }

                    $erp_transfer_in_detail->addAll($detail);
                }
            }
        }

        return true;
    }

    //修改收货数量 $in_code up,上架 receipt 收货
    public function updateStockInQty($in_code, $pro_code, $batch, $qty, $status = null,$is_up = 'receipt'){
        $erp_in_detail = M('erp_transfer_in_detail');
        $erp_transfer_in = M('erp_transfer_in');
        if (!$in_code || !$pro_code || !$batch || !$qty) {
            return false;
        }
        $map = array();
        $map['code'] = $in_code;
        $map['is_deleted'] = 0;
        $id = $erp_transfer_in->where($map)->getField('id');
        if (!$id) {
            return false;
        }

        unset($map);
        $map['pid'] = $id;
        $map['pro_code'] = $pro_code;
        $map['batch_code'] = $batch;
        if ($is_up == 'receipt') {
            $erp_in_detail->where($map)->setInc('prepare_qty',$qty);
            $erp_in_detail->where($map)->setInc('receipt_qty',$qty);
        } elseif ($is_up == 'up') {
            M('erp_transfer_in_detail')->where($map)->setDec('prepare_qty',$qty);
            M('erp_transfer_in_detail')->where($map)->setInc('done_qty',$qty);
            if($status == 'qualified'){
                M('erp_transfer_in_detail')->where($map)->setInc('qualified_qty',$qty);
            }
            if($status == 'unqualified'){
                M('erp_transfer_in_detail')->where($map)->setInc('unqualified_qty',$qty);
            }
        }

    }

    //修改erp状态
    public function updateTransferInStatus($in_code, $is_up = 'receipt'){
        $erp_in_detail = M('erp_transfer_in_detail');
        $erp_transfer_in = M('erp_transfer_in');
        if (!$in_code) {
            return false;
        }
        $map = array();
        $map['code'] = $in_code;
        $map['is_deleted'] = 0;
        $id = $erp_transfer_in->where($map)->getField('id');
        unset($map);
        $map['pid'] = $id;
        if ($is_up == 'receipt' && $id) {
            $in = $erp_in_detail->where($map)->field('receipt_qty')->select();
            foreach ($in as $key => $val) {
                if (intval($val['receipt_qty']*100) > 0){
                    unset($map);
                    $map['id'] = $id;
                    $data['status'] = 'waitingup';
                    M('erp_transfer_in')->where($map)->save($data);
                    return 2;
                }
            }

        } elseif ($is_up == 'up' && $id){
            $in = $erp_in_detail->where($map)->field('done_qty')->select();
            foreach ($in as $key => $val) {
                if (intval($val['done_qty']*100) > 0){
                    unset($map);
                    $map['id'] = $id;
                    $data['status'] = 'up';
                    M('erp_transfer_in')->where($map)->save($data);
                    return 2;
                }
            }
        }
        
        
        return 1;
    }



}
/* End of file TransferInLogic.class.php */
/* Location: ./Application/Logic/TransferInLogic.class.php */