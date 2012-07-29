<?php
/**
 * 常用杂项函数类
 */
/**
[示例]
-------- Mfun.php 控制器代码如下 ------------
echo Func::randNum(); //返回一个随机的整数
echo Func::randNum(1,100); //返回1-100之间的整数
echo Func::md5Rand(); //返回永远唯一32位md5的随机数
echo Func::msubstr('我是a中国人',0,3); //字符串截取，支持中文和其他编码
echo Func::randString();//得到默认长度6位,字母和数字混合的随机字串
echo Func::toHtml('<a href="a.php">aaaa</a>');//输出对应的HTML字符
echo Func::strExists('34','12345');//判断'34'是否出现在'1234'中
-------------------------------------------
[注意]
1：这个工具只是一个函数库,调用方法时不需要关心调用的先后顺序.
*/
class Func{
    
    /**
     * 返回指间区间内的一个随机数,如果没有指定则返回 0- RAND_MAX 中取一个乱数 
     */
    static public function randNum($min=null,$max=null)
    {
        mt_srand((double)microtime()*1000000);
    	if($min===null || $max===null) return mt_rand();
    	else return mt_rand($min,$max);
    }
    
    /**
     * 产生随机字串,默认长度6位,字母和数字混合
     * @param string $len 长度 
     * @param string $type 随机符类型 0:数字 1:小写字母 2:大写字母 3:所有字母与数字组合
     * @param string $addChars 额外字符 
     * @return string
     */
    static public function randString($len=6,$type=3,$addChars='')
    { 
        $str ='';
        switch($type)
        { 
            case 0:
                $chars = str_repeat('0123456789',3).$addChars; 
                break;
            case 1:
                $chars='abcdefghijklmnopqrstuvwxyz'.$addChars; 
                break;
            case 2:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars; 
                break;
            case 3:
                //去掉了容易混淆的字符oOLl和数字01
                $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars; 
                break;
        }
        //位数过长重复字符串一定次数
        if($len>10 ) {
            $chars= $type==0? str_repeat($chars,$len) : str_repeat($chars,5); 
        }
        //得到随机数
        $charLen = strlen($chars)-1;
        for ($i=0; $i<$len; $i++)
        {
            $n = self::randNum(0,$charLen);
            $str .= $chars[$n];
        }
        return $str;
    }    
    
    /**
     * 字符串截取，支持中文和其他编码
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符,是否显示 '...'
     * @return string
     */
    static public function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
    {
    	if(function_exists("mb_substr"))
    		$slice = mb_substr($str, $start, $length, $charset);
    	elseif(function_exists('iconv_substr')) {
    		$slice = iconv_substr($str,$start,$length,$charset);
    	}else{
        	$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        	$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        	$re['gbk']	  = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        	$re['big5']	  = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        	preg_match_all($re[$charset], $str, $match);
        	$slice = join("",array_slice($match[0], $start, $length));
    	}
    	if($suffix) return $slice."…";
    	return $slice;
    }
    
    /**
     * 判断指定的字符串是否存在
     * @param string $str :字符或字符串(子串)
     * @param string $string :字符串(母串)
     * 例子: $str='34' $string='1234' 返回 TRUE
     */
    static public function strExists($str,$string)
    {
    	$string = (string) $string;
    	$str = (string) $str;
    	return strstr($string,$str)===false ? false : true;
    }
    
    /**
     * 转换 HTML 特殊字符以及空格和换行符
     * 一般将 <textarea> 标记中输入的内容从数据库中读出来后在网页中显示
     */
    static public function toHtml($text)
    {
		$text =  htmlspecialchars($text);
		$text =  nl2br(str_replace(' ', '&nbsp;', $text));
		return my_recho($text); //define in MyPHP\Libs\CoreFunction.inc.php  	
    }
    
    /**
     * 返回永远唯一32位md5的随机数
     */
    static public function md5Rand()
    {
    	srand((double)microtime()*1000000);
    	return md5(uniqid(time().rand()));
    }    

}

?>