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
    public function printBill($bill) {

        $data = array(
            array(0x1B, 0x57, 0x01),
            '小票打印               小票打印',
            '小票打印               小票打印',
            array(0x1b, 0x61, 0x01),
            '小票打印',
            array(0x1b, 0x61, 0x01),
            '小票打印小票打印',
            array(0x0A),
            array(0x1B, 0x40),
        );
        foreach ($data as &$value) {
            $value = is_array($value) ? $this->byteToStr($value) : $value;
        }
        return json_encode($data);
    }

    /**
     * [getPrintCommand 获取一条打印指令]
     * @param  [type] $key [指令对应key]
     * @return [type]      [description]
     */
    public function getPrintCommand($key) {

        $commands = array(
            'reset'            => array(0x1B, 0x40),//复位打印机
            'print'            => array(0x0A),//打印并换行
            'text_big_size'    => array(0x1B, 0x57, 0x01),//宽高加倍
            'text_normal_size' => array(0x1B, 0x57, 0x00),//普通字号
            'no_hightlight'    => array(0x1B, 0x69, 0x00),//禁止反白打印
        );
        if(!$key || !isset($commands[$key])){
            return '';
        }
        $command = $this->byteToStr();
        return $command;
    }

    /**
     * [getHead 小票头]
     * @return [type] [description]
     */
    public function getHead() {

    }

    /**
     * [getUnderLine 返回一行横线]
     */
    public function getUnderLine() {
        return '--------------------------------';
    }

    /**
     * [formateLine 根据参数，返回一行处理好间隔的字符串用作打印]
     * @param  string $left   [左边字符]
     * @param  string $right  [右边字符]
     */
    public function formateLine($left = '', $right = '') {
        $max  = 32;
        $llen = mb_strwidth($left, 'utf-8');
        $rlen = mb_strwidth($left, 'utf-8');
        $space_len = $max - $llen - $rlen;
        $sapce = '';
        for ($i = 0; $i < $space_len; $i++) { 
            $space .= ' ';
        }
        return $left . $space . $right;
    }

    /**
     * [formateName 产品名称超过16汉字处理]
     * @param  string $name [产品名称]
     * @return [type]       [description]
     */
    public function formateName($name = '') {
        return mb_strimwidth($name, 0, 32, '', 'utf-8');
    }

    /**
     * [formatePros 格式化产品数据为可打印字符串]
     * @param  array  $data [产品数据(二维数组)]
     * @return [type]       [description]
     */
    public function formatePros($data = array()) {
        $res = array();
        if($data) {
            foreach ($data as $pro) {
                foreach ($value as $val) {
                    $res[] = $this->formateName($val['proname']);
                    $res[] = $this->formateLine($val['real_sign_qty'], $val['sum_price']);
                }
            }
        }

        return $res;
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
