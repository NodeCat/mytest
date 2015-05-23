<?php
return array(
	//'配置项'=>'配置值'
	//安全配置
	'REQUEST_LIMIT_UNIT'   => 60 , //用户请求频率限制端时间单位，单位秒
	'REQUEST_WARING_LIMIT' => 30, //单位时间内用户请求频率限制，超过这个限制会被加入到怀疑列表中，但不会加入黑名单
	'REQUEST_FORBID_LIMIT' => 100 ,//单位时间内用户请求频率限制，如每60秒内100次请求，超过这个请求会被直接拉入黑名单
	'API_TIME_DEVIATION'   => 300, //API请求时间戳与服务器时间戳的允许误差范围，单位秒，
	'USER_ALLOW_REGISTER'  => 'TRUE',

	//DB
	'DB_TYPE'   		=> 	'mysql', 	// 数据库类型
	'DB_HOST'   		=> 	'',// 服务器地址
	'DB_NAME'   		=> 	'wms', 		// 数据库名
	'DB_USER'   		=> 	'root', 	// 用户名
	'DB_PWD'    		=> 	'', // 密码
	'DB_PORT'   		=> 	3306, 		// 端口
	'DB_PREFIX' 		=> 	'', 		// 数据库表前缀
	'DB_CHARSET'=>'utf8',// 数据库编码默认采用utf8
	//应用设置
	'MODULE_ALLOW_LIST' => 'Wms',

	//模版设置
	'LAYOUT_ON'         => TRUE,

	//URL设置
	'URL_MODEL'			=> 2,
	'URL_HTML_SUFFIX'	=> 'htm',
	'URL_CASE_INSENSITIVE' => FALSE,

	//数据设置
	'PAGE_SIZE'         => 10,
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
	'SHOW_ERROR_MSG' 	=> false,
    'ERROR_MESSAGE'    	=> '页面错误！请稍后再试～',
    'URL_404_REDIRECT'	=> '',

);
define('AUTH_KEY', '_D1i,v5sSp])th$3#w"jx6gW/nYZUl[<Lrb>+uOa');
