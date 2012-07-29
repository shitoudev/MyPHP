<?php
/**
 * 缓存基类
 */

//Cache的接口
define('MY_CACHE_BASECLASS',1);
class cBase
{
	//构造函数
	public function __construct(){}
	
    //缓存是否可用
	public function enable()
	{
		return MY_CACHE_ENABLE;
	}
	
	//加密码 KEY
	public function key($key)
	{
		return md5($key);
	}
}
?>