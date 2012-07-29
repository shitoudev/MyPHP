<?php
/**
 * 表单验证
*/
 
/*
Compare  => 两个控件的值是否相同
Email    => 是否为有效的Email地址
RegExp   => 用指定的正则进行验证
Required => 是否为空(包括不能为全是空格)
StrSize  => 字符串长度是否在指定长度内
Numeric  => 是否为全是数字的字符串(可以是 "0" 开头的数字串)
Integer  => 判断整数数值大小(包括正数和负数)

QQ       => 腾讯QQ号
IdCard   => 身份证号码
China    => 是否为中文
Zip      => 邮政编码
Phone    => 固定电话(区号可有可无)
Mobile   => 手机号码
MobilePhone => 手机和固定电话
*/
class iValid{
    static private $coreRule = array('Compare','Required','StrSize','Integer','Float','Email','RegExp','Numeric','Zip','Phone','Mobile','MobilePhone','QQ','China','IdCard');
    static private $regExp = array(
    'RegExp'      => "", //自定义正则
    'Email'       => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([_a-z0-9]+\.)+[a-z]{2,5}$/',
    'Numeric'     => '/^[0-9]+$/',
    'Zip'         => '/^[1-9]\d{5}$/',
    'Phone'       => '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/',
    'Mobile'      => '/^((\(\d{2,3}\))|(\d{3}\-))?13\d{9}$/',
    'MobilePhone' => '/(^[0-9]{3,4}\-[0-9]{3,8}$)|(^[0-9]{3,12}$)|(^\([0-9]{3,4}\)[0-9]{3,8}$)|(^0{0,1}13[0-9]{9}$)/',
    'QQ'          => '/^[1-9]*[1-9][0-9]*$/',
    'China'       => '/[\u4e00-\u9fa5]/',
    'IdCard'      => '/\d{15}|\d{18}/', 
    );
    
    static public function isCoreRule($ruleName)
    {
        return in_array($ruleName,self::$coreRule);
    }
    static public function isRegular($ruleName)
    {
        return array_key_exists($ruleName,self::$regExp);
    }
    static public function regularServer($rule,$request,$data)
    {  
        $Regular = $rule=='RegExp' ? $data['param'] : self::$regExp[$rule];
        $value = $request->getPost($data['field']);
        return preg_match($Regular,$value);
    }
    
