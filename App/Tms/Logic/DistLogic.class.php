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
}