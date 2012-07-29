<?php
/**
 * SESSION 相关操作
 
[示例]
----------------- Msess.php控制器的代码如下 --------------
Session::set('admin', true); // 设置SESSION名为'admin'的值为true
Session::set(array('uid'=>1, 'name'=>'myphp')); //一次设置多个值
echo Session::get('name'); //输出 "myphp"
Session::del('name');// 删除名为'admin'的SESSION
Session::clear();// 清空当前会话内所有的SESSION

//以下三个接口只有当SESSION管理方式为自定义时才有效

Session::init(); //当SESSION管理方式为数据库方式时的初始化
Session::num(); //返回当前在有效时间内的SESSION数量(概数)
Seesion::allSid(); //返回当前有效时间内所有的SESSION ID
--------------------------------------------------------
[注意]
1:SESSION的管理方式在 MyPHPConfig.inc.php 中有详细说明
2:不管SESSION的管理方式为那种,接口都使用这些.
*/

class Session {

	/**
	 * 设置SESSION的值
	 * @param string $name :SESSION变量名称
	 * @param mixed $value :对应值
	 * 例子: set('uid',10);
	 *      set(array('uid'=>10,'uname'=>'myphp'));
	 */
    static public function set($name,$value='')
    {
		if(!is_array($name))
		{
			$_SESSION[$name] = $value;
		}else{
			foreach ($name as $k=>$v) $_SESSION[$k] = $v;
		}
    }
    
    /**
     * 获得 SESSION 数据
     * @param string $name:域名称,如果为空则返回整个 $SESSION 数组
     * @param string $callback:回调函数
     * @return mixed
     */		
	static public function get($name='',$callback='')
	{
       $re =  $name ? (isset($_SESSION[$name]) ? $_SESSION[$name] : null) : $_SESSION;
	   if (!$re || !$callback || !function_exists($callback))
	   {
	   	    return $re;
	   }else{
			if (!$name) return array_map($callback,$_SESSION);
			elseif (isset($_SESSION[$name])) return call_user_func($callback,$_SESSION[$name]);
			else return null;
	   }    
	}

	/**
	 * 删除指定的 SESSION
	 */
	static public function del($name)
	{
		if (isset($_SESSION[$name])) unset($_SESSION[$name]);
		return true;		
	}
	
	/**
	 * 清空 SESSION
	 */
	static public function clear()
	{
	    session_destroy();
	}
	
	////////////////////////////////////////////////////////////////////////////////
	//
	//      以下三个接口只有当SESSION管理方式为自定义时才有效
	//
	//      define('MY_SESSION_HANDLER','system'); 定义为 file 或 db 时才有效
	//
	////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * 初始化SESSION数据表,框架会在第一次时使用时自动初始化数据表
	 */
	static public function init()
	{
		if (strtolower(MY_SESSION_HANDLER) == 'db')
		{
		    include_once('Libs/Session/sessionDb.php');
		    MY_SESSION::init();
		}
	}
	
	/**
	 * 返回当前在有效时间内的SESSION数量(概数)
	 */
	static public function num()
	{
	    if (MY_SESSION_HANDLER != 'system') return MY_SESSION::num();
	}
	
	/**
	 * 返回当前在有效时间内所有的 SID
	 */
	static public function allSid()
	{
	    if (MY_SESSION_HANDLER != 'system') return MY_SESSION::allSid();
	}
    
}
?>