<?php

namespace Fms\Controller;

use Think\Controller;

class FmsReportController extends \Common\Controller\CommonController
{
    protected $columns = array (
        'username' => '财务人员姓名',
        'payment_date' => ' 结算日期 ',
        'dist_count' => '结算配送单数',
        'order_count' => '结算订单数',
        'wipezero_sum' => '抹零总计',
        'deposit_sum' => '押金总计',
        'receivable_sum' => '结算订单总流水',
        'cash_on_delivery_sum' => '货到付款订单总流水',
        'wechat_sum' => '微信支付订单总流水',
        'account_pay_sum' => '账期支付订单总流水',
        'deal_price_sum' => '实收现金总额',
    );
    
    protected $query = array (
        
        'date(stock_wave_distribution.payment_time)' => array (
            'title'         => '结算时间',
            'query_type'    => 'between',
            'control_type'  => 'date',
            'value'         => '',
        )
    );

    public function before_index()
    {
        $this->table = array(
            'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => false, //是否显示表格中的复选框
            'status'    => false,
            'toolbar_tr'=> true,
            'statusbar' => true
        );

        $this->toolbar_tr = array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
        );
        //$this->search_addon = true;

    }

    
    public function view()
    {
        $id = I('id');
        $dist = M('stock_wave_distribution');
        $para = explode(',',$id);
        $final_price_total = 0;
        $deal_price_total  = 0;

        $detail = $dist->where(array(
                                    'payment_user = '.$para[0],
                                    "date(payment_time) = '".$para[1]."'",
                                    'stock_wave_distribution.is_deleted = 0',
                                    ))
                        ->join(array('inner join stock_wave_distribution_detail detail on detail.pid = stock_wave_distribution.id and detail.is_deleted = 0',
                                     'inner join stock_bill_out bill on bill.id = detail.bill_out_id and bill.is_deleted = 0',))
                        ->group('bill.refer_code,stock_wave_distribution.id, detail.pay_type')
                        ->getField('bill.refer_code, stock_wave_distribution.id, detail.pay_type, sum(bill.total_amount) as final_price, sum(detail.real_sum) as deal_price');
        foreach ($detail as $key => $value) {
            $final_price_total += $value['final_price'];
            $deal_price_total  += $value['deal_price'];
        }
        $this->final_price_total = $final_price_total;
        $this->deal_price_total  = $deal_price_total;
        $this->assign('payment_date',$para[1]);
        $this->assign("data",$detail);
        $this->display();
    }

    protected function after_lists(&$data)
    {
        if (!$this->auth['view_all']) {
            //过滤数据，只显示当前用户的数据
            $userid = session('user.uid');
            $data_array = array();
            foreach ($data as $key => $value) {
                if ($value['payment_user'] == $userid) {
                    $data_array[] = $value;
                }
            }
            $data = $data_array;
        }
    }

    //在search方法执行后 执行该方法
    protected function after_search(&$map)
    {
        if (empty($map)) {
            $map = array('date(stock_wave_distribution.payment_time) > date_sub(curdate(),interval 7 day)');
        }
    }
    
}