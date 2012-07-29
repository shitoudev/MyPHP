<?php
/**
 * 框架 ACL 基于数据库的实现
 * 权限机制的建立流程
 * 1.初始化权限系统数据库.
 * 2.添加资源.
 * 3.为添加的资源建立一个个资源组.
 * 4.建立角色并为角色分配对应的资源组.
 * 5.添加用户到对应的角色中.
*/

/* 权限表相关常量定义 */
define('MY_ACL_RESGROUP_TAB',         MY_ACL_TAB_PREFIX.'acl_resgroup');         //资源组表
define('MY_ACL_RESGROUP_RESOURCE_TAB',MY_ACL_TAB_PREFIX.'acl_resgroup_resource');//资源组与资源的关系表
define('MY_ACL_RESOURCE_TAB',         MY_ACL_TAB_PREFIX.'acl_resource');         //资源表
define('MY_ACL_ROLE_TAB',             MY_ACL_TAB_PREFIX.'acl_role');             //角色表
define('MY_ACL_ROLE_EXTEND_TAB',      MY_ACL_TAB_PREFIX.'acl_role_extend');      //角色继承关系表
define('MY_ACL_ROLE_RESGROUP_TAB',    MY_ACL_TAB_PREFIX.'acl_role_resgroup');    //角色与资源组的关系表
define('MY_ACL_USER_ROLE_TAB',        MY_ACL_TAB_PREFIX.'acl_user_role');        //用户与角色关系表

require_once('Model.php');
class Acl{

	private $db = null;                    //数据库操作对象
	private $productType = '';             //当前产品类型
	private $roleResourceAction = array(); //保存当前角色的资源动作列表

	/**
     * 构造函数,连接数据库
     */
	public function __construct($productType = '')
	{
		$this->db = new Model();
		$this->productType = $productType;
		$this->roleResourceAction['Admin'] = false;   //当前用户的角色是否为超级角色
		$this->roleResourceAction['Deny'] = array();  //当前用户的不可访问列表
		$this->roleResourceAction['Allow'] = array(); //当前用户的可访问列表
	}

	/**
     * 初始化ACL系统
     * 注意:该操作会重建系统中权限管理的相关表
     */
	public function initACL()
	{
		$arraySQL = include('Libs/Acl/aclInitTable.inc.php');
		foreach ($arraySQL as $sql)
		{
			$search = array('[ENGINE]','[CHARSET]');
			$replace = array(MY_ACL_TAB_ENGINE,MY_ACL_TAB_CHARSET);
			$sql = str_replace($search,$replace,$sql);
			$this->db->Execute($sql);
		}
	}

	/**
     * 清空ACL系统(清空相关表)
     * @param string $product_type:产品类型标识,如果为空则清空整个ACL权限系统
     */
	public function clearACL($product_type='')
	{
		//--清空所有表
		if ($product_type == '')
		{
			$this->db->clear(MY_ACL_RESGROUP_TAB);
			$this->db->clear(MY_ACL_RESGROUP_RESOURCE_TAB);
			$this->db->clear(MY_ACL_RESOURCE_TAB);
			$this->db->clear(MY_ACL_ROLE_TAB);
			$this->db->clear(MY_ACL_ROLE_EXTEND_TAB);
			$this->db->clear(MY_ACL_ROLE_RESGROUP_TAB);
			$this->db->clear(MY_ACL_USER_ROLE_TAB);
		}
		//--清空对应产品的记录
		else{
			//--删除用户角色关系
			$where = array();
			$where['product_type'] = $this->productType;
			$this->db->Delete(MY_ACL_USER_ROLE_TAB,$where);

			//--得到产品的所有角色
			$sql = sprintf("SELECT role_id FROM %s WHERE product_type='%s'",MY_ACL_ROLE_TAB,$this->productType);
			$roleIds = implode(',',$this->db->fetchCol($sql));
			if ($roleIds != '')
			{
				//--删除产品的所有角色
				$where = array();
				$where['product_type'] = $this->productType;
				$this->db->Delete(MY_ACL_ROLE_TAB,$where);

				//--删除角色继承关系
				$where = sprintf("role_id in (%s)",$roleIds);
				$this->db->Delete(MY_ACL_ROLE_EXTEND_TAB,$where);

				//--删除角色与资源组的关系
				$where = sprintf("role_id in (%s)",$roleIds);
				$this->db->Delete(MY_ACL_ROLE_RESGROUP_TAB,$where);
			}

			//--得到产品的所有资源组
			$sql = sprintf("SELECT resgroup_id FROM %s WHERE product_type='%s'",MY_ACL_RESGROUP_TAB,$this->productType);
			$resGroupIds = implode(',',$this->db->fetchCol($sql));
			if ($resGroupIds != '')
			{
				//--删除产品中的资源组
				$where = array();
				$where['product_type'] = $this->productType;
				$this->db->Delete(MY_ACL_RESGROUP_TAB,$where);

				//--删除资源组与资源的关系
				$where = sprintf("resgroup_id in (%s)",$resGroupIds);
				$this->db->Delete(MY_ACL_RESGROUP_RESOURCE_TAB,$where);
			}

			//--删除产品中的所有资源
			$where = array();
			$where['product_type'] = $this->productType;
			$this->db->Delete(MY_ACL_RESOURCE_TAB,$where);
		}
	}

