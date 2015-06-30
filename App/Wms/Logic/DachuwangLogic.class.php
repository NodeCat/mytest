<?php
namespace Wms\Logic;

class DachuwangLogic{
    protected $server = '';
    protected $request ;
    public function __construct(){
        $this->server = C('DACHUWANG_API_PATH');
        import("Common.Lib.HttpCurl");
        $this->request = new \HttpCurl();
    }

    /**通知实时库存接口
    * 参数
    * $params = array(
    * 'wh_id' => xxx, 仓库id
    * 'pro_code' => xxx, sku货号
    * 'type' => xxxx 如果是普通订单的出库引发的库存变化，这个字段传outgoing其余情况都不传
    * 'qty' => xxx 本次库存的变化量
    * );
    * 
    */
    public function notice_stock_update($params = array()){
        $data = array(
        	0=>array(
	            'picking_type_id' => $params['wh_id'],
	            'product_code' => $params['pro_code'],
	            'type' => $params['type'],
	            'qty' => $params['qty'],
	        	),
        	);
        $url = $this->server.'/mall_stock/notice_stock_update';
        $json_data = json_encode($data);
        $result = $this->request->post($url,$json_data);
        return json_decode($result,true);
        
    }
}