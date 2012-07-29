<?php
/**
 * 视图组件
 */

//MY_VIEW_INTERFACE define in iView.php 防止重复引用
defined('MY_VIEW_INTERFACE') || require('View/iView.php');
class View
{
	public function __construct()
	{
		throw new Exception ("致开发者:请使用 View::parse('模板文件','是否缓存') 方式调用..");
	}

	/**
	 * 解析视图
	 *
	 * @param string $tplFile :视图文件
	 * @param array $data :数据数组
	 * @param int $state :是否开启页面缓存 1 开启 0关闭 注意:这里的优先级别大于 MY_CACHE_DEFAULT 常量的定义
	 * @return object
	 */
	static public function parse($tplFile, $data=array(), $state=null)
	{
		$viewEngineObj = 'View_'.ucfirst(MY_TPL_ENGINE);
		$viewEngineFile = MY_CORE_ROOT."View/".MY_TPL_ENGINE.MY_EXT;
		if(file_exists($viewEngineFile)) include_once($viewEngineFile);
		if(!class_exists($viewEngineObj)) {
			$lang = Lang::getInstance();
			throw new Exception (sprintf($lang->get('Core_TplEngineNotFound'),MY_TPL_ENGINE));
		}		
		$data['vanDomain'] = MY_VAN_DOMAIN!='' ? 'http://'.MY_VAN_DOMAIN : '';
		$data['rootDomain'] = MY_ROOT_DOMAIN!='' ? 'http://www.'.MY_ROOT_DOMAIN : '';
		$viewEngineObj = new  $viewEngineObj($tplFile, $data);
		if (isset($state)) {
			if ($state) $viewEngineObj -> setCacheState(true);
			else        $viewEngineObj -> setCacheState(false);
		}else{
			if (MY_CACHE_DEFAULT) $viewEngineObj -> setCacheState(true); //更改view缓存状态
		}
		return $viewEngineObj;
	}

	/**
	 * 解析视图后生成静态文件
	 *
	 * @param string $tplFile :视图文件
	 * @param string $writeFile :生成的静态文件名
	 */
	static public function createHtml($tplFile,$writeFile)
	{
		$tplObj = View::parse($tplFile);
		$Contents = $tplObj->fetch();
		if (file_put_contents($writeFile,$Contents) <= 0)
		{
			throw new Exception(sprintf(Lang::get('Core_TplWriteHtmlError'),$writeFile));
		}
		return true;
	}

	/**
	* 配合CacheLite_Output输出
	*
	 * @param string $tplFile :视图文件	 
	*/
	static public function showHtml($tplFile, $data=array())
	{
		$tplObj = View::parse($tplFile, $data);
		$Contents = $tplObj->fetch();
		return $Contents;
	}

	/**
	 * 页面重定向
	 *
	 * @param string $url :将要跳转的URL,如果为空则自动返回到上一页.
	 * @param string $msg :消息文本
	 * @param int $sec :页面显示停留的时间,单位:秒
	 * @param string $tplFile :为空则使用框架自带的消息模板.
	 */
	static public function redirect($url,$msg = '',$sec = 0,$tplFile = '')
	{
		if ($url != '' && $sec == 0)
		{
			header("Location: $url");
			exit;
		}
		if ($msg != '' && $sec == 0) $sec = 5;
		if(!$tplFile && defined('MY_SHOWMSG_TPL') && MY_SHOWMSG_TPL) $tplFile = MY_SHOWMSG_TPL;
		if($tplFile)
		{
			$tplFile = MY_VIEW_PATH._LANG."."._CHARSET.'/'.$tplFile; //加上路径
			if(file_exists($tplFile)) $content = file_get_contents($tplFile);
		}
		if(!isset($content)) //使用默认消息页面
		{
			$content =  file_get_contents(MY_CORE_ROOT.'Libs/msgbox.html');
		}
		//替换文件中的内容
		$search = array("{URL}","{MSG}","{TIME}");
		$replace = array($url,$msg,$sec);
		$content = str_replace($search,$replace,$content);
		exit($content);
	}

	/**
	 * 消息提示,自动返回到上一个页面 redirect() 函数别名
	 */
	static public function Alert($msg,$sec=5)
	{
		View::redirect('',$msg,$sec);
	}

	/**
	 * js方式消息提示，並返回
	 */
	static public function jsAlert($msg, $url) {
		die('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<script type="text/JavaScript">
			alert("'.$msg.'"); 
			window.location="'.$url.'";
			</script>
			');
		exit();
	}

	/**
	 * 自动返回到上一个页面
	 */
	static public function Back()
	{
		header("Location: ".$_SERVER['HTTP_REFERER']);
	}
}
?>