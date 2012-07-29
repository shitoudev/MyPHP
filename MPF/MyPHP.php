<?php
/**
 * 框架主体
 */
//框架版本
define('MYPHP_VER','0.1.2');
//定义框架核心配置文件
define('MY_CORE_CONF',MY_APP_ROOT .'Configs/MyPHPConfig.inc.php');
//加载框架所要用的函数库
require(MY_CORE_ROOT.'Libs/CoreFunction.inc.php');
//如果框架核心配置文件不存在,则自动创建应用程序相关的目录和文件
if (!file_exists(MY_CORE_CONF)) makeApp();
//加载框架配置文件
require(MY_CORE_CONF);
//请求处理组件
require(MY_CORE_ROOT.'Request.php');            
//数据交互响应组件
require(MY_CORE_ROOT.'Response.php');           
//"工厂"组件
require(MY_CORE_ROOT.'Factory.php');            
//语言组件
require(MY_CORE_ROOT.'Lang.php');               
//控件器
require(MY_CORE_ROOT.'Controller.php');         
//调试器
require(MY_CORE_ROOT.'Dispatcher.php');         
//视图解析组件
require(MY_CORE_ROOT.'View.php');           
//框架初始化程序
require(MY_CORE_ROOT.'Libs/CoreInit.php');  

class MyPHP
{
	private $request;
	private $response;
	private $cache;	
	private $controller;
    public function __construct()
    {
    	$this -> request  = Request::getInstance();
    	$this -> response = Response::getInstance();
    	//判断是否存在有效缓存
		if(MY_CACHE_ENABLE && !$this -> request -> isPostBack()) //缓存开启,且不是提交过的页面
		{
			include('Cache.php');
			$this -> cache = Cache::initial(md5($this -> request -> currentURL())); //以$_GET为缓存的key,注意用户包括用户session
			if($output =  $this -> cache -> fetch()) //有有效缓存,输出并终止程序执行
			{  
				exit($output);
			}
		}
    }
    
    public function run()
    { 	
    	$dispatcher = new Dispatcher();
    	//调用钩子
    	if (function_exists(HOOK_MYPHP_BEGIN)) call_user_func(HOOK_MYPHP_BEGIN,$this -> request,$this -> response);
		$controller = $dispatcher -> getController();
    	if(is_object($controller))
    	{
			$view = $controller-> execute(); //一般返回的是视图对象
    		if($view instanceof View_Common)  //输出view,如有需要,先缓存
    		{
				if(is_object($this -> cache))
				{
					$content = $view -> fetch();
					$this -> cache -> store($content);
					echo $content;
				}
				else{ //不缓存
					$view -> display();
				}
			}
    	}else{ //控制器初始失败

            //定义了404出错页面
            if (MY_HTTP_404_PAGE)
            {
                header("Location: ".MY_HTTP_404_PAGE);
                exit(1);
			}else{
                $lang = Lang::getInstance();
			    throw new Exception(sprintf($lang -> get('Core_ControllerNotFound'),$this -> response->get('ControllerName')));
            }
		}
    }
	
}
?>