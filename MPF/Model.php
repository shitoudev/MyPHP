<?php
/**
 * 数据库操作模型基类
 */ 
class Model
{
	private $db = null;
	public $response = null;
	static private $dbObj = array();
	/**
	 * 根据驱动类型加载对应的文件
	 * @param string $driver :驱动名称
	 * @return string :驱动对应的类名
	 */ 
	private function _loadDriver($driver)
	{
		$class = 'Db_'.ucfirst(strtolower($driver));
		require_once ('Libs/Db/'.$class.'.php');
		return $class;
	}


	/**
	 * 构造函数,自动连接数据库
	 */ 
	function __construct($dbSet='')
	{
		if ($dbSet == '')
		{
			$key = md5('');
		}else{
			$key = md5($dbSet['type'].$dbSet['host'].$dbSet['user'].$dbSet['database']);
		}

		if (!isset(self::$dbObj[$key]))
		{
			self::$dbObj[$key] = $this->_getDB($dbSet);
		}
		$this->db = self::$dbObj[$key];

		if (MY_DEBUG) {
			$this ->response = Response::getInstance();
		}
	}

	//得到实例对象
	private function _getDB($dbSet='')
	{
		//采用默认值
		if ($dbSet == '')
		{
			$dbSet['type']     = MY_DB_DEFAULT_TYPE;
			$dbSet['host']     = MY_DB_DEFAULT_HOST;
			$dbSet['user']     = MY_DB_DEFAULT_USER;
			$dbSet['password'] = MY_DB_DEFAULT_PASSWORD;
			$dbSet['database'] = MY_DB_DEFAULT_DATABASE;
			$dbSet['port']     = MY_DB_DEFAULT_PORT;
			$dbSet['charset']  = MY_DB_DEFAULT_CHARSET;
			$dbSet['pconnect'] = MY_DB_DEFAULT_PCONNECT;
			$dbSet['driver']   = MY_DB_DEFAULT_DRIVER;
		}
		//加载对应的驱动文件
		$class = $this->_loadDriver($dbSet['driver']);
		switch ($class)
		{
			case 'Db_Pdo':
				//--没有安装PDO库
				if (!extension_loaded('pdo'))
				{
					die('The PDO extension not loaded!');
				}
				$dbn = new Db_Pdo($dbSet['type'],$dbSet['host'],$dbSet['user'],$dbSet['password'],$dbSet['database'],$dbSet['port'],$dbSet['charset'],$dbSet['pconnect']);
				break;
			case 'Db_Mysql':
				$dbn = new Db_Mysql($dbSet['host'],$dbSet['user'],$dbSet['password'],$dbSet['database'],$dbSet['port'],$dbSet['charset'],$dbSet['pconnect']);
				break;
		}
		return $dbn;
	}

	/**
	 * 组合各种条件查询并返回结果集
	 *
	 * 说明:$where 可以是字符串或数组,如果定义为数组则格式有如下两种:
	 *      $where = array('id'=>1,
	 *                     'name'=>'myphp');
	 *      解析后条件为: "id=1 AND name='myphp'"
	 * 
	 *      $where = array('id'=>array('>='=>1),
	 *                     'name'=>array('like'=>'%myphp%'));
	 *      解析后条件为: "id>=1 AND name LIKE '%myphp%'"
	 * 
	 * 注意:#where 中的条件解析后都是用 AND 连接条件,其它形式请直接用字符串的方法传值
	 * 
	 * @param string $fields :字段名
	 * @param string $tables :表
	 * @param mixed $where   :条件
	 * @param string $order  :排序字段
	 * @param string $limit  :返回记录行,格式 "0,10"
	 * @param string $group  :分组字段
	 * @param string $having :筛选条件
	 * @param int $cacheTime :结果集缓存的时间,单位秒 0:表示不缓存
	 * @return array
	 */
	public function Select($fields,$tables,$where='',$order='',$limit='',$group='',$having='',$cacheTime=0)
	{
		$sql = 'SELECT '.$fields.' FROM '.$tables;
		if ($where != '') $sql .= ' WHERE '.$this->_parseWhere($where);
		if ($group != '') $sql .= ' GROUP BY '.$group;
		if ($having != '') $sql .= ' HAVING '.$having;
		if ($order != '') $sql .= ' ORDER BY '.$order;
		if ($limit != '') $sql .= ' LIMIT '.$limit;
		if (MY_DEBUG) {
			$this->responseSql($sql);
		}
		return $this->fetchAll($sql,array(),$cacheTime);
	}


