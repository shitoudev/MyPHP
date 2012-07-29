<?php
/**
 * PDO 数据库驱动层
*/
class Db_Pdo {
    
	private $Debug      = MY_DEBUG;       //调用模式下显示错误信息,否则不显示错误信息
	private $DBType     = 'MYSQL';        // 'MYSQL' 或 'ORACLE'
    private $PConnect   = false;          //是否打开常连接
    private $Charset    = '';             //数据库编码
    private $response   = null;           //Response对象,调试时用到
    private $totalTime  = 0;              //SQL总的执行时间,调试时用到
    /**
     * 定义返回结果集数组的模型,取值如下:
     * PDO::FETCH_NUM   :使用数字作数组下标索引,下标从 0 开始
     * PDO::FETCH_ASSOC :使用字段名作数组下标索引(推荐)
     * PDO::FETCH_BOTH  :同时使用数字和字段名作数组下标索引
     */
    private $FETCH_MODE = PDO::FETCH_ASSOC;
    private $dnsType = '';   //PDO 连接的类型
    private $LinkID  = null; //数据库连接句柄
	private $QueryID = null; //查询句柄
	
	/**
	 * 构造函数,自动连接数据库
	 * @param string $Type     :数据库类型 
	 * @param string $Host     :主机名
	 * @param string $User     :用户名
	 * @param string $Pass     :密码
	 * @param string $DB       :数据库名
	 * @param int    $Port     :数据库连接端口
	 * @param string $Charset  :数据库编码字符集
	 * @param int    $Pconnect :是否打开常连接
	 * @return Db_Mysql Object
	 */
	public function __construct($Type='',$Host='',$User='',$Pass='',$DB='',$Port='',$Charset='',$Pconnect=false)
	{
	    $this->LinkID = null;
	    $this->DBType = $Type;
	    $this->PConnect = $Pconnect;
	    $this->Charset = $Charset;
	    
        //检测对应的PDO驱动是否安装了
        switch ($this->DBType)
        {
            case 'MYSQL':
                if (!in_array('mysql',PDO::getAvailableDrivers()))
                {
                    die('The PDO_MYSQL extension not loaded!');
                }	    
                $this->dnsType = 'mysql';
                break;
            case 'ORACLE':
                if (!in_array('oci',PDO::getAvailableDrivers()))
                {
                    die('The PDO_ORACLE extension not loaded!');
                }
                $this->dnsType = 'oci';
                break;
        }

        //--如果打开了调试则要生成数据对象
        if ($this->Debug) $this->response = Response::getInstance();			
        
        $this->Connect($Host,$User,$Pass,$DB,$Port);			
	}
     
	/**
	 * 连接数据库
	 *
	 * @param string $Host :主机名
	 * @param string $User :用户名
	 * @param string $Pass :密码
	 * @param string $DB   :数据库名
	 * @param int $Port    :数据库连接端口
	 * @return bool
	 */ 
	private function Connect( $Host='',  $User='',  $Pass='',  $DB='', $Port='') 
	{
	    $DNS = $this->dnsType.':host='.$Host.';dbname='.$DB;
	    if ($Port != '') $DNS .= ';port='.$Port;
	    if ($this->Charset != '') $DNS .= ';charset='.$this->Charset;
        try {
            $driver_options = array();
            if ($this->PConnect) $driver_options = array(PDO::ATTR_PERSISTENT => true);
            
            if ($this->Debug)
            {
    	        $beginTime = microtime_float();
                $this->LinkID = new PDO($DNS, $User, $Pass, $driver_options);
    	        $endTime = microtime_float();
    	        $execTime = $endTime - $beginTime; //执行时间
    	        $this->totalTime += $execTime;     //总时间
    	        
    	        $dbDebug = $this->response->get('dbDebug');
    	        if (!$dbDebug) $dbDebug = array();
    	        $dbDebug[] = array('Sql'=>'<font color="red">Connect DB Server.</font>',
    	                           'ExecTime'=>$execTime,
    	                           'TotalTime'=>$this->totalTime);
    	        $this->response->set('dbDebug',$dbDebug);
            }else{ 
                $this->LinkID = new PDO($DNS, $User, $Pass, $driver_options);
            }
            
        }catch (PDOException $e){
            die('Error: '.$e->getMessage()); 
        }	
        
        //设置连接的数据库编码,不知为什么 DNS 中的 charset 属性对MYSQL没有作用.  
        if ($this->dnsType == 'mysql')
        {
            $Charset = str_replace('-','',$this->Charset);//将如 utf-8 改为 utf8
            $this->LinkID->exec("SET NAMES $Charset");
        }
        
        //--强制列名是小写
        $this->LinkID->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        
        //--错误提示 PDO::ERRMODE_EXCEPTION 抛出异常 
        //          PDO::ERRMODE_WARNING   显示警告错误.
        //          PDO::ERRMODE_SILENT    不显示错误信息，只显示错误码.
        if ($this->Debug) $this->LinkID->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        else $this->LinkID->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

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
		return $this->LinkID->lastInsertId();
	}

