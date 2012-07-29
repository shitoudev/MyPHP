<?php
/**
 * 工厂组件
 */
class Factory 
{
	/**
	 * 载入 model
	 *
	 * @param string $objPath :模型类库路径,以模型根目录为准.如:"admin_jobs",表示引用 Admin/Jobs.php
	 * @param mixed $param :模型构造函数的参数 如果是int型，只是传送参数
	 * @param mixed $param2 :模型构造函数的参数 如果是int型，只是传送参数
	 */
		
	static function loadModel($objPath, $parama=null, $paramb=null)
	{
		require_once(MY_CORE_ROOT.'Model.php');
		//支持多级目录,如 'admin_news'
		$objPath = str_replace("_","/",$objPath,$count); 
		// 'Admin/News'
		$mPath = implode("/",array_map('ucfirst',explode("/",$objPath)));
		// 'News'
		$mObj = $count > 0 ? substr(strrchr($mPath, "/"), 1) : $mPath;
		// 'Admin/News.php'
		$modelFile = MY_MODEL_PATH.$mPath.MY_EXT;
		if(file_exists($modelFile)) 
		{
			require_once($modelFile);
			$modelClass = "Model_".ucfirst($mObj);
			if(class_exists($modelClass)) 
			{
				if (is_numeric($parama) || in_array($parama,array("quick","slow"))) {					
					$obj = new $modelClass($paramb);
					$obj->chipId = $parama;
				} else {					
					$obj = new $modelClass($parama);
				}
			}
		}
		
		if(!isset($obj) || !is_object($obj) || !($obj instanceof Model))
		{
			$lang = Lang::getInstance();
			throw new Exception(sprintf($lang->get('Core_ModelNotFound'),$mPath.MY_EXT));
		}
		return $obj;
	}


	/**
	 * 载入 Controller
	 * 
	 * @param string $objPath :控制器类库路径,以控制器根目录为准.如:"admin_jobs",表示引用 Admin/Jobs.php
	 * @param mixed $param :控制器构造的函数
	 */
	static function loadController($objPath,$param = null)
	{
		//支持多级目录,如 'admin_news'
		$objPath = str_replace("_","/",$objPath,$count); 
		// 'Admin/News'
		$cPath = implode("/",array_map('ucfirst',explode("/",$objPath)));
		// 'News'
		$cObj = $count > 0 ? substr(strrchr($cPath, "/"), 1) : $cPath;
		// 'Admin/News.php'
		$controlFile = MY_CONTROLLER_PATH.$cPath.MY_EXT;
		if(file_exists($controlFile)) 
		{
			require_once($controlFile);
			$controlClass = ucfirst($cObj); 
			if(class_exists($controlClass)) $obj = new $controlClass($param,true);
		}
		if(!isset($obj) || !is_object($obj) || !($obj instanceof Controller))
		{
			$lang = Lang::getInstance();
			throw new Exception(sprintf($lang->get('Core_ControllerNotFound'),$cPath.MY_EXT));
		}
		return $obj;
	}	
	
	/**
	 * 载入自定义类库,一般是该系统所专用的,注意:使用该方法前一定要定义 MY_COMMON_PATH 常量
	 * 
	 * @param string $objPath :自定义类库路径,以自定义类库根目录为准.如:"admin_jobs",表示引用 Admin/Jobs.php
	 * @param mixed $param :控制器构造的函数
	 */
	static function loadCommon($objPath,$param = null)
	{
		//支持多级目录,如 'admin_news'
		$objPath = str_replace("_","/",$objPath,$count); 
		// 'Admin/News'
		$cPath = implode("/",array_map('ucfirst',explode("/",$objPath)));
		// 'News'
		$cObj = $count > 0 ? substr(strrchr($cPath, "/"), 1) : $cPath;
		// 'Admin/News.php'
		$commonlFile = MY_COMMON_PATH.$cPath.MY_EXT;
		if(file_exists($commonlFile)) 
		{
			require_once($commonlFile);
			$commonClass = ucfirst($cObj); 
			if(class_exists($commonClass)) $obj = new $commonClass($param);
		}
		if(!isset($obj) || !is_object($obj))
		{
			$lang = Lang::getInstance();
			throw new Exception(sprintf($lang->get('Core_CommonNotFound'),$cPath.MY_EXT));
		}
		return $obj;
	}
	

	/**
	 * 载入数据库对象模型
	 * 
	 * @param string $dbHandler :数据库句柄,请看 MyPHPConfig.inc.php 中的 [多数据库设置]
	 *                          : $dbHandler 取值为其中的下标如 "mysql2" 和 "oracle"
	 * @return Model Object     :表示你可以用所有Model中的所有方法了
	 */
	static public $db = array(); 
	static function loadDB($dbHandler)
	{
	    $key = md5($dbHandler);
	    if (!isset(self::$db[$key]))
	    {
    	    $moreDB = unserialize(MY_MORE_DBS);
    	    if (!isset($moreDB[$dbHandler])) return false;
    	    $dbSet = $moreDB[$dbHandler];	        
	        //self::$db[$key] = new Model($dbSet);
	    }
	    //return self::$db[$key];
		return $dbSet;
	}
	
	
	/**
	 * 载入 Api
	 * 
	 * @param string $objPath :Api方法
	 */
	 
	static function loadApi($objPath) {
		$apiFile = MY_API_ROOT.$objPath.MY_EXT;
		if(file_exists($apiFile)) 
		{
			require_once($apiFile);
			return new $objPath();			
		}
	}
}
?>