	/**
     * 核心资源检测接口,检测用户是否对指定的资源动作有访问权限
     * @param string $userId:用户标识
     * @param string $resourceAction:资源动作; 'this'表示当前控制器动作全称.如:"admin_main_index"
     * @return bool
     */
	public function privCheck($userId,$resourceAction='this')
	{
		if (!MY_ACL_ACCESS) return true; //不使用权限机制

		$resourceAction = strtolower($resourceAction);
		if ($resourceAction == 'this')
		{
			$response = Response::getInstance();
			$resourceAction = strtolower($response->get('currentActionPath'));
		}

		//--支持通配符 "*" 表示该功能下的所有方法
		$actionAll = substr($resourceAction,0,strrpos($resourceAction,'_')) . '_*';
		//--判断是否在绝对权限列表中
		if (MY_ACL_ACCESS_ALLOW != '')
		{
			$arrActionList = explode(',',MY_ACL_ACCESS_ALLOW);
			if (in_array($resourceAction,$arrActionList) || in_array($actionAll,$arrActionList)) return true;
		}

		if ($userId == '' || $resourceAction == '') return false;

		if (MY_ACL_CACHE)//----权限使用绶存
		{
			switch (MY_ACL_CACHE_METHOD)
			{
				case 'session':
					$sessionKey = '_MY_CORE_ALC_';
					$request = Request::getInstance();
					if ($request->getSession($sessionKey))
					{
						$this->roleResourceAction = unserialize($request->getSession($sessionKey));
					}else{
						$this->_beginCheck($userId);
						$request->setSession($sessionKey,serialize($this->roleResourceAction));
					}
					break;
				case 'memcache':
					throw new Exception('MemCache功能暂未完成');
					break;
			}
		}
		else{ //--------------不使用绶存
			$this->_beginCheck($userId);
		}

		//--检查是否有超级角色(在所有的继承关系中)
		if ($this->roleResourceAction['Admin']) return true;
		//--查询是否在禁止权限列表中
		if (in_array($resourceAction,$this->roleResourceAction['Deny']) || in_array($actionAll,$this->roleResourceAction['Deny'])) return false;
		//--查询是否在允许权限列表中
		return in_array($resourceAction,$this->roleResourceAction['Allow']) || in_array($actionAll,$this->roleResourceAction['Allow']);
	}

	/**
     * 根据用户标识执行检测操作
     */
	private function _beginCheck($userId)
	{
		//--得到用户的所有角色
		$sql = sprintf("SELECT role_id FROM %s WHERE user_id='%s' AND product_type='%s'",MY_ACL_USER_ROLE_TAB,$userId,$this->productType);
		$arrRoleid = $this->db->fetchCol($sql);
		if (count($arrRoleid) < 1) //用户没有找到
		{
			$this->roleResourceAction['Admin'] = false;
			$this->roleResourceAction['Deny'] = array();
			$this->roleResourceAction['Allow'] = array();
		}
		else{
			//--过滤掉不存在的角色,即角色ID为0的值
			$arrTmp = array();
			foreach ($arrRoleid as $v) if ($v > 0) $arrTmp[] = $v;
			$arrRoleid = $arrTmp; $arrTmp = null;
			//--循环递归得到角色的禁止访问资源动作和允许访问资源动作
			$this->_recursionGetRoleResourceActionList($arrRoleid);
		}
	}

	/**
     * 循环递归得到角色的禁止访问资源动作和允许访问资源动作
     * @param array $arrRoleid:一维数组,角色ID
     * @return void
     */
	private function _recursionGetRoleResourceActionList($arrRoleid)
	{
		//--循环角色得到角色所有的资源组
		foreach ($arrRoleid as $roleId)
		{
			//--判断角色是否为超级管理员
			$sql = sprintf("SELECT role_isadmin FROM %s WHERE role_id=%d",MY_ACL_ROLE_TAB,$roleId);
			$isadmin = $this->db->fetchOne($sql);
			if ($isadmin > 0)
			{
				//--是超级管理员的话则为最大权限了
				$this->roleResourceAction['Admin'] = true;
				$this->roleResourceAction['Deny'] = array();
				$this->roleResourceAction['Allow'] = array();
				return true;
			}

			//--累加角色的不可用资源列表中
			$actionList = $this->_getResourceAction($roleId,false);
			$this->roleResourceAction['Deny'] = array_unique(array_merge_recursive($this->roleResourceAction['Deny'],$actionList));

			//--累加角色的可用资源列表中
			$actionList = $this->_getResourceAction($roleId,true);
			$this->roleResourceAction['Allow'] = array_unique(array_merge_recursive($this->roleResourceAction['Allow'],$actionList));

			//--得到角色的父级角色再做资源动作的判断
			$sql = sprintf("SELECT role_parent_id FROM %s WHERE role_id=%d ORDER BY are_id DESC",MY_ACL_ROLE_EXTEND_TAB,$roleId);
			$arrParentRoleid = $this->db->fetchCol($sql);
			if (count($arrParentRoleid) > 0)
			{
				$this->_recursionGetRoleResourceActionList($arrParentRoleid);
			}
		}//End Foreach
	}


