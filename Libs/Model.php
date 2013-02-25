<?php
/**
 * 数据库操作模型基类
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */
class Model
{
	private $link  = NULL;    // 数据库连接句柄
	private $query = NULL;    // 查询句柄
	private $db    = NULL;    // db
	public  $load  = NULL;    // load类实例

	/**
	 * 构造函数,自动连接数据库
	 */
	function __construct($db)
	{
		$this->db = $db;
		if (!isset(Myphp::$data['db'][$db])) $this->_getDB($db);
		$this->load = new Load();
	}

	/**
	 * 得到实例对象
	 */
	private function _getDB($db = 'default')
	{
		// 获取数值
		$dbs = unserialize(MYPHP_DBS);
		$dba = $dbs[$db];

		// 数据检验
		if(!is_array($dba)) die('err:dba');
		if(MYPHP_DEBUG) $beginTime = microtime(TRUE);

		// 链接数据库
		$host       = empty($dba['port']) ? $dba['host'] : $dba['host'].':'.$dba['port'];
		$this->link = mysql_connect($host, $dba['user'], $dba['password'], true) or die("err:not connect".mysql_error());
		mysql_query('SET NAMES UTF8', $this->link);
		mysql_select_db($dba['database'], $this->link) or die("err:db select db".mysql_error());

		// DEBUG
		if(MYPHP_DEBUG) Myphp::$data['debug']['db'][] = array('sql'=>'Connect DB Server.', 'time'=>microtime(TRUE)-$beginTime);

		// 赋值
		Myphp::$data['db'][$db]   = true;
		Myphp::$data['link'][$db] = $this->link;

		// 输出
		return true;
	}

	/**
	 * 统一执行SQL (debug模式未加)
	 */
	public function query($sql)
	{
		if(MYPHP_DEBUG) $beginTime = microtime(TRUE);
		// $this->query = mysql_query($sql, Myphp::$data['link'][$this->db]) or die("err:db query ".mysql_error().' sql:'.$sql);
		$this->query = mysql_query($sql, Myphp::$data['link'][$this->db]);
		if(MYPHP_DEBUG) Myphp::$data['debug']['db'][] = array('sql'=>$sql, 'time'=>microtime(TRUE)-$beginTime);
		return $this->query;
	}

	/**
	 * 解析字段名,防止字段名是关键字
	 *
	 * @param  unknown $value
	 * @return string
	 */
	private function _returnField($fieldName)
	{
		return '`'.$fieldName.'`';
	}

	/**
	 * 根据值的类型返回SQL语句式的值
	 *
	 * @param  unknown $value
	 * @return string
	 */
	private function _returnValue($value)
	{
		if (is_int($value) || is_float($value)) return $value;
		else return $this->_returnStr($value);
	}

	/**
	 * 格式化用于数据库的字符串
	 *
	 * @param  unknown $value
	 * @return string
	 */
	private function _returnStr($value)
	{
		$value = mysql_real_escape_string($value, Myphp::$data['link'][$this->db]);
		return "'{$value}'";
	}

	/**
	 * 解析 SQL WHERE 条件
	 *
	 * @param  mixed  $where
	 * @return string
	 */
	private function _where($where)
	{
		if (is_array($where))
		{
			$count = count($where);
			$i = 0;
			foreach ($where as $k => $v)
			{
				$prefix    = $i==0 ? '' : ' and ';
				$whereSql .= $prefix.$this->_returnField($k).'='.$this->_returnValue($v);
				$i++;
			}
		}
		else
		{
			$whereSql = $where;
		}
		return $whereSql;
	}

