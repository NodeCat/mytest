<?php
$startId   = 200;                               //开始ID
$limit     = 200;                               //每次处理数据条数
$snap_time = date('Y-m-d', strtotime('-1 day'));	//快照备份日期
$_time     = date('Y-m-d H:i:s');               //当前时间
$api_where = array(                             //api接口时间
    'currentPage'  => 1,
    'itemsPerPage' => $limit
);

define('WMS_API', 'http://s.dachuwang.com/sku/manage');

//curl封装接口
function http_post_data($url, $data_string) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));

    ob_start();
    curl_exec($ch);
    $return_content = ob_get_contents();
    ob_end_clean();
    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return array($return_code, $return_content);
}
