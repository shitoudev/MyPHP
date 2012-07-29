<?php
/**
 * 框架级的所有要用到的函数都定义在这里
*/

/**
 * 去掉魔术引用,格式化字符串; 引用在 Request.php
 *
 * @param mixed $value
 * @return mixed
 */
function stripslashes_deep($value)
{
	return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
}

/**
 * 格式化字符串; 引用在 Request.php
 *
 * @param mixed $value
 * @return mixed
 */
function addslashes_deep($value)
{
	return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
}


/**
 * REQUEST 变量包括 GET 和 POST 值的输出
 * 因为框架可以设定所有GET 和 POST的值是否加魔术引用处理,即特殊字符加 "\"
 * 但输出显示时我们又不想要显示 "\" 所以请用该函数进行输出
 * 该函数一般在模板中使用得比较多,一般用于从数据库中取出数据后在页面上的显示
 *
 * @return unknown
 */
function recho($request_text)
{
	static $bool = null;
	if ($bool === null)
	{
		if (QP_MAGIC_QUOTES && get_magic_quotes_gpc()) //框架开启了自动加 "\" 功能
		{
			$bool = true;
		}else{
			$bool = false;
		}
	}
	return $bool ? stripslashes($request_text) : $request_text;
}

/**
 * 返回当前浮点式的时间,单位秒;主要用在调试程序程序时间时用
 * @return float
 */
function microtime_float()
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}

/**
 * 得到当前的根域名 如"www.163.com" 中的 "163.com"
 */
function rootDomain()
{
	$host = (defined('MY_ROOT_DOMAIN')&&MY_ROOT_DOMAIN) ? MY_ROOT_DOMAIN : $_SERVER["HTTP_HOST"];
	if (strtolower($host) == 'localhost' || $host == '127.0.0.1') $host = null;
	return $host;
}

/**
 * 任何变量的调试输出
 * @param mixed $var
 */
function dump($var,$exit=false)
{
	echo '<pre style="font-size:16px; color:#0000FF">';
	if (is_array($var))
	{
		print_r($var);
	}else if(is_object($var)){
		echo get_class($var)." Object";
	}else if(is_resource($var)){
		echo (string)$var;
	}else{
		echo var_dump($var);
	}
	echo '</pre>';
	if ($exit) exit;
}

/**
 * 自动装载类库组件,只限于框架自带的组件,这样在程序中就可以直接
 *
 * $obj = new Acl();//会自动执行 __autoload() 函数
 * 
 * @param string $class :组件名称 
 */
function __autoload($class) {
	// $fileName = ucfirst($class).MY_EXT;
	// if (file_exists($fileName)) {
	// 	require_once($fileName);
	// }

	require_once(ucfirst($class).MY_EXT);

}

/**
 * Het模板引擎专用include
 * @param string $file :模板文件
 */
 function include_tpl($file) {
	include(MY_VIEW_PATH.MY_TEMPLATE.'/'.$file);	
 }
 
 /**
 * Het模板引擎专用parse
 * @param string $file :模板文件
 */
 function include_parse($c, $a, $param='') {
	echo file_get_contents('http://'.MY_APP_DOMAIN.'/index.php?c='.$c.'&a='.$a.'&param='.$param);
 }
 
 /**
 * Het模板引擎专用 Response
 * @param  string $str
 * @return string
 */
function response_get($name) {	
	 $response = Response::getInstance();
	 echo $response->get($name);
}

/**
* 传送加密数据
*/
function base64_enarr($arr) {
	$code = base64_encode(serialize($arr));
	$rep1 = array("+", "=", "/");
	$rep2 = array("jiajia", "dengdeng", "xiexie");
	$code = str_replace($rep1, $rep2, $code);
	return $code;
}

/**
* 获取解密数据
*/
function base64_dearr($str) {
	$rep1 = array("+", "=", "/");
	$rep2 = array("jiajia", "dengdeng", "xiexie");
	$code = str_replace($rep2, $rep1, $str);
	$code = unserialize(base64_decode($code));
	return $code;
}

/**
 * 装载框架本身的组件,并返回实例对象.
 * 
 * $acl = load('acl'); 
 *
 * @param string $class :组件名称
 * @param mixed  $param :构造时的参数
 */
function load($class,$param='')
{
	$C = ucfirst($class);
	$file = $C.MY_EXT;
	include_once($file);
	return new $C($param);
}

