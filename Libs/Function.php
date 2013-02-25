<?php
/**
 * 自定义函数库
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */

/**
 * 获取ids字符串
 *
 * @param  array  $array
 * @param  string $str
 * @return string
 *
 */
function get_ids($array, $str = ',')
{
	if(empty($array)) return FALSE;
	foreach ($array as $key => $value) $ids .= $value.$str;
	$ids = substr($ids, 0, -1);
	return $ids;
}

/**
 * 字符串按字节截取，支持中英文
 *
 * @param  string $string 需要转换的字符串
 * @param  string $length 截取长度(字节数)
 * @param  string $etc    后缀
 * @param  string $countWords 是否判断字节
 * @return string
 */
function msubstr($string, $length = 80, $etc = '..', $countWords = TRUE)
{
	if ($length == 0) return '';
	if (strlen( $string ) <= $length) return $string;
	preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $info);
	if($countWords)
	{
		$j = 0;
		for($i=0; $i<count($info[0]); $i++)
		{
			$wordscut .= $info[0][$i];
			if(ord($info[0][$i]) >= 128)
			{
				$j = $j+2;
			}
			else
			{
				$j = $j + 1;
			}
			if ($j >= $length )
			{
				return $wordscut.$etc;
			}
		}
		return join('', $info[0]);
	}
	return join('', array_slice($info[0],0,$length)).$etc;
}

/**
 * 页面重定向
 *
 * @param string $url :将要跳转的URL,如果为空则自动返回到上一页.
 * @param string $msg :消息文本
 * @param int $time   :页面显示停留的时间,单位:秒
 * @param string $tplFile :为空则使用框架自带的消息模板.
 */
function redirect($url, $msg='', $time=0, $tplFile='')
{
	if (empty($msg)) $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
	if (!headers_sent())
	{
		if (0 === $time)
		{
			header('Location: ' . $url);
		}
		else
		{
			header("refresh:{$time};url={$url}");
			echo($msg);
		}
		exit();
	}
	else 
	{
		$str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
		if ($time != 0) $str .= $msg;
		exit($str);
	}
}


/**
 * 获取公共页头
 *
 * @param  int $menu 当前菜单
 * @return string html
 */
function get_header($menu = 0)
{
	load_action('ViewCommon', 'header', $menu);
}

/**
 * 获取公共页脚
 *
 * @return string html
 */
function get_footer()
{
	load_action('ViewCommon', 'footer');
}

/* End */