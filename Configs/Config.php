<?php
/**
 * 项目主配置文件
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */

// 是否载入项目函数库 Libs/Function.php
define('MYPHP_APP_LOAD_FUN', TRUE);

// 是否载入项目HOOK Configs/Hook.php
define('MYPHP_APP_LOAD_HOOK', FALSE);

// 数据库设置
define('MYPHP_DBS', serialize(array(
	'master' => array('host' => '127.0.0.1', 'user' => 'root', 'password' => 'qiumicc', 'database' => 'test', 'port' => '3306'),
	'slave'  => array('host' => '127.0.0.1', 'user' => 'root', 'password' => 'qiumicc', 'database' => 'test', 'port' => '3306'),
)));

// redis 相关设置 [cache/nosql/queue]
define('MYPHP_REDIS', serialize(array(
	'cache' => array('host' => '127.0.0.1', 'port' => 6379, 'db' => 0),
	'nosql' => array('host' => '127.0.0.1', 'port' => 6379, 'db' => 1),
	'queue' => array('host' => '127.0.0.1', 'port' => 6379, 'db' => 2),
)));

// mongodb 相关设置
define('MYPHP_MONGO', serialize(array(
	'master' => array('host' => '127.0.0.1', 'port' => 11611, 'db' => 'xxx'),
	'slave'  => array('host' => '127.0.0.1', 'port' => 11611, 'db' => 'xxx'),
)));

// memcache 相关设置
define('MYPHP_MEMCACHE', serialize(array(
	'master' => array('host' => '127.0.0.1', 'port' => 11211),
)));


// 核心框架常量配置
define('MYPHP_DEFAULT_CONTROLLER', 'welcome');         // 默认控制器文件
define('MYPHP_DEFAULT_ACTION',     'index');           // 默认方法
define('MYPHP_HTTP_404_PAGE',      '/views/404.html'); // 404页面

/* End */