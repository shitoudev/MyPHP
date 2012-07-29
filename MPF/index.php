<?php
//是否显示错误信息:建议在开发时打开,上线时关闭.值: 1:打开 0:关闭
ini_set('display_errors',1);
//显示所有的错误，除了提醒
error_reporting(E_ALL & ~E_NOTICE);
//当前项目应用程序的根目录
define('MY_WEB_ROOT' ,dirname(__FILE__).DIRECTORY_SEPARATOR);
//MyPHP框架根路径
define('MY_CORE_ROOT',MY_WEB_ROOT.'MyPHP/');
//项目应用程序根路径(路径不存在则框架会自动创建)
define('MY_APP_ROOT' ,MY_WEB_ROOT.'/');
//加载框架
require_once(MY_CORE_ROOT.'MyPHP.php');
//生成应用程序实例并开始运行程序
try{
	$app = new MyPHP();
	$app -> run();
}catch(Exception $e){
	dump($e->getMessage());
}
?>