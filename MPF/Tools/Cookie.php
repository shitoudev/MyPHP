<?php
/**
COOKIE 相关操作
[示例]
-------- cook.php 控制器代码如下 ------------
Cookie::set('ck', 'myphp', 24*60*60); // 设置一个名为'ck'的COOKIE,内容为"myphp",有效时间为一天.
Cookie::get('ck'); // 取得名为'ck'的COOKIE值.
Cookie::del('ck'); // 删除名为'ck'的COOKIE.
-------------------------------------------
[注意]
1:有了这个工具后请不要使用 Request 中的 getCookie() 和 setCookie() 方法了.
*/
class Cookie {

    /**
     * 私有方法：加密 COOKIE 数据
     */	
	static private function _encode($str)
	{
		$str = base64_encode($str);
		$search = array('=','+','/');
		$replace = array('_','-','|');
		return str_replace($search,$replace,$str);
	}

    /**
     * 私有方法：解密 COOKIE 数据
     */	
	static private function _decode($str)
	{
		$replace = array('=','+','/');
		$search = array('_','-','|');
        $str = str_replace($search,$replace,$str);
		return base64_decode($str);
	}
    
    /**
     * 获得 COOKIE 数据
     *
     * @param string $name:域名称,如果为空则返回整个 $COOKIE 数组
     * @param boolean $decode:是否自动解密,如果 set() 时加密了则这里必需要解密,并且解密只能针对单个值
     * @return mixed
     */	
	static public function get($name='',$decode=false)
	{
	   $request = Request::getInstance();
	   $cookie = $request->magicQuotes($_COOKIE); 
       $val=  $name ? (isset($cookie[$name]) ? $cookie[$name] : null) : $cookie;
       return $name!=''&&$decode ? self::_decode($val) : $val;
	}
	
	/**
	 * 设置COOKIE
	 *
	 * @param string $name :COOKIE名称
	 * @param string $value :值
	 * @param int $time :有效时间,以秒为单位,0:表示会话期间内
	 * @param boolean $encode :是否加密
	 */
	static public function set($name,$value='',$time=0,$encode=false)
	{
        $time = ($time == 0) ? 0 : (time()+$time); 
        $value = $encode ? self::_encode($value) : $value;
		return setcookie($name, $value, $time,'/',rootDomain());
	}
	
	/**
	 * 删除 COOKIE
	 *
	 * @param string $name :COOKIE名称
	 */
	static public function del($name)
	{
	    self::set($name,'',-86400 * 365);
	}	
	

	/**
	 * 清除 COOKIE
	 */
	static public function clear()
	{
	    foreach ($_COOKIE as $key=>$val) self::del($key);
	}
}
?>