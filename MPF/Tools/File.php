<?php
/**
 * 文件系统相关操作
 */

/**
[示例]
-------- Mfile.php 控制器代码如下 -----------
File::copy('a.jpg','b.jpg'); //把 a.jpg 复制一个新文件为 b.jpg
File::copy('/home/a.jpg','/usr/b.jpg'); //同上，支持路径
File::clearDir('c:\\temp'); //清空c:\temp 目录下的所有文件,只保留目录结构
File::del('/home/a.jpg'); //删除 /home/a.jpg 文件
File::deltree('c:\\temp'); //删除 c:\temp 目录下的所有文件包括子目录
File::dieEmpty('c:\\temp');//判断 c:\temp 是否为空目录,即没有文件存在
echo File::ext('/home/a.jpg'); //输出文件的扩展名 .jpg
print_r(File::dieFile('c:\\temp')); //打印 c:\temp 目录下所有的文件
File::mkDir('c:\\temp\a\b');//递归的创建目录
File::move('/home/a.jpg','/tmp/');//把 /home/a.jpg 移动到 /tmp 目录下
File::move('/home/a.jpg','/tmp/b.jpg');//把 /home/a.jpg 移动到 /tmp 目录下并改名为 b.jpg
echo File::read('/home/a.txt'); //显示 a.txt 文件中的所有内容
File::write('/home/a.txt','yuanwei OK');//将 "yuanwei OK" 字符串保存为 a.txt
-------------------------------------------
[注意]
1：这个工具只是一个函数库,调用方法时不需要关心调用的先后顺序.
*/

class File {
    
    /**
     * 复制文件
     *
     * @return Boolean
     */
    static public function copy($sourceFile,$destFile,$mode=0755,$overlay=false)
    {
        if ($mode == null) $mode = 0755;
        //无条件覆盖或目标文件不存在
        if ($overlay || !file_exists($destFile))
        {
            $bool = @copy($sourceFile,$destFile);
            @chmod($destFile,$mode);
        }else{
            $bool = false;
        }
        return $bool;
    }

    /**
     * 将内容写为文件
     *
     * @return Boolean
     */
    static public function write($fileName, $content,$mode=0755)
    {
        $fp = fopen($fileName, 'wb');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $content);
            @chmod($fileName,$mode);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } else {
            return false;
        }        
    }    
    
    /**
     * 读文件内容
     *
     * @return Boolean
     */
    static public function read($filename)
    {
        $fp = fopen($filename, 'rb');
        if ($fp) {
            flock($fp, LOCK_SH);
            clearstatcache();
            $filesize = filesize($filename);
            if ($filesize > 0) {
                $data = fread($fp, $filesize);
            } else {
                $data = false;
            }
            flock($fp, LOCK_UN);
            fclose($fp);
            return $data;
        } else {
            return false;
        }        
    }
    
    /**
     * 删除文件
     *
     * @return Boolean
     */
    static public function del($filename)
    {
        return @unlink($filename);        
    }
    
    /**
     * 移动文件
     * 注意: $destFile 可以是目录名也可以是目标文件名
     */
    static public function move($sourceFile,$destFile)
    {
        self::copy($sourceFile,$destFile);
        return self::del($sourceFile);  
    }
    
    /**
     * 递归创建目录
     * @return Boolean
     */
    static public function mkDir($dir, $mode = 0755)
    {
      if (is_dir($dir) || @mkdir($dir,$mode)) return true;
      if (!self::mkDir(dirname($dir),$mode)) return false;
      return @mkdir($dir,$mode);
    }    

    
	/**
	 * 得到目录下指定的文件
	 * @param string $path:目录名
	 * @param string $pattern:文件类型,支持通配符
	 * @param string $flag: 'all':返回包括目录 'file':只包括文件
	 * @return array
	 */
	static public function dirFile($path,$pattern='*',$flag='file') 
	{
		static $listFiles = array();
		$lastN = strlen($path)-1;
		if ($path[$lastN]!='\\' && $path[$lastN]!='/') $path .= '/';
		foreach (glob($path.$pattern) as $file)
		{
		    if (is_dir($file))
		    {
		        if ($flag == 'all') $listFiles[] = $file;
		        self::dirFile($file,$pattern);
		    }else{
		        $listFiles[] = $file;
		    }
		}
		return $listFiles;
	}

	/**
	 * 判断目录是否为空
	 * @return bool
	 */
	static public function dirEmpty($directory)
	{
		$handle = opendir($directory);
		while (($file = readdir($handle)) !== false)
		{
			if ($file != "." && $file != "..")
			{
				closedir($handle);
				return false;
			}
		}
		closedir($handle);
		return true;
	}

	/**
	 * 删除目录树,包抱文件
	 * @param string $path:目录名
	 * @param string $pattern:文件类型,支持通配符
	 * @param string $subDir:删除是否应用于子目录
	 * @return void
	 */
	static public function deltree($path,$pattern='*',$subDir=false)
	{
	    if (!is_dir($path)) return;
		$lastN = strlen($path)-1;
		if ($path[$lastN]!='\\' && $path[$lastN]!='/') $path .= '/';
		foreach (glob($path.$pattern) as $file)
		{
		    if (is_dir($file) && $subDir)
		    {
		        self::deltree($file,$pattern,$subDir);
		        rmdir($file);
		    }else{
		        @unlink($file);
		    }
		}
	}

	/**
	 * 清空目录,即删除所有文件,只保留目录结构
	 * @return void
	 */
	static public function clearDir($path)
	{
	    if (!is_dir($path)) return;
		$lastN = strlen($path)-1;
		if ($path[$lastN]!='\\' && $path[$lastN]!='/') $path .= '/';
		foreach (glob($path.'*') as $file)
		{
		    if (is_dir($file)) self::clearDir($file);
            else @unlink($file);
		}
	}
	
	/**
	 * 得到文件扩展名,默认带 "."
	 */
	static public function ext($fileName,$char='.')
	{
        $pathParts = pathinfo($fileName);
        return $char.$pathParts["extension"];
	}
	
}
?>