	/**
	 * 最后 DELETE UPDATE 语句所影响的行数 
	 * 
	 * @return int
	 */
	public function Affected_Rows()
	{
		return $this->QueryID->rowCount();
	}
	
	/**
	 * 最后查询结果集的行数 
	 * 
	 * @return int
	 */
	public function rowCount()
	{
		return $this->QueryID->rowCount();
	}
	
	
	/**
	 * 返回当前MYSQL的错误信息
	 * 
	 * @return string
	 */
	public function ErrorMsg()
	{
	    $ay = $this->LinkID->errorInfo();
	    return $ay ? $ay[2] : '';
	}
	
	/**
	 * 返回当前MYSQL的错误序号  
	 * 
	 * @return string
	 */
	public function ErrorNo() 
	{
		return $this->LinkID->errorCode();
	}
	
	/**
	 * 得到执行SQL语句后结果集第一行,第一行的值
	 * 
	 * @param string $sql
	 * @return unknown
	 */
	public function fetchOne($sql)
	{
	    $this->Execute($sql);
	    return $this->QueryID->fetchColumn();
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
		return $this->QueryID->fetch();
	}
	
	/**
	 * 得到执行SQL语句后结果集的第一列的所有值
	 * 
	 * @param string $sql
	 * @return array
	 */
	public function fetchCol($sql)
	{
		$this->Execute($sql);
		return $this->QueryID->fetchAll(PDO::FETCH_COLUMN);
	}
	
	/**
	 * 返回处理后的结果集
	 * @param string $sql
	 * @return array
	 */
	public function fetchAssoc($sql)
	{
	    $this->Execute($sql);
        $data = array();
        while ($row = $this->QueryID->fetch($this->FETCH_MODE))
        {
            $data[current($row)] = $row;
        }
        return $data;	    
	}	
	
	/**
	 * 得到执行SQL语句后所有的结果集
	 * 
	 * @param string $sql
	 * @return array
	 */
	public function fetchAll($sql)
	{
		$this->Execute($sql);
		return $this->QueryID->fetchAll();
	}
		
	/**
	 * 关闭当前数据库的连接
	 */
	public function Close()
	{
		$this->LinkID  = false;
	}
	
	/**
	 * 格式化用于数据库的字符串
	 *
	 * @param string $value
	 * @return string
	 */
	public function quoteStr($value)
	{
	    return $this->LinkID->quote($value);
	}
	
	/**
	 * 开始事务
	 * @return bool
	 */
	public function beginTransaction()
	{
	    return $this->LinkID->beginTransaction();
	}
	
	/**
	 * 提交事务
	 * @return bool
	 */
	public function commit()
	{
	    return $this->LinkID->commit();
	}
	
	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollBack()
	{
	    return $this->LinkID->rollBack();
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
	        $beginTime = microtime_float();
	        $this->QueryID = $this->LinkID->query($sql);
	        $endTime = microtime_float();
	        $execTime = $endTime - $beginTime; //执行时间
	        $this->totalTime += $execTime;     //总时间
	        
	        $dbDebug = $this->response->get('dbDebug');
	        if (!$dbDebug) $dbDebug = array();
	        $dbDebug[] = array('Sql'=>$sql,
	                           'ExecTime'=>$execTime,
	                           'TotalTime'=>$this->totalTime);
	        $this->response->set('dbDebug',$dbDebug);
	    }else{
	        $this->QueryID = $this->LinkID->query($sql);
	    }
	    //--查询成功后设置模式
	    if ($this->QueryID)
	    {
	        //--只有字段名做为下标索引
            $this->QueryID->setFetchMode($this->FETCH_MODE);
            return true;
	    }
	    return false;
	}	
	
}
?>