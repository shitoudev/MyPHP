<?php
/**
 * 表单验证组件
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

require_once('Valid/iValid.php');
class Valid
{
    private $funTag = '';
    private $_rules = array();
	private $_error = array();
	private $jsCode = '';
	private $falseCallBack = '';
	private $arrObj = '';
	private $request = null;
	
	public function __construct($fromTag='')
	{
	    $this->funTag = $fromTag;
	    $this->request = Request::getInstance();
	}
	
	/**
	 * 添加表单验证规则
	 * @param string $field    :表单域的名称,即 name 属性的值.
	 * @param string $rule     :验证规则
	 * @param string $param    :规则对应的参数
	 * @param string $errorMsg :错误消息
	 * @param $falseCallBack   :验证错误时的回调函数(JS函数)
	 * @param $trueCallBack    :验证正确时的回调函数(JS函数)
	 */
    public function addRule($field,$rule,$param,$errorMsg,$falseCallBack=MY_VALID_DEFAULT_FUN,$trueCallBack=false)
    {
        $this->falseCallBack = $falseCallBack;
        $this -> _rules[$rule][] = array('field'=>$field,
                                         'param'=>$param,
                                         'msg'=>$errorMsg,
                                         'falseCallBack'=>$falseCallBack,
                                         'trueCallBack'=>$trueCallBack);
    }
    
    /**
     * 得到规则所对应的 JS 代码.
     *
     * @return string
     */
    public function getJS()
    {
		$this -> checkRule(true);
		$arrObj = $this->arrObj;
		$funTag = $this->funTag ? '_'.$this->funTag : '';
		$js = '';
		if ($this->falseCallBack == '_default_')
		{
		    $js .= <<<SCR
 function _MY_FalseFun{$funTag}(msg,eleObj)
 {
        var spanObj = document.getElementById("__ErrorMsg"+eleObj.name);
        if (spanObj == null)
        {
            var span = document.createElement("SPAN");
        	span.id = "__ErrorMsg"+eleObj.name;
        	span.style.color = "red";
        	eleObj.parentNode.appendChild(span);
        	span.innerHTML = ' '+msg; 
        }
 }\n	
 function _MY_TrueFun{$funTag}(eleObj)
 {
        var spanObj = document.getElementById("__ErrorMsg"+eleObj.name); 
        if (spanObj != null)
        {
           eleObj.parentNode.removeChild(spanObj);   
           spanObj = null;
        }
 }\n	
SCR;
		}
		
		$js .= <<<SCR
 function __Check{$funTag}(eleObj,isSubmit)
 {
       var eleValue = eleObj.value;
       var eleName = eleObj.name;
       switch(eleName)
       {\n
SCR;
       $js .= $this -> jsCode;
       $js .= <<<SCR
	   }
	   return true;
 }\n
SCR;
	    $js.= <<<SCR
 function MY_Check{$funTag}(obj)
 {
    if(obj.tagName.toLowerCase()=='form')
    {
        var arrObj = [$arrObj];
        var len = arrObj.length;
        for (var i=0; i<len; i++)
        {
            if (!__Check{$funTag}(arrObj[i],true)) return false;
        }
        return true;
    }else{
        return  __Check{$funTag}(obj,false);
    }
 }
SCR;
		return $js;
    }
    
    /**
     * 服务器端进行规则验证
     *
     * @param boolean $client : false:生成客户端JS代码 true:服务器端规则验证.
     * @return unknown
     */
    public function checkRule($client = false)
    {
        foreach($this -> _rules as $rule=>$rows)
        {
            //--判断是否为系统所定义的规则
            $isCoreRule = iValid::isCoreRule($rule);
			
			foreach ($rows as $row)//对同一规则的元素
			{ 
				if($client == false) //服务器端验证
				{
				    if ($isCoreRule) //系统定义的规则才进行服务器端的验证
				    {  
				        if (iValid::isRegular($rule)) //规则是正则表达式
				        { 
				            if (!iValid::regularServer($rule,$this->request,$row)) $this -> _error[$row['field']] = $row['msg'];
				        }else{
				            $fun = "MY_Valid_{$rule}_Server";
				            if (!$fun($this->request,$row)) $this -> _error[$row['field']] = $row['msg']; 
				        }
				    }
				}else{ //得到客户端JS代码
				    //--得到所有加了规则的表单对象
				    if ($this->arrObj == '') $this->arrObj = "obj.{$row['field']}";
				    else $this->arrObj .= ','."obj.{$row['field']}";
                    
                    if ($isCoreRule)
                    {
                        if (iValid::isRegular($rule)) //规则是正则表达式
                        {
                            $js = iValid::regularClient($rule,$row); //客户端JS代码
                        }else{
                            $fun = "MY_Valid_{$rule}_Client";
                            $js = $fun($row);
                        }
                    }
                    else{
                        $js = MY_Valid_UserRuleJS($rule,$row);//--是用户自定义的规则函数
                    }
                    
                    //--替换函数
                    $search = array("[FalseFunction]","[TrueFunction]");
		            $replace = array($this->falseFunction($row),$this->trueFunction($row));
		            $this -> jsCode .= str_replace($search,$replace,$js);                    
                }
            }
            
        }
        return !(count($this -> _error) > 0);
   }    
   
   /**
    * 返回所有错误信息
    * @return array
    */
   public function getError()
   {
   	   return $this -> _error;
   }
   
    //------得到错误处理回调函数
    public function falseFunction($data)
    {
        $funTag = $this->funTag ? '_'.$this->funTag : '';
        $msg = $data['msg'];
        if ($data['falseCallBack']=='_default_')
             return '_MY_FalseFun'.$funTag."(\"$msg\",eleObj);";
        else return $data['falseCallBack']."(\"$msg\",eleObj);";
    }
    
    //------得到正确处理回调函数
    public function trueFunction($data)
    {
        $trueFun = '';
        if ($this->falseCallBack == '_default_') $trueFun .= '_MY_TrueFun'.$this->funTag.'(eleObj);';
        
        if ($data['trueCallBack']) $trueFun .= $data['trueCallBack']."(eleObj);";
        else $trueFun .= ';';
        
        return $trueFun;
    }   
   
}
?>