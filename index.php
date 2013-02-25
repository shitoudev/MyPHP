<?php
/**
 * 入口文件
 *
 * MYPHP的目标高性质
 * 追求极致性能-优化到每个字节、每K内存、每毫秒执行时间
 * 严格要求质量-高标准、高要求、高质量
 * 配置文件详见Configs目录
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */

// 是否显示错误信息:建议在开发时打开,上线时关闭
define('MYPHP_DEBUG', TRUE);

// 项目版本库版本:针对SVN或GIT中版本号，需要时用于更新前端静态文件用户缓存
define('MYPHP_APP_VER', 1);

// 项目名称，注意与目录名保持一致
define('MYPHP_APP_NAME', 'myphp');

// 网站域名设置
define('MYPHP_DOMAIN',     'qiumi.cc');
define('MYPHP_APP_DOMAIN', 'myphp.qiumi.cc');
define('IMYPHP_WEB_DOMAIN', 'www.qiumi.cc');

// 当前项目应用程序的根目录
define('MYPHP_APP_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);

// 框架根路径
define('MYPHP_CORE_ROOT', MYPHP_APP_ROOT.'/Libs/');

// 常用路径
define('IMYPHP_WEB_ROOT', 'http://www.qiumi.cc/');
define('MYPHP_ROOT', 'http://www.qiumi.cc/');
define('MYPHP_IMAGE_ROOT', 'Views/images/');
define('MYPHP_CSS_ROOT', 'Views/css/');
define('MYPHP_JS_ROOT', 'Views/js/');

// ini_set设置,详细请看 php.ini 
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MYPHP_CORE_ROOT);               // 框架根目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MYPHP_CORE_ROOT.'Tools/');      // 框架工具目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MYPHP_APP_ROOT.'libs/');        // 项目类库目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MYPHP_APP_ROOT.'Controllers/'); // 项目控制器根目录
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.MYPHP_APP_ROOT.'Commons/');     // 项目公共方法目录
ini_set('session.cookie_domain', MYPHP_DOMAIN); // 避免二级域名无法得到SESSION的问题

// 设置时区,上海--以免出现时间不正确的情况
date_default_timezone_set("Asia/Shanghai");

// DEBUG
if(MYPHP_DEBUG)
{
	define('MYPHP_BEGIN_TIME',      microtime(TRUE));
	define('MYPHP_MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
	if(MYPHP_MEMORY_LIMIT_ON) define('MYPHP_START_MEMS', memory_get_usage());
	ini_set('display_errors', 1);
	error_reporting(E_ALL & ~E_NOTICE);
}
else
{
	error_reporting(0);
}

// 加载项目配置文件
require_once(MYPHP_APP_ROOT.'Configs/Config.php');

// 加载主体
require_once(MYPHP_CORE_ROOT.'Myphp.php');

// 运行
try
{
	$app = new Myphp();
	$app->run();
}
catch(Exception $e)
{
	dump($e->getMessage());
}

/* End */