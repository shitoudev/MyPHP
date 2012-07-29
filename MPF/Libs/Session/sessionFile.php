<?php
/**
 * SESSION 基于文件的管理
 */
session_save_path(MY_SESSION_SAVE_PATH);
class MY_SESSION {
    /**
     * 对外的接口,返回当前在有效时间内的SESSION数量
     *
     * @return int
     */
    static public function num()
    {
        return count(glob(MY_SESSION_SAVE_PATH . '*.session'));
    }

    /**
     * 对外的接口,返回当前在有效时间内所有的 SID
     *
     * @return array
     */
    static public function allSid()
    {
        $arrSfile =  glob(MY_SESSION_SAVE_PATH . '*.session');
        return array_map('SESSION::getSidByFile',$arrSfile);   
    }
    
    /**
     * 初始化
     */
    static public function init()
    {
        return true;
    }
    
    /**
     * 私有方法,根据 SID 得到文件名
     */
    static private function sessionFile($session_id)
    {
        return MY_SESSION_SAVE_PATH . $session_id . '.session';
    }
    /**
     * 私有方法,根据文件名得到 SID 
     */
    static private function getSidByFile($sessionFile)
    {
		$sessionFile = basename($sessionFile);
        return substr($sessionFile,0,strpos($sessionFile,'.'));
    }
    
    static public function open()
    {
        return true;
    }
    static public function close()
    {
        return true;
    }
    static public function read($session_id)
    {
        $sessFile = self::sessionFile($session_id);
        return (string) @file_get_contents($sessFile);
    }
    static public function write($session_id,$session_val)
    {
        $sessFile = self::sessionFile($session_id);
        if ($fp = @fopen($sessFile,"w+"))
        {
            $bool = fwrite($fp,$session_val);
            fclose($fp);
            return $bool;
        }
        return false;
    }
    static public function destroy($session_id)
    {
        $sessFile = self::sessionFile($session_id);
        return @unlink($sessFile);
    }
    static public function gc($maxlifetime)
    {
        foreach (glob(MY_SESSION_SAVE_PATH . '*.session') as $filename)
        {
            if (filemtime($filename)+$maxlifetime < time()) @unlink($filename);
        }
        return true;
    }
}
session_set_save_handler('MY_SESSION::open','MY_SESSION::close','MY_SESSION::read','MY_SESSION::write','MY_SESSION::destroy','MY_SESSION::gc');
?>