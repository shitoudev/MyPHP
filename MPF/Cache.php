<?php
/**
 * 缓存组件
 */

//防止重复引用文件
defined('MY_CACHE_INTERFACE') || require('Cache/iCache.php');
defined('MY_CACHE_BASECLASS') || require('Cache/cBase.php');
class Cache
{
	private static $instance = array('file'=>null,
	                                 'memcache'=>null,
	                                 'apc'=>null);
	/**
	 * 得到缓存实例
	 *
	 * @param string $key :缓存KEY
	 * @param string $cacheType :自定义缓存类型,为空表示采用系统默认;可选择的类型有: "file" "apc"
	 * @return unknown
	 */
	static public function getInstance($cacheType=MY_CACHE_DEFAULT_TYPE)
	{
		$cacheType = strtolower($cacheType);
		if (null === self::$instance[$cacheType])
		{
			$CType = ucfirst($cacheType);
			// ucfirst:将字符串第一个字符改大写
			$cacheObj = 'Cache_'.$CType;
			//加载对应的缓存组件
			$cacheFile = MY_CORE_ROOT."Cache/".$CType.MY_EXT;
			if(file_exists($cacheFile)) include_once($cacheFile);
			if(!class_exists($cacheObj)) 
			{
				$lang = Lang::getInstance();
				throw new Exception(sprintf($lang->get('Core_CacheClassNotFound'),$cacheObj));
			} 			
			self::$instance[$cacheType] = new $cacheObj();
		}
		return self::$instance[$cacheType];
	}
}
?>