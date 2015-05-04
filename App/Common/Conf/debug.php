<?php
return array(

	'SHOW_PAGE_TRACE' => TRUE, 
	'TMPL_CACHE_ON'   => FALSE,      // 默认开启模板缓存
	'TMPL_CACHE_ON'   => FALSE,  // 默认开启模板编译缓存 false 的话每次都重新编译模板
	'ACTION_CACHE_ON' => FALSE,  // 默认关闭Action 缓存
	'HTML_CACHE_ON'   => FALSE,   // 默认关闭静态缓存
	'DATA_CACHE_TIME' => 1, // 数据缓存有效期 0表示永久缓存

	'LOAD_EXT_CONFIG' =>'columns',
	
	//Database
	'DB_TYPE'   		=> 	'mysql', 	// 数据库类型
	'DB_HOST'   		=> 	'127.0.0.1',// 服务器地址
	'DB_NAME'   		=> 	'wms', 	// 数据库名
	'DB_USER'   		=> 	'root', 	// 用户名
	'DB_PWD'    		=> 	'dachuwang', 	// 密码
	'DB_PORT'   		=> 	3306, 		// 端口
	'DB_PREFIX' 		=> 	'', 		// 数据库表前缀

);