	/**
     * 得到指定角色对应的资源动作列表
     * @param int $roleId:角色ID
     * @param bool $access:角色对应资源组的访问控制选项 false:禁止访问列表 true:允许访问列表
     * @return array 一维数组,格式如:array('动作1','动作2');
     */
	private function _getResourceAction($roleId,$access=true)
	{
		$access = $access ? 1 : 0;
		//--得到角色对应的资源组ID
		$sql = sprintf("SELECT resgroup_id FROM %s WHERE role_id=%d AND allow_deny=%d",MY_ACL_ROLE_RESGROUP_TAB,$roleId,$access);
		$resGroupIds = implode(',',$this->db->fetchCol($sql));
		if ($resGroupIds != '')
		{
			//--得到资源组对应的资源ID
			$sql = sprintf("SELECT resource_id FROM %s WHERE resgroup_id in (%s)",MY_ACL_RESGROUP_RESOURCE_TAB,$resGroupIds);
			$resourceIds = implode(',',$this->db->fetchCol($sql));
			if ($resourceIds != '')
			{
				//--得到资源ID对就的资源动作
				$sql = sprintf("SELECT resource_action FROM %s WHERE resource_id in (%s)",MY_ACL_RESOURCE_TAB,$resourceIds);
				return array_map('strtolower',$this->db->fetchCol($sql));
			}
		}
		return array();
	}

	////////////////////////////////////////////////////////////////////////////////////////////
	//           用户相关操作接口
	////////////////////////////////////////////////////////////////////////////////////////////

	/**
     * 得到当前产品中的用户列表;如果指定了角色则返回角色所对应的用户列表,没有指定角色则返回所有用户列表.
     * @param string $roleName:角色名称
     * @return array 二维数组格式如:array(array('user'=>'张三','role'=>'管理员')
     *                                  array('user'=>'李四','role'=>'编辑人员')
     *                                 );
     */
	public function userList($roleName='')
	{
		$sql = sprintf("SELECT user_id,role_id FROM %s WHERE product_type='%s'",MY_ACL_USER_ROLE_TAB,$this->productType);
		if ($roleName != '')
		{
			$roleId = $this->_getRoleID($roleName);
			$sql .= " AND role_id=$roleId";
		}

		$userList = array();
		$result = $this->db->fetchAll($sql);
		foreach ($result as $row)
		{
			$sql = sprintf("SELECT role_name FROM %s WHERE role_id=%d",MY_ACL_ROLE_TAB,$row['role_id']);
			$_roleName = $this->db->fetchOne($sql);
			$userList[] = array('user'=>$row['user_id'],
			'role'=>$_roleName);
		}
		return $userList;
	}

	/**
     * 得到用户的角色名称.(一个用户同时可能有多种角色)
     * @param string $userId:用户标识
     * @return array 一维数组,格式为: array('角色1','角色2');
     */
	public function getUserRoleName($userId)
	{
		$sql = sprintf("SELECT role_id FROM %s WHERE user_id='%s' AND product_type='%s'",MY_ACL_USER_ROLE_TAB,$userId,$this->productType);
		$roleIds = implode(',',$this->db->fetchCol($sql));
		if ($roleIds != '')
		{
			$sql = sprintf("SELECT role_name FROM %s WHERE role_id in (%s)",MY_ACL_ROLE_TAB,$roleIds);
			return $this->db->fetchCol($sql);
		}
		return array();
	}

	/**
     * 添加用户到角色中
     * @param string $userId:用户标识
     * @param string $roleName:角色名称
     * @return bool
     */
	public function addUser($userId,$roleName)
	{
		$roleId = $this->_getRoleID($roleName);
		if ($roleId > 0)
		{
			//检查是否已经添加过了
			$sql = sprintf("SELECT COUNT(aur_id) FROM %s WHERE user_id='%s' AND role_id=%d AND product_type='%s'",MY_ACL_USER_ROLE_TAB,$userId,$roleId,$this->productType);
			if ($this->db->fetchOne($sql) < 1)
			{
				$add = array();
				$add['user_id'] = $userId;
				$add['role_id'] = $roleId;
				$add['product_type'] = $this->productType;
				$this->db->Insert(MY_ACL_USER_ROLE_TAB,$add);
				return true;
			}
			return false;
		}
		return false;
	}

	/**
     * 移除用户
     * 注意:如果指定了 $roleName 则只将用户从该角色中移除；否则将用户从所有角色中移除.
     * @param string $userId:用户标识
     * @param string $roleName:角色名称
     * @return bool
     */
	public function removeUser($userId,$roleName='')
	{
		$where = array();
		$where['user_id'] = $userId;
		$where['product_type'] = $this->productType;
		if ($roleName != '')
		{
			$roleId = $this->_getRoleID($roleName);
			if ($roleId > 0) $where['role_id'] = $roleId;
			else return false;
		}
		$this->db->Delete(MY_ACL_USER_ROLE_TAB,$where);
		return true;
	}


