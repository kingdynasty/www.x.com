<?php
defined('APP_NAME') or exit('No permission resources.');

class BaseAction extends Action {
	public function __construct() {
	    parent::__construct();
		//主机协议
		define('SITE_PROTOCOL', isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://');
		//当前访问的主机名
		define('SITE_URL', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
		//来源
		define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		define ('CHARSET' ,C('default_charset'));
		define ('SITE_PATH', C('site_path'));
		define ('SYS_STYLE', C('DEFAULT_LANG'));
		define ('APP_URL', C('app_url'));
		define ('IMG_PATH', C('tmpl_parse_string.__IMG__'));
		define ('JS_PATH', C('tmpl_parse_string.__JS__'));
		define ('CSS_PATH', C('tmpl_parse_string.__CSS__'));
    }
    protected function mytrace()
    {
    	$this->display('page_trace');
    }    
}