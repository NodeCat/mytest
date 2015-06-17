<?php
return array(
	//'配置项'=>'配置值'
	//安全配置
	'REQUEST_LIMIT_UNIT'   => 60 , //用户请求频率限制端时间单位，单位秒
	'REQUEST_WARING_LIMIT' => 30, //单位时间内用户请求频率限制，超过这个限制会被加入到怀疑列表中，但不会加入黑名单
	'REQUEST_FORBID_LIMIT' => 100 ,//单位时间内用户请求频率限制，如每60秒内100次请求，超过这个请求会被直接拉入黑名单
	'API_TIME_DEVIATION'   => 300, //API请求时间戳与服务器时间戳的允许误差范围，单位秒，
	'USER_ALLOW_REGISTER'  => 'TRUE',

	'DB_CHARSET'=>'utf8',// 数据库编码默认采用utf8
	//应用设置
	'MODULE_ALLOW_LIST' => 'Wms',
	'HOP_API_PATH'=>'http://s.test3.dachuwang.com',
	//模版设置
	'LAYOUT_ON'         => TRUE,
	'TMPL_CACHE_ON'   => FALSE,  // 默认开启模板编译缓存 false 的话每次都重新编译模板
	'ACTION_CACHE_ON' => FALSE,  // 默认关闭Action 缓存
	'HTML_CACHE_ON'   => FALSE,
	'DATA_CACHE_TIME' => 1,
	//URL设置
	'URL_MODEL'			=> 2,
	'URL_HTML_SUFFIX'	=> 'htm',
	'URL_CASE_INSENSITIVE' => FALSE,

	//数据设置
	'PAGE_SIZE'         => 50,
	'DB_BIND_PARAM'    =>    true,

	//session设置
	'SESSION_OPTIONS'	=> array('expire'=>'36000'),
	//'SESSION_PREFIX'	=>'wms',

	//缓存设置
	'DATA_CACHE_TYPE'   => 'redis',
	'REDIS_HOST'        => '127.0.0.1',
    'REDIS_PORT'        => 6379,
    'DATA_CACHE_TIME'   => 60,

    //错误及日志
    'LOG_RECORD' 		=> true,
	'LOG_LEVEL'  		=> 'EMERG,ALERT,CRIT,ERR',
	'SHOW_ERROR_MSG' 	=> TRUE,
    'ERROR_MESSAGE'    	=> '页面错误！请稍后再试～',
    'URL_404_REDIRECT'	=> '',

    //正式PMS接口
	'PMS_API'			=> 'http://s.dachuwang.com',

);
