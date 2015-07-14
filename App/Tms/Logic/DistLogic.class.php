<?php
namespace Tms\Logic;

class DistLogic {
    protected $wms_api_path = 'Wms/dist/';
    protected $server;
    protected $request;

    public function __construct() {
        $this->server  = C('HOP_API_PATH');
        import("Common.Lib.HttpCurl");
        $this->request = new \HttpCurl();
    }

    //订单详情--WMS
    public function distInfo($id) {
        $action = 'distInfo';
        $res = R($this->wms_api_path . $action, array($id),'Api');
        return $res;
    }

    //出库单列表--WMS
    public function billOut($map = array()){
        $action = 'lists';
        //wms API 获取出库单列表
        $res = R($this->wms_api_path . $action, array($map),'Api');
        //配送单关联订单信息
        if($res) {
            $order_ids = array();
            foreach ($res as $re) {
                $order_ids[] = $re['refer_code'];
            }
            $map['order_ids'] = $order_ids;
            $map['itemsPerPage'] = count($res);
            unset($map['dist_id']);
            $cA = A('Common/Order','Logic');
            $orders = $cA->order($map);
            //配送单关联订单信息
            foreach ($res as &$bill) {
                foreach ($orders as $value) {
                    if($bill['refer_code'] == $value['id']) {
                        $bill['order_info'] = $value;
                    }
                }
            }
            $res = array(
                'orders'     => $res,
                'orderCount' => count($orders),
            );
        }
        return $res;
    }
}