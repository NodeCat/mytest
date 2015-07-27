<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Wms\Logic;

class PurchaseOutLogic{

    /**
     * 根据条件获取要退货的商品
     * getOutInfoByPurchaseCode
     *  
     * @param Int $id 采购单id
     * @param String $purchaseCode 采购单单号
     * @param String $flg success 正品 error 残次
     * @param String $batch_code 批次
     * @author liuguangping@dachuwang.com
     * @return Array $returnRes;
     * 
     */
    public function getOutInfoByPurchaseCode($id, $purchaseCode, $batch_code, $flg='success'){
        $where = array();
        $returnRes = array();
        if($purchaseCode){
            $where['s.refer_code'] = $purchaseCode;
        }
        if($batch_code){
            $where['s.batch_code'] = $batch_code;
        }
        if($id){
            $where['p.pid'] = $id;
        }
        $where['s.status'] = 33;//上架
        $where['s.type']   = 1;//采购到库单
        $where['s.is_deleted'] = 0;
        $where['d.is_deleted'] = 0;
        $where['p.is_deleted'] = 0;
        $wh_id = M('stock_purchase')->where(array('id'=>$id))->getField('wh_id');
        if(!$wh_id){
            return false;
        }

        $m = M();
        $result = $m->table('stock_bill_in_detail as d')
        ->field('d.pro_uom,p.price_unit,d.pro_code,d.pro_name,d.pro_attrs,s.batch_code,d.done_qty')
        ->join('left join stock_bill_in as s on s.id=d.pid 
                left join stock_purchase_detail as p ON d.pro_code=p.pro_code')
        ->where($where)->select();
        $map = array();
        $map['batch'] = $batch_code;
        $map['is_deleted'] = 0;
        $map['wh_id'] = $wh_id;
        //查找商品是否进库存表 没有进库存则也加入结果集中
        if($result){
            foreach ($result as $ky => $val) {
                $whes = $map;
                $whes['pro_code'] = $val['pro_code'];
                if(!M('stock')->where($whes)->find()){
                   array_push($returnRes, $val);
                }
            }
        }

        $pro_codeR = array();
        if($flg == 'success'){
            $map['status'] = 'qualified';
            $pro_codeR = $this->getStockProCodeByStatus($map);
        }elseif($flg == 'error'){
            $map['status'] = 'unqualified';
            $pro_codeR = $this->getStockProCodeByStatus($map);
        }
        if($pro_codeR){
            if($result){
                foreach ($result as $key => $value) {
                    if(in_array($value['pro_code'],$pro_codeR)){
                        array_push($returnRes, $value);
                    }
                }
            }
        }
        
        return $returnRes;

    }

    public function getStockProCodeByStatus($map = array()){
        $stckM = M('stock');
        $successR = $stckM->field('pro_code')->where($map)->select();
        $pro_codeR = getSubByKey($successR,'pro_code');
        return $pro_codeR;
    }

    public function getInserDate($array = array(),$pid=0){
        if(!$array){
            return FALSE;
        }
        $resultR = array();
        if(!$array['wh_id']){
            return FALSE;
        }
        $wh_id = $array['wh_id'];
        $pros = $array['pros'];

        $plan_return_qtyA = $pros['plan_return_qty'];

        foreach ($pros as $key => $value) {
            foreach ($value as $ky => $val) {
              if($plan_return_qtyA[$ky]){
                $resultR[$ky][$key] = $val;
                $resultR[$ky]['wh_id'] = $wh_id;
                $resultR[$ky]['pid'] = $pid;
                $resultR[$ky]['created_user'] = UID;
                $resultR[$ky]['created_time'] = get_time();
                $resultR[$ky]['status'] = 0;
              }
                    
            }        
        }

        if($resultR){
            return $resultR;
        }else{
            return FALSE;
        }
    }

    public function addStockOut($addAll = array(),$param = array()){
        if(!$addAll || !$param){
            return FALSE;
        }
        $stockOut = M('stock_bill_out');
        $addStockOut = array();
        $addStockOut['code'] = get_sn('RTSG',$param['wh_id']);
        $addStockOut['wh_id'] = $param['wh_id'];
        $addStockOut['type'] = 3;
        $addStockOut['refer_code'] = $param['refer_code'];
        $addStockOut['notes'] = $param['remark'];
        $addStockOut['process_type'] = 1;
        $addStockOut['refused_type'] = 1;
        $addStockOut['status'] = 1;
        $addStockOut['created_user'] = UID;
        $addStockOut['created_time'] = get_time();
        $addStockOut['is_deleted'] = 0;
        $addStockOut['company_id'] = 1;
        $arrsum = 0;
        foreach($addAll as $vals){
            $arrsum += $vals['price_unit']*$vals['plan_return_qty'];
        }
        $addStockOut['total_amount'] = formatMoney($arrsum, 2);
        $addStockOut['total_qty'] = count($addAll);
        $addStockOut['order_type'] = 1;

        if($stockOut->create($addStockOut)){
            if($pid = $stockOut->add($addStockOut)){
                $insertAll = array();
                foreach ($addAll as $key => $value) {
                    $insertAll[$key]['pid'] = $pid;
                    $insertAll[$key]['wh_id'] = $param['wh_id'];
                    $insertAll[$key]['pro_code'] = $value['pro_code'];
                    $insertAll[$key]['pro_name'] = $value['pro_name'];
                    $insertAll[$key]['pro_attrs'] = $value['pro_attrs'];
                    $insertAll[$key]['price'] = $value['price_unit'];
                    $insertAll[$key]['order_qty'] = $value['plan_return_qty'];
                    $insertAll[$key]['status'] = 1;
                    $insertAll[$key]['created_user'] = UID;
                    $insertAll[$key]['created_time'] = get_time();
                    $insertAll[$key]['batch_code'] = $value['batch_code'];
                }

                if(M('stock_bill_out_detail')->addAll($insertAll)){
                    return TRUE;
                }else{
                    //做处理如果详细插入失败，则删除退货单
                    $stockOut->where(array('id'=>$pid))->save(array('is_deleted'=>1));
                    return FALSE;
                }
            }
        }else{
           return FALSE; 
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
            $distribution_logic = A('Distribution','Logic');
            $key = $bill_out_result['type'];
            $type = $distribution_logic->get_stock_bill_out_type($key);
            if ($type[$key] == 'RTSG') {
                $purchaseCode = $bill_out_result['refer_code'];
                if($purchaseCode){
                    $where['rtsg_code'] = $purchaseCode;
                    $purSave['status'] = 'refunded';
                    $purSave['updated_time'] = get_time();
                    $purchase_out = M('stock_purchase_out');
                    if($purchase_out->where($where)->save($purSave)){
                        $where = array();
                        $where['rtsg_code'] = $purchaseCode;
                        $id = $purchase_out->where($where)->getField('id');
                        unset($map);
                        $map['pid'] = $out_id;
                        $map['is_deleted'] = 0;
                        //修改采购退货单实际出库量
                        $detail = $stock_bill_out_detail->field('batch_code,pro_code,delivery_qty')->where($map)->select();
                        if($detail){
                            foreach ($detail as $vals) {
                                unset($map);
                                $map['pro_code'] = $vals['pro_code'];
                                $map['batch_code'] = $vals['batch_code'];
                                $map['pid'] = $id;
                                $saveDetail = array();
                                $saveDetail['updated_time'] = get_time();
                                $saveDetail['real_return_qty'] = $vals['delivery_qty'];
                                $purchase_out_detail->where($map)->save($saveDetail);
                            }
                            $return = TRUE;
                        }else{
                            $where = array();
                            $purSave = array();
                            $where['rtsg_code'] = $purchaseCode;
                            $purSave['status'] = 'tbr';
                            $purchase_out->where($where)->save($purSave);
                            continue;
                        }
                    }else{
                        continue;
                    }
                }else{
                    continue;
                }
            }elseif($type[$key] == 'STO'){
                //调拨出库
                //修改调拨单
                $transfer_code = $bill_out_result['refer_code'];//调拨单
                //wms和erp的出库单和wms出库单详细的详细关联单号
                $bill_out_code = $bill_out_result['code'];
                if($this->updateTransfer($transfer_code, $out_id)){
                    //修改erp 出库单
                    if ($this->erpUpdateOut($bill_out_code, $out_id)) {
                        //添加erp出库单详细
                        $this->insertErpContainer($bill_out_code);
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
        $data['updated_user'] = UID;
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
                    $saveDetail['updated_user'] = UID;
                    $saveDetail['stauts'] = 'refunded';
                    $saveDetail['real_out_qty'] = $vals['delivery_qty'];
                    $erp_transfer_detail_m->where($map)->save($saveDetail);
                }
                $return = true;
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
        $
        $map = array();
        $data = array();
        $map['code'] = $bill_out_code;
        $data['stauts'] = 'refunded';
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
                    $saveDetail['updated_user'] = UID;
                    $saveDetail['stauts'] = 'refunded';
                    $saveDetail['real_out_qty'] = $vals['delivery_qty'];
                    $erp_transfer_detail_m->where($map)->save($saveDetail);
                }
                $return = true;
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
    public function insertErpContainer($bill_out_code){
        $stock_bill_out_container = M('stock_bill_out_container');
        $erp_bill_out_container = M('erp_transfer_out_container');
        $map['refer_code'] = $bill_out_code;
        $stock_container = $stock_bill_out_container->where($map)->select();
        if($stock_container){
            $data = array();
            $process_logic = A('Process', 'Logic');
            foreach ($stock_container as $key => $value) {
                $data[$key]['refer_code'] = $bill_out_code;
                $data[$key]['pro_code'] = $value['pro_code'];
                $data[$key]['batch'] = get_batch($value['batch']);
                $data[$key]['price'] = $process_logic->get_price_by_sku($value['batch'], $value['pro_code']);//平均价
                $data[$key]['pro_qty'] = $value['qty'];
                $data[$key]['location_id'] = $value['location_id'];
                $data[$key]['wh_id'] = $value['wh_id'];
                $data[$key]['created_time'] = get_time();
                $data[$key]['created_user'] = UID;
                $data[$key]['updated_time'] = get_time();
                $data[$key]['updated_user'] = UID;
            }
            $erp_bill_out_container->addAll($data);
            return true;
        } else {
            return false;
        }
    }

}
/* End of file PurchaseOut.class.php */
/* Location: ./Application/Logic/PurchaseOut.class.php */