<?php
namespace Tms\Controller;
use Think\Controller;
class IndexController extends \Common\Controller\AuthController{
	    public function index() {
        $data['stockout']['title'] = '出库单实时统计';
        $data['stockout']['data'] = M()->table('stock_wave_distribution_detail dd ')
        ->field('wh.name,dd.status,date(deliver_date) deliver_date,deliver_time,count(*) qty')
        ->join('stock_wave_distribution d on dd.pid = d.id and d.is_deleted = 0 and dd.is_deleted = 0 and d.wh_id='.session('user.wh_id'))
        ->join('warehouse wh on d.wh_id = wh.id')
        ->where('d.deliver_date = date(now())')
        ->group('d.wh_id,dd.status,deliver_date,deliver_time')
        ->order('d.wh_id,d.deliver_time,d.status')
        ->select();

        $data['distribution']['title'] = '配送单实时统计';
        $data['distribution']['data'] = M()->table('stock_wave_distribution d')
        ->field('wh.name,d.status,date(deliver_date) deliver_date,d.deliver_time,count(*) qty')
        ->join('warehouse wh on d.wh_id = wh.id')
        ->where('deliver_date = date(now()) and d.is_deleted = 0 and d.wh_id='.session('user.wh_id'))
        ->group('wh_id,status,deliver_time')
        ->order('d.wh_id,d.deliver_time,d.status')
        ->select();

        $date_week = date('Y-m-d',strtotime('-1 week'));

        $data['driver_fee']['title'] = '近7天数据统计';
        $data['driver_fee']['data'] = M()->query('
                    select a.*,b.name,b.driver_qty,b.notpaid_qty,b.fee,ROUND(b.fee / a.total_price * 100,2) fee_precent,
                    c.order_qty,c.ontime_qty,c.order_qty-c.ontime_qty overtime_qty,c.ontime_percent,
                    ROUND(a.dist_ontime_qty / a.dist_qty * 100,2) dist_ontime_percent
                     from(
                    SELECT 
                        d.wh_id,
                        DATE(deliver_date) deliver_date,
                        SUM(total_price) total_price,
                        COUNT(*) dist_qty,
                        SUM(order_count) order_qty,
                        ROUND(SUM(total_price) / COUNT(*), 2) dist_price_average,
                        ROUND(SUM(total_price) / SUM(order_count), 2) decim_price_average,
                        ROUND(300 / (SUM(total_price) / COUNT(*)) * 100, 2) dist_price_percent,
                        ROUND(SUM(order_count) / COUNT(*), 2) dist_order_average,
                        SUM(CASE
                                WHEN
                                    deliver_time = 1
                                THEN
                                    CASE end_time < DATE_ADD(deliver_date, INTERVAL 6 HOUR)
                                        WHEN 1 THEN 1
                                        ELSE 0
                                    END
                                WHEN
                                    deliver_time = 2
                                THEN
                                    CASE end_time < DATE_ADD(deliver_date, INTERVAL 12 HOUR)
                                        WHEN 1 THEN 1
                                        ELSE 0
                                    END
                            END) dist_ontime_qty
                    FROM
                        stock_wave_distribution d
                    WHERE
                        d.is_deleted = 0 and  DATE(deliver_date) > "'.$date_week.'" and d.wh_id = '.session('user.wh_id').'
                    GROUP BY wh_id , deliver_date
                    order by deliver_date desc
                    ) a 
                    inner join (
                    SELECT 
                        wh.id wh_id,
                        wh.name,
                        COUNT(*) driver_qty,
                        sum(sl.fee) fee,
                        SUM(CASE sl.fee
                            WHEN 0 THEN 1
                            ELSE 0
                        END) notpaid_qty,
                        DATE(sl.created_time) deliver_date
                    FROM
                        tms_sign_list sl
                            INNER JOIN
                        tms_user u ON sl.userid = u.id
                            INNER JOIN
                        warehouse wh ON wh.id = u.warehouse
                    WHERE
                        sl.is_deleted = 0 and DATE(sl.created_time) > "'.$date_week.'" and wh.id = '.session('user.wh_id').'
                    GROUP BY u.warehouse , deliver_date
                    ORDER BY u.warehouse , deliver_date DESC
                    ) b 
                    on a.wh_id = b.wh_id and a.deliver_date = b.deliver_date
                    INNER JOIN
                    (SELECT 
                        wh_id,
                            COUNT(*) order_qty,
                            SUM(dd.delivery_ontime) ontime_qty,
                            ROUND(SUM(dd.delivery_ontime) / COUNT(*) * 100, 2) ontime_percent,
                            DATE(d.deliver_date) deliver_date
                    FROM
                        stock_wave_distribution_detail dd
                    INNER JOIN stock_wave_distribution d ON dd.pid = d.id
                    WHERE
                        d.is_deleted = 0 and dd.is_deleted = 0 and  DATE(d.deliver_date) > "'.$date_week.'" and d.wh_id = '.session('user.wh_id').'
                    GROUP BY d.deliver_date , d.wh_id
                    ORDER BY d.deliver_date DESC
                    ) c 
                    ON a.wh_id = c.wh_id AND a.deliver_date = c.deliver_date
                    '
            );
/*
            $data['summary']['title'] = '今日运费占比［粗略预估统计］';
        $data['summary']['data'] = M()->table('stock_wave_distribution d')
        ->field('
            wh_id,wh.name,date(deliver_date) deliver_date,
            SUM(total_price) total_price,
            COUNT(*) dist_qty,
            SUM(order_count) order_qty,
            ROUND(SUM(total_price) / COUNT(*), 2) dist_price_average,
            ROUND(SUM(total_price) / SUM(order_count), 2) decim_price_average,
            ROUND(300 / (SUM(total_price) / COUNT(*)) * 100, 2) dist_price_percent,
            ROUND(SUM(order_count) / COUNT(*), 2) dist_order_average
        ')
        ->join('warehouse wh on d.wh_id = wh.id and d.wh_id='.session('user.wh_id'))
        ->where('deliver_date = date(now()) and d.is_deleted = 0')
        ->group('wh_id')
        ->select(); 

        $data['yestoday_fee']['title'] = '近7天历史运费占比';
        $data['yestoday_fee']['data'] = M()->query('select a.id,a.name,a.fee,b.total_price,ROUND(a.fee / b.total_price * 100,2) fee_precent,b.deliver_date from (
                        select wh.id,wh.name,date(sl.created_time) sign_date,sum(sl.fee) fee from tms_sign_list sl
                        inner join tms_user u on sl.userid = u.id
                        inner join warehouse wh on u.warehouse = wh.id
                        where date(sl.created_time) > "'.$date_week.'"
                        group by u.warehouse,date(sl.created_time)
                    ) a 
                    inner join (
                        select wh_id,date(deliver_date) deliver_date,sum(total_price) total_price from stock_wave_distribution
                        where date(deliver_date) > "'.$date_week.'"
                        group by wh_id,date(deliver_date)
                    ) b
                    on a.id = b.wh_id and a.sign_date = b.deliver_date  and b.wh_id='.session('user.wh_id').
                    ' order by b.wh_id,b.deliver_date desc
                ');
*/
        $status['stockout'] = array(
                '0' => '已分拨',
                '1' => '已装车',
                '2' => '已签收',
                '3' => '已拒收',
                '4' => '已完成',
                '5' => '已发运',
        );

        $status['distribution'] = array(
                '0' => '未知',
                '1' => '未发运',
                '2' => '已发运',
                '3' => '已配送',
                '4' => '已结算',
        );

        $deliver_time = array(
            '1' =>'上午',
            '2' =>'下午'
        );

        foreach ($data['stockout']['data'] as &$value) {
            $value['status_cn'] = $status['stockout'][$value['status']];
            $value['deliver_time'] = $deliver_time[$value['deliver_time']];         
        }
        foreach ($data['distribution']['data'] as &$value) {
            $value['status_cn'] = $status['distribution'][$value['status']];
            $value['deliver_time'] = $deliver_time[$value['deliver_time']];         
        }

        $data['stockout']['columns'] = array(
            'name' => '仓库',
            'deliver_date' => '配送日期',
            'deliver_time' => '配送时间',
            'status_cn' => '状态',
            'qty' => '数量',
        );

        $data['distribution']['columns'] = array(
            'name' => '仓库',
            'deliver_date' => '配送日期',
            'deliver_time' => '配送时间',
            'status_cn' => '状态',
            'qty' => '数量',
        );

        $data['summary']['columns'] = array(
            'name' => '仓库',
            'deliver_date' => '配送日期',
            'dist_qty' => '配送单数量',
            'order_qty' => '出库单数量',
            'dist_order_average' => '平均每个车单包含出库单数',
            'dist_price_percent' => '预计运费比'
        );

        $data['yestoday_fee']['columns'] = array(
            'name' => '仓库',
            'deliver_date' => '配送日期',
            'fee_precent' => '运费占比',
        );

        $data['driver_fee']['columns'] = array(
            'name' => '仓库',
            'deliver_date' => '配送日期',
            'driver_qty' => '签到司机数量',
            'notpaid_qty' => '无运费司机数量',
            'dist_qty' => '配送单数量',
            'order_qty' => '出库单数量',
            'overtime_qty' => '晚点配送数',
            'dist_ontime_percent' => '发运准点率',
            'ontime_percent' => '配送准点率',
            'dist_order_average' => '平均出库单数/每配送单',
            'dist_price_percent' => '预计运费比',
            'fee_precent' => '实际运费占比'
        );

        $this->data = $data;
        $this->display('Dispatch/home');
    }

}
