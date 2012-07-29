<?php
/**
 * SESSION 基于数据库的管理
 */ 
define('MY_SESSION_TABLE',MY_SESSION_TAB_PREFIX.'session'); //SESSION表名
require_once('Model.php');
class MY_SESSION {
    static private $db = null;
    static private $leftTime = 0;
    
    /**
     * 对外接口,返回当前在有效时间内的SESSION数量
     *
     * @return int
     */    
    static public function num()
    {
        self::conDB();
        $time = self::$leftTime + time();
        $where = 'sess_time>'.$time;
        return self::$db->Count(MY_SESSION_TABLE,$where);
    }
    
    /**
     * 对外的接口,返回当前在有效时间内所有的 SID
     *
     * @return array
     */
    static public function allSid()
    {
        self::conDB();
        $time = self::$leftTime + time();
        $sql = sprintf("select sess_id from %s where sess_time>%d",MY_SESSION_TABLE,$time);
        return self::$db->fetchCol($sql);
    }    
    
    /**
     * 初始化
     */    
    static public function init()
    {
        self::conDB();
        $arraySQL = include('Libs/Session/initTable.php');
        foreach ($arraySQL as $sql)
        {
            $search = array('[ENGINE]','[CHARSET]');
            $replace = array(MY_SESSION_TAB_ENGINE,MY_SESSION_TAB_CHARSET);
		    $sql = str_replace($search,$replace,$sql);
		    self::$db->Execute($sql);
        }        
    }  
      
    /**
     * 连接数据库
     */    
    static private function conDB()
    {
        if (self::$db === null)
        {
            self::$db = new Model();
            self::$leftTime = ini_get('session.gc_maxlifetime');
        }
    }
    
    static public function open()
    {
        return true;
    }
    
    static public function close()
    {
        if (self::$db != null) self::$db->Close();
        return true;
    }
    
    static public function read($session_id)
    {
        self::conDB();
        $sql = sprintf("select sess_value from %s where sess_id='%s' and sess_time>%d",MY_SESSION_TABLE,$session_id,time());
        return self::$db->fetchOne($sql);
    }
    
    static public function write($session_id,$session_val)
    {
        self::conDB();
        $time = self::$leftTime + time();
        $sets = array('sess_id'=>$session_id,
                      'sess_time'=>$time,
                      'sess_value'=>$session_val);
        //REPLACE INTO 方式插入
        self::$db->Insert(MY_SESSION_TABLE,$sets,true);
        return true;
    }
    
    static public function destroy($session_id)
    {
        self::conDB();
        $where = "sess_id='$session_id'";
        self::$db->Delete(MY_SESSION_TABLE,$where);
    }
    
    static public function gc($maxlifetime)
    {
        self::conDB();
        $where = 'sess_time<'.time();
        self::$db->Delete(MY_SESSION_TABLE,$where);
    }
}

session_set_save_handler('MY_SESSION::open','MY_SESSION::close','MY_SESSION::read','MY_SESSION::write','MY_SESSION::destroy','MY_SESSION::gc');
?>