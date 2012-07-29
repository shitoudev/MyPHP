<?php
/**
 * 系统日志工具
 * 
 * [以下为三个日志类型]
 * 
 * DEBUG_LOG  调试信息
 * ERROR_LOG  错误信息
 * SQL_LOG    SQL调试
 * 
 */

class Log{
	
	/**
	 * 写日志
	 * @param string $key :日志KEY,用来区分不同的日志
	 * @param string $message :日志内容
	 * @param string $logType :日志内容
	 */
	static public function write($key,$message,$logType='DEBUG_LOG')
	{
		$enter = "\r\n";
		$fileName = self::_logfile($key,$logType);
		$fp = fopen($fileName, 'a+');
        if ($fp) {
        	$content = date('[ Y-m-d H:i:s ]');
        	$content .= $enter.$message.$enter.$enter;
            flock($fp, LOCK_EX);
            fwrite($fp, $content);
            @chmod($fileName,0777);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } else {
            return false;
        }    		
	}
	
	/**
	 * 读日志
	 *
	 * @param string $key :日志KEY,用来区分不同的日志
	 * @param string $logType :日志内容
	 * @param string $outType :输出日志的类型 'TXT':文本格式 'HTML':HTML格式
	 */
	static public function read($key,$outType='txt',$logType='DEBUG_LOG')
	{
		$outType = strtolower($outType);
		$fileName = self::_logfile($key,$logType);
		$str = @file_get_contents($fileName);
		if ($str && $outType=='html')
		{
			$str = nl2br($str);
		}
		return $str; 
	}
	
	/**
	 * 清空日志
	 *
	 * @param string $key :日志KEY,用来区分不同的日志
	 * @param string $logType :日志内容
	 */
	static public function clear($key,$logType='DEBUG_LOG')
	{
		$fileName = self::_logfile($key,$logType);
		return @unlink($fileName);				
	}
	
	/**
	 * 私有方法,得到日志文件名
	 *
	 * @param string $key :日志KEY,用来区分不同的日志
	 * @param string $logType :日志内容
	 */
	static public function _logfile($key,$logType='DEBUG_LOG')
	{
		switch ($logType)
		{
			case 'DEBUG_LOG':return QP_LOGS_PATH.'debug_'.$key.'.log';
			case 'ERROR_LOG':return QP_LOGS_PATH.'error_'.$key.'.log';
			case 'SQL_LOG':  return QP_LOGS_PATH.'sql_'.$key.'.log';
			default:	     return QP_LOGS_PATH.'debug_'.$key.'.log';
		}
	}
}
?>