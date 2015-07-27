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

class TransferLogicIn
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
        $stock_in_detail = M('stock_bill_detail');
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
        if ($wms_stock_in_r) {
            return false;
        }
        $data = array();
        $detail = array();
        foreach ($wms_stock_in_r as $key => $value) {
            //插入erp_transfer_in 调拨入库单
            $data['code'] = $wms_stock_in_r['code'];
            $data['wh_id_out'] = $wms_stock_in_r['code'];
            $data['wh_id_in'] = $wms_stock_in_r['wh_id'];
            $data['cat_total'] = $wms_stock_in_r['cat_total'];
            $data['qty_tobal'] = $wms_stock_in_r['qty_tobal'];
            $data['status'] = 'waiting';
            $data['created_time'] = get_time();
            $data['created_user'] = session('user.uid');
            $data['updated_time'] = get_time();
            $data['created_user'] = session('user.uid');
            if($pid = $erp_transfer_in->add($data)) {
                
            }
        }


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



}
/* End of file TransferLogic.class.php */
/* Location: ./Application/Logic/TransferLogic.class.php */