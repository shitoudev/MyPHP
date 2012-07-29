<?php
/**
 * iCache
 */

//Cache的接口
define('MY_CACHE_INTERFACE',1);
interface iCache
{
    //设置
	public function set($key, $val, $expire=0);
	//获得单个值
	public function get($key);
	//同时获得多个值
	public function gets($keys=array());
	//删除
	public function delete($key);
	//刷新
	public function flush();
	//清空所有
	public function clear();
}
?>