	/**
	 * 查询单条记录,组合各种条件查询并返回结果集
	 *
	 * @param string $fields :字段名
	 * @param string $tables :表
	 * @param mixed $where   :条件,详细请看 Select()成员
	 * @param string $order  :排序字段
	 * @param string $group  :分组字段
	 * @param string $having :筛选条件
	 * @return array
	 */
	public function SelectOne($fields,$tables,$where='',$order='',$group='',$having='')
	{
		$sql = 'SELECT '.$fields.' FROM '.$tables;
		if ($where != '') $sql .= ' WHERE '.$this->_parseWhere($where);
		if ($group != '') $sql .= ' GROUP BY '.$group;
		if ($having != '') $sql .= ' HAVING '.$having;
		if ($order != '') $sql .= ' ORDER BY '.$order;
		$sql .= ' LIMIT 1';
		if (MY_DEBUG) {
			$this->responseSql($sql);
		}
		return $this->FetchRow($sql);
	}

	/**
     * 更新记录,执行 UPDATE 操作
     *
     * 说明: $arrSets 格式如下:
     *      $arrSets = array('uid'=>1,
     *                       'name'=>'myphp');
     * 
     * 解析后SET为: "uid=1,name='myphp'"
     * 
     * @param string $table  :表
     * @param array $arrSets :设置的字段值
     * @param mixed $where   :条件,详细请看 Select()成员
     * @param string $order  :排序字段
     * @param int $limit     :记录行
	 * @param string $group  :分组字段
     * @return boolean
     */
	public function Update($table,$arrSets,$where='',$order='',$limit='',$group='')
	{
		$sqlSet = $this->_parseUpdateSet($arrSets);
		$sql = sprintf("UPDATE %s SET %s",$table,$sqlSet);
		if ($where != '') $sql .= ' WHERE '.$this->_parseWhere($where);
		if ($order != '') $sql .= ' ORDER BY '.$order;
		if ($group != '') $sql .= ' GROUP BY '.$group;
		if ($limit != '') $sql .= ' LIMIT '.$limit;
		return $this->Execute($sql);
	}

	/**
	 * 快速有筛选更新
	 * @param 表名 $table
	 * @param 记录 $arrSets
	 * @param 条件 $where
	 * @param 列表 $list
	 * @return 影响的记录数
	 * $db->updatePart('news', $_POST, $condition, '|id|title|author');
	 */
	function updatePart($table, $arrSets, $where, $list) {
		$str = '';
		$list_ = explode("|", $list);
		foreach ($list_ as $l => $m) {
			$list__[$m] = $l;
		}
		foreach ($arrSets as $k => $v) {
			if($list__[$k] != "") {
				$str .= "`$k`='$v',";
			}
		}
		$str = substr($str, 0, -1);
		$sql = "UPDATE $table SET $str WHERE ".$this->_parseWhere($where);
		return $this->Execute($sql);
	}

	/**
     +----------------------------------------------------------
     * 保存某个字段的值
     +----------------------------------------------------------
     * @param string $field 要保存的字段名
     * @param string $value  字段值
     * @param string $table  数据表
     * @param string $where 保存条件  
     * @param boolean $asString 字段值是否为字符串
     +----------------------------------------------------------
     */
	public function setField($field,$value,$table,$where,$asString=false) {
		if($asString) {
			$value = '"'.$value.'"'; // 更新字段内容为纯字符串
		}
		$sql = 'UPDATE '.$table.' SET '.$field.'='.$value.' where '.$this->_parseWhere($where);
		return $this->Execute($sql);
	}