	/**
	 * 获取数据
	 * 
	 * $this->get('id', 1);
	 * $this->getID(1);
	 * $this->getOne(array('id'=>1); $this->getOne('id=1');
	 * $this->getList('id>1', '0,5', 'id desc', '*');
	 * $this->getCount('id>1');
	 *
	 * @param  int     $id     :ID
	 * @param  string  $fields :字段
	 * @param  string  $order  :排序字段
	 * @param  mixed   $where  :条件
	 * @return mixed
	 */
	public function get($field, $value)
	{
		$sql = 'select * from `'.$this->table.'` where `'.$field.'`='.$this->_returnValue($value). ' limit 1';
		$this->query($sql);
		return mysql_fetch_assoc($this->query);
	}
	public function getID($id, $fields = '*')
	{
		$id  = __intval($id);
		$sql = 'select '.$fields.' from `'.$this->table.'` where `'.$this->pk.'`='.$id. ' limit 1';
		$this->query($sql);
		return mysql_fetch_assoc($this->query);
	}
	public function getOne($where = '', $order = '', $fields = '*')
	{
		$sql = 'select '.$fields.' from '.$this->table;
		if (!empty($where)) $sql .= ' where '.$this->_where($where);
		if (!empty($order)) $sql .= ' order by '.$order. ' limit 1';
		$this->query($sql);
		return @mysql_fetch_assoc($this->query);
	}
	public function getList($where = '', $limit = '', $order = '', $fields = '*', $group = '')
	{
		$sql = 'select '.$fields.' from '.$this->table;
		if (!empty($where)) $sql .= ' where '.$this->_where($where);
		if (!empty($group)) $sql .= ' group by '.$group;
		if (!empty($order)) $sql .= ' order by '.$order;
		if (!empty($limit)) $sql .= ' limit '.$limit;
		$this->query($sql);
		$data = array();
		while ($array = @mysql_fetch_assoc($this->query))
		{
			$data[] = $array;
		}
		return $data;
	}
	public function getCount($where = '', $countField = 'COUNT(*)')
	{
		$sql = sprintf("select %s from %s", $countField, $this->table);
		if (!empty($where)) $sql .= ' where '.$this->_where($where);
		$this->query($sql);
		$count = mysql_fetch_array($this->query);
		return $count[0];
	}
	public function getOneBySQL($sql)
	{
		$this->query($sql);
		return mysql_fetch_assoc($this->query);
	}
	public function getListBySQL($sql)
	{
		$this->query($sql);
		$data = array();
		while ($array = mysql_fetch_assoc($this->query))
		{
			$data[] = $array;
		}
		return $data;
	}

	/**
	 * 插入数据
	 *
	 * $this->add($array);
	 * $this->add(array('id'=>8, 'title'=>'HTML'));
	 *
	 * @param array $array :数组
	 * @return int
	 */
	public function add($array)
	{
		foreach ($array as $k => $v)
		{
			$value = $this->_returnValue($v);
			if(is_scalar($value))
			{
				$values[] = $value;
				$fields[] = $this->_returnField($k);
			}
		}
		$sql = 'insert into '.$this->table.' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
		return $this->query($sql);
	}

	// 获取add id
	public function getAddID()
	{
		return mysql_insert_id();
	}

	/**
	 * 编辑数据
	 *
	 * $this->update($array, 'id>1');
	 * $this->update(array('title'=>'HTM'), array('id'=>8));
	 * 
	 * @param  int      $id     :ID
	 * @param  array    $array  :数组
	 * @param  string   $where  :条件
	 * @param  string   $order  :排序字段
	 * @param  string   $limit  :数量
	 * @return mixed
	 */
	public function update($array, $where, $order = '', $limit = '')
	{
		foreach ($array as $k=>$v)
		{
			$value = $this->_returnValue($v);
			if(is_scalar($value))
			{
				$set[] = $this->_returnField($k).'='.$value;
			}
		}
		$setSql = 'set '.implode(',', $set);
		$sql    = sprintf("update %s %s", $this->table, $setSql);
		if (!empty($where)) $sql .= ' where '.$this->_where($where);
		if (!empty($order)) $sql .= ' order by '.$order;
		if (!empty($limit)) $sql .= ' limit '.$limit;
		return $this->query($sql);
	}

	/**
	 * 删除数据
	 * $this->delID(1);
	 * $this->del('id>1'); $this->del(array('id'=>2));
	 *
	 * @param  int $id    :ID
	 * @param  string   $where  :条件
	 * @param  string   $order  :排序字段
	 * @param  string   $limit  :数量
	 * @return mixed
	 */
	public function delID($id)
	{
		$id  = __intval($id);
		$sql = 'delete from `'.$this->table.'` where `'.$this->pk.'`='.$id. ' limit 1';
		return $this->query($sql);
	}

	public function del($where, $order = '', $limit = 1)
	{
		$sql = 'delete from `'.$this->table.'`';
		if (!empty($where)) $sql .= ' where '.$this->_where($where);
		if (!empty($order)) $sql .= ' order by '.$order;
		if (!empty($limit)) $sql .= ' limit '.$limit;
		return $this->query($sql);
	}


	// 事务处理

	/**
	 * 启动事务
	 * @return bool
	 */
	public function begin()
	{
		if ($this->transDepth == 0)
		{
			$this->query('START TRANSACTION');
		}
		$this->transDepth++;
		return TRUE;
	}

	/**
	 * 事务提交
	 * @return bool
	 */
	public function commit()
	{
		if ($this->transDepth > 0)
		{
			$result = $this->query('COMMIT');
			$this->transDepth = 0;
			if(!$result) die("err:trans commit ".mysql_error());
		}
		return TRUE;
	}

	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollback()
	{
		if ($this->transDepth > 0)
		{
			$result = $this->query('ROLLBACK');
			$this->transDepth = 0;
			if(!$result) die("err:trans commit ".mysql_error());
		}
		return TRUE;
	}
}

/* End */