	////////////////////////////////////////////////////////////////////////////////////////////
	//           角色相关操作接口
	////////////////////////////////////////////////////////////////////////////////////////////

	/**
     * 添加角色,支持角色的继承.
     * 注意:继承的角色在检测时顺序是从后向前的.如 addRole('角色A',array('角色B','角色C')) 
     *     表示 "角色A" 继承了 "角色B"和"角色C" 检查权限时是先检查 "角色C" 后再检测 "角色B"
     * @param string $roleName:角色名称
     * @param array $parentRole:要继承的角色数组,可以为空
     * @return bool
     */
	public function addRole($roleName,$parentRole=array())
	{
		//--检查角色是否存在
		$where = array();
		$where['role_name'] = $roleName;
		$where['product_type'] = $this->productType;
		if ($this->db->Count(MY_ACL_ROLE_TAB,$where) > 0) return false;

		//--检查所要继承的角色是否存在
		$parentRoleIds = array();
		foreach ($parentRole as $PRole)
		{
			$roleId = $this->_getRoleID($PRole);
			if ($roleId < 1) return false;
			else $parentRoleIds[] = $roleId;
		}

		//--添加角色
		$add = array();
		$add['role_name'] = $roleName;
		$add['product_type'] = $this->productType;
		$this->db->Insert(MY_ACL_ROLE_TAB,$add);
		$newRoleId = $this->db->Insert_ID();

		//--插入继承关系
		foreach ($parentRoleIds as $PRoleId)
		{
			$add = array();
			$add['role_id'] = $newRoleId;
			$add['role_parent_id'] = $PRoleId;
			$this->db->Insert(MY_ACL_ROLE_EXTEND_TAB,$add);
		}
		return true;
	}

	/**
     * 重命名角色
     * @param string $oldRoleName:要更改的角色名称
     * @param string $newRoleName:新的角色名称
     * @return bool
     */
	public function renameRole($oldRoleName,$newRoleName)
	{
		//--如果一样的名称则不用改了
		if ($oldRoleName == $newRoleName) return true;

		//--判断改名角色是否存在
		$oldRoleId = $this->_getRoleID($oldRoleName);
		if ($oldRoleId < 1) return false;

		//--判断要改的新名称是否存在了
		if ($this->_getRoleID($newRoleName) > 0) return false;

		//--重命名
		$sets = array();
		$sets['role_name'] = $newRoleName;
		$where = "role_id=$oldRoleId";
		$this->db->Update(MY_ACL_ROLE_TAB,$sets,$where);
		return true;
	}

	/**
     * 在当前产品中移除角色,如果没有指定角色名则移除所有的角色
     * @param string $roleName:角色名称
     * @return bool
     */
	public function removeRole($roleName = '')
	{
		$sql = sprintf("SELECT role_id FROM %s WHERE product_type='%s'",MY_ACL_ROLE_TAB,$this->productType);
		if ($roleName != '') $sql .= sprintf(" AND role_name='%s'",$roleName);
		$arrRoleId = $this->db->fetchCol($sql);
		foreach ($arrRoleId as $roleId)
		{
			//--删除角色对应的资源组关系
			$where = "role_id = $roleId";
			$this->db->Delete(MY_ACL_ROLE_RESGROUP_TAB,$where);

			//--删除被继承的关系
			$where = "role_parent_id = $roleId";
			$this->db->Delete(MY_ACL_ROLE_EXTEND_TAB,$where);

			//--删除角色本身
			$where = "role_id = $roleId";
			$this->db->Delete(MY_ACL_ROLE_TAB,$where);

			//--清除用户的角色关系
			$sets = array();
			$sets['role_id'] = 0;
			$where = "role_id = $roleId";
			$this->db->Update(MY_ACL_USER_ROLE_TAB,$sets,$where);
		}
		return true;
	}

	/**
     * 得到当前产品中所有角色列表
     * @return array 一维数组
     */
	public function roleList()
	{
		$sql = sprintf("SELECT role_name FROM %s WHERE product_type='%s'",MY_ACL_ROLE_TAB,$this->productType);
		return $this->db->fetchCol($sql);
	}


	/**
     * 在当前产品中判断该角色是否为超级角色;如果设置了 $isAdmin 则设置角色的该值 0:取消超级角色 1:设定为超级角色
     * @param string $roleName:角色名称
     * @param int $isAdmin:设置是否为超级角色 0:取消超级角色 1:设定为超级角色
     * @return bool
     */
	public function roleIsAdmin($roleName,$isAdmin=-1)
	{
		if ($isAdmin != -1)
		{
			$isAdmin = $isAdmin ? 1 : 0;
			//--设置超级属性
			$sets = array();
			$sets['role_isadmin'] = $isAdmin;
			$where = sprintf("role_name='%s' AND product_type='%s'",$roleName,$this->productType);
			$this->db->Update(MY_ACL_ROLE_TAB,$sets,$where);
			return true;
		}
		else{
			$sql = sprintf("SELECT role_isadmin FROM %s WHERE role_name='%s' AND product_type='%s'",MY_ACL_ROLE_TAB,$roleName,$this->productType);
			return $this->db->fetchOne($sql);
		}

	}

