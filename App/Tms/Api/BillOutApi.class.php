<?php
namespace Tms\Api;
use Think\Controller;

/**
 * 配送单接口
 */
class BillOutApi extends CommApi {

	protected $model;

	protected function _initialize () {
		$this->model = M('stock_bill_out');
	}

	public function printBill($bill_out_id = 0) {
		
	}
    
}
