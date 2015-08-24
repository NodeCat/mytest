<?php
set_time_limit(0);
date_default_timezone_set('PRC');
error_reporting(E_ERROR);
define('ROOT', __DIR__);

include_once ROOT . "/ezsql/ez_sql_core.php";
include_once ROOT . "/ezsql/ez_sql_mysqli.php";
include_once ROOT . "/ezsql/config.inc.php";

//引入配置文件
$db_config = require(ROOT . "/../App/Wms/Conf/production.php");

$db 	= new ezSQL_mysqli($db_config['DB_USER'], $db_config['DB_PWD'], $db_config['DB_NAME'], $db_config['DB_HOST'], 'utf8');
//获取数据库中最大的ID
$maxQuey= "SELECT count(*) FROM `stock` WHERE `is_deleted`='0'";
$max  = $db->get_var($maxQuey);

$start = 0;

//循环批次处理数据
while ($start <= $max) {
    $insert_query = array();    //初始化本次插入数据库语句
    $pro_codes    = array();    //批次处理SKU号
    $insert_array = array();    //批次插入数据
    $sku_number   = array();    //SKU接口处理后的数据


    //获取stock库存表数据，并关联stock_bill_in_detail详情表，获取sku详细信息
    $query  = "SELECT wh_id, pro_code, batch, sum(stock_qty) as stock_qty, `status` FROM `stock` WHERE `is_deleted`='0' group by wh_id, pro_code, batch,`status` limit {$start},200";
    $result = $db->get_results($query);

    if (empty($result)) {
        break;
    }

    foreach ($result as $val) {
        $pro_codes[]    = $val->pro_code;   //获取SKU，用于分类接口调用
        $insert_array[] = array(
            'wh_id'     => $val->wh_id,
            'pro_code'  => $val->pro_code,
            //'pro_name'  => $val->pro_name,
            'batch'     => $val->batch,
            //'price_unit'=> $val->price_unit,
            //'pro_attrs' => $val->pro_attrs,
            'stock_qty' => $val->stock_qty,
            'snap_time' => $snap_time,
            'status'    => $val->status,
            'is_deleted'=> 0,
            'created_time' => $_time,
        );
    }

    unset($result);
    //$startId = $val->id;

    //SKU去重
    $unique = array_unique($pro_codes);
    $api_where['where'] = array('in' => array('sku_number' => $unique));
    $json_data = json_encode($api_where);

    //请求SKU接口，获取SKU信息
    $json_res  = http_post_data(WMS_API, $json_data);

    //接口返回数据处理
    if ($json_res[0] == 200) {
        $result = json_decode($json_res[1], true);

        foreach ($result['list'] as $val) {
            $sku_number[$val['sku_number']] = $val;
        }
        unset($result);
    } else {
        die('接口访问出错');
    }

    foreach ($insert_array as $key => $val) {
        $insert_array[$key]['pro_name'] = $sku_number[$val['pro_code']]['name'];
        $insert_array[$key]['pro_attrs'] = $sku_number[$val['pro_code']]['description'][0]['name'].':'.$sku_number[$val['pro_code']]['description'][0]['val'].';'.$sku_number[$val['pro_code']]['description'][1]['name'].':'.$sku_number[$val['pro_code']]['description'][1]['val'];
        $insert_array[$key]['pro_uom'] = $sku_number[$val['pro_code']]['unit_name'];
        $insert_array[$key]['category1'] = $sku_number[$val['pro_code']]['category_info']['top'][0]['id'];
        $insert_array[$key]['category2'] = $sku_number[$val['pro_code']]['category_info']['second'][0]['id'];
        $insert_array[$key]['category3'] = $sku_number[$val['pro_code']]['category_info']['third'][0]['id'];
        $insert_array[$key]['category_name1'] = $sku_number[$val['pro_code']]['category_info']['top'][0]['name'];
        $insert_array[$key]['category_name2'] = $sku_number[$val['pro_code']]['category_info']['second'][0]['name'];
        $insert_array[$key]['category_name3'] = $sku_number[$val['pro_code']]['category_info']['third'][0]['name'];
    }

    foreach ($insert_array as $item) {
        $insert_query_array[] = "('" . implode ( "','", $item ) . "')";
    }

    $insert_query = implode(',', $insert_query_array);
    unset($insert_query_array);
    unset($insert_array);
    unset($pro_codes);

    $insert = "INSERT INTO stock_snap(`wh_id`, `pro_code`, `batch`, `stock_qty`, `snap_time`, `status`, `is_deleted`, `created_time`,`pro_name`, `pro_attrs`, `pro_uom`, `category1`, `category2`, `category3`, `category_name1`, `category_name2`, `category_name3`) VALUES {$insert_query}";

    $db->query($insert);

    $start += 200;
}
echo "库存快照备份已完成";