	/**
     * 插入记录,执行 INSERT 操作
     *
     * 说明:有关 $arrSets 数组的定义请看: Update()成员
     * 
     * @param string $table  :表名
     * @param array $arrSets :插入的字段
     * @return int
     */
	public function Insert($table,$arrSets)
	{
		$ret = $this->_parseInsertSet($arrSets);
		$sql = sprintf("INSERT INTO %s(%s) VALUES(%s)",$table,$ret['key'],$ret['val']);
		return $this->Execute($sql);
	}

	/**
	 * 快速有筛选插入
	 * @param 表名 $table
	 * @param 记录 $arrSets
	 * @param 列表 $list
	 * @return 影响的记录数
	 * $db->insertPart('news', $_POST, '|id|title|author');
	 */
	function insertPart($table, $arrSets, $list) {
		$str1  = ''; // 记录字段
		$str2  = ''; // 记录数值
		$list_ = explode("|", $list);
		foreach ($list_ as $l => $m) {
			$list__[$m] = $l;
		}
		foreach ($arrSets as $k => $v) {
			if($list__[$k] != "") {
				$str1 .= "`$k`,";
				$str2 .= "'$v',";
			}
		}
		$str1 = '('.substr($str1, 0, -1).')';
		$str2 = '('.substr($str2, 0, -1).')';
		$sql = "INSERT INTO $table $str1 VALUES $str2";
		return $this->Execute($sql);
	}

	/**
	 * 删除记录,执行 DELETE 操作,返回删除的记录行数
	 *
	 * @param string $table :表
	 * @param mixed $where  :条件,详细请看 Select()成员
	 * @param string $order :排序字段
	 * @param string $limit :记录行
	 * @param string $group :分组
	 */
	public function Delete($table,$where,$order='', $limit='',$group='')
	{
		$sql = "DELETE FROM $table";
		if ($where != '') $sql .= ' WHERE '.$this->_parseWhere($where);
		if ($order != '') $sql .= ' ORDER BY '.$order;
		if ($group != '') $sql .= ' GROUP BY '.$group;
		if ($limit != '') $sql .= ' LIMIT '.$limit;
		return $this->Execute($sql);
	}

	/**
	 * 求记录数
	 *
	 * 说明:如果是求表的所有记录(没有WHERE),对于MyISAM表 $countField 请用 '*',否则请指定字段名
	 * 
	 * @param string $table      :表
	 * @param mixed $where       :条件      
	 * @param string $countField :COUNT字段名
	 * @param string $group      :分组
	 * @return int
	 */
	public function Count($table,$where='',$countField='COUNT(*)',$group='')
	{
		$sql = sprintf("SELECT %s FROM %s",$countField,$table);
		if ($where != '') $sql .= ' WHERE '.$this->_parseWhere($where);
		if ($group != '') $sql .= ' GROUP BY '.$group;
		if (MY_DEBUG) {
			$this->responseSql($sql);
		}
		return $this->FetchOne($sql);
	}

	/**
	 * 执行任何SQL语句
	 *
	 * 说明:$sql语句中可以传参数,格式如:"select * from user where userid=:uid and username=:name" 其中: ":uid"和":name" 表示参数变量
	 *     则必需定义$bind为: $bind=array('uid'=>3,
	 *                                  'name'=>'myphp')
	 *     表示$sql中 :uid 的值为3, :name 的值为'myphp'
	 * 
	 * 注意:SQL中的参数只能用于 WHERE 条件中
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @return bool
	 */
	public function Execute($sql, $bind=array())
	{
		$sql = $this->_parseSQL($sql,$bind);
		if (MY_DEBUG) {
			$this->responseSql($sql);
		}
		return $this->db->Execute($sql);
	}

