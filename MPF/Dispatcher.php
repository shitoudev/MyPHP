<?php
/**
 * 调试器
 */

class Dispatcher
{
    private $tmpController;
	private $controller;
	private $controllerFile;
	private $action;
	private $request;
	private $response;
	private $actionPath;

	function __construct()
	{
		$this -> request = Request::getInstance();
		$this -> response = Response::getInstance();
		$this -> router();
		//钩子调用
		if (function_exists(HOOK_PARSE_DOMAIN))
		{
		    if (!call_user_func(HOOK_PARSE_DOMAIN,$_SERVER["SERVER_NAME"],$this -> request,$this -> response)) exit(1);
		}
	}

	/**
	 * 生成路径
	 */
	private function router()
	{
	    //检测提交防刷新
        if (MY_POST_NO_REFRESH && $this->request->isPostBack())
        {
            $this->chkPostRefresh();
        }

	    //分析URL模式
	    switch (MY_URL_METHOD)
	    {
	        case 'STANDARD':
	            $this->urlStandard();
	            break;
	        case 'SEO':
	        	// Nginx 特殊处理
				//$_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'];
				//$_SERVER['PATH_INFO'] = str_replace('index.php?','',$_SERVER['PATH_INFO']);
				//SEO模式
    		    if (!isset($_SERVER['PATH_INFO']) || empty($_SERVER['PATH_INFO']) || $_SERVER['PATH_INFO']=='/')
    		    {
    		        $this->urlStandard();
    		    }else{
    		        $this->urlPathinfo('seo');
    		    }
	        	break;
	        default: //PATHINFO 或 REWRITE
    		    //兼容STANDARD模式
    		    if (!isset($_SERVER['PATH_INFO']) || empty($_SERVER['PATH_INFO']) || $_SERVER['PATH_INFO']=='/')
    		    {
    		        $this->urlStandard();
    		    }else{
    		        $this->urlPathinfo();
    		    }
	            break;
	    }

	    //将控制器与方法加入到GET中
	    $this -> request -> setGet(array(MY_CONTROLLER_NAME=>$this->tmpController,
	                                     MY_ACTION_NAME=>$this->action));

        $controller = $this->tmpController;
		$controller =  preg_replace("/[^a-z\_1-9]/i","",$controller); //过滤一些危险字符
		//--保存当前的纯控制器名称
		$this->response->set('ControllerName',$controller);

		$this -> actionPath = $controller."_".$this -> action; //动作全称,用来检查权限

		$controller = str_replace("_","/",$controller,$count);
		$controller = implode("/",array_map('ucfirst',explode("/",$controller))); //统一将路径和文件名转成首字母大写
		$this -> controller = $count > 0 ? substr(strrchr($controller, "/"), 1) : $controller; //得到如 "school/main" 中的 "main" 最后执行的控制器
		$this -> controllerFile = MY_CONTROLLER_PATH .$controller.MY_EXT;

		//--如果打开了调试功能
		if (MY_DEBUG)
		{
		    $this->response->set('ControllerFile',$this -> controllerFile);
		    $this->response->set('ActionName',$this -> action);
	    }
		//--将控制控制器文件的相关路径保存给 SMARTY 对象使用
		//这里修复制如 admin_ad 这种控制器与目录名有重复字符时在SMARTY得到路径时的BUG 2008-7-31
		if ($count > 0)
		{
		    $currentControllerPath = str_replace('/'.$this->controller,'',$controller) . '/';
		}else{
		    $currentControllerPath = str_replace($this->controller,'',$controller);
		}
		$this->response->set('currentControllerPath',$currentControllerPath);
		//--将当前动作全称保存给 ACL 权限使用
		$this->response->set('currentActionPath',$this -> actionPath);
    }

