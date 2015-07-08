<?php
namespace Tms\Api;
use Think\Controller;

/**
 * 配送单接口
 */
class BillOutApi extends CommApi {

    protected $model;

    protected function _initialize () {}

    /**
     * [printBill 打印小票接口]
     * @param  [array]   $bill [订单数据]
     * @param  [integer] $ver  [WMS版本]
     * @return [json]          [打印指令与数据组合的json串]
     */
    public function printBill($bill, $ver = 1) {
        //获取有效的打印数据
        if($ver === 1){
            $pdata = $this->getPrintDataByOrder($bill);
        }
        else {
           $pdata = $this->getPrintDataByBill($bill); 
        }
        $head = $this->getHead($pdata);
        $list = $this->getList($pdata);
        $res = array_merge($head);
        return json_encode($res);
    }

    public function getPrintDataByOrder($order) {
        $data = array(
            'shop_name'    => $order['shop_name'],
            'order_id'     => $order['id'],
            'created_time' => $order['created_time'],
            'pay_status'   => $order['pay_status'],
            'final_price'  => $order['final_price'],
            'minus_amount' => $order['minus_amount'],
            'deliver_fee'  => $order['deliver_fee'],
            'deal_price'   => $order['deal_price'],
        );
        //获取签收商品列表和拒收商品列表
        foreach($order['detail'] as $val) {
            //一个签收商品数据
            $tmp_sign = array(
                'name'             => $val['name'],
                'actual_price'     => $val['single_price'],
                'actual_quantity'  => $val['actual_quantity'],
                'actual_sum_price' => $val['actual_sum_price'],
            );
            //一个拒收商品数据
            if($val['actual_quantity'] < $val['quantity']) {
                $tmp_refuse = array(
                    'name'      => $val['name'],
                    'price'     => $val['price'],
                    'quantity'  => $val['quantity'] - $val['actual_quantity'],
                    'sum_price' => 0,
                );
            }
            $sign[] = $tmp_sign;
            if(is_array($tmp_refuse)) {
                $refuse[] = $tmp_refuse;
            }
        }
        $data['sign']   = $sign;
        $data['refuse'] = $refuse;
        return $data;

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
            'center'           => array(0x1b, 0x61, 0x01),//居中
            'left'             => array(0x1b, 0x61, 0x00),//居左
            'text_big_size'    => array(0x1B, 0x57, 0x01),//宽高加倍
            'text_normal_size' => array(0x1B, 0x57, 0x00),//普通字号
            'no_hightlight'    => array(0x1B, 0x69, 0x00),//禁止反白打印
        );
        if(!$key || !isset($commands[$key])){
            return '';
        }
        $command = $this->byteToStr($commands[$key]);
        return $command;
    }

    /**
     * [getHead 小票头]
     * @return [type] [description]
     */
    public function getHead($bill) {
        $tmp = array();
        //头部信息
        $title = '大厨配送';
        $tmp[] = $this->getPrintCommand('center');
        $tmp[] = $this->getPrintCommand('text_big_size');
        $tmp[] = $title;
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        $shop_name = '店名：' . $bill['shop_name'];
        $order_id = 'ID：' . $bill['id'];
        $tmp[] = $this->formateLine($shop_name,$order_id);
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getUnderLine();
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('left');
        //下单时间：
        $created_time = '下单时间：' . $bill['created_time'];
        $tmp[] = $created_time;
        $tmp[] = $this->getPrintCommand('print');
        //支付方式
        $pay_status = '支付方式：' . $bill['pay_status'];
        $tmp[] = $pay_status;
        $tmp[] = $this->getPrintCommand('print');
        //订单金额
        $final_price = '订单金额：' . $bill['final_price'];
        $tmp[] = $final_price;
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getUnderLine();
        $tmp[] = $this->getPrintCommand('print');
        return $tmp;
    }

    /**
     * [getList 收退商品列表]
     * @param  [type] $bill [description]
     * @return [type]       [description]
     */
    public function getList($bill) {
        
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
