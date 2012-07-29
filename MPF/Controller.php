<?php
/**
 * 控制器其类
 */
 
abstract class Controller
{
	protected $request;
	protected $response;
	private $action;
	
	/**
	 * 控制器初始化 
	 * 
	 * @param string $action :控制器中的方法名称
	 * @param boolean $loadController :当前是否是用 Factory 组件载入控制器
	 */
	function __construct($action = MY_DEFAULT_ACTION,$loadController=false)
	{
		$this -> request = Request::getInstance();
		$this -> response = Response::getInstance();
		$this -> action = $action;
		if(!$loadController && !method_exists($this,$action)) 
		{
		    //检查该控制器是否定义专门的空操作动作
		    if (!method_exists($this,MY_EMPTY_ACTION))
		    { 
                if (MY_HTTP_404_PAGE)
                {
                    header("Location: ".MY_HTTP_404_PAGE);
                    exit(1);
                }else{
                    $lang = Lang::getInstance();
                    throw new Exception(sprintf($lang->get('Core_ActionNotFound'),get_class($this),$action));
                }
		    }else{
		        $this -> action = MY_EMPTY_ACTION;
		    }
		}
	}
	
	/*
	 * 执行相应的方法
	 */
	function execute()
	{
	    $bool = false; //标识当前是否有钩子
	    if (function_exists(HOOK_EXECUTE_ACTION))
	    {
	        $arrHook = array();
	        $arrTmp = call_user_func(HOOK_EXECUTE_ACTION);
	        if (!is_array($arrTmp))
	        {
    			$lang = Lang::getInstance();
    			throw new Exception(sprintf($lang->get('Core_HookFunError'),HOOK_EXECUTE_ACTION));
	        }
	        //下标转为小写,因为动作名称是小写的
	        foreach ($arrTmp as $key=>$arr) $arrHook[strtolower($key)] = $arr;
	        $actionPath = $this->response->get('currentActionPath');
	        //如果当前动作定义的钩子则调用它
	        if (array_key_exists($actionPath,$arrHook))
	        {
	            $bool = true;
	            if (array_key_exists('begin',$arrHook[$actionPath]))
	            {
    	            //如果是类方式调用
    	            if (is_array($arrHook[$actionPath]['begin'])) $arrHook[$actionPath]['begin'][0]->{$arrHook[$actionPath]['begin'][1]}();
    	            else $arrHook[$actionPath]['begin']();
	            }
	        }
	    }
	    
		$obj = $this -> {$this -> action}();
		
		if ($bool)
		{
		    if (array_key_exists('end',$arrHook[$actionPath]))
		    {
                //如果是类方式调用
                if (is_array($arrHook[$actionPath]['end'])) $arrHook[$actionPath]['end'][0]->{$arrHook[$actionPath]['end'][1]}();
                else $arrHook[$actionPath]['end']();
		    }
		}
		
		return $obj;
	}
}
?>