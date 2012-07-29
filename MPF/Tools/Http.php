<?php
/**
 * HTTP相关工具
 * 此工具参数了 ThinkPHP 并加以改进
 */
/**
[示例]
-------- 控制器代码如下 ------------
Http::download('/home/a.zip');//下载 /home/a.zip 文件
Http::download('/home/a.zip','MyPHP');//下载时显示 'MyPHP'
Http::download('','MyPHP','yuanwei OK');//将字符串 "yuanwei OK" 当做文件内容下载
echo Http::clientIp();//显示客户IP
Http::setFormCache(); //设置表单在返回时不清空

// 模板请求
1:以GET方式请求得到 http://a.com/get.php?c=1&b=2 页面的内容
$url = 'http://a.com/get.php?c=1&b=2';
echo Http::sendRequest($url);
或
$url = 'http://a.com/get.php';
$param = array('c'=>1,'b'=>2);
echo Http::sendRequest($url,$param);

2:以POST方式请求 http://a.com/get.php
$url = 'http://a.com/get.php';
$param = array('c'=>1,'b'=>2);
echo Http::sendRequest($url,$param,'POST');


//发送404找不到页面的HTTP头信息
Http::sendStatus(404); 

// HTTP AUTH USER 请求,弹出浏览器系统论证对话框进行身份论证
$user = Http::getAuthUser(); //得到用户输入的用户名和密码
if ($user && $user['user']=='admin' && $user['pwd']=='admin')
{
    //如果正确则记录SESSION前返回到指定页面
    Session::set('ADMIN',true);
    return View::redirect(url('main'));
}else{
    //如果没有得到论证数据则弹出对话框,不正确则会再次调用当前脚本
    Http::sendAuthUser('Enter Username And Passwork','ERROR');
}

--------------------------------------------
[注意]
1：这个工具只是一个函数库,调用方法时不需要关心调用的先后顺序.
2:对于  HTTP AUTH USER 请求的两个方法比较特殊,请单独写个程序好好体会一下.
*/
class Http{
    
    /**
     * 下载文件 
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容
     * @param string $filename 下载文件名,要是绝对路径
     * @param string $showname 下载时显示的文件名,默认为下载的文件名
     * @param string $content  下载的内容
     * @return void
     */    
    static public function download($filename='', $showname='',$content='',$expire=180)
    {
        //得到下载长度
        if(file_exists($filename)) {
            $length = filesize($filename);
        }elseif($content != '') {
            $length = strlen($content);
        }else {
            $lang = Lang::getInstance();
            throw new Exception(sprintf($lang->get('Core_NotDownLoadFile'),$filename));
        }
        //最到显示的下载文件名
        if($showname == '') $showname = $filename;
        $showname = basename($showname);
        //发送Http Header信息 开始下载
	    Header("Content-type: application/octet-stream"); 
        Header("Accept-Ranges: bytes"); 
        Header("Accept-Length: ".$length); 
        Header("Content-Disposition: attachment; filename=".$showname); 
        //优先下载指定的内容再下载文件
        if($content == '' )
        {
            $file = @fopen($filename,"r");
            if (!$file)
            {
                $lang = Lang::getInstance();
                throw new Exception(sprintf($lang->get('Core_NotDownLoadFile'),$filename));
            }
            //一次读一K内容
            while(! @feof($file)) echo @fread($file,1024*1000);
            @fclose($file);             
        }else {
        	echo($content);
        }
        exit();
    }    

    /**
     * 发送HTTP状态头
     */
	static public function sendStatus($code) {
		static $_status = array(
			// Informational 1xx
			100 => 'Continue',
			101 => 'Switching Protocols',
			
			// Success 2xx
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			
			// Redirection 3xx
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',  // 1.1
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			// 306 is deprecated but reserved
			307 => 'Temporary Redirect',
			
			// Client Error 4xx
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			
			// Server Error 5xx
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			509 => 'Bandwidth Limit Exceeded'
		);
		if(array_key_exists($code,$_status)) {
			header('HTTP/1.1 '.$code.' '.$_status[$code]);
		}
	}
	
	/**
	 * 发送 HTTP AUTH USER 请求
	 * 使其弹出一个用户名／密码输入窗口。当用户输入用户名和密码后,脚本将会被再次调用.
	 * 这时就可以调用 Http::getAuthUser()方法得到输入的用户名和密码了
	 */
	static public function sendAuthUser($hintMsg,$errorMsg='')
	{
        header("WWW-Authenticate: Basic realm=\"{$hintMsg}\"");
        header('HTTP/1.0 401 Unauthorized');
        exit($errorMsg);
	}
	
	/**
	 * 得到 HTTP AUTH USER 请求后的用户名和密码
	 * 如果没有发送该请求该会返回 false,否则返回包含用户名和密码的数组，格式如下:
	 * array('user'=>'yuanwei',
	 *       'pwd'=>'123456');
	 */
	static public function getAuthUser()
	{
	    if (isset($_SERVER['PHP_AUTH_USER']))
	    {
	        return array('user'=>$_SERVER['PHP_AUTH_USER'],
	                     'pwd' =>$_SERVER['PHP_AUTH_PW']);
	    }else{
	        return false;
	    }
	}
	
	/**
	 * 得到客户端IP地址
	 */
	static public function clientIp()
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
	 * 设置页面缓存,使表单在返回时不清空
	 */
	static public function setFormCache()
	{
	    session_cache_limiter('private,must-revalide');
	}
	
	/**
	 * 发送 HTTP 请求,支持 GET POST 方式,推荐系统中安装 CURL 扩展库
	 * 返回请求后的页面内容
	 *
	 * @param string $url : URL地址,如果是当前域名下的话则支持以域名为根的相对路径,如 "/get.php"
	 * @param array $params : GET或POST的参数,如 array('id'=>1,'name'=>'yuanwei')
	 * @param string $method : 请求方式 GET POST
	 */
	static public function sendRequest($url,$params=array(),$method='GET')
	{
		//URL 支持相对路径,但必需以当前域名为根
		if (strstr($url,'http') === false)
		{
			$url = 'http://'.$_SERVER["HTTP_HOST"].$url;
		}

		//组合参数
		$dataParams = array();
		foreach ($params as $key => &$val) {
		  if (is_array($val)) $val = implode(',', $val);
		  $dataParams[] = $key.'='.urlencode($val);
		}
		$dataParams = implode('&',$dataParams);

		//如果是 GET 方式的话则要连接 URL
		if ($method=='GET' && $dataParams!='')
		{
			$url .= strstr($url,'?')!==false ? '&'.$dataParams : '?'.$dataParams;
		}

		//如果安装了 curl 库则优先使用它
		if (function_exists('curl_init'))
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			if ($method == 'POST')
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParams);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			if ($error = curl_error($ch))
			{   
				return null;   
			}  
			curl_close($ch);
			return $result;

		}else{

			$context =
			array('http' =>
				  array('method' => $method,
						'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
									'Content-length: '.strlen($dataParams),
						'content' => $dataParams));
			$contextid=stream_context_create($context);
			$sock=fopen($url, 'r', false, $contextid);
			if ($sock)
			{
				$result='';
				while (!feof($sock)) $result.=fgets($sock, 4096);
				fclose($sock);
			}
			return $result;
		}
	}
	
	
}
?>