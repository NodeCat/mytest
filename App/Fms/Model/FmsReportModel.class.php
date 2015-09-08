<?php
namespace Fms\Model;

use Think\Model;

class FmsReportModel extends Model
{
    public $tableName = 'stock_wave_distribution';

    public $_scope = array(
        'default'=>array(
            'where'=>array('stock_wave_distribution.is_deleted'=>'0', 
                            //'date(stock_wave_distribution.payment_time) > date_sub(curdate(),interval 7 day)',
                        ),
            "join"=>array(
                "inner join stock_wave_distribution_detail detail on detail.pid = stock_wave_distribution.id and detail.is_deleted = 0",
                "inner join warehouse on warehouse.id = stock_wave_distribution.wh_id and warehouse.is_deleted = 0",
                "inner join user on user.id = stock_wave_distribution.payment_user and user.is_deleted = 0",
                "inner join stock_bill_out bill on bill.id = detail.bill_out_id and bill.is_deleted = 0",
            ),
            "field"=>"concat(stock_wave_distribution.payment_user, ',',date(stock_wave_distribution.payment_time)) as id,
                      count(stock_wave_distribution.id) as dist_count, stock_wave_distribution.payment_user, date(stock_wave_distribution.payment_time) payment_date,
                      count(detail.id) as order_count, sum(detail.wipe_zero) as wipezero_sum, sum(detail.deposit) as deposit_sum, 
                     sum(detail.receivable_sum) as receivable_sum, 
                     sum(case
                            when 
                                detail.pay_type = 0
                            then
                                detail.real_sum
                            else
                                0
                         end) as deal_price_sum, 
                     sum(case 
                            when
                                detail.pay_type = 0 
                            then
                                detail.receivable_sum
                            else
                                0
                         end) as cash_on_delivery_sum,
                     sum(case
                            when 
                                detail.pay_type = 1
                            then 
                                detail.receivable_sum
                            else
                                0
                         end) as wechat_sum,
                     sum(case
                            when
                                detail.pay_type = 2 
                            then
                                detail.receivable_sum
                            else
                                0
                         end) as account_pay_sum,
                     user.nickname as username",
            "group"=>"date(stock_wave_distribution.payment_time), user.nickname",
            "order"=>" date(stock_wave_distribution.payment_time) asc",
        )
    );
}