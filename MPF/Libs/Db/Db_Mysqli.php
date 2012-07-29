<?php
/**
 * Mysqli 数据库驱动层
 */
class Db_Mysqli {

	private $Debug      = MY_DEBUG;       //调用模式下显示错误信息,否则不显示错误信息
    private $PConnect   = false;          //是否打开常连接
    private $Charset    = '';             //数据库编码
    private $response   = null;           //Response对象,调试时用到
    private $totalTime  = 0;              //SQL总的执行时间,调试时用到
    /**
     * 定义返回结果集数组的模型,取值如下:
     * MYSQLI_NUM   :使用数字作数组下标索引,下标从 0 开始
     * MYSQLI_ASSOC :使用字段名作数组下标索引(推荐)
     * MYSQLI_BOTH  :同时使用数字和字段名作数组下标索引
     */
    private $FETCH_MODE = MYSQLI_ASSOC;
    private $LinkID  = null; //数据库连接句柄
	private $QueryID = null; //查询句柄
	
	/**
	 * 构造函数,自动连接数据库
	 * @param string $Host     :主机名
	 * @param string $User     :用户名
	 * @param string $Pass     :密码
	 * @param string $DB       :数据库名
	 * @param int    $Port     :数据库连接端口
	 * @param string $Charset  :数据库编码字符集
	 * @param int    $Pconnect :是否打开常连接
	 * @return Db_Mysql Object
	 */
	public function __construct($Host='',$User='',$Pass='',$DB='',$Port='',$Charset='',$Pconnect=false)
	{
	    $this->Charset = str_replace('-','',$Charset);//将 utf-8 改为 utf8
	    $this->PConnect = $Pconnect;
        //--如果打开了调试则要生成数据对象
        if ($this->Debug) $this->response = Response::getInstance();			
        $this->Connect($Host,$User,$Pass,$DB,$Port);			
	}
     
	/**
	 * 连接数据库
	 *
	 * @param string $Host     :主机名
	 * @param string $User     :用户名
	 * @param string $Pass     :密码
	 * @param string $DB       :数据库名
	 * @param int    $Port     :数据库连接端口
	 * @return bool
	 */
	private function Connect($Host='',$User='',$Pass='',$DB='',$Port='') 
	{
	    $Host = $Port=='' ? $Host : $Host.':'.$Port;
	    $con = 'mysqli_connect';
	    if ($this->PConnect) $con = 'mysqli_connect';
	    if ($this->Debug)
	    {
	        $beginTime = MY_microtime_float();
	        $this->LinkID = $con($Host, $User, $Pass);
	        $endTime = MY_microtime_float();
	        $execTime = $endTime - $beginTime; //执行时间
	        $this->totalTime += $execTime;     //总时间
	        
	        $dbDebug = $this->response->get('dbDebug');
	        if (!$dbDebug) $dbDebug = array();
	        $dbDebug[] = array('Sql'=>'<font color="red">Connect DB Server.</font>',
	                           'ExecTime'=>$execTime,
	                           'TotalTime'=>$this->totalTime);
	        $this->response->set('dbDebug',$dbDebug);
	    }else{
	        $this->LinkID = $con($Host, $User, $Pass);
	    }
	    
	    if (!$this->LinkID)
	    {
            die('MYSQL Error: '.mysqli_error()); 
	    }
	    
	    if ($this->Charset != '') @mysqli_query($this->LinkID,'SET NAMES '.$this->Charset);
	    
        if (!@mysqli_select_db($this->LinkID,$DB))
		{
           die('MYSQL Error: '.$this->ErrorMsg()); 
        }	    
		return true;
	}

	/**
	 * 执行任何SQL语句
	 * 
	 * @param string $sql
	 * @return bool
	 */
	public function Execute($sql) 
	{
		return $this->_query($sql);
	}
    
	/**
	 * 返回最后 INSERT 时 auto_increment 型的主键值 
	 *
	 * @return int
	 */
	public function Insert_ID()
	{
		/* [PHP手册]
           如果 AUTO_INCREMENT 的列的类型是 BIGINT，则 mysqli_insert_id() 返回的值将不正确。
           可以在 SQL 查询中用 MySQL 内部的 SQL 函数 LAST_INSERT_ID() 来替代。 
		*/
		//return @mysqli_insert_id($this->LinkID);
		$id = $this->fetchOne('select last_insert_id()');
		return $id ? $id : 0;
	}

	/**
	 * 最后 DELETE UPDATE 语句所影响的行数 
	 * 
	 * @return int
	 */
	public function Affected_Rows()
	{
		return @mysqli_affected_rows($this->LinkID);
	}
	
	/**
	 * 最后查询结果集的行数 
	 * 
	 * @return int
	 */
	public function rowCount()
	{
		return @mysqli_num_rows($this->QueryID);
	}
	
	
	/**
	 * 返回当前MYSQL的错误信息
	 * 
	 * @return string
	 */
	public function ErrorMsg()
	{
	    return @mysqli_error($this->LinkID);
	}
	
	/**
	 * 返回当前MYSQL的错误序号  
	 * 
	 * @return string
	 */
	public function ErrorNo() 
	{
		return @mysqli_errno($this->LinkID);
	}
	