	/**
	 * 返回最后执行 Insert() 操作时表中有 auto_increment 类型主键的值
	 * 
	 * @return int
	 */
	public function Insert_ID()
	{
		return $this->db->Insert_ID();
	}

	/**
	 * 最后 DELETE UPDATE 语句所影响的行数 
	 *
	 * @return int
	 */
	public function Affected_Rows()
	{
		return $this->db->Affected_Rows();
	}

	/**
	 * 返回处理后的查询二维结果集,返回的结果格式为:
	 * 
	 * 如果SQL的结果集为:
	 *   -uid- -name- -age-  (字段名)
	 *    u1    yuan   20    (第一行记录)
	 *    u2    zhan   19    (第二行记录)
	 * 
	 * 则则函数返回的数组值为:   
	 *   array('u1'=>array('uid'=>'u1','name'=>'yuan','age'=>20),
	 *         'u2'=>array('uid'=>'u2','name'=>'zhan','age'=>19)
	 *        )
	 * 
	 * 说明:有关 $sql和$bind 的用法请看 DB_Msql::Execute()
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @param int $cacheTime :结果集缓存的时间,单位秒 0:表示不缓存
	 * @return array
	 */
	public function fetchAssoc($sql,$bind=array(),$cacheTime=0)
	{
		$sql = $this->_parseSQL($sql,$bind);
		return $this->resultCache($sql,$cacheTime,'fetchAssoc');
	}

	/**
	 * 执行SQL并返回所有结果集
	 *
	 * 说明:有关 $sql和$bind 的用法请看 DB_Msql::Execute()
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @param int $cacheTime :结果集缓存的时间,单位秒 0:表示不缓存
	 * @return array
	 */
	public function fetchAll($sql,$bind=array(),$cacheTime=0)
	{
		$sql = $this->_parseSQL($sql,$bind);
		return $this->resultCache($sql,$cacheTime,'fetchAll');
	}

	/**
	 * 执行SQL并返回结果集的第一行(一维数组)
	 *
	 * 说明:有关 $sql和$bind 的用法请看 DB_Msql::Execute()
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @param int $cacheTime :结果集缓存的时间,单位秒 0:表示不缓存
	 * @return array
	 */
	public function FetchRow($sql,$bind=array(),$cacheTime=0)
	{
		$sql = $this->_parseSQL($sql,$bind);
		return $this->resultCache($sql,$cacheTime,'fetchRow');
	}

	/**
	 * 返回结果集中第一列的所有值(一维数组)
	 *
	 * 说明:有关 $sql和$bind 的用法请看 DB_Msql::Execute()
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @param int $cacheTime :结果集缓存的时间,单位秒 0:表示不缓存
	 * @return array
	 */
	public function FetchCol($sql,$bind=array(),$cacheTime=0)
	{
		$sql = $this->_parseSQL($sql,$bind);
		return $this->resultCache($sql,$cacheTime,'fetchCol');
	}

	/**
	 * 执行SQL并返回结果集中第一行第一列的值
	 * 
	 * 说明:有关 $sql和$bind 的用法请看 DB_Msql::Execute()
	 * 
	 * @param string $sql
	 * @param array $bind
	 * @param int $cacheTime :结果集缓存的时间,单位秒 0:表示不缓存
	 * @return array
	 */
	public function FetchOne($sql,$bind=array(),$cacheTime=0)
	{
		$sql = $this->_parseSQL($sql,$bind);
		return $this->resultCache($sql,$cacheTime,'fetchOne');
	}

	/**
	 * 返回调用 当前查询 后的结果集中的记录数
	 * 注意:如果当前查询中使用了缓存功能则该方法不完全有效
	 */
	public function RowCount()
	{
		return $this->db->rowCount();
	}

	/**
	 * 开始事务
	 * @return bool
	 */
	public function beginTransaction()
	{
		return $this->db->beginTransaction();
	}

	/**
	 * 提交事务
	 * @return bool
	 */
	public function commit()
	{
		return $this->db->commit();
	}

	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollBack()
	{
		return $this->db->rollBack();
	}

