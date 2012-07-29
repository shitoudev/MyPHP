<?php
/**
 * 这里常量定义的钩子函数框架会在运行时根据定义自行调用.常量的值都是对应的函数名,
 * 例如:define('HOOK_ACL_ACCOUNTS','aclAutoAccounts'); 
*/
 
 
/**
 * 返回当前系统中用户唯一标识和当前产品标识,这两个标识应该与成员权限管理中的用户标识和产品标识一一对应.
 * 如果框架定义为自动进行权限检测时(即 define('MY_ACL_AUTO_CHECK',true)),则一定要定义指定这个函数.
 * 框架在调用该函数时会自动按顺序传以下参数给函数
 * @param object $request  :Request对象
 * @param object $response :Response对象
 * @author array
 * 
 * [钩子函数示例如下]:
    function aclAutoAccounts($request,$response)
    {
        return array('userId'=>$_SESSION['currentUser'],
                     'productType'=>$_SESSION['currentPro']);
    }
 */
define('HOOK_ACL_ACCOUNTS',''); 


/**
 * 当框架定义为自动进行权限检测时(即 define('MY_ACL_AUTO_CHECK',true) 时),
 * 如果当前用户没有访问权限时将调用这个函数,你可以在这里重新定义任何方式的提示信息或做些日志记录等
 * 框架在调用该函数时会自动按顺序传以下参数给函数
 * @param string $userId     :当前访问的用户标识
 * @param string $actionPath :当前用户没有权限访问的资源动作
 * @param object $request    :Request对象
 * @param object $response   :Response对象
 * @return boolean  true:框架执行默认动作输出权限不足的信息后退出程序 
 *                 false:框架不执行默认动作,但也会退出.
 * 
 * [钩子函数示例如下]:
    function aclAutoCheckNotPriv($userId,$actionPath,$request,$response)
    {
        return true;
    } 
 */
define('HOOK_ACL_NOT_PRIV',''); 



/**
 * 控制器执行时的钩子.你可以在指定的动作前后做一些逻辑,比如说:在用户注册或有最新动态时发一封邮件给用户等.
 * 示例中说明如下:
 * 'actionPath1'          :动作路径,如 "main_addNews" 表示执行这个动作时做些特殊的处理
 * 'begin'                :表示开始执行这个动作之前所要做的处理,如果没有可以不定义
 * 'end'                  :表示执行完这个动作之后所要做的处理,如果没有可以不定义
 * 'funName'              :函数名,该函数没有参数和没有返回值.
 * array($obj,'funName')  :表示调试类的成员方法; $obj类的实例对象, 'funName'对象的成员方法名称.
 * @return array
 * 
 * [钩子函数示例如下]:
    function executeAction()
    {
        return array('actionPath1'=>array('begin'=>array($obj,'funName'),
                                          'end'=>'funName'),
                     'actionPath2'=>array('begin'=>'funName',
                                          'end'=>'funName'),
        );
    }
 */
define('HOOK_EXECUTE_ACTION',''); 



/**
 * SMARTY 预设变量接口,在这里你可以为视图中设置一些公共的全局变量或对象,
 * 就像框架自动在视图中预设了 $request 和 $response 两个全局的对象一样.
 * @return array
 * 
 * [钩子函数示例如下]:
    function smartyAssign()
    {
        return array('public'=>'/Public/',
                     'obj'=>$obj);
    }
 */
define('HOOK_SMARTY_ASSIGN',''); 



/**
 * 框架运行前域名解析钩子,你可以在这里对域名进行分析做跳转其它操作
 * 框架在调用该函数时会自动按顺序传以下参数给函数
 * @param string $domain   :当前请求的域名,如 www.163.com 或 ss.blog.163.com
 * @param object $request  :Request对象
 * @param object $response :Response对象
 * @return boolean   true :框架继续运行
 *                  false :框架终止运行
 * 
 * [钩子函数示例如下]:
    function parseDomain($domain,$request,$response)
    {
        return true;
    }
 */
define('HOOK_PARSE_DOMAIN',''); 


/**
 * 所有控制器开始运行前的钩子,在这里你可以做一些像自动COOKIE登录等其它操作
 * 框架在调用该函数时会自动按顺序传以下参数给函数
 * @param object $request  :Request对象
 * @param object $response :Response对象
 * @return void
 * 
 * [钩子函数示例如下]:
    function autoLogin($request,$response)
    {
    }
 */
define('HOOK_MYPHP_BEGIN',''); 
?>