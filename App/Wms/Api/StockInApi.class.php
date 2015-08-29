<?php
/**
* 入库单操作
* @author liuguangping 2015-8-27
*/
namespace Wms\Api;
use Think\Controller;
class StockInApi extends CommApi{

    //获取入库单状态'0' => '草稿','21'=>'待收货','31'=>'待上架','33'=>'已上架',//'04'=>'已作废'
    public function getStockInStatus(){
        //入库单单号
        $in_code = I('json.in_code');
        $return = array('status' => 1, 'data' => '', "msg"=>"错误信息");
        if (!$in_code) {
            $return = array('status' => 1, 'data' => '', "msg"=>"请合法传参");
            $this->ajaxReturn($return);
        }
        $stock_in_m = M('stock_bill_in');
        $map = array();
        $map['code'] = $in_code;
        $res = $stock_in_m->field('status,is_deleted')->where($map)->find();
        if ($res) {

            $result = array();
            if ($res['is_deleted'] == 1) {
                $status = "-1";//关闭
            } else {
               $status = $res['status']; 
            }

            if ($status == '21') {
                $status = "received";//待收货
            } elseif ($status == '31'){
                $status = "shelves";//待上架
            } elseif($status == '33'){
                $status = "up";//已上架
            } elseif($status == '0') {
                $status = "draft";//草稿
            } elseif($status == '-1'){
                $status = "close";//关闭
            } else {
                $status = "error";//错误
            }

            $result['status'] = $status;
            $result['in_code'] = $in_code;
            $return['status'] = 0;
            $return['data'] = $result;
            $return['msg'] = "获取状态成功";
            $this->ajaxReturn($return);
        } else {
            $return = array('status' => 1, 'data' => '', "msg"=>"请合法传单，没有该单数据");
            $this->ajaxReturn($return);
        }
    }

    //入库单关闭
    public function stockInClosed(){
         //入库单单号
        $in_code = I('json.in_code');
        $return = array('status' => 1, 'data' => '', "msg"=>"错误信息");
        if (!$in_code) {
            $return = array('status' => 1, 'data' => '', "msg"=>"请合法传参");
            $this->ajaxReturn($return);
        }
        $stock_in_m = M('stock_bill_in');
        $map = array();
        $map['code'] = $in_code;
        $result = $stock_in_m->field('id,status,is_deleted')->where($map)->find();
        if (!$result || !$result['status'] || $result['is_deleted'] == 1) {
            $return = array('status' => 1, 'data' => '', "msg"=>"没有找到该单数据或该单已被删除");
            $this->ajaxReturn($return);
        }
        if ($result['status'] == "21") {
            unset($map);
            $map['id']   = $result['id'];
            $data = array();
            $data['is_deleted'] = 1;
            $data['updated_time'] = date('Y-m-d H:i:s');
            if ($stock_in_m->where($map)->save($data)) {
                unset($map);
                $map['pid'] = $result['id'];
                unset($data);
                $data['is_deleted'] = 1;
                $data['updated_time'] = date('Y-m-d H:i:s');
                M('stock_bill_in_detail')->where($map)->save($data);
                $return['status'] = 0;
                $return['data'] = '';
                $return['msg'] = "关闭成功！";
                $this->ajaxReturn($return);
            } else {
                $return = array('status' => 1, 'data' => '', "msg"=>"该订单关闭失败");
                $this->ajaxReturn($return); 
            }

        } else {
           $return = array('status' => 1, 'data' => '', "msg"=>"该订单状态不能关闭");
           $this->ajaxReturn($return); 
        }
    }
}
