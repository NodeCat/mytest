<?php
namespace Tms\Api;
use Think\Controller;

/**
 * 配送单接口
 */
class BillOutApi extends CommApi {

    protected $model;
    protected $max_len = 32;//打印机单行最大宽度
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
        $foot = $this->getFoot($pdata);
        $res = array_merge($head, $list, $foot);
        return json_encode($res);
    }

    /**
     * [getPrintDataByOrder 根据订单组合一组打印数据]
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function getPrintDataByOrder($order) {
        //订单描述
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
     * [getPrintDataByBill 根据出库单组合一组打印数据]
     * @param  [type] $bill [description]
     * @return [type]       [description]
     */
    public function getPrintDataByBill($bill) {
        //订单描述
        $data = array(
            'shop_name'    => $bill['shop_name'],
            'order_id'     => $bill['refer_code'],
            'created_time' => $bill['order_info']['created_time'],
            'pay_status'   => $bill['pay_status'],
            'final_price'  => $bill['order_info']['final_price'],
            'minus_amount' => $bill['minus_amount'],
            'deliver_fee'  => $bill['deliver_fee'],
            'deal_price'   => $bill['deal_price'],
        );
        //签收列表
        foreach($bill['detail'] as $val) {
            //一个签收商品数据
            $tmp_sign = array(
                'name'             => $val['pro_name'],
                'actual_price'     => $val['single_price'],
                'actual_quantity'  => $val['quantity'],
                'actual_sum_price' => $val['sum_price'],
            );
            $sign[] = $tmp_sign;
        }
        //退货列表
        if(is_array($bill['refuse_bill'])) {
            foreach($bill['refuse_bill'] as $v) {
                $tmp_refuse = array(
                    'name'      => $v['pro_name'],
                    'price'     => $v['price_unit'],
                    'quantity'  => $v['expected_qty'],
                    'sum_price' => 0,
                );
                $refuse[] = $tmp_refuse;
            }
        }
        $data['sign'] = $sign;
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
            'right'            => array(0x1b, 0x61, 0x02),//居右
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
        $title = '-大厨配送-';
        $tmp[] = $this->getPrintCommand('center');
        $tmp[] = $title;
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('right');
        $order_id = 'ID：' . $bill['order_id'];
        $tmp[] = $order_id;
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('left');
        $shop_name = '店名：' . $bill['shop_name'];
        $tmp[] = $this->formateName($shop_name);
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getUnderLine();
        $tmp[] = $this->getPrintCommand('print');
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
        $tmp = array();
        $tmp[] = $this->getPrintCommand('left');
        $tmp[] = $this->formateLine('商品名称', '单价  数量  小计  ');
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getLineText('签收商品');
        $tmp[] = $this->getPrintCommand('print');
        //签收列表
        foreach($bill['sign'] as $val) {
            $tmp[] = $this->getPrintCommand('left');
            $tmp[] = $this->formateName($val['name']);
            $tmp[] = $this->getPrintCommand('print');
            $tmp[] = $this->getPrintCommand('right');
            $pqs   = $val['actual_price'] . '   ';
            $pqs  .= $val['actual_quantity'] .'   ';
            $pqs  .= $val['actual_sum_price'] . '  ';
            $tmp[] = $pqs;
            $tmp[] = $this->getPrintCommand('print');
        }
        //退货列表
        if(is_array($bill['refuse'])) {
            $tmp[] = $this->getLineText('退货商品');
            $tmp[] = $this->getPrintCommand('print');
            foreach($bill['refuse'] as $val) {
                $tmp[] = $this->getPrintCommand('left');
                $tmp[] = $this->formateName($val['name']);
                $tmp[] = $this->getPrintCommand('print');
                $tmp[] = $this->getPrintCommand('right');
                $pqs   = $val['price'] . '   ';
                $pqs  .= $val['quantity'] .'   ';
                $pqs  .= $val['sum_price'] . '  ';
                $tmp[] = $pqs;
                $tmp[] = $this->getPrintCommand('print');
            }
        }
        $tmp[] = $this->getPrintCommand('center');
        $tmp[] = $this->getUnderLine();
        $tmp[] = $this->getPrintCommand('print');

        return $tmp;

    }

    /**
     * [getFoot 小票底部]
     * @param  [type] $bill [description]
     * @return [type]       [description]
     */
    public function getFoot($bill) {
        $tmp = array();
        //优惠
        $tmp[] = $this->getPrintCommand('left');
        $tmp[] = $this->formateLine('活动优惠', '-' . $bill['minus_amount']);
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->formateLine('微信支付', '-' . 0);
        $tmp[] = $this->getPrintCommand('print');
        //运费
        $tmp[] = $this->formateLine('运费', '+' . $bill['deliver_fee']);
        $tmp[] = $this->getPrintCommand('print');
        //下划线
        $tmp[] = $this->getPrintCommand('center');
        $tmp[] = $this->getUnderLine();
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        //合计
        $tmp[] = $this->formateLine('合计', $bill['deal_price'] . ' 元');
        $tmp[] = $this->getPrintCommand('print');
        //确认信息
        $tmp[] = $this->getPrintCommand('left');
        $tmp[] = '本人确认以上交易，已完成签收';
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        //签名
        $tmp[] = '签名：';
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        //售后电话
        $tmp[] = $this->getPrintCommand('right');
        $tmp[] = '售后电话：401-xxxxxxx';
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        $tmp[] = $this->getPrintCommand('print');
        return $tmp;
    }

    /**
     * [getUnderLine 返回一行横线]
     */
    public function getUnderLine() {
        $line = '';
        for($i=0; $i < $this->max_len; $i++) { 
            $line .= '-';
        }
        return $line;
    }

    /**
     * [getUnderLine 返回一行中间包含文字的横线]
     */
    public function getLineText($text) {
        $len = mb_strwidth($text, 'utf-8');
        $lines = $this->max_len - $len;
        $mid = $lines/2;
        if(is_int($mid)) {
            $llen = $mid;
            $rlen = $mid;
        }
        else {
            $llen = floor($mid);
            $rlen = $llen ++;
        }
        $left  = '';
        $right = '';
        for($i=0; $i < $llen; $i++) { 
            $left .= '-';
        }
        for($i=0; $i < $rlen; $i++) { 
            $right .= '-';
        }
        return $left . $text . $right;
    }

    /**
     * [formateLine 根据参数，返回一行处理好间隔的字符串用作打印]
     * @param  string $left   [左边字符]
     * @param  string $right  [右边字符]
     */
    public function formateLine($left = '', $right = '') {
        $llen = mb_strwidth($left, 'utf-8');
        $rlen = mb_strwidth($right, 'utf-8');
        $space_len = $this->max_len - $llen - $rlen;
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
        return mb_strimwidth($name, 0, 30, '....', 'utf-8');
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
