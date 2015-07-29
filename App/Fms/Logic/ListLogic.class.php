<?php
namespace Fms\Logic;
class ListLogic {
	/*根据配送单id或者配送单号dist_code获得配送单信息
    *@param array(id,dist_code)
    *@return $dist结果集
    */
    protected function distInfo($map){
        if(empty($map)){
            return null;
        }
        $map['is_deleted'] = 0;
        $dist = M('stock_wave_distribution')->where($map)->find();
        unset($map);
        //查询条件为配送单id
        $map['pid'] = $dist['id'];
        $map['is_deleted'] = 0;
        //根据配送单id查配送详情单里与出库单相关联的出库单id
        $dist_detail = M('stock_wave_distribution_detail')->where($map)->select();
        $dist['detail'] = $dist_detail;
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
}