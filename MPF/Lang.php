<?php
/**
 * 语言组件
 */

class Lang
{
	private $ln = array();
	static private $instance = null;
	function __construct()
	{
		$this->ln = include_once(MY_CORE_ROOT."Lang/".MY_LANG.".".MY_CHARSET.MY_EXT); //框架核心语言包
	}
	
	//单例
	static public function getInstance()
	{
    	if (null === self::$instance)
    	{
            	self::$instance = new Lang();
        }
        return self::$instance;
    }
    
    /**
     * 载入语言包
     * 
     * 注意:语言包的格式定义请看 zh-cn.utf-8.php 的定义
     * @param string $langFileName :语言文件名,以常量 MY_APP_LANG_PATH 为根目录
     */
    public function load($langFileName)
    {
        if (file_exists(MY_APP_LANG_PATH.$langFileName))
        {
            $lang = include_once(MY_APP_LANG_PATH.$langFileName);
            $this->ln = array_merge($this->ln,$lang);
            return $lang;
        }
        return false;
    }
    
    //返回对应语言
	public function get($key,$format = true)
	{
		if(isset($this->ln[$key])) return $this->ln[$key];
		elseif($format)          return $key; //直接返回键名
		else                     return null;
	}
}
?>