	/**
	 * 得到当前的控件器
	 */
	public function getController()
	{
	    if (MY_ACL_ACCESS && MY_ACL_AUTO_CHECK) //框架自动检测权限
	    {
	        //--调用钩子程序
	        if (function_exists(HOOK_ACL_ACCOUNTS))
	        {
	            $aclInfo = call_user_func(HOOK_ACL_ACCOUNTS,$this -> request,$this -> response);
	            if (!is_array($aclInfo))
	            {
	                $lang = Lang::getInstance();
	                throw new Exception(sprintf($lang->get('Core_HookFunError'),HOOK_ACL_ACCOUNTS));
	            }
	        }else{
	            $aclInfo = array('userId'=>'','productType'=>'');
	        }

	        require_once('Acl.php');
	        $acl = new Acl($aclInfo['productType']);
	        if (!$acl->privCheck($aclInfo['userId'],$this -> actionPath))
	        {
	            $bool = true;
	            //调用钩子程序
	            if (function_exists(HOOK_ACL_NOT_PRIV))
	            {
	               $bool = call_user_func(HOOK_ACL_NOT_PRIV,$aclInfo['userId'],$this -> actionPath,$this -> request,$this -> response);
	            }
	            if ($bool)
	            {
        			$_SESSION['request_url'] = $_SERVER['SCRIPT_NAME']."?".$_SERVER['QUERY_STRING']; //保存当前url,以便登陆后重定向
        			$lang = Lang::getInstance();
        			$msg = $lang->get('Core_NotPriv');
        			View::redirect(MY_DEFAULT_PAGE,$msg,10);
	            }
	            exit;
	        }
	    }

		if(file_exists($this -> controllerFile))
		{
    		require_once($this -> controllerFile);
    		if(class_exists($this -> controller)) //控制器的类名要与文件名一致
    		{
				$objController = new $this -> controller($this -> action);
			}
		}

		if (isset($objController) && $objController instanceof Controller) return $objController;
		else return null;
	}

	/**
	 * 私有方法:防止页面刷新提交
	 */
	private function chkPostRefresh()
	{
		$urlKey	= 'lastPostRefreshTime_'.md5($_SERVER['PHP_SELF']);
		//检查最后的时间
		$ltime = Cookie::get($urlKey);
		//echo $ltime;
		//是属于刷新操作
	    if ($ltime && ($_SERVER['REQUEST_TIME']-$ltime)<=MY_POST_INTERVAL_TIME)
	    {
	        //转到当前页面(将提交取消掉)
	        header('Location: '.$this->request->currentURL());
			//header('HTTP/1.1 304 Not Modified');
			exit;
	    }else{
	        //记录当前页面的访问时间
            Cookie::set($urlKey,$_SERVER['REQUEST_TIME'],MY_POST_INTERVAL_TIME);
	        //header('Last-Modified:'.(date('D,d M Y H:i:s',$_SERVER['REQUEST_TIME']-MY_INTERVAL_TIME)).' GMT');
	    }
	}


	/**
	 * 私有方法:处理 URL STANDARD模式
	 */
	private function urlStandard()
	{
		$this -> action = $this -> request -> get(MY_ACTION_NAME) ? $this -> request -> get(MY_ACTION_NAME) : MY_DEFAULT_ACTION;
		$this->tmpController = $this -> request -> get(MY_CONTROLLER_NAME) ? $this -> request -> get(MY_CONTROLLER_NAME) : MY_DEFAULT_CONTROLLER;
	}

	/**
	 * 私有方法:处理 URL PATHINFO模式 或 REWRITE模式 或 SEO模式
	 */
	private function urlPathinfo($mode='')
	{
	    //分解PATHINFO
	    $url = MY_URL_EXT ? str_replace(MY_URL_EXT,'',$_SERVER['PATH_INFO']) : $_SERVER['PATH_INFO'];
	    //支持在 PATHINFO 模式下后面接参数,如 /index.php/user/reg.html&a=6&b=6
	    $url = str_replace('&','/',$url);
	    $url = str_replace('=','/',$url);

	    if ($mode=='seo') {
	    	$url = str_replace('/', '_', $url);
	    	$tmpArr = array_map('urldecode',explode('_',$url));
	    } else {
	    	$tmpArr = array_map('urldecode',explode('/',$url));
	    }

	    array_splice($tmpArr,0,1);
        //dump($tmpArr,true);
	    //约定前两项值为分别为控制器名和动作方法
	    $this->tmpController = $tmpArr[0];
	    $this -> action = isset($tmpArr[1])&&$tmpArr[1]!=''&&$tmpArr[1]!='p' ? $tmpArr[1] : MY_DEFAULT_ACTION;
	    //将后续参数加入到 Request 组件中
	    $putGet = array();
	    $len = count($tmpArr)-1;
	    for ($i=$tmpArr[1]=='p'?1:2; $i<=$len; $i+=2)
	    {
	        $key = $tmpArr[$i];
	        $val = isset($tmpArr[$i+1]) ? $tmpArr[$i+1] : '';
	        $putGet[$key] = $val;
	    }
	    $this -> request -> setGet($putGet);
	}

}
?>