$pro_codes    = array();
$insert_array = array();
//获取交易日志表中存在的SKU，但不存在于库存表的SKU
$not_query = $db->get_col("SELECT pro_code FROM stock group by pro_code", 0);
$not_in    = implode(',', $not_query);
$move_query = "SELECT stock_move.`wh_id`, stock_move.`pro_code`, stock_bill_in_detail.`pro_name`, stock_bill_in_detail.`pro_attrs`, stock_move.`batch`, stock_move.`status` FROM stock_move INNER JOIN stock_bill_in_detail ON stock_bill_in_detail.`pro_code`=stock_move.`pro_code` WHERE DATE(stock_move.created_time)=date_sub(CURDATE(),INTERVAL 1 day) AND stock_move.`pro_code` NOT IN($not_in) GROUP BY stock_move.`pro_code`";
$move_result = $db->get_results($move_query);

if (empty($move_result)) {
    die('没有交易日志，快照备份完成');
}

foreach ($move_result as $val) {
    $pro_codes[]    = $val->pro_code;   //获取SKU，用于分类接口调用
    $insert_array[] = array(
        'wh_id'     => $val->wh_id,
        'pro_code'  => $val->pro_code,
        //'pro_name'  => $val->pro_name,
        'batch'     => $val->batch,
        //'price_unit'=> 0,
        //'pro_attrs' => $val->pro_attrs,
        'stock_qty' => 0,
        'snap_time' => $snap_time,
        'status'    => $val->status,
        'is_deleted'=> 0,
        'created_time' => $_time,
    );
}
unset($move_result);
$api_where['where'] = array('in' => array('sku_number' => $pro_codes));
$json_data = json_encode($api_where);

//请求SKU接口，获取SKU信息
$json_res  = http_post_data(WMS_API, $json_data);

//接口返回数据处理
if ($json_res[0] == 200) {
    $result = json_decode($json_res[1], true);

    $sku_move = array();
    foreach ($result['list'] as $val) {
        $sku_move[$val['sku_number']] = $val;
    }
    unset($result);
} else {
    die('MOVE接口访问出错');
}

foreach ($insert_array as $key => $val) {
    $insert_array[$key]['pro_name']  = $sku_number[$val['pro_code']]['name'];
    $insert_array[$key]['pro_attrs'] = $sku_number[$val['pro_code']]['description'][0]['name'].':'.$sku_number[$val['pro_code']]['description'][0]['val'].';'.$sku_number[$val['pro_code']]['description'][1]['name'].':'.$sku_number[$val['pro_code']]['description'][1]['val'];
    $insert_array[$key]['pro_uom']   = $sku_move[$val['pro_code']]['unit_name'];
    $insert_array[$key]['category1'] = $sku_move[$val['pro_code']]['category_info']['top'][0]['id'];
    $insert_array[$key]['category2'] = $sku_move[$val['pro_code']]['category_info']['second'][0]['id'];
    $insert_array[$key]['category3'] = $sku_move[$val['pro_code']]['category_info']['third'][0]['id'];
    $insert_array[$key]['category_name1'] = $sku_move[$val['pro_code']]['category_info']['top'][0]['name'];
    $insert_array[$key]['category_name2'] = $sku_move[$val['pro_code']]['category_info']['second'][0]['name'];
    $insert_array[$key]['category_name3'] = $sku_move[$val['pro_code']]['category_info']['third'][0]['name'];
}

foreach ($insert_array as $item) {
    $insert_query_array[] = "('" . implode ( "','", $item ) . "')";
}

$insert_query = implode(',', $insert_query_array);
unset($insert_query_array);
unset($insert_array);
unset($pro_codes);

$insert = "INSERT INTO stock_snap(`wh_id`, `pro_code`, `batch`, `stock_qty`, `snap_time`, `status`, `is_deleted`, `created_time`,`pro_name`, `pro_attrs`, `pro_uom`, `category1`, `category2`, `category3`, `category_name1`, `category_name2`, `category_name3`) VALUES {$insert_query}";

$db->query($insert);

echo "交易日志快照备份已完成";
exit;