/**
 * 无限连接字符串
 *
 * @return string
 */
function concat() {
	$args = func_get_args();
	$str = '';
	foreach ($args as $val)	$str.= $val;
	return $str;
}

/**
 +----------------------------------------------------------
 * 字符串按字节截取，支持中英文
 +----------------------------------------------------------
 * @static
 * @access public 
 +----------------------------------------------------------
 * @param string $string 需要转换的字符串
 * @param string $length 截取长度(字节数)
 * @param string $etc 后缀
 * @param string $count_words 是否判断字节
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr( $string,$length = 80,$etc='...',$count_words = true ) {
	//if(!extension_loaded("mbstring")){dl("mbstring.so");}
	//mb_internal_encoding("UTF-8");
	if ($length == 0)return '';
	if ( strlen( $string ) <= $length ) return $string;
	preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $info);
	if( $count_words ){
		$j = 0;
		for($i=0; $i<count($info[0]); $i++) {
			$wordscut .= $info[0][$i];
			if( ord( $info[0][$i] ) >=128 ){
				$j = $j+2;
			}else{
				$j = $j + 1;
			}
			if ($j >= $length ) {
				return $wordscut.$etc;
			}
		}
		return join('', $info[0]);
	}
	return join("",array_slice( $info[0],0,$length ) ).$etc;
}

/**
 * 框架自动创建应用程序目录与文件
 *
 * @return void
 */
function makeApp()
{
	//因因还没有运行 CoreInit.php 所以要显示包含
	require_once(MY_CORE_ROOT.'Tools/File.php');
	//定义默认目录
	$appDir = array('controller'    => MY_APP_ROOT.'Controller/',
	'models'        => MY_APP_ROOT.'Models/',
	'views'         => MY_APP_ROOT.'Views/default/',
	'configs'       => MY_APP_ROOT.'Configs/',
	'common'        => MY_APP_ROOT.'Common/',
	'libs'          => MY_APP_ROOT.'Libs/',
	'lang'          => MY_APP_ROOT.'Lang/',
	'hook'          => MY_APP_ROOT.'Hook/',
	'data'          => MY_APP_ROOT.'Cache/Data/',
	'logs'          => MY_APP_ROOT.'Cache/Logs/',
	'fileCache'     => MY_APP_ROOT.'Cache/File_Cache/',
	'session'       => MY_APP_ROOT.'Cache/Session/',
	'hetCompile'    => MY_APP_ROOT.'Cache/Het_Compile/',
	'hetCache'      => MY_APP_ROOT.'Cache/Het_Cache/',
	);
	//创建目录
	foreach ($appDir as $key=>$dir)
	{
		File::mkDir($dir);
	}
	//复制默认目录配置文件到指定的目录中
	$corePath = 'Libs/AppDefaultFile/';
	$defaultFile = array(
	//框架核心配置文件
	array('file'=>'MyPHPConfig.inc.php',
	'dstDir'=>'configs'),
	//框架构子函数定义文件
	array('file'=>'AppHooks.inc.php',
	'dstDir'=>'hook'),
	//应用程序自定义配置文件
	array('file'=>'AppConfig.inc.php',
	'dstDir'=>'configs'),
	//应用程序自定义函数文件
	array('file'=>'appFunction.inc.php',
	'dstDir'=>'libs'),

	//测试语言文件
	array('file'=>'lang.php',
	'dstDir'=>'lang'),
	//测试控制器文件
	array('file'=>'Main.php',
	'dstDir'=>'controller'),
	//测试组件文件
	array('file'=>'TestCommon.php',
	'dstDir'=>'common'),
	//测试模型文件
	array('file'=>'TestModel.php',
	'dstDir'=>'models'),
	//测试视图模板文件
	array('file'=>'index.html',
	'dstDir'=>'views'),
	);
	foreach ($defaultFile as $array)
	{
		$file = $array['file'];
		$p = $array['dstDir'];
		File::copy(MY_CORE_ROOT.$corePath.$file , $appDir[$p].$file);
	}
}

