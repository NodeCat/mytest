<?php
set_time_limit(0);
date_default_timezone_set('PRC');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
define('ROOT', __DIR__);

include_once ROOT . "/ezsql/ez_sql_core.php";
include_once ROOT . "/ezsql/ez_sql_mysqli.php";
include_once ROOT . "/ezsql/config.inc.php";

//引入配置文件
$db_config = require(ROOT . "/../App/Wms/Conf/production.php");

$db 	= new ezSQL_mysqli($db_config['DB_USER'], $db_config['DB_PWD'], $db_config['DB_NAME'], $db_config['DB_HOST']);
//获取数据库中最大的ID
$maxQuey= "SELECT MAX(id) FROM `stock` WHERE `is_deleted`='0' and `status`='qualified'";
$maxId  = $db->get_var($maxQuey);

//循环批次处理数据
while ($startId < $maxId) {
    $insert_query = array();    //初始化本次插入数据库语句
    $pro_codes    = array();    //批次处理SKU号
    $insert_array = array();    //批次插入数据
    $sku_array    = array();    //SKU接口处理后的数据

    //获取stock库存表数据，并关联stock_bill_in_detail详情表，获取sku详细信息
    $query  = "SELECT a.id as id, a.wh_id as wh_id, a.pro_code as pro_code, a.batch as batch, b.price_unit as price_unit,(a.stock_qty-a.assign_qty) as stock_qty, a.status as status, b.pro_name as pro_name, b.pro_attrs as pro_attrs, b.pro_uom as pro_uom  FROM `stock` as a INNER JOIN stock_bill_in_detail as b ON b.pro_code=a.pro_code AND b.refer_code=a.batch AND b.is_deleted=0 WHERE a.`id`>$startId AND a.`is_deleted`='0' AND a.`status`='qualified' limit 200";
    $result = $db->get_results($query);

    if (empty($result)) {
        die('暂无数据备份');
    }

    foreach ($result as $val) {
        $pro_codes[]    = $val->pro_code;   //获取SKU，用于分类接口调用
        $insert_array[] = array(
            'wh_id'     => $val->wh_id,
            'pro_code'  => $val->pro_code,
            'pro_name'  => $val->pro_name,
            'batch'     => $val->batch,
            'price_unit'=> $val->price_unit,
            #'pro_uom'   => $val->pro_uom,
            'pro_attrs' => $val->pro_attrs,
            'stock_qty' => $val->stock_qty,
            'snap_time' => $snap_time,
            'status'    => $val->status,
            'is_deleted'=> 0,
            'created_time' => $_time,
        );
    }

    unset($result);
    $startId = $val->id;

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
        $insert_array[$key]['pro_uom'] = $sku_number[$val['pro_code']]['unit_name'];
        $insert_array[$key]['category1'] = $sku_number[$val['pro_code']]['category_info']['top']['id'];
        $insert_array[$key]['category2'] = $sku_number[$val['pro_code']]['category_info']['second']['id'];
        $insert_array[$key]['category3'] = $sku_number[$val['pro_code']]['category_info']['third']['id'];
        $insert_array[$key]['category_name1'] = $sku_number[$val['pro_code']]['category_info']['top']['name'];
        $insert_array[$key]['category_name2'] = $sku_number[$val['pro_code']]['category_info']['second']['name'];
        $insert_array[$key]['category_name3'] = $sku_number[$val['pro_code']]['category_info']['third']['name'];
    }

    foreach ($insert_array as $item) {
        $insert_query_array[] = "('" . implode ( "','", $item ) . "')";
    }

    $insert_query = implode(',', $insert_query_array);
    unset($insert_query_array);
    unset($insert_array);
    unset($pro_codes);

    $insert = "INSERT INTO stock_snap(`wh_id`, `pro_code`, `pro_name`, `batch`, `price_unit`, `pro_attrs`, `stock_qty`, `snap_time`, `status`, `is_deleted`, `created_time`, `pro_uom`, `category1`, `category2`, `category3`, `category_name1`, `category_name2`, `category_name3`) VALUES {$insert_query}";

    $db->query($insert);
}
echo "已完成";
exit;






