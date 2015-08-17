<?php
namespace Tms\Controller;
use Think\Controller;
class IndexController extends \Common\Controller\AuthController{
	    public function index() {
        $data['stockout']['title'] = '出库单实时统计';
        $data['stockout']['data'] = M()->table('stock_wave_distribution_detail dd ')
        ->field('wh.name,dd.status,date(deliver_date) deliver_date,deliver_time,count(*) qty')
        ->join('stock_wave_distribution d on dd.pid = d.id and d.is_deleted = 0 and dd.is_deleted = 0')
        ->join('warehouse wh on d.wh_id = wh.id')
        ->where('d.deliver_date = date(now())')
        ->group('d.wh_id,dd.status,deliver_date,deliver_time')
        ->order('d.wh_id,d.deliver_time,d.status')
        ->select();

        $data['distribution']['title'] = '配送单实时统计';
        $data['distribution']['data'] = M()->table('stock_wave_distribution d')
        ->field('wh.name,d.status,date(deliver_date) deliver_date,d.deliver_time,count(*) qty')
        ->join('warehouse wh on d.wh_id = wh.id')
        ->where('deliver_date = date(now()) and d.is_deleted = 0')
        ->group('wh_id,status,deliver_time')
        ->order('d.wh_id,d.deliver_time,d.status')
        ->select();

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
        ->join('warehouse wh on d.wh_id = wh.id')
        ->where('deliver_date = date(now()) and d.is_deleted = 0')
        ->group('wh_id')
        ->select(); 

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

        $this->data = $data;
        $this->display('Dispatch/home');
    }
}
