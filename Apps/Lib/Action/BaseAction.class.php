<?php
defined('APP_NAME') or exit('No permission resources.');
// 本类由系统自动生成，仅供测试用途
class BaseAction extends Action {
	public function __construct() {
		parent::__construct();
		define ('CHARSET' ,C('default_charset'));
		define ('SITE_PATH', C('site_path'));
		define ('SYS_STYLE', cookie('language'));
		define ('APP_URL', C ( 'app_url' ) );
		define ('IMG_PATH', C('tmpl_parse_string.__IMG__'));
		define ('JS_PATH', C('tmpl_parse_string.__JS__'));
		define ('CSS_PATH', C('tmpl_parse_string.__CSS__'));		
    }
    protected function mytrace()
    {
    	$this->display('page_trace');
    }    
}