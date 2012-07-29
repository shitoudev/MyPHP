<?php
/**
 * SMARTY接口
 */

require_once("Common.php");
class View_Smarty extends View_Common implements iView
{
	private $tpl;
	private $tplFile;

	public function __construct($tplFile)
	{
		include_once('Libs/Smarty/Smarty.class.php');
		$this -> tpl = new Smarty();
		$this -> tpl -> left_delimiter = MY_SMARTY_LEFT_DELIMITER;
		$this -> tpl -> right_delimiter = MY_SMARTY_RIGHT_DELIMITER;
		$this -> tpl -> template_dir = MY_VIEW_PATH;
		$this -> tpl -> config_dir   = MY_SMARTY_CONFIG_DIR;
		$this -> tpl -> compile_dir  = MY_SMARTY_COMPILE_DIR;
        $this -> tpl -> caching		 = $this->getCacheState();
		$this -> tpl -> cache_dir	 = MY_SMARTY_CACHE_DIR;
		$this -> tpl -> cache_lifetime	 = MY_SMARTY_CACHE_DIR;
        $this -> tpl -> force_compile = MY_DEBUG;
        $response = Response::getInstance();
        
		switch (MY_TPL_PATH_ORDER)
		{
		    case 'AUTO':
		        $controllerPath = $response->get('currentControllerPath');
		        break;
		    case 'ONLY':
		        $controllerPath = '';
		        break;
		}
		$this -> tpl -> template_dir .= MY_TEMPLATE.'/'.$controllerPath;
		//$this -> tpl -> template_dir .= MY_LANG.'.'.MY_CHARSET.'/'.$controllerPath;
		//$this -> tplFile = MY_LANG.'.'.MY_CHARSET.'/'.$controllerPath.$tplFile; 
		$this->tplFile = $tplFile;
		if(!file_exists($this -> tpl -> template_dir . $this -> tplFile))
		{
			$lang = Lang::getInstance();
			throw new Exception (sprintf($lang->get('Core_TplNotDefine'),$this -> tpl -> template_dir . $this -> tplFile));
		}
		
		if (MY_DEBUG)
		{
		    $this -> tpl -> debugging = true;
		    $this -> tpl -> debug_tpl = MY_CORE_ROOT.'Libs/smarty.debug.tpl';
		    $response->set('TemplateFile', $this -> tpl -> template_dir . $this -> tplFile);
		}		
		
		//--赋于视图中可用的对象
		$this -> tpl -> assign('request', Request::getInstance());
		$this -> tpl -> assign('response', $response);
		$this -> tpl -> assign('vanDomain', MY_VAN_DOMAIN!='' ? 'http://'.MY_VAN_DOMAIN : '');
		$this -> tpl -> assign('rootDomain', MY_ROOT_DOMAIN!='' ? 'http://www.'.MY_ROOT_DOMAIN : '');
		
		//--钩子扩展
		if (function_exists(HOOK_SMARTY_ASSIGN))
		{
		    $assign = call_user_func(HOOK_SMARTY_ASSIGN);
	        if (!is_array($assign))
	        {
    			$lang = Lang::getInstance();
    			throw new Exception(sprintf($lang->get('Core_HookFunError'),HOOK_SMARTY_ASSIGN));
	        }		    
		    $this -> tpl -> assign($assign);
		}
	}
	
	public function display()
	{
	    if (MY_DEBUG) $this->tpl->display($this -> tplFile);
        else echo $this -> fetch();
	}
	
	public function fetch()
	{
		return $this -> tpl -> fetch($this -> tplFile);
	}
}
?>