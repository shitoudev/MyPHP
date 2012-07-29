<?php
/**
 * MEMCACHE
 */
class Cache_Memcache extends cBase implements iCache
{
	private $memcache;

	//构造函数
	public function __construct()
	{
		$this->memcache = new Memcache;
		$servers = unserialize(MY_MEMCACHE_HOSTS);
		foreach ($servers as $server)
		{
			$this->memcache->addServer($server['host'],$server['port'],MY_MEMCACHE_PCONNECT);
		} 
	}
		
    //设置
	public function set($key, $val, $expire=MY_CACHE_DEFAULT_LIFETIME)
	{
		return $this->memcache->set($this->key($key) , $val, MEMCACHE_COMPRESSED, $expire);
	}
	
    //获得单个值
	public function get($key)
	{
	    if (!$this->enable())
	    {
	    	return false;
	    }
		return $this->memcache->get($this->key($key), MEMCACHE_COMPRESSED);
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
		return $this->memcache->delete($this->key($key));
	}
	
	//刷新
	public function flush()
	{
		return $this->memcache->flush();
	}
	
	//清空所有
    public function clear()
    {
    	return true;
    }
}
?>