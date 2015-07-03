<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

if($_SERVER['SERVER_ENV'] == 'production') {
  define('APP_DEBUG',FALSE);
  define('APP_STATUS','production');
} else {
  define('APP_DEBUG',TRUE);
}

define('BIND_MODULE','Tms');

define('DEFAULT_C_LAYER','Api');

define('RUNTIME_PATH','../Runtime/');

// 定义应用目录
define('APP_PATH','./App/');

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
// 亲^_^ 后面不需要任何代码了 就是如此简单