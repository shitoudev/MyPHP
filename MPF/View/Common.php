<?php
/**
 * view的通用方法
 */
class View_Common
{
	private $cacheState = MY_CACHE_DEFAULT; //默认是否缓存
   
	//获取是否需要缓存
	public function getCacheState()
	{ 
		return $this -> cacheState;
	}
	
	//设置view缓存状态
	public function setCacheState($state)
	{ 
		$this -> cacheState = $state;
	}
	
	//静态方法:系统提示及跳转
	public static function redirect($url,$sec = 0,$msg = '',$tplFile = '')
	{
		if(!$tplFile && defined('MY_SHOWINFO_TPL')) $tplFile = MY_SHOWINFO_TPL;
		if($tplFile)
		{
			$tplFile = MY_VIEW_PATH."templates/".MY_LANG.".".MY_CHARSET.'/'.$tplFile; //加上路径
			if(file_exists($tplFile)) $content = file_get_contents($tplFile);
		}
		
		$lang = Lang::getInstance();
		$text = sprintf($lang->get('Core_RedirectText'),$url,$url);
		if(!isset($content))
		{
			$content = "<html><head><meta name=\"content-type\" content=\"text/html; utf-8\"><meta http-equiv=\"refresh\" content=\"$sec;url=$url\"></head><body><div>$msg</div><div>$text</div></body></html>";
		}else{
			$search = array("\$msg","\$sec","\$url","\$text");
			$replace = array($msg,$sec,$url,$text);
			$content = str_replace($search,$replace,$content);
		}
		exit($content);
	}
}
?>