	/**
	 * 格式化用于数据库的字符串
	 *
	 * @param string $value
	 * @return string
	 */
	public function quoteStr($value)
	{
		return $this->db->quoteStr($value);
	}

	/**
	 * 返回MYSQL系统中当前所有可用的数据库
	 * 
	 * @return array
	 */
	public function MetaDatabases()
	{
		return $this->fetchCol('SHOW DATABASES');
	}

	/**
	 * 返回数据库中所有的表,如果为空则返回当前数据库中所有的表名
	 * 
	 * @param string $DBname :数据库名
	 * @return array
	 */
	public function MetaTables($DBname='')
	{
		$sql = "SHOW TABLES";
		if (trim($DBname) != '') $sql .= ' FROM '.trim($DBname);
		return $this->fetchCol($sql);
	}

	/**
	 * 返回指定表的所有字段名  
	 * 
	 * @param string $table :表名
	 * @return Array
	 */
	public function MetaColumnNames($table)
	{
		$sql = 'SHOW COLUMNS FROM '.trim($table);
		return $this->fetchCol($sql);
	}

	/**
	 * 清空表,执行 TRUNCATE TABLE 操作
	 * @param string $table:表名称
	 */
	public function clear($table)
	{
		$sql = "TRUNCATE TABLE $table";
		return $this->Execute($sql);
	}

	/**
	 * 优化表,执行 OPTIMIZE TABLE 操作
	 * @param string $table:表名称,如果表名空则优化库中所有的表
	 */
	public function optimize($table='')
	{
		if ($table == '') $table = $this->FetchCol('SHOW TABLES');
		else $table = array($table);

		foreach ($table as $tab)
		{
			$sql = "OPTIMIZE TABLE $tab";
			$this->Execute($sql);
		}
		return true;
	}

	/**
	 * 修复表,执行 REPAIR TABLE 操作
	 * @param string $table:表名称,如果表名空则修复库中所有的表
	 */
	public function repair($table='')
	{
		if ($table == '') $table = $this->FetchCol('SHOW TABLES');
		else $table = array($table);

		foreach ($table as $tab)
		{
			$sql = "REPAIR TABLE $tab";
			$this->Execute($sql);
		}
		return true;
	}

	/**
	 * 关闭连接
	 */
	public function Close()
	{
		$this->db->Close();
	}

	/* =================================================================================================
	以下为私有成员函数定义
	================================================================================================= */

