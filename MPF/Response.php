<?php
/**
 * 数据交互(应答)组件
 */

class Response
{
	private $data = array();
	private $error = array();
	private $debug = array();
	private static $instance = null;
	
	private function __construct(){}
	
	/**
	 * 生成单个实例
	 *
	 * @return Response object
	 */
	public static function getInstance() 
	{
    	if (null === self::$instance)
    	{
    	    self::$instance = new Response();
        }
        return self::$instance;
    }
	
	/**
	 * 设置数据
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function set($key,$value='')
	{
	    if (is_array($key))
	    {
	        foreach ($key as $k=>$v) $this -> data[$k] = $v;
	    }else{
	        $this -> data[$key] = $value;
	    }
	}
	
	/**
	 * 获得数据
	 *
	 * @param string $key :键名,没有其键值则返回 null 如果为空则返回对应的数据.
	 * @return unknown
	 */
	function get($key = '')
	{
		return $key ? (isset($this -> data[$key]) ? $this -> data[$key] : null) : $this -> data;
	}    

    /**
     * 判断是否有错误信息
     *
     * @return bool
     */
	function hasError() 
	{
		return count($this -> error)>0;
	}
	
	/**
	 * 添加错误信息
	 *
	 * @param mixed $field
	 * @param string $errorMsg
	 * 
	 * 例子: addError('error1','文件没有找到!');
	 *      addError(array('error1'=>'文件没有找到!',
	 *                     'error1'=>'没有登录!')); 
	 */
	function addError ($field,$errorMsg='')
	{
		if (is_array($field))
		{
			foreach ($field as $k=>$v) $this -> error[$k] = $v;
		}else{
			$this -> error[$field] = $errorMsg;
		}
	}
	
	/**
	 * 得到错误信息
	 *
	 * @param mixed $field :错误索引,如果没有定义则返回所有的错误信息,否则返回对应的错误信息
	 * @return unknown
	 */
	function getError ($field = '') 
	{
		return $field ? (isset($this -> error[$field]) ? $this -> error[$field] : null) : $this -> error;
	}

	
    /**
     * 判断是否有调试信息
     *
     * @return bool
     */
	function hasDebug() 
	{
		return count($this -> debug) > 0;
	}
	
	/**
	 * 添加调试信息
	 *
	 * @param mixed $field
	 * @param string $debugMsg
	 * 
	 * 例子: addDebug('error1','文件没有找到!');
	 *      addDebug(array('error1'=>'文件没有找到!',
	 *                     'error2'=>'没有登录!')); 
	 */
	function addDebug ($field,$debugMsg='')
	{
		if (is_array($field))
		{
			foreach ($field as $k=>$v) $this -> debug[$k] = $v;
		}else{
			$this -> debug[$field] = $errorMsg;
		}
	}
	
	/**
	 * 得到调试信息
	 *
	 * @param mixed $field :错误索引,如果没有定义则返回所有的调试信息,否则返回对应的调试信息
	 * @return unknown
	 */
	function getDebug ($field = '') 
	{
		return $field ? (isset($this -> debug[$field]) ? $this -> debug[$field] : null) : $this -> debug;
	}

	/**
	 * 得到数据库调试信息
	 * 
	 * 说明:SQL调试信息只有当 MY_DEBUG 常量设为 true 时才有
	 * @param prec:SQL查询的时间小数位
	 * @return string 
	 */
	function getSQLDebugInfo($prec=8)
	{
	    $html = '';
	    $body = '';
	    if (MY_DEBUG && $this->get('dbDebug'))
	    {
	        $tpl = MY_CORE_ROOT.'Libs/db.debug.tpl';
	        $conent = file_get_contents($tpl);
	        eval($conent); //生成 $_InfoHeader, $_InfoBaby, $_InfoFooter 三个变量
	        foreach ($this->get('dbDebug') as $row)
	        {
        		$search = array("[ExecTime]","[TotalTime]","[Sql]");
        		$replace = array(round($row['ExecTime'],$prec),round($row['TotalTime'],$prec),$row['Sql']);
        		$body .= str_replace($search,$replace,$_InfoBaby);	            
	        }
	        $html = $_InfoHeader . $body . $_InfoFooter;
	    }
	    return $html;
	}
}
?>