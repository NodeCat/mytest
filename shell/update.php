<?php
set_time_limit(0);
date_default_timezone_set('PRC');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
define('ROOT', __DIR__);

include_once ROOT."/ezsql/ez_sql_core.php";
include_once ROOT."/ezsql/ez_sql_mysqli.php";
include_once ROOT."/ezsql/config.inc.php";

$db 	= new ezSQL_mysqli($user, $pwd, $db, $host);
//获取数据库中最大的ID
$query= "SELECT pro_code FROM `stock_snap` group by pro_code";
$result  = $db->get_results($query);
$array   = array();
$i = 0;
foreach ($result as $key => $val) {
    $array[$i][] = $val->pro_code;
    if ($key > 0 && ($key % 200 == 0)) {
        $i++;
    }
}

foreach ($array as $val) {
    //SKU去重
    $api_where['where'] = array('in' => array('sku_number' => $val));
    $json_data = json_encode($api_where);

    //请求SKU接口，获取SKU信息
    $json_res  = http_post_data(WMS_API, $json_data);

    //接口返回数据处理
    if ($json_res[0] == 200) {
        $result = json_decode($json_res[1], true);

        foreach ($result['list'] as $val) {
            $sku_number[$val['sku_number']]['category1'] = $val['category_info']['top'][0]['id'];
            $sku_number[$val['sku_number']]['category_name1'] = $val['category_info']['top'][0]['name'];
            $sku_number[$val['sku_number']]['category2'] = $val['category_info']['second'][0]['id'];
            $sku_number[$val['sku_number']]['category_name2'] = $val['category_info']['second'][0]['name'];
            $sku_number[$val['sku_number']]['category3'] = $val['category_info']['third'][0]['id'];
            $sku_number[$val['sku_number']]['category_name3'] = $val['category_info']['third'][0]['name'];
        }

        unset($result);
    } else {
        die('接口访问出错');
    }
}

foreach ($sku_number as $key => $value) {
    
    $insert = "UPDATE `stock_snap` SET `category1` = '{$value[category1]}', `category_name1` = '{$value[category_name1]}', `category2` = '{$value[category2]}', `category_name2` = '{$value[category_name2]}', `category3` = '{$value[category3]}', `category_name3` = '{$value[category_name3]}' WHERE `pro_code` = '{$key}';";

    $db->query($insert);
}

echo "已完成";
exit;