    static public function regularClient($rule,$data)
    {
        $eleName = $data['field'];
        $Regular = $rule=='RegExp' ? $data['param'] : self::$regExp[$rule];
return <<<SCR
        case '$eleName':
           var ExpStr = $Regular;
           if (!ExpStr.test(eleValue))
           {
               [FalseFunction]
               return false;
           }else{
               if (!isSubmit)
    {[TrueFunction]}
           }
           break;\n
SCR;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                    函数式规则定义在这里
///////////////////////////////////////////////////////////////////////////////

//------判断两个控件的值是否相同
function MY_Valid_Compare_Server($request,$data)
{
    return $request -> getPost($data['field']) == $request -> getPost($data['param']);
}
function MY_Valid_Compare_Client($data)
{
    $eleNameCom = $data['param'];
    $eleName = $data['field'];
return <<<SCR
        case '$eleName':
           var comValue = eleObj.form.$eleNameCom.value;
           if (eleValue != comValue)
           {
               [FalseFunction]
               return false;
           }else{
               if (!isSubmit)
    {[TrueFunction]}
           }
           break;\n
SCR;
}

//------是否为空(包括不能为全是空格)
function MY_Valid_Required_Server($request,$data)
{
    return trim($request -> getPost($data['field'])) != '';
}
function MY_Valid_Required_Client($data)
{
    $eleName = $data['field'];
return <<<SCR
        case '$eleName':
           var val = eleValue.replace(/(^\s+)|(\s+$)/g,'');
           if (val == '')
           {
               [FalseFunction]
               return false;
           }else{
               if (!isSubmit)
    {[TrueFunction]}
           }
           break;\n
SCR;
}

//------字符串长度是否有规定长度内
function MY_Valid_StrSize_Server($request,$data)
{
	$value = $request->getPost($data['field']);
	$params = array_map('intval',explode(",",$data['param']));
	if(function_exists('mb_strlen')) $len = mb_strlen($value,MY_CHARSET);
	else $len = strlen($value); //不支持多字节函数
	@list($min,$max) = $params;
	
    if (isset($max)) return ($len>=$min && $len<=$max);
    else return $len>=$min;
}
function MY_Valid_StrSize_Client($data)
{
	$params = array_map('intval',explode(",",$data['param']));
	@list($min,$max) = $params;
	if (!isset($max)) $max = 99999;
	$eleName = $data['field'];
return <<<SCR
      case '$eleName':
          var min = $min;
          var max = $max;
          var len = eleValue.length;
          if (len<min || len>max)
          {
              [FalseFunction]
              return false;
          }else{
              if (!isSubmit)
    {[TrueFunction]}
          }  
          break;\n
SCR;
}

//------判断整数数值大小(包括正数和负数)
function MY_Valid_Integer_Server($request,$data)
{
    $value = $request->getPost($data['field']);
    $bool = (string)(int)$value === (string)$value;
    if ($bool) //是整数
    {
        $params = explode(",",$data['param']);
        @list($min,$max) = $params;
        if(!isset($max)) return ($value >= intval($min)); //没有设定最大值
        else return ($value >= intval($min) && $value <= intval($max));
    }
    return false;
}
function MY_Valid_Integer_Client($data)
{
    $params = explode(",",$data['param']);
    @list($min,$max) = $params;
    if (!isset($max)) $max = 'null';
    $eleName = $data['field'];
return <<<SCR
        case '$eleName':
           if (eleValue.length == 1) var strExp = /^[0-9]$/;
           else var strExp = /^[-1-9]*[1-9][0-9]*$/;
           if (!strExp.test(eleValue))
           {
               [FalseFunction]
               return false;
           }
           var min = $min;
           var max = $max;
           var bool = false;
           if (max == null) bool = (eleValue>=min);
           else bool = (eleValue>=min && eleValue<=max);
           
           if (!bool)
           {
               [FalseFunction]
               return false;
           }else{
               if (!isSubmit)
    {[TrueFunction]}
           }
           break;\n
SCR;
}

//------判断浮点数数值大小(包括正浮点数和负浮点数)
function MY_Valid_Float_Server($request,$data)
{
    $value = $request->getPost($data['field']);
    $bool = preg_match("/^(-?\d+)(\.\d+)?$/",$value);
    //$bool = (string)(float)$value === (string)$value;
    if ($bool) //是浮点数
    { 
        $params = explode(",",$data['param']);
        @list($min,$max) = $params;
        if(!isset($max)) return ($value >= $min); //没有设定最大值
        else return ($value >= $min && $value <= $max);
    }
    return false;
}
function MY_Valid_Float_Client($data)
{
    $params = explode(",",$data['param']);
    @list($min,$max) = $params;
    if (!isset($max)) $max = 'null';
    $eleName = $data['field'];
return <<<SCR
        case '$eleName':
           var strExp = /^(-?\d+)(\.\d+)?$/;
           if (!strExp.test(eleValue))
           {
               [FalseFunction]
               return false;
           }
           var min = $min;
           var max = $max;
           var bool = false;
           if (max == null) bool = (eleValue>=min);
           else bool = (eleValue>=min && eleValue<=max);
           
           if (!bool)
           {
               [FalseFunction]
               return false;
           }else{
               if (!isSubmit)
    {[TrueFunction]}
           }
           break;\n
SCR;
}




//////////////////////////////////////////////////////////////////////////////////////////////
//                以下是关于成生JS代码特定的函数
//////////////////////////////////////////////////////////////////////////////////////////////
//------返回用户自定义规则的JS代码
function MY_Valid_UserRuleJS($ruleName,$data)
{
    $eleName = $data['field'];
return <<<SCR
        case '$eleName':
           if (!$ruleName(eleValue))
           {
               [FalseFunction]
               return false;
           }else{
               if (!isSubmit)
    {[TrueFunction]}
           }
           break;\n
SCR;
}
?>