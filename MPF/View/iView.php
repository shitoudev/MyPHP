<?php
/**
 * View的接口
 */
 
define('MY_VIEW_INTERFACE',1);
interface iView
{
	public function display(); //显示模板内容的抽象方法
	public function fetch();//获得模板内容的抽象方法
}
?>