	/**
     * 返回角色的父级角色列表,没有则返回空;如果要返回所有继承级别的角色，则要循环调用该方法.
     * @param string $roleName:角色名称
     * @return array 一维数组
     */
	public function parentRoleList($roleName)
	{
		$sql = sprintf("SELECT role_id FROM %s WHERE role_name='%s' AND product_type='%s'",MY_ACL_ROLE_TAB,$roleName,$this->productType);
		$roleId = intval($this->db->fetchOne($sql));
		//--得到所有的父级角色
		$parentRole = array();
		$sql = sprintf("SELECT role_parent_id FROM %s WHERE role_id=%d ORDER BY are_id ASC",MY_ACL_ROLE_EXTEND_TAB,$roleId);
		$result = $this->db->fetchCol($sql);
		foreach ($result as $rolePid)
		{
			$sql = sprintf("SELECT role_name FROM %s WHERE role_id=%d",MY_ACL_ROLE_TAB,$rolePid);
			$parentRole[] = $this->db->fetchOne($sql);
		}
		return $parentRole;
	}

	/**
     * 为角色添加父级角色,即添加继承关系.
     * @param string $roleName:角色名称
     * @param array $parentRole:要添加继承的角色一维数组
     * @author bool
     */
	public function addParentRole($roleName,array $parentRole)
	{
		//--检查角色是否存在
		$sql = sprintf("SELECT role_id FROM %s WHERE role_name='%s' AND product_type='%s'",MY_ACL_ROLE_TAB,$roleName,$this->productType);
		$newRoleId = intval($this->db->fetchOne($sql));
		if ($newRoleId < 1) return false;

		//--检查所要继承的角色是否存在
		$parentRoleIds = array();
		foreach ($parentRole as $PRole)
		{
			$sql = sprintf("SELECT role_id FROM %s WHERE role_name='%s' AND product_type='%s'",MY_ACL_ROLE_TAB,$PRole,$this->productType);
			$roleId = intval($this->db->fetchOne($sql));
			if ($roleId < 1) return false;
			else $parentRoleIds[] = $roleId;
		}

		//--插入继承关系
		foreach ($parentRoleIds as $PRoleId)
		{
			$add = array();
			$add['role_id'] = $newRoleId;
			$add['role_parent_id'] = $PRoleId;
			$this->db->Insert(MY_ACL_ROLE_EXTEND_TAB,$add);
		}
		return true;
	}

	/**
     * 移除角色所指定继承的父角色;如果 $parentRoleName 为空则移除所有继承的角色
     * @param string $roleName:角色名称
     * @param string $parentRoleName:父角色名称
     * @author bool
     */
	public function removeParentRole($roleName,$parentRoleName='')
	{
		//--检查角色是否存在
		$roleId = $this->_getRoleID($roleName);
		if ($roleId < 1) return false;

		$where = array();
		$where['role_id'] = $roleId;

		if ($parentRoleName != '')
		{
			$PRoleId = $this->_getRoleID($parentRoleName);
			if ($PRoleId < 1) return false;
			$where['role_parent_id'] = $PRoleId;
		}
		$this->db->Delete(MY_ACL_ROLE_EXTEND_TAB,$where);
		return true;
	}

	/**
     * 返回角色的允许资源组列表,不包括父角色的
     * @param string $roleName:角色名称
     * @return array 一维数组,格式为: array('资源组1','资源组2');
     */
	public function roleAllowResGroupList($roleName)
	{
		$result = array();
		$roleId = $this->_getRoleID($roleName);
		if ($roleId >0)
		{
			$sql = sprintf("SELECT resgroup_id FROM %s WHERE role_id=%d AND allow_deny=1",MY_ACL_ROLE_RESGROUP_TAB,$roleId);
			$arrRGid = $this->db->fetchCol($sql);
			$RGids = implode(',',$arrRGid);
			if ($RGids != '')
			{
				$sql = sprintf("SELECT resgroup_name FROM %s WHERE resgroup_id in (%s)",MY_ACL_RESGROUP_TAB,$RGids);
				return $this->db->fetchCol($sql);
			}
		}
		return $result;
	}

	/**
     * 返回角色的禁止资源组列表,不包括父角色的
     * @param string $roleName:角色名称
     * @return array 一维数组,格式为: array('资源组1','资源组2');
     */
	public function roleDenyResGroupList($roleName)
	{
		$result = array();
		$roleId = $this->_getRoleID($roleName);
		if ($roleId >0)
		{
			$sql = sprintf("SELECT resgroup_id FROM %s WHERE role_id=%d AND allow_deny=0",MY_ACL_ROLE_RESGROUP_TAB,$roleId);
			$arrRGid = $this->db->fetchCol($sql);
			$RGids = implode(',',$arrRGid);
			if ($RGids != '')
			{
				$sql = sprintf("SELECT resgroup_name FROM %s WHERE resgroup_id in (%s)",MY_ACL_RESGROUP_TAB,$RGids);
				return $this->db->fetchCol($sql);
			}
		}
		return $result;
	}

