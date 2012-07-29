<?php
/**
 * 正则表达式应用工具
 *
*/
class Regexp{
    
    static private $regExp = array(
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
    
    /**
     * 已定义正则或自定义正则检查字符串
     *
     * @param string $regExp :正则类型或正则表达式,即 $regExp 中定义的.如 "Email" 或自定义的正则表达式
     * @param string $string :要检查的字符串
     * @return boolean
     *
     * 例子: Regexp::check('Email','aaaa@162.com');
     *       Regexp::check('/\d{15}|\d{18}/','aaaa@162.com');
     */
    static public function check($regExp,$string)
    {
        $regExpValue = array_key_exists($regExpType,self::$regExp) ? self::$regExp[$regExpType] : $regExp;
        return preg_match(self::$regExp[$regExpType],$string);
    }
}


?>