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

	/**
	 * [printBill 打印小票接口]
	 * @param  [integer] $bill_out_id [出库单ID]
	 * @return [json]                 [打印指令与数据组合的json串]
	 */
	public function printBill($bill_out_id = 0) {
        $data = array(
            array(0x1B, 0x57, 0x01),
            '小票打印               小票打印',
            '小票打印               小票打印',
            '小票打印               小票打印',
            '小票打印               小票打印',
            array(0x0A),
            array(0x1B, 0x40),
        );
        foreach ($data as &$value) {
            $value = is_array($value) ? $this->toStr($value) : $value;
         }
         // echo json_encode($data);
        $this->ajaxReturn($data);
    }

	/**
	 * [byteToStr Byte数组转字符串]
	 * @param  [array] $bytes  [byte数组]
	 * @return [string]        [转换后的字符串]
	 */
	public function byteToStr($bytes) { 
        $bytes = array_map('chr',$bytes);  
    	$str   = implode('',$bytes);  
    	return $str; 
    }
    
}