/**
 * 根据 MY_URL_METHOD 模式生成URL地址
 * 
 * @param string $controller :控制器,'this'表示当前控制器名称
 * @param string $action :动作名,为空表示用默认的动作名称.
 * 
 * 例子:
 * url('main','index',array('id'=>1,'name'=>'myphp')); (一般在程序中调用)
 * url('main','index','id',1,'name','myphp'); (一般在SMARTY视图中调用)
 * url('main','index','id=10','name=myphp'); (程序和模板中都适合)
 * 
 * 根据 MY_URL_METHOD 模式生成的如果如下:
 * index.php/main/index/id/1/name/myphp.html  或
 * index.php?c=main&a=index&id=1&name=myphp
 * @return string
 */
function url($controller,$action='',$param='')
{
	$args = func_get_args();
	if (!is_array($param))
	{
		$args = _urlParam($args);
		//将后续参数组合成数组
		$putArr = array();
		$len = count($args)-1;
		for ($i=2; $i<=$len; $i+=2)
		{
			$key = $args[$i];
			$val = isset($args[$i+1]) ? $args[$i+1] : '';
			$putArr[$key] = $val;
		}
		$param = $putArr;
	}
	$response = Response::getInstance();
	$controller = $controller=='this' ? $response->get('ControllerName') : $controller;
	$action = $action ? $action : MY_DEFAULT_ACTION;
	if ($action == 'this') $action = $response->get('ActionName');
	switch (MY_URL_METHOD)
	{
		case 'STANDARD':
			$url  = $_SERVER['SCRIPT_NAME'].'?'.MY_CONTROLLER_NAME.'='.$controller;
			$url = str_replace('index.php','',$url);
			$url .= '&'.MY_ACTION_NAME.'='.$action;
			foreach ($param as $key=>$val) $url .= '&'.$key.'='.urlencode($val);
			break;
		case 'SEO':
			$url = dirname($_SERVER['SCRIPT_NAME']);
			if (strlen($url) == 1) $url = '/'.$controller;//根目录的情况
			else                   $url .= '/'.$controller;
			$url .= '/'.$action;
			foreach ($param as $key=>$val) $url .= '/'.$key.'/'.urlencode($val);
			$url .= MY_URL_EXT;
			break;
		default: //PATHINFO模式 或 REWRITE模式
		if (MY_URL_METHOD == 'REWRITE')
		{
			$url = dirname($_SERVER['SCRIPT_NAME']);
			if (strlen($url) == 1) $url = '/'.$controller;//根目录的情况
			else                   $url .= '/'.$controller;
		}else{
			$url = $_SERVER['SCRIPT_NAME'].'/'.$controller;
		}
		$url .= '/'.$action;
		foreach ($param as $key=>$val) $url .= '/'.$key.'/'.urlencode($val);
		$url .= MY_URL_EXT;
		break;
	}
	return $url;
}

/**
 * url() 函数调用(专用)
 */
function & _urlParam($args)
{
	if (count($args) < 3) return $args;
	$arr = array_slice($args,0,2);
	$len = count($args)-1;
	//替换内容
	$repStr = '/';
	$search = array('&','=');
	$replace = array($repStr,$repStr);
	for ($i=2; $i<=$len; $i++)
	{
		$str = trim(str_replace($search,$replace,$args[$i]),$repStr);
		$ay = explode($repStr,$str);
		$arr = array_merge($arr,$ay);
	}
	return $arr;
}

/**
 * 获取用户IP
 * @return string
 */
function get_client_ip()
{
   if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
       $ip = getenv("HTTP_CLIENT_IP");
   else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
       $ip = getenv("HTTP_X_FORWARDED_FOR");
   else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
       $ip = getenv("REMOTE_ADDR");
   else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
       $ip = $_SERVER['REMOTE_ADDR'];
   else
       $ip = "unknown";
   return($ip);
}

/**
 * 过滤HTMl角本
 * @param  string $str
 * @return string
 */
function strip_script($str)
{
	$farr = array(
	"/<(\/?)(script|i?frame|style|html|body|title|link|meta|\?|\%)([^>]*?)>/isU", // 过滤 <script 等可能引入恶意内容或改变布局的代码
	"/(<[^>]*)on([^>]*?)>/isU", // 过滤js的on事件，現在可能會誤殺一些正常代碼，還需完善
	);
	$tarr = array(
	"",
	"",
	);
	$str = preg_replace( $farr, $tarr, $str);
	return $str;
}

/**
 * 过滤ID
 * @param  string $str
 * @return string
 */
function _intval($int) {
	$int = intval($int);
	if (empty($int)) {
		exit('error:int');
	} else {
		return $int;
	}
}
 
?>