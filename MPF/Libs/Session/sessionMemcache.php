<?php
/**
 * SESSION 基于 MEMCACHE 的管理
 * 说明: 由于这里考虑到效率和不受缓存的控制,所以直接连接MEMCACHE不使用 Cache 组件
 */
 
class MY_SESSION {
    static private $mem = null;
    static private $leftTime = 0;
    
    /**
     * 连接数据库
     */    
    private static function conMEM() 
    {
        if (self::$mem === null)
        {
        	self::$mem = new Memcache;
			$servers = unserialize(MY_MEMCACHE_HOSTS);
			foreach ($servers as $server)
			{
				self::$mem->addServer($server['host'],$server['port'],MY_MEMCACHE_PCONNECT);
			} 		        	
            self::$leftTime = ini_get('session.gc_maxlifetime');
        }
    }
    
    /**
     * 返回当前在有效时间内的SESSION数量,注:当前无效
     *
     * @return int
     */    
    static public function num()
    {
    	return false;
    }
    
    /**
     * 返回当前在有效时间内所有的 SID,注:当前无效
     *
     * @return array
     */
    static public function allSid()
    {
    	return false;
    }    
    
    /**
     * 初始化
     */    
    static public function init()
    {
    	return true;
    }  
      
    //打开
    static public function open()
    {
        return true;
    }
    
    //关闭
    static public function close()
    {
        return true;
    }
    
    static public function read($session_id)
    {
    	self::conMEM();
    	return self::$mem->get($session_id,MEMCACHE_COMPRESSED);
    }
    
    static public function write($session_id,$session_val)
    {
    	self::conMEM();
    	self::$mem->set($session_id, $session_val, MEMCACHE_COMPRESSED, self::$leftTime);
        return true;
    }
    
    static public function destroy($session_id)
    {
    	self::conMEM();
    	return self::$mem->delete($session_id);
    }
    
    static public function gc($maxlifetime)
    {
    	//MEMCACHE 可以自动回收过期的数据
    	return true;
    }
}

session_set_save_handler('MY_SESSION::open','MY_SESSION::close','MY_SESSION::read','MY_SESSION::write','MY_SESSION::destroy','MY_SESSION::gc');
?>