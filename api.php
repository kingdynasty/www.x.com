<?php 
define('CMS_PATH', './');
define('APP_NAME', 'API');
define('APP_PATH', './Apps/');
define('THINK_PATH','./ThinkPHP/');
define('RUNTIME_PATH',APP_PATH.'Runtime/');
//主机协议
define('SITE_PROTOCOL', isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://');
//当前访问的主机名
define('SITE_URL', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
//来源
define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

if(version_compare(PHP_VERSION,'5.2.0','<'))  die('require PHP > 5.2.0 !');

//   系统信息
if(version_compare(PHP_VERSION,'5.3.0','<')) {
    set_magic_quotes_runtime(0);
    define('MAGIC_QUOTES_GPC',get_magic_quotes_gpc()?True:False);
}else{
    define('MAGIC_QUOTES_GPC',True);
}
define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' ? 1 : 0 );
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
define('IS_CLI',PHP_SAPI=='cli'? 1   :   0);


if(!IS_CLI) {
    // 当前文件名
    if(!defined('_PHP_FILE_')) {
        if(IS_CGI) {
            //CGI/FASTCGI模式下
            $_temp  = explode('.php',$_SERVER['PHP_SELF']);
            define('_PHP_FILE_',  rtrim(str_replace($_SERVER['HTTP_HOST'],'',$_temp[0].'.php'),'/'));
        }else {
            define('_PHP_FILE_',    rtrim($_SERVER['SCRIPT_NAME'],'/'));
        }
    }
}

// 路径设置 可在入口文件中重新定义 所有路径常量都必须以/ 结尾
defined('CORE_PATH') or define('CORE_PATH',THINK_PATH.'Lib/'); // 系统核心类库目录
defined('EXTEND_PATH') or define('EXTEND_PATH',THINK_PATH.'Extend/'); // 系统扩展目录
defined('MODE_PATH') or define('MODE_PATH',EXTEND_PATH.'Mode/'); // 模式扩展目录
defined('ENGINE_PATH') or define('ENGINE_PATH',EXTEND_PATH.'Engine/'); // 引擎扩展目录
defined('VENDOR_PATH') or define('VENDOR_PATH',EXTEND_PATH.'Vendor/'); // 第三方类库目录
defined('LIBRARY_PATH') or define('LIBRARY_PATH',EXTEND_PATH.'Library/'); // 扩展类库目录
defined('COMMON_PATH') or define('COMMON_PATH',    APP_PATH.'Common/'); // 项目公共目录
defined('LIB_PATH') or define('LIB_PATH',    APP_PATH.'Lib/'); // 项目类库目录TODO
defined('CONF_PATH') or define('CONF_PATH',  APP_PATH.'Conf/'); // 项目配置目录
defined('LANG_PATH') or define('LANG_PATH', APP_PATH.'Lang/'); // 项目语言包目录
defined('TMPL_PATH') or define('TMPL_PATH',APP_PATH.'Tpl/'); // 项目模板目录
defined('HTML_PATH') or define('HTML_PATH',CMS_PATH.'Html/'); // 项目静态目录
defined('LOG_PATH') or define('LOG_PATH',  RUNTIME_PATH.'Logs/'); // 项目日志目录
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH.'Temp/'); // 项目缓存目录
defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH.'Data/'); // 项目数据目录
defined('CACHE_PATH') or define('CACHE_PATH',   RUNTIME_PATH.'Cache/'); // 项目模板缓存目录


// 为了方便导入第三方类库 设置Vendor目录到include_path
set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);

// 加载运行时所需要的文件 并负责自动目录生成
function load_runtime_file() {
    // 加载系统基础函数库
    require THINK_PATH.'Common/common.php';
    require THINK_PATH.'Common/functions.php';

    // 读取核心编译文件列表
    $list = array(
        CORE_PATH.'Core/Think.class.php',
        CORE_PATH.'Core/ThinkException.class.php',  // 异常处理类
    );
    // 加载模式文件列表
    foreach ($list as $key=>$file){
        if(is_file($file))  require_cache($file);
    }
    // 加载系统类库别名定义
    alias_import(include THINK_PATH.'Conf/alias.php');
    // 加载底层惯例配置文件
    C(include THINK_PATH.'Conf/convention.php');
    // 加载项目配置文件
    if(is_file(CONF_PATH.'config.php'))  	
        C(include CONF_PATH.'config.php');
          
}

// 加载运行时所需文件
load_runtime_file();

$op = isset($_GET['op']) && trim($_GET['op']) ? trim($_GET['op']) : exit('Operation can not be empty');
if (isset($_GET['callback']) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $_GET['callback']))  unset($_GET['callback']);

if (!preg_match('/([^a-z_]+)/i',$op) && file_exists(CMS_PATH.'Api/'.$op.'.php')) {
	include CMS_PATH.'Api/'.$op.'.php';
} else {
	exit('API handler does not exist');
}