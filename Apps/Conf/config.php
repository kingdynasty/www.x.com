<?php
defined('APP_NAME') or exit();
return  array(
    /* 项目设定 */
    'APP_STATUS'            => 'debug',  // 应用调试模式状态 调试模式开启后有效 默认为debug 可扩展 并自动加载对应的配置文件
    'APP_FILE_CASE'         => false,   // 是否检查文件的大小写 对Windows平台有效
    'APP_AUTOLOAD_PATH'     => '',// 自动加载机制的自动搜索路径,注意搜索顺序
    'APP_TAGS_ON'           => true, // 系统标签扩展开关
    'APP_SUB_DOMAIN_DEPLOY' => false,   // 是否开启子域名部署
    'APP_SUB_DOMAIN_RULES'  => array(), // 子域名部署规则
    'APP_SUB_DOMAIN_DENY'   => array(), //  子域名禁用列表
    'APP_MODULE_LIST'        => 'Admin,Content',      // 项目模块设定,多个组之间用逗号分隔,例如'Home,Admin'
    'ACTION_SUFFIX'         =>  '', // 操作方法后缀

    /* Cookie设置 */
    'COOKIE_EXPIRE'         => 0,    // Coodie有效期
    'COOKIE_DOMAIN'         => '',      // Cookie有效域名
    'COOKIE_PATH'           => '/',     // Cookie路径
    'COOKIE_PREFIX'         => 'tp_',      // Cookie前缀 避免冲突

    /* 默认设定 */
    'DEFAULT_M_LAYER'       =>  'Model', // 默认的模型层名称
    'DEFAULT_C_LAYER'       =>  'Action', // 默认的控制器层名称
    'DEFAULT_APP'           => '@',     // 默认项目名称，@表示当前项目
    'DEFAULT_LANG'          => 'zh-cn', // 默认语言
    'DEFAULT_THEME'         => 'Default',	// 默认模板主题名称
    'DEFAULT_MODULE'         => 'Content',  // 默认模块
    'DEFAULT_CONTROLER'        => 'Index', // 默认控制器名称
    'DEFAULT_ACTION'        => 'init', // 默认操作名称
    'DEFAULT_CHARSET'       => 'utf-8', // 默认输出编码
    'DEFAULT_TIMEZONE'      => 'PRC',	// 默认时区
    'DEFAULT_AJAX_RETURN'   => 'JSON',  // 默认AJAX 数据返回格式,可选JSON XML ...
    'DEFAULT_FILTER'        => 'htmlspecialchars', // 默认参数过滤方法 用于 $this->_get('变量名');$this->_post('变量名')...

    /* 数据库设置 */
    'DB_TYPE'               => 'mysql',     // 数据库类型
    'DB_HOST'               => 'localhost', // 服务器地址
    'DB_NAME'               => 'www_c_com',          // 数据库名
    'DB_USER'               => 'root',      // 用户名
    'DB_PWD'                => '123456',          // 密码
    'DB_PORT'               => '',        // 端口
    'DB_PREFIX'             => 'v9_',    // 数据库表前缀
    'DB_FIELDTYPE_CHECK'    => false,       // 是否进行字段类型检查
    'DB_FIELDS_CACHE'       => true,        // 启用字段缓存
    'DB_CHARSET'            => 'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE'        => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE'        => false,       // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM'         => 1, // 读写分离后 主服务器数量
    'DB_SLAVE_NO'           => '', // 指定从服务器序号
    'DB_SQL_BUILD_CACHE'    => false, // 数据库查询的SQL创建缓存
    'DB_SQL_BUILD_QUEUE'    => 'file',   // SQL缓存队列的缓存方式 支持 file xcache和apc
    'DB_SQL_BUILD_LENGTH'   => 20, // SQL缓存的队列长度
    'DB_SQL_LOG'            => false, // SQL执行日志记录

    /* 数据缓存设置 */
    'DATA_CACHE_TIME'       => 0,      // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS'   => false,   // 数据缓存是否压缩缓存
	'data_cache_datatype'   => 'array',		/*缓存格式：array数组，serialize序列化，null字符串*/
    'DATA_CACHE_CHECK'      => false,   // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX'     => '',     // 缓存前缀
    'DATA_CACHE_TYPE'       => 'File',  // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'DATA_CACHE_PATH'       => DATA_PATH,// 缓存路径设置 (仅对File方式缓存有效)
    'DATA_CACHE_SUBDIR'     => false,    // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
    //'DATA_PATH_LEVEL'       => 1,        // 子目录缓存级别

    /* 错误设置 */
    'ERROR_MESSAGE'         => '页面错误！请稍后再试～',//错误显示信息,非调试模式有效
    'ERROR_PAGE'            => '',	// 错误定向页面
    'SHOW_ERROR_MSG'        => false,    // 显示错误信息

    /* 日志设置 */
    'LOG_RECORD'            => false,   // 默认不记录日志
    'LOG_TYPE'              => 3, // 日志记录类型 0 系统 1 邮件 3 文件 4 SAPI 默认为文件方式
    'LOG_DEST'              => '', // 日志记录目标
    'LOG_EXTRA'             => '', // 日志记录额外信息
    'LOG_LEVEL'             => 'EMERG,ALERT,CRIT,ERR',// 允许记录的日志级别
    'LOG_FILE_SIZE'         => 2097152,	// 日志文件大小限制
    'LOG_EXCEPTION_RECORD'  => false,    // 是否记录异常信息日志
    'admin_log'=>0,//是否记录后台操作日志

    /* SESSION设置 */
    'SESSION_AUTO_START'    => true,    // 是否自动开启Session
    'SESSION_OPTIONS'       => array('type'=>'Db','expire'=>1800,'path'=>CACHE_PATH.'Sessions/'), // session 配置数组 支持type name id path expire domian 等参数
    'SESSION_TYPE'          => 'Db', // session hander类型 默认无需设置 除非扩展了session hander驱动
    'SESSION_PREFIX'        => '', // session 前缀
    //'VAR_SESSION_ID'      => 'session_id',     //sessionID的提交变量
	/*语言相关设置*/
	'LANG_SWITCH_ON' => true,
	'DEFAULT_LANG' => 'zh-cn', // 默认语言
	'LANG_AUTO_DETECT' => true, // 自动侦测语言
	'LANG_LIST'=>'en,zh-cn',//必须写可允许的语言列表
	'LANG_FILE_DEPR'=>'.',
    /* 模板引擎设置 */
    'TMPL_CONTENT_TYPE'     => 'text/html', // 默认模板输出类型
    'TMPL_ACTION_ERROR'     => THINK_PATH.'Tpl/dispatch_jump.tpl', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'   => THINK_PATH.'Tpl/dispatch_jump.tpl', // 默认成功跳转对应的模板文件
    'TMPL_EXCEPTION_FILE'   => THINK_PATH.'Tpl/think_exception.tpl',// 异常页面的模板文件
    'TMPL_DETECT_THEME'     => false,       // 自动侦测模板主题
    'TMPL_TEMPLATE_SUFFIX'  => '.html',     // 默认模板文件后缀
    'TMPL_FILE_DEPR'        =>  '/', //模板文件CONTROLER_NAME与ACTION_NAME之间的分割符，只对项目模块部署有效
	'tmpl_parse_string' => array
		(
				'__UPLOAD__' => '/Uploads/',
				'__IMG__' =>'/Statics/images/',
				'__JS__' => '/Statics/js/',
				'__CSS__' => '/Statics/css/'
		),

    /* URL设置 */
    'URL_CASE_INSENSITIVE'  => false,   // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'             => 1,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式，提供最好的用户体验和SEO支持
    'URL_PATHINFO_DEPR'     => '/',	// PATHINFO模式下，各参数之间的分割符号
    'URL_PATHINFO_FETCH'    =>   'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', // 用于兼容判断PATH_INFO 参数的SERVER替代变量列表
    'URL_HTML_SUFFIX'       => '',  // URL伪静态后缀设置
    'URL_PARAMS_BIND'       =>  true, // URL变量绑定到Action方法参数

    /* 系统变量名称设置 */
    'VAR_MODULE'             => 'm',     // 默认模块获取变量
    'VAR_CONTROLER'            => 'c',		// 默认控制器获取变量
    'VAR_ACTION'            => 'a',		// 默认操作获取变量
    'VAR_AJAX_SUBMIT'       => 'ajax',  // 默认的AJAX提交变量
    'VAR_PATHINFO'          => 'p',	// PATHINFO 兼容模式获取变量例如 ?s=/controler/action/id/1 后面的参数取决于URL_PATHINFO_DEPR
    'VAR_URL_PARAMS'        => '_URL_', // PATHINFO URL参数变量
    'VAR_TEMPLATE'          => 't',		// 默认模板切换变量
    'VAR_FILTERS'           =>  '',     // 全局系统变量的默认过滤方法 多个用逗号分割

    'OUTPUT_ENCODE'         =>  true, // 页面压缩输出
    'HTTP_CACHE_CONTROL'    =>  'private', // 网页缓存控制

		'pc_version' => 'V9.2.4',	//phpcms 版本号
		'pc_release' => '20121112',	//phpcms 更新日期
		//网站路径
		'site_path' => '/',

		
		//附件相关配置
		'upload_path' => CMS_PATH.'UploadFile/',
		'upload_url' => 'http://www.x.com/UploadFile/', //附件路径
		'attachment_stat' => '1',//是否记录附件使用状态 0 统计 1 统计， 注意: 本功能会加重服务器负担
		
		'js_path' => 'http://www.x.com/Statics/js', //CDN JS
		'css_path' => 'http://www.x.com/Statics/css/', //CDN CSS
		'img_path' => 'http://www.x.com/Statics/images/', //CDN img
		'app_url' => 'http://www.x.com/',//动态域名配置地址
		
		'charset' => 'utf-8', //网站字符集
		'timezone' => 'Etc/GMT-8', //网站时区（只对php 5.1以上版本有效），Etc/GMT-8 实际表示的是 GMT+8
		'debug' => 1, //是否显示调试信息
		'admin_log' => 0, //是否记录后台操作日志
		'errorlog' => 1, //1、保存错误日志到 cache/error_log.php | 0、在页面直接显示
		'gzip' => 1, //是否Gzip压缩后输出
		'auth_key' => 'mfIan89XPYVk4F1yGL8S', //密钥
		'lang' => 'zh-cn',  //网站语言包
		'lock_ex' => '1',  //写入缓存时是否建立文件互斥锁定（如果使用nfs建议关闭）
		
		'admin_founders' => '1', //网站创始人ID，多个ID逗号分隔
		'execution_sql' => 0, //EXECUTION_SQL
		
		'phpsso' => '1',	//是否使用phpsso
		'phpsso_appid' => '1',	//应用id
		'phpsso_api_url' => 'http://www.c.com/phpsso_server',	//接口地址
		'phpsso_auth_key' => 'doykD1ntfcO9FthMMQd0gAo9HWlTvvFv', //加密密钥
		'phpsso_version' => '1', //phpsso版本

		'safe_card'=>'1',//是否启用口令卡
		
		'connect_enable' => '1',	//是否开启外部通行证/////////////////zS20255		'sina_akey' => '',	//sina AKEY
		'sina_skey' => '',	//sina SKEY
		
		'snda_akey' => '',	//盛大通行证 akey
		'snda_skey' => '',	//盛大通行证 skey
		
		'qq_akey' => '',	//qq skey
		'qq_skey' => '',	//qq skey
		
		'qq_appkey' => '',	//QQ号码登录 appkey
		'qq_appid' => '',	//QQ号码登录 appid
		'qq_callback' => '',	//QQ号码登录 callback
		
		'plugin_debug' => '0',
		'admin_url' => '',	//允许访问后台的域名	
);