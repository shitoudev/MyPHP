<?php
/**
 * 该文件是用于框架运行前的一些初始化操作,主要是根据 MyPHPConfig.inc.php 中的配置
*/

//设置include路径,详细请看 php.ini 
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MY_CORE_ROOT);//框架根目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MY_CORE_ROOT.'Tools/');//框架工具目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MY_CONTROLLER_PATH);//项目控制器根目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MY_APP_CFG_PATH); //项目配置文件根目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MY_APP_LIB_PATH); //项目配置文件根目录

/////////////////////////////////////////////////////////////////////////
//  为框架中额外设置做自动化的初始化
//  1:如果 SESSION 的管理方式为 db 方式时则必需要先初始化系统表
//  2:如果应用了基于ACL的权限管理时，则必需先初始化权限相关的系统表
////////////////////////////////////////////////////////////////////////
/*// SESSION
$sessionDbLockFile = MY_RUNSTAT_PATH . 'sessionDb.lock';
if (strtolower(MY_SESSION_HANDLER)=='db' && !file_exists($sessionDbLockFile))
{
	Session::init();
	File::write($sessionDbLockFile,'Session Table Install Ok!');
}
unset($sessionDbLockFile); 
// ACL
$aclDbLockFile = MY_RUNSTAT_PATH . 'aclDb.lock';
if (MY_ACL_ACCESS && !file_exists($aclDbLockFile))
{
	$acl = MY_load('acl');
	$acl->initACL();
	unset($acl);
	File::write($aclDbLockFile,'ACL Table Install Ok!');		
}
unset($aclDbLockFile);
*/

//设置SESSION有效时间
if (MY_SESSION_LIFETIME) ini_set('session.gc_maxlifetime',MY_SESSION_LIFETIME); 
//避免二级域名无法得到SESSION的问题
ini_set('session.cookie_domain',rootDomain());  
//设置时区,上海--以免出现时间不正确的情况
date_default_timezone_set("Asia/Shanghai");
//根据SESSION配置加载对应的接口
if (MY_SESSION_HANDLER != 'system') require_once('Libs/Session/session'.ucfirst(strtolower(MY_SESSION_HANDLER)).'.php');
//开始会话
session_start();

//设定编码
if (MY_FORCE_CHARSET) header("Content-Type:text/html; charset=".MY_CHARSET);

//是否强制不缓冲 
if (MY_BROWSE_NO_CACHE) header("Cache-Control: no-store, no-cache, must-revalidate");

//--载入用户自定义的配置及函数库
if (defined('MY_APP_LOAD_CFG') && file_exists(MY_APP_LOAD_CFG)) require_once(MY_APP_LOAD_CFG);
if (defined('MY_APP_LOAD_LIB') && file_exists(MY_APP_LOAD_LIB)) require_once(MY_APP_LOAD_LIB);

//--载入框架钩子函数库文件
require_once(MY_CORE_HOOK_FILE);
?>