	/**
     * 增加角色对应的资源组
     * @param string $roleName:角色名称
     * @param string|array $resGroupName:格式为 "资源组1,资源组2" 或 array('资源组1','资源组2')
     * @param int $access:访问控制选项 1:允许 0:禁止
     * @return bool
     */
	public function addRoleResGroup($roleName,$resGroupName,$access=1)
	{
		$roleId = $this->_getRoleID($roleName);
		if ($roleId > 0)
		{
			$arrRGid = array();
			$arrResGroupName = is_array($resGroupName) ? $resGroupName : explode(',',$resGroupName);
			foreach ($arrResGroupName as $RGName)
			{
				$_rgid = $this->_getResGroupID($RGName);
				if ($_rgid > 0) $arrRGid[] = $_rgid;
				else return false;
			}

			foreach ($arrRGid as $RGid)
			{
				$add = array();
				$add['role_id'] = $roleId;
				$add['resgroup_id'] = $RGid;
				$add['allow_deny'] = $access;
				$this->db->Insert(MY_ACL_ROLE_RESGROUP_TAB,$add);
			}
			return true;
		}
		return false;
	}

	/**
     * 移除角色对应的资源组
     * @param string $roleName:角色名称
     * @param string|array $resGroupName:格式为 "资源组1,资源组2" 或 array('资源组1','资源组2'),
     *        注意:为空则删除角色所有对应的资源组关系
     * @return bool     
     */
	public function removeRoleResGroup($roleName,$resGroupName='')
	{
		$roleId = $this->_getRoleID($roleName);
		if ($roleId > 0)
		{
			$where = "role_id=$roleId";
			if ($resGroupName != '')
			{
				$arrRGid = array();
				$arrResGroupName = is_array($resGroupName) ? $resGroupName : explode(',',$resGroupName);
				foreach ($arrResGroupName as $RGName)
				{
					$_rgid = $this->_getResGroupID($RGName);
					if ($_rgid > 0) $arrRGid[] = $_rgid;
					else return false;
				}
				$where .= sprintf(" AND resgroup_id in (%s)",implode(',',$arrRGid));
			}
			$this->db->Delete(MY_ACL_ROLE_RESGROUP_TAB,$where);
			return true;
		}
		return false;
	}

	/**
     * 私有成员:返回资源组名称对应的资源组ID
     * @param string $resGroupName:资源组名称
     * @return int
     */
	private function _getResGroupID($resGroupName)
	{
		$sql = sprintf("SELECT resgroup_id FROM %s WHERE resgroup_name='%s' AND product_type='%s'",MY_ACL_RESGROUP_TAB,$resGroupName,$this->productType);
		return intval($this->db->fetchOne($sql));
	}

	/**
     * 私有成员:返回角色名称对应的角色ID
     * @param string $roleName:角色名称
     * @return int
     */
	private function _getRoleID($roleName)
	{
		$sql = sprintf("SELECT role_id FROM %s WHERE role_name='%s' AND product_type='%s'",MY_ACL_ROLE_TAB,$roleName,$this->productType);
		return intval($this->db->fetchOne($sql));
	}


	////////////////////////////////////////////////////////////////////////////////////////////
	//           资源组相关操作接口
	////////////////////////////////////////////////////////////////////////////////////////////

	/**
     *  添加资源组,可以同时为资源组添加对应的资源
     * @param string $resGroupName:资源组名称
     * @param string|array $arrResourceName:资源名称,格式为 "资源名1,资源名2" 或 array('资源名1','资源名2')
     * @return bool
     */
	public function addResGroup($resGroupName,$arrResourceName='')
	{
		//--判断是否存在
		$RGid = $this->_getResGroupID($resGroupName);
		if ($RGid > 0) return false;

		//--不存在则添加
		$add = array();
		$add['resgroup_name'] = $resGroupName;
		$add['product_type'] = $this->productType;
		$this->db->Insert(MY_ACL_RESGROUP_TAB,$add);
		$resGroupId = $this->db->Insert_ID();

		//--如果指定了资源则添加
		if ($arrResourceName != '')
		{
			$arrResourceName = is_array($arrResourceName) ? $arrResourceName : explode(',',$arrResourceName);
			foreach ($arrResourceName as $resourceName)
			{
				$resId = $this->_getResourceID($resourceName);
				if ($resId > 0)
				{
					//--检查是否已经存在了
					$where = array();
					$where['resgroup_id'] = $resGroupId;
					$where['resource_id'] = $resId;
					if ($this->db->Count(MY_ACL_RESGROUP_RESOURCE_TAB,$where) < 1)
					{
						$add = array();
						$add['resgroup_id'] = $resGroupId;
						$add['resource_id'] = $resId;
						$this->db->Insert(MY_ACL_RESGROUP_RESOURCE_TAB,$add);
					}
				}
			}//End Foreach
		}
		return true;
	}

