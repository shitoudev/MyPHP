<?php
/**
 * 请求组件
 */

class Request
{
	private $post    = array();
	private $get     = array();
	private static $instance = null;
	
	/**
	 * 构造函数
	 */
	public function __construct()	
	{
	    $_POST   = $this->magicQuotes($_POST);
	    $_GET   = $this->magicQuotes($_GET);
		$this -> post =  $_POST;
		$this -> get =  $_GET;
		unset($_POST,$_GET);
	}
	
	/**
	 * 根据设置处理魔术引用
	 */
	public function magicQuotes($val,$flag=false)
	{
	    if (MY_MAGIC_QUOTES) //自动加 "\"
	    {  
    		if (!get_magic_quotes_gpc() || $flag)
    		{
    		    if (is_array($val)) $val = array_map('addslashes_deep', $val);
    		    else $val = addslashes_deep($val);
    		}
	    }else{ //自动去掉 "\"
    		if (get_magic_quotes_gpc() || $flag)
    		{
    		    if (is_array($val)) $val = array_map('stripslashes_deep', $val);
    		    else $val = stripslashes_deep($val);
    		}
	    }	    
	    return $val;
	}
	
	/**
	 * 生成单个实例
	 *
	 * @return Request object
	 */
	static public function getInstance() 
	{
    	if (null === self::$instance)
    	{
            self::$instance = new Request();
        }
        return self::$instance;
    }
    
    /**
     * 设置 POST 的值
     *
     * @param mixed $name:域名称
     * @param string $value:值
     */
    public function setPost($name,$value='')
    {
        if (!is_array($name))
        {
            
            $this -> post[$name] = $this->magicQuotes($value,true);
        }else{
            $name = $this->magicQuotes($name,true);
            foreach ($name as $k=>$v) $this -> post[$k] = $v;           
        }
    }
    
    /**
     * 获得 POST 数据
     *
     * @param string $name:域名称,如果为空则返回整个 $_POST 数组
     * @param string $callback:回调函数
     * @return mixed
     */
	public function getPost($name = '',$callback = '')
	{       
       $re =  $name ? (isset($this -> post[$name]) ? $this -> post[$name] : null) : $this -> post;
	   if (!$re || !$callback || !function_exists($callback))
	   {
	   	    return $re;
	   }else{
			if (!$name) return array_map ($callback,$this->post);
			elseif (isset($this->post[$name])) return call_user_func($callback,$this->post[$name]);
			else return null;
	   }
	}
	
    /**
     * 设置 GET 的值
     *
     * @param string $name:域名称
     * @param string $value:值
     */
    public function setGet($name,$value='')
    {
        if (!is_array($name))
        {
            $this -> get[$name] =  $this->magicQuotes($value,true);
        }else{
            $name = $this->magicQuotes($name,true);
            foreach ($name as $k=>$v) $this -> get[$k] = $v;           
        }
    }

    /**
     * 获得 GET 数据
     *
     * @param string $name:域名称,如果为空则返回整个 $_POST 数组
     * @param string $callback:回调函数
     * @return mixed
     */    
	public function getGet($name = '',$callback = '')
	{       
       $re =  $name ? (isset($this -> get[$name]) ? $this -> get[$name] : null) : $this -> get;
	   if (!$re || !$callback || !function_exists($callback))
	   {
	   	    return $re;
	   }else{
			if (!$name) return array_map ($callback,$this->get);
			elseif (isset($this->get[$name])) return call_user_func($callback,$this->get[$name]);
			else return null;
	   }	    
	}
	
	/**
	 * getGet() 函数别名
	 */
    public function get($name = '',$callback = '')
    {       
       return $this -> getGet($name,$callback);
	}
	
    /**
     * 获得 COOKIE 数据
     *
     * @param string $name:域名称,如果为空则返回整个 $COOKIE 数组
     * @param string $callback:回调函数
     * @return mixed
     * 
     * 注意:该方法是考虑兼容问题，请直接使用 Cookie::get() 
     */	
	public function getCookie($name = '',$callback='')
	{
	    return Cookie::get($name,$callback);
	}
	
	/**
	 * 设置COOKIE
	 *
	 * @param string $name :COOKIE名称
	 * @param string $value :值
	 * @param int $time :有效时间,以秒为单位,0:表示会话期间内
	 * 
	 * 注意:该方法是考虑兼容问题，请直接使用 Cookie::set() 
	 */
	public function setCookie($name,$value='',$time=0)
	{
	    return Cookie::set($name,$value='',$time);
	}
		
    /**
     * 获得 SESSION 数据,
     *
     * @param string $name:域名称,如果为空则返回整个 $SESSION 数组
     * @param string $callback:回调函数
     * @return mixed
     * 
     * 注意:该方法是考虑兼容问题，请直接使用 Session::get() 
     */		
	public function getSession ($name='',$callback='')
	{
	    return Session::get($name,$callback);
	}
	
	/**
	 * 设置SESSION的值
	 *
	 * @param string $name :SESSION变量名称
	 * @param mixed $value :对应值
	 * 
	 * 例子: setSession ('uid',10);
	 *      setSession(array('uid'=>10,
	 *                       'uname'=>'myphp'));
	 * 
	 * 注意:该方法是考虑兼容问题，请直接使用 Session::set() 
	 */
	public function setSession ($name,$value='')
	{
	    Session::set($name,$value);
	}
	
	/**
	 * 判断当前请求是否为 POST
	 *
	 * @return bool
	 */
    public function isPostBack()
	{
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	
	/**
	 * 返回上一个页面的 URL 地址(来源)
	 * @return string
	 */
	public function frontURL()
	{
	    return isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
	}
	
	/**
	 * 返回当前页面的 URL 地址
	 * @return string
	 */
	public function currentURL()
	{
	    $http = isset($_SERVER["HTTPS"])&&$_SERVER["HTTPS"] ? 'https' : 'http';
	    $http .= '://';
	    return $http.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];	    
	}
	
	 /**
     * 得到输入的参数变量 $_SERVER['argv']数组中的变量
     *
     * @param int $offset:域名称
	 * @return mixed
     */
    public function argv($offset=null)
    {
		if (null === $offset)
		{
			return $_SERVER['argv'];
		}else{
			return isset($_SERVER['argv']) && isset($_SERVER['argv'][$offset]) ? $_SERVER['argv'][$offset] : null;
		}
    }

    /**
     * 得到文件域 $_FILES 数组
     *
     * @param int $offset:域名称
	 * @return mixed
     */
    public function file($name='')
    {
		if (!$name)
		{
			return $_FILES;
		}else{
			return isset($_FILES[$name]) ? $_FILES[$name] : null;
		}
    }

	/**
	 * 得到全局变量 $_SERVER 的值
	 * 
	 * @param string $name
	 * @return string
	 */
	public function server($name)
	{
		if ('REMOTE_ADDR' == $name)
		{
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else
			{
				$clientIp = $_SERVER[$name];
			}
			return $clientIp;
		}
		else
		{
			return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
		}
	}
}
?>