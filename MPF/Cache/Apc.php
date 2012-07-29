<?php
/**
 * APC缓存
 */

class Cache_Apc extends cBase implements iCache 
{
	//构造函数
	public function __construct(){}
		
    //设置
	public function set($key, $val, $expire=MY_CACHE_DEFAULT_LIFETIME)
	{
		return apc_store($this->key($key),$val,$expire);
	}
	
    //获得单个值
	public function get($key)
	{
	    if (!$this->enable())
	    {
	    	return false;
	    }	
		return apc_fetch($this->key($key));
	}
	
	//获得多个值
    public function gets($key=array())
    {
		$result = array();
		foreach ($key as $k)
		{
			$result[$k] = $this->get($k);
		}
		return $result;
    }
	
	//删除
	public function delete($key)
	{
		return apc_delete($this->key($key));
	}
	
	//刷新
	public function flush()
	{
		return true;
	}
	
	//清空所有
    public function clear()
    {
    	return apc_clear_cache();
    }
}

?>