	/**
     * 重命名资源组
     * @param string $oldResGroupName:要更改的资源组名称
     * @param string $newRoleName:新的资源组名称
     * @return bool
     */
	public function renameResGroup($oldResGroupName,$newResGroupName)
	{
		//--如果一样的名称则不用改了
		if ($oldResGroupName == $newResGroupName) return true;

		//--判断改名资源组名是否存在
		$oldResGroupId = $this->_getResGroupID($oldResGroupName);
		if ($oldResGroupId < 1) return false;

		//--判断要改的新名称是否存在了
		if ($this->_getResGroupID($newResGroupName) > 0) return false;

		//--重命名
		$sets = array();
		$sets['resgroup_name'] = $newResGroupName;
		$where = "resgroup_id=$oldResGroupId";
		$this->db->Update(MY_ACL_RESGROUP_TAB,$sets,$where);
		return true;
	}

	/**
     * 移除资源组
     * @param string|array $resGroupName:资源组名称,格式为 "资源组名1,资源组名2" 或 array('资源组名1','资源组名2')
     * @return bool
     */
	public function removeResGroup($resGroupName)
	{
		$resGroupName = is_array($resGroupName) ? $resGroupName : explode(',',$resGroupName);
		foreach ($resGroupName as $RGname)
		{
			$_rgid = $this->_getResGroupID($RGname);
			if ($_rgid > 0) $arrRGid[] = $_rgid;
			else return false;
		}

		$RRIds = implode(',',$arrRGid);

		$where = sprintf("resgroup_id in (%s)",$RRIds);
		$this->db->Delete(MY_ACL_RESGROUP_TAB,$where);

		//--移除资源组与资源关系
		$where = sprintf("resgroup_id in (%s)",$RRIds);
		$this->db->Delete(MY_ACL_RESGROUP_RESOURCE_TAB,$where);

		//--角色与资源组关系
		$where = sprintf("resgroup_id in (%s)",$RRIds);
		$this->db->Delete(MY_ACL_ROLE_RESGROUP_TAB,$where);

		return true;
	}

	/**
     * 得到所有的资源组列表
     * @return array 一维数组,格式如: array('资源组1','资源组2');
     */
	public function resGroupList()
	{
		$sql = sprintf("SELECT resgroup_name FROM %s WHERE product_type='%s'",MY_ACL_RESGROUP_TAB,$this->productType);
		return $this->db->fetchCol($sql);
	}