	/**
	 * 得到执行SQL语句后结果集第一行,第一行的值
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @return unknown
	 */
	public function fetchOne($sql)
	{
	    $row = $this->fetchRow($sql);
	    if ($row) return current($row);
	    else return false;
	}
	
	/**
	 * 得到执行SQL语句后结果集的第一行
	 * 
	 * @param string $sql
	 * @return array
	 */
	public function fetchRow($sql)
	{
		$this->Execute($sql);
		$row = @mysqli_fetch_array($this->QueryID,$this->FETCH_MODE);
		return $row;
	}
	
	/**
	 * 得到执行SQL语句后结果集的第一列的所有值
	 * 
	 * 说明: $bind 的用法请参考 Execute()成员
	 * 
	 * @param string $sql
	 * @return array
	 */
	public function fetchCol($sql)
	{
	    $result = array();
		$this->Execute($sql);
		while ($row = @mysqli_fetch_array($this->QueryID,$this->FETCH_MODE))
		{
		    $result[] = current($row);
		}
		return $result;
	}
	
	/**
	 * 返回处理后的结果集
	 * @param string $sql
	 * @return array
	 */
	public function fetchAssoc($sql)
	{
	    $result = array();
		$this->Execute($sql);
		while ($row = @mysqli_fetch_array($this->QueryID,$this->FETCH_MODE))
		{
		    $result[current($row)] = $row;
		}
		return $result;	    
	}	
	
	/**
	 * 得到执行SQL语句后所有的结果集
	 * 
	 * 说明: $bind 的用法请参考 Execute()成员
	 * 
	 * @param string $sql
	 * @return array
	 */
	public function fetchAll($sql)
	{
	    $result = array();
		$this->Execute($sql);
		while ($row = @mysqli_fetch_array($this->QueryID,$this->FETCH_MODE))
		{
		    $result[] = $row;
		}
		return $result;	  
	}
		
	/**
	 * 关闭当前数据库的连接
	 */
	public function Close()
	{
	    @mysqli_close($this->LinkID);
	    @mysqli_free_result($this->QueryID);
	    return true;
	}
	
	/**
	 * 格式化用于数据库的字符串
	 *
	 * 注意:这个函数与PDO中的不一样,它不会自动加 ''
	 * @param string $value
	 * @return string
	 */
	public function quoteStr($value)
	{
	    $value = @mysqli_real_escape_string($this->LinkID,$value);
	    return "'$value'";
	}
	
	/**
	 * 开始事务
	 * @return bool
	 */
	public function beginTransaction()
	{
		if (!mysqli_autocommit($this->LinkID,false)) return false;
	    $resource = @mysqli_query($this->LinkID,'BEGIN');
	    return $resource ? true : false;
	}
	
	/**
	 * 提交事务
	 * @return bool
	 */
	public function commit()
	{
	    return @mysqli_commit($this->LinkID);
	}
	
	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollBack()
	{
		return @mysqli_rollback($this->LinkID);
	}
	
		
    /////////////////////////////////////////////////////////////////////////////////////////////////	
    //                         以下为私有方法
    /////////////////////////////////////////////////////////////////////////////////////////////////	
    
    /**
	 * 统一执行SQL语句
	 */
	private function _query($sql)
	{
	    if ($this->Debug)
	    {
	        $beginTime = MY_microtime_float();
	        $this->QueryID = @mysqli_query($this->LinkID,$sql);
	        $endTime = MY_microtime_float();
	        $execTime = $endTime - $beginTime; //执行时间
	        $this->totalTime += $execTime;     //总时间
	        
	        $dbDebug = $this->response->get('dbDebug');
	        if (!$dbDebug) $dbDebug = array();
	        $dbDebug[] = array('Sql'=>$sql,
	                           'ExecTime'=>$execTime,
	                           'TotalTime'=>$this->totalTime);
	        $this->response->set('dbDebug',$dbDebug);
	    }else{
	        $this->QueryID = @mysqli_query($this->LinkID,$sql);
	    }
	    
	    //--如果在调试下执行SQL失败则提示
	    if (!$this->QueryID)
	    {
            if ($this->Debug) die(sprintf("<b>[SQL_QUERY]</b><br />%s <br /><b>[Error]</b><br />%s",$sql,$this->ErrorMsg())); 
	        return false;
	    }
	    return true;
	}	
	
	/**
	 * 解析SQL语句中的值定义
	 *
	 * @param string $sql
	 * @param array $bind
	 * @return string
	 */
	private function _parseSQL( $sql, $bind=array())
	{
		$searchArr = array();
		$replaceArr = array();
		if (count($bind))
		{
			foreach ($bind as $k=>$v)
			{
				$searchArr[] = ":$k";
				$replaceArr[] = $this->_returnValue($v);
			}
			$sql = str_replace($searchArr,$replaceArr,$sql);
		}
		return $sql;
	}        
	
    /**
     * 根据值的类型返回SQL语句式的值
     *
     * @param unknown_type $val
     * @return unknown
     */
	private function _returnValue($val)
	{
		if (is_int($val) || is_float($val)) return $val;
		else return $this->quoteStr($val);
	}	
	
}
?>