<?php
/**
 * 文件式缓存
 */
/**
 * 由于文件缓存没办法设置过期自动失效，所以只能用一个临时文件记录来模似,该文件的保存的数据格式如下:
 * array('key1'=>array('create'=>'创建时间',
 *                     'life'=>'过期时间'),
 *       'key2'=>array('create'=>'创建时间',
 *                     'life'=>'过期时间'),
 *      );
 */
defined('MY_CACHE_SDATA_FILE') || define('MY_CACHE_SDATA_FILE',MY_CACHE_FILE_PATH.'fileCacheRuntime.data');

class Cache_File extends cBase implements iCache 
{
	private $SDATA;
	//构造函数：得到缓存关系数据
	public function __construct()
	{
		if (file_exists(MY_CACHE_SDATA_FILE))
		{
			$this->SDATA = include_once(MY_CACHE_SDATA_FILE);
		}else{
			$this->SDATA = array();
		}
	}
	
	//将缓存系统数据存盘
	private function _saveSDATAfile()
	{
        $fileContent = '<?php return '.var_export($this->SDATA,true).' ?>';
        File::write(MY_CACHE_SDATA_FILE,$fileContent);
        //file_put_contents(MY_CACHE_SDATA_FILE,$fileContent);	                           		
	}
	
	//记录缓存系统数据
	private function _saveSDATA($key,$life)
	{
		$key = $this->key($key);
		$this->SDATA[$key] = array('create'=>time(),
		                           'life'=>$life);
		$this->_saveSDATAfile();                           
	}
	
	//检查缓存KEY是否已过期，如果过期则删除它
	private function _checkSDATA($key, $forceRemove=false)
	{
		$key = $this->key($key);
		//KEY不存在
		if (!isset($this->SDATA[$key]))
		{
			return false;
		} 
		//缓存无效
		if (time() > ($this->SDATA[$key]['create']+$this->SDATA[$key]['life']) || $forceRemove)
		{
			@unlink($this->_filename($key));
			unset($this->SDATA[$key]);
			$this->_saveSDATAfile();    
			return $forceRemove ? true : false;
		}else{
			return true;
		}
	}
    //根据加密后的吸最后最到文件
    private function _cachefile($encodeKey)
    {
    	return MY_CACHE_FILE_PATH.'file_'.$encodeKey.'.cache';       	
    }
    //得到缓存文件名
    private function _filename($key)
    {
    	return $this->_cachefile($this->key($key));    	
    }
	
    //设置
	public function set($key, $val, $expire=MY_CACHE_DEFAULT_LIFETIME)
	{
		$file = $this->_filename($key);
		File::write($file,$val);
		//记录数据
		$this->_saveSDATA($key,$expire);
		return true;
	}
	
	//获得单个值
	public function get($key)
	{
		//关闭了缓存 或 缓存过期了
		if (!$this->enable() || !$this->_checkSDATA($key))
		{
			return false;
		}
		$file = $this->_filename($key);
		return @file_get_contents($file);
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
		return $this->_checkSDATA($key, true);		
	}
	
	//刷新
	public function flush()
	{
    	foreach ($this->SDATA as $key=>$values)
    	{
			//缓存无效
			if (time() > ($this->SDATA[$key]['create']+$this->SDATA[$key]['life']))
			{
				@unlink($this->_cachefile($key));
				unset($this->SDATA[$key]);
				$this->_saveSDATAfile();    
			}
		}
		return true;
	}
	
	//清空所有
    public function clear()
    {
    	foreach ($this->SDATA as $key=>$values)
    	{
    		@unlink($this->_cachefile($key));
    	}
    	$this->SDATA = array();
    	$this->_saveSDATAfile();
    }
}
?>