	/**
	 * 解析字段名,防止字段名是关键字
	 */
	private function _parseField($fieldName)
	{
		return '`'.$fieldName.'`';
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

	/**
	 * 解析 SQL WHERE 条件
	 *
	 * @param mixed $where
	 * @return string
	 */
	private function _parseWhere($where)
	{
		$sqlWhere = '1 ';
		if (is_array($where))
		{
			foreach ($where as $k=>$v)
			{
				$sqlWhere .= " AND ".$this->_parseField($k);
				if (is_array($v))
				{
					foreach ($v as $_k=>$_v) $sqlWhere .= sprintf(" %s ",strtoupper($_k)).$this->_returnValue($_v);
				}else{
					$sqlWhere .= '='.$this->_returnValue($v);
				}
			}
		}else{
			$sqlWhere = $where;
		}
		return $sqlWhere;
	}


	/**
	 * 解析 UPDATE 操作字段设置
	 *
	 * @param array $arrSet
	 * @return string
	 */
	private function _parseUpdateSet($arrSet)
	{
		$sqlSet = $spr = '';
		if (is_array($arrSet))
		{
			foreach ($arrSet as $k=>$v)
			{
				$sqlSet .= $spr.$this->_parseField($k).'='.$this->_returnValue($v);
				$spr = ',';
			}
		}else{
			$sqlSet = $arrSet;
		}
		return $sqlSet;
	}

	/**
	 * 解析 INSERT 操作字段设置
	 *
	 * @param array $arrSet
	 * @return array
	 */
	private function _parseInsertSet($arrSet)
	{
		$Keys = $Values = $spr = '';
		foreach ($arrSet as $k=>$v)
		{
			$Keys .= $spr.$this->_parseField($k);
			$Values .= $spr.$this->_returnValue($v);
			$spr = ',';
		}
		return array('key'=>$Keys,'val'=>$Values);
	}

	/**
	 * 缓存结果集
	 */
	private function resultCache($sql,$cacheTime,$dbFunName)
	{
		//是否启用缓存
		if ($cacheTime > 0)
		{
			$cache = Cache::initial(md5($sql));//生成缓存对象
			$cacheVal = $cache->fetch($cacheTime);//得到缓存内容
			if ($cacheVal == null) //缓存不存在则生成缓存
			{
				$listVal = $this->db->{$dbFunName}($sql);
				$cache->store(serialize($listVal),$cacheTime);
			}else{
				$listVal = unserialize($cacheVal);
			}
		}else{
			$listVal = $this->db->{$dbFunName}($sql);
		}
		return $listVal;
	}


	/**
	 * 辅助功能
	 */
	public function responseSql($sql) {
		$sqlVal = $this->response->get('sql');
		$this->response->set('sql', $sqlVal.'<br>'.$sql);
	}

	/**
	 * 分库分表后缀计算
	 */	
	public function chipDb($chipId, $chipD) {
		//$chipId = intval($chipId);
		//if (empty($chipId)) {
		//	return '';
		if(in_array($chipId, array("quick","slow"))){
			if (empty($chipId)) {
				return MDP;
			} else {
				return MDP.'_'.$chipId;
			}
		} else {
			switch($chipD){		
				case 0:
					return '';
					break;
				case 10:
					return MDP.'_'.substr(sprintf("%05d",$chipId), -1, 1);
					break;
				case 100:
					return MDP.'_'.substr(sprintf("%05d",$chipId), -2, 2);
					break;
				case 1000:
					return MDP.'_'.substr(sprintf("%05d",$chipId), -3, 3);
					break;
			}
		}
	}
	public function chipTable($chip, $chipId) {
		$chipId = intval($chipId);
		if (empty($chipId)) {
			return '';
		} else {
			switch($chip){
				case 0:
				case 1:
					return '';
					break;
				case 10:
					return '_'.substr(sprintf("%05d",$chipId), -3, 1);
					break;
				case 100:
					return '_'.substr(sprintf("%05d",$chipId), -4, 2);
					break;
				case 1000:
					return '_'.substr(sprintf("%05d",$chipId), -5, 3);
					break;
			}

		}
	}
	 
	/**
	 * 获取数据库及表名
	 */	
	public function getDbTableName() {
		if (empty($this->chipDb) && empty($this->chip)) {
			return '`'.$this->prefix.$this->table.'`';
		} else {
			return $this->chipDb($this->chipId, $this->chipD).'.'.$this->prefix.$this->table.$this->chipTable($this->chip,$this->chipId);
		}
		
	}

	// 常用数据模型

	/**
	 +------------------------------------------------------------------------------
	 * 获取数据
	 +------------------------------------------------------------------------------ 
	 * @param int     $id     :ID
	 * @param string  $fields :字段
	 * @param string  $order  :排序字段
	 * @param         $where  :条件
	 +------------------------------------------------------------------------------ 
	 * $this->getById(1);
	 * $this->getOne('id=1');
	 * $this->getList('id>1', '0,5', 'id desc', '*');
	 * $this->getCount('id>1');
	 * $this->getFields('id', 'name', 1);
	 +------------------------------------------------------------------------------ 
	 */

	public function getById($id, $fields='*') {
		return $this->SelectOne($fields, $this->getDbTableName(), $this->pk.'='.intval($id));
	}

	public function getOne($where='', $order='', $fields='*') {
		$order = empty($order) ? $this->pk.' desc' : $order;
		return $this->SelectOne ( $fields, $this->getDbTableName(), $where, $order );
	}

	public function getList($where='', $limit='', $order='', $fields='*', $group='') {
		$order = empty($order) ? $this->pk.' desc' : $order;
		return $this->Select ( $fields, $this->getDbTableName(), $where, $order, $limit, $group );
	}

	public function getCount($where='', $countField='COUNT(*)') {
		return $this->Count($this->getDbTableName(), $where, $countField);
	}
	
	public function getFields($firstFields, $secondFields, $val) {
		$data = $this->SelectOne($secondFields, $this->getDbTableName(), "{$firstFields}='".$val."'");		
		return $data["{$secondFields}"];
	}

	public function getTableName() {
		echo $this->table;
	}

	/**
	 +------------------------------------------------------------------------------
	 * 插入数据
	 +------------------------------------------------------------------------------
	 * @param array  $arr :数组	 
	 * @param string $str :字段
	 * @return int
	 +------------------------------------------------------------------------------ 
	 * $this->add($arr);
	 * $this->addSel($_POST, '|title|author');
	 +------------------------------------------------------------------------------ 
	 */

	public function add($arr) {
		$this->Insert($this->getDbTableName(), $arr);
		return $this->Insert_ID();
	}

	public function addSel($arr, $str) {
		$this->insertPart($this->getDbTableName(), $arr, $str);
		return $this->Insert_ID();
	}


	/**
	 +------------------------------------------------------------------------------
	 * 编辑数据
	 +------------------------------------------------------------------------------
	 * @param int      $id     :ID
	 * @param array    $arr    :数组	 
	 * @param string   $str    :字段
	 * @param          $where  :条件
	 +------------------------------------------------------------------------------ 
	 * $this->updateById(1, $arr);
	 * $this->updateSelById(1, $_POST, '|title|author');
	 * $this->updateOne($arr, 'id>1');
	 * $this->updateSel($_POST, '|title|author', 'id>1');
	 * $this->updateField('name', 'name+1', 'id=1');
	 +------------------------------------------------------------------------------ 
	 */		

	public function updateById($id, $arr) {
		$this->Update($this->getDbTableName(), $arr, $this->pk.'='.intval($id), '', 1);
		return $id;
	}

	public function updateOne($arr, $where, $order="") {
		$this->Update($this->getDbTableName(), $arr, $where, $order, 1);
		return $this->Affected_Rows();
	}
	
	public function updateList($arr, $where, $order="", $limit="") {
		$this->Update($this->getDbTableName(), $arr, $where, $order, $limit);
		return $this->Affected_Rows();
	}
	
	public function updateSelById($id, $arr, $str) {
		$this->updatePart($this->getDbTableName(), $arr, $this->pk.'='.intval($id), $str );
		return $id;
	}

	public function updateSel($arr, $str, $where) {
		$this->updatePart($this->getDbTableName(), $arr, $where, $str);
		return $this->Affected_Rows();
	}

	public function updateField($fields, $value, $where, $asString=false) {
		$this->setField($fields, $value, $this->getDbTableName(), $where, $asString);
		return $this->Affected_Rows();
	}


	/**
	 +------------------------------------------------------------------------------
	 * 删除数据
	 +------------------------------------------------------------------------------
	 * @param int $id  :ID
	 * @param 　　$where  :条件
	 +------------------------------------------------------------------------------ 
	 * $this->delId(1);
	 * $this->delOne('id>1');
	 * $this->delList('id>1');
	 +------------------------------------------------------------------------------ 
	 */	

	public function delId($id) {
		$this->Delete($this->getDbTableName(), $this->pk.'='.intval($id), '', 1);
		return $id;
	}

	public function delOne($where, $order="") {
		$this->Delete($this->getDbTableName(), $where, '', 1);
		return $this->Affected_Rows();
	}

	public function delList($where, $order="", $limit="") {
		$this->Delete($this->getDbTableName(), $where, $order, $limit);
		return $this->Affected_Rows();
	}

}
?>