	/**
     * 添加资源组对应的资源关系.
     * @param string $resGroupName:资源组名称
     * @param string|array $arrResourceName:资源名称,格式为 "资源名1,资源名2" 或 array('资源名1','资源名2')
     * @return bool
     */
	public function addResGroupResource($resGroupName,$arrResourceName)
	{
		$resGroupId = $this->_getResGroupID($resGroupName);
		if ($resGroupId < 1) return false;

		//--添加资源
		$arrResourceId = array();
		$arrResourceName = is_array($arrResourceName) ? $arrResourceName : explode(',',$arrResourceName);
		foreach ($arrResourceName as $resourceName)
		{
			$resId = $this->_getResourceID($resourceName);
			if ($resId > 0)
			{
				//--检查是否已经存在了
				$where = array();
				$where['resgroup_id'] = $resGroupId;
				$where['resource_id'] = $resId;
				if ($this->db->Count(MY_ACL_RESGROUP_RESOURCE_TAB,$where) < 1)
				{
					$arrResourceId[] = $resId;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}//End Foreach

		foreach ($arrResourceId as $resId)
		{
			$add = array();
			$add['resgroup_id'] = $resGroupId;
			$add['resource_id'] = $resId;
			$this->db->Insert(MY_ACL_RESGROUP_RESOURCE_TAB,$add);
		}
		return true;
	}

	/**
     * 移除资源组对应的资源关系.
     * @param string $resGroupName:资源组名称
     * @param string|array $arrResourceName:资源名称,格式为 "资源名1,资源名2" 或 array('资源名1','资源名2'),
     *        注意:如果为空则删除该资源组所对应的所有资源关系
     * @return bool
     */
	public function removeResGroupResource($resGroupName,$arrResourceName='')
	{
		$resGroupId = $this->_getResGroupID($resGroupName);
		if ($resGroupId < 1) return false;

		$where = sprintf("resgroup_id=%d",$resGroupId);

		if ($arrResourceName != '')
		{
			$arrResourceId = array();
			$arrResourceName = is_array($arrResourceName) ? $arrResourceName : explode(',',$arrResourceName);
			foreach ($arrResourceName as $resourceName)
			{
				$resId = $this->_getResourceID($resourceName);
				if ($resId > 0)
				{
					//--检查是否已经存在了
					$_where = array();
					$_where['resgroup_id'] = $resGroupId;
					$_where['resource_id'] = $resId;
					if ($this->db->Count(MY_ACL_RESGROUP_RESOURCE_TAB,$_where) >= 1)
					{
						$arrResourceId[] = $resId;
					}else{
						return false;
					}
				}else{
					return false;
				}
			}//End Foreach
			$where .= sprintf(" AND resource_id in (%s)",implode(',',$arrResourceId));
		}

		$this->db->Delete(MY_ACL_RESGROUP_RESOURCE_TAB,$where);
		return true;
	}

	/**
     * 得到资源组所对应的资源列表
	 * @param integer $n  :输出数组类型 1 输出一维数组
     * @param string $resGroupName:资源组名称
     * @return array 一维数组,格式如: array('资源名1'=>'动作1',
     *                                    '资源名2'=>'动作2')
     */
	public function resGroupResourceList($resGroupName,$n=0)
	{
		$resGroupId = $this->_getResGroupID($resGroupName);
		if ($resGroupId < 1) return false;

		//--得到所有的资源ID
		$sql = sprintf("SELECT resource_id FROM %s WHERE resgroup_id=%d",MY_ACL_RESGROUP_RESOURCE_TAB,$resGroupId);
		$resIds = implode(',',$this->db->fetchCol($sql));

		//--得到资源名称
		$arrResName = array();
		if ($resIds != '')
		{
			$sql = sprintf("SELECT resource_name,resource_action FROM %s WHERE resource_id in (%s)",MY_ACL_RESOURCE_TAB,$resIds);
			$result = $this->db->fetchAll($sql);
			foreach ($result as $row) {
				if ($n==1) {
					$arrResName[$row['resource_name']] = $row['resource_action'];
				} else {
					$arrResName[] = array($row['resource_name']=>$row['resource_action']);
				}
			}
		}
		return $arrResName;
	}

	////////////////////////////////////////////////////////////////////////////////////////////
	//           资源相关操作接口
	////////////////////////////////////////////////////////////////////////////////////////////

	/**
     * 返回当前产品中所有的资源列表
	 * @param integer $n  :输出数组类型 1 输出一维数组
     * @return array('资源名1'=>'动作1',
     *               '资源名2'=>'动作2')
     * @return array 默认二维数组
     */
	public function resourceList($n=0)
	{
		$where = array();
		$where['product_type'] = $this->productType;
		$result = $this->db->Select('resource_name,resource_action',MY_ACL_RESOURCE_TAB,$where);
		$array = array();
		foreach ($result as $row) {
			if ($n==1) {
				$array[$row['resource_name']] = $row['resource_action'];
			} else {
				$array[] = array($row['resource_name']=>$row['resource_action']);
			}
		}
		return $array;
	}

	/**
     * 返回当前产品中所有的资源列表
     * @return array 一维数组
     */
	public function getResourceList()
	{
		$where = array();
		$where['product_type'] = $this->productType;
		$result = $this->db->Select('resource_name,resource_action',MY_ACL_RESOURCE_TAB,$where);
		$array = array();
		foreach ($result as $row)
		{
			$array[$row['resource_name']] = $row['resource_action'];
		}
		return $array;
	}

	/**
     * 在当前产品中添加资源
     * @param array $arrResource:资源数组,格式为 array('资源名1'=>'动作1',
     *                                               '资源名2'=>'动作2')
     * @param bool
     */
	public function addResource(array $arrResource)
	{
		//--检查要添加的资源是否已经存在了
		foreach ($arrResource as $rname=>$raction)
		{
			if ($this->_getResourceID($rname) > 0) return false;
		}

		//--都不存在则添加
		foreach ($arrResource as $rname=>$raction)
		{
			$add = array();
			$add['resource_name'] = $rname;
			$add['resource_action'] = $raction;
			$add['product_type'] = $this->productType;
			$this->db->Insert(MY_ACL_RESOURCE_TAB,$add);
		}
		return true;
	}

	/**
     * 在当前产品中移除指定的资源
     * @param string|array $resourceName:格式为 "资源名1,资源名2" 或 array('资源名1','资源名2')
     * @return bool
     */
	public function removeResource($resourceName)
	{
		$arrRid = array();
		$arrResourceName = is_array($resourceName) ? $resourceName : explode(',',$resourceName);
		foreach ($arrResourceName as $resource)
		{
			$_rid = $this->_getResourceID($resource);
			if ($_rid > 0) $arrRid[] = $_rid;
			else return false;
		}

		$resIds = implode(',',$arrRid);

		$where = sprintf("resource_id in (%s)",$resIds);
		$this->db->Delete(MY_ACL_RESOURCE_TAB,$where);

		//--移除资源同时要将资源组的关系也一起移除
		$where = sprintf("resource_id in (%s)",$resIds);
		$this->db->Delete(MY_ACL_RESGROUP_RESOURCE_TAB,$where);

		return true;
	}

	/**
     * 私有成员:返回资源名所对应的资源ID
     * @param string $resourceName:资源名称
     * @return int
     */
	private function _getResourceID($resourceName)
	{
		$sql = sprintf("SELECT resource_id FROM %s WHERE resource_name='%s' AND product_type='%s'",MY_ACL_RESOURCE_TAB,$resourceName,$this->productType);
		return intval($this->db->fetchOne($sql));
	}



}
?>