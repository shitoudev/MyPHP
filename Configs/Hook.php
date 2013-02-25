<?php
/**
 * Hook 配置
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */

/**
 * 所有控制器开始运行前的钩子
 *
 * @author daniel
 * @param  object $controller :控制器
 * @param  object $action     :方法
 * @return array
 */
define('HOOK_BEGIN', 'hookBegin'); 
function hookBegin()
{
	// return array('UserCommon', 'isLogin');
}

/**
 * 控制器执行时的钩子
 *
 * @author daniel
 * @param  object $actionPath1 :动作路径,如 "main_index" *代表所有方法
 * @param  object $begin       :表示开始执行这个动作之前所要做的处理,如果没有可以不定义
 * @param  object $end         :表示执行完这个动作之后所要做的处理,如果没有可以不定义
 * @param  object $funName     :函数名,该函数没有参数和没有返回值.
 * @param  object $begin       :表示开始执行这个动作之前所要做的处理,如果没有可以不定义
 * @return array
 */
define('HOOK_EXECUTE_ACTION', '');
function executeAction()
{
	return array(
		'Main_test'=>array('begin'=>array('Main', 'hook'), 'end'=>''),
	);
}

/* End */