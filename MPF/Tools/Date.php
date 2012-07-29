<?php
/**
 * 日期时间操作相关
 */
/**
[示例]
-------- 控制器代码如下 ------------
echo Date::getDate(); //返回当前的日期如 "2008-10-12"
echo Date::getDate(1229173896); //1229173896 是指定的时间戳
echo Date::getTime(); //返回当前的时间如 "2008-10-12 10:36:48"
echo Date::getTime(1229173896); //1229173896 是指定的时间戳
echo Date::compareTiem('2006-10-12','2006-10-11'); //比较两个时间
echo Date::dateAddDay("2005-10-20",6);// 2005-9-25"+6 = "2005-10-01"
echo Date::dateDecDay("2005-10-20",10); //"2005-10-20"-10 = "2005-10-10"
echo Date::dateDiff('2005-10-20','2005-10-10');//"2005-10-20"-"2005-10-10"=10
echo Date::timeDiff('2005-10-20 10:00:00','2005-10-20  08:00:00'); // 2小时
-------------------------------------------
[注意]
1：这个工具只是一个函数库,调用方法时不需要关心调用的先后顺序.
*/
class Date{
    
    /**
     * 得到当前日期
     * @param string $fmt :日期格式
     * @param int $time :时间，默认为当前时间
     * @return string
     */
    static public function getDate($time=null,$fmt='Y-m-d')
    {
        $times = $time?$time:time();
        return date($fmt,$times);
    }
    
    /**
     * 得到当前日期时间
     * @param string $fmt :日期格式
     * @param int $time :时间，默认为当前时间
     * @return string
     */
    static public function getTime($time=null,$fmt='Y-m-d H:i:s')
    {
        return self::getDate($time,$fmt);
    }
    
    /**
     * 计算日期天数差
     * @param string $Date1 :如 "2005-10-20"
     * @param string $Date2 :如 "2005-10-10"
     * @return int 
     * 例子:"2005-10-20"-"2005-10-10"=10
     */
    static public function dateDiff($Date1,$Date2)
    {
    	$DateList1=explode("-",$Date1);
    	$DateList2=explode("-",$Date2);
    	$d1=mktime(0,0,0,$DateList1[1],$DateList1[2],$DateList1[0]);
    	$d2=mktime(0,0,0,$DateList2[1],$DateList2[2],$DateList2[0]);
    	$Days=round(($d1-$d2)/3600/24);
    	return $Days;
    }

    /**
     * 计算日期加天数后的日期
     * @param string $date :如 "2005-10-20"
     * @param int $day  :如 6
     * @return string 
     * 例子:2005-9-25"+6 = "2005-10-01"
     */
    static public function dateAddDay($date,$day)
    {
    	$daystr = "+$day day";
    	$dateday = date("Y-m-d",strtotime($daystr,strtotime($date)));
    	return $dateday;
    }    
    
    /**
     * 计算日期加天数后的日期
     * @param string $date :如 "2005-10-20"
     * @param int $day  :如 10
     * @return string 
     * 例子:"2005-10-20"-10 = "2005-10-10'
     */
    static public function dateDecDay($date,$day)
    {
    	$daystr="-$day day";
    	$dateday=date("Y-m-d",strtotime($daystr,strtotime($date1)));
    	return $dateday;
    }       
    
    /**
     * 比较两个时间
     * @param string $timeA :格式如 "2006-10-12" 或 "2006-10-12 12:30" 或 "2006-10-12 12:30:50"
     * @param string $timeB :同上
     * @return int   0:$timeA = $timeB
     *              -1:$timeA < $timeB 
     *               1:$timeA > $timeB 
     */
    static public function compareTiem($timeA,$timeB)
    {
    	$a=strtotime($timeA);
    	$b=strtotime($timeB);
    	if($a > $b)        return 1;
    	else if($a == $b)  return 0;
    	else               return -1;        
    }       
    
    /**
     * 计算时间a减去时间b的差值
     * @param string $timeA :格式如 "2006-10-12" 或 "2006-10-12 12:30" 或 "2006-10-12 12:30:50"
     * @param string $timeB :同上
     * @return flat   实数的小时,如"2.3333333333333"小时
     */
    static public function timeDiff($timeA,$timeB)
    {
    	$a=strtotime($timeA);
    	$b=strtotime($timeB);
    	$c=$a-$b;
    	$c=$c / 3600;
    	return $c;
    }       

}
?>