<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
class SettingAction extends BaseAction {
	private $db;
	function __construct() {
		parent::__construct();
		$this->db = M('Module');
		
	}
	
	/**
	 * 配置信息
	 */
	public function init() {
		$show_validator = true;
		//$setconfig = PcBase::loadConfig('system');	
		//extract($setconfig);
		if(!function_exists('ob_gzhandler')) $gzip = 0;
		$info = $this->db->where(array('module'=>'admin'))->find();
		extract(string2array($info['setting']));
		$show_header = true;
		$show_validator = 1;
		
		include Admin::adminTpl('setting');
	}
	
	/**
	 * 保存配置信息
	 */
	public function save() {
		
		$setting = array();
		$setting['admin_email'] = is_email($_POST['setting']['admin_email']) ? trim($_POST['setting']['admin_email']) : showmessage(L('email_illegal'),HTTP_REFERER);
		$setting['maxloginfailedtimes'] = intval($_POST['setting']['maxloginfailedtimes']);
		$setting['minrefreshtime'] = intval($_POST['setting']['minrefreshtime']);
		$setting['mail_type'] = intval($_POST['setting']['mail_type']);		
		$setting['mail_server'] = trim($_POST['setting']['mail_server']);	
		$setting['mail_port'] = intval($_POST['setting']['mail_port']);	
		$setting['category_ajax'] = intval(abs($_POST['setting']['category_ajax']));	
		$setting['mail_user'] = trim($_POST['setting']['mail_user']);
		$setting['mail_auth'] = intval($_POST['setting']['mail_auth']);		
		$setting['mail_from'] = trim($_POST['setting']['mail_from']);		
		$setting['mail_password'] = trim($_POST['setting']['mail_password']);
		$setting['errorlog_size'] = trim($_POST['setting']['errorlog_size']);
		$setting = array2string($setting);
		$this->db->data(array('setting'=>$setting))->where(array('module'=>'admin'))->save(); //存入admin模块setting字段
		
		//如果开始盛大通行证接入，判断服务器是否支持curl
		$snda_error = '';
		if($_POST['setconfig']['snda_akey'] || $_POST['setconfig']['snda_skey']) {
			if(function_exists('curl_init') == FALSE) {
				$snda_error = L('snda_need_curl_init');
				$_POST['setconfig']['snda_enable'] = 0;
			}
		}

		set_config($_POST['setconfig']);	 //保存进config文件
		$this->cache();
		showmessage(L('setting_succ').$snda_error, HTTP_REFERER);
	}
	
	/*
	 * 测试邮件配置
	 */
	public function publicTestMail() {
		load('mail');
		$subject = 'phpcms test mail';
		$message = 'this is a test mail from phpcms team';
		$mail= Array (
			'mailsend' => 2,
			'maildelimiter' => 1,
			'mailusername' => 1,
			'server' => $_POST['mail_server'],
			'port' => intval($_POST['mail_port']),
			'mail_type' => intval($_POST['mail_type']),
			'auth' => intval($_POST['mail_auth']),
			'from' => $_POST['mail_from'],
			'auth_username' => $_POST['mail_user'],
			'auth_password' => $_POST['mail_password']
		);	
		
		if(sendmail($_GET['mail_to'],$subject,$message,$_POST['mail_from'],$mail)) {
			echo L('test_email_succ').$_GET['mail_to'];
		} else {
			echo L('test_email_faild');
		}	
	}
	
	/**
	 * 设置缓存
	 * Enter description here ...
	 */
	private function cache() {
		$result = $this->db->where(array('module'=>'admin'))->find();
		$setting = string2array($result['setting']);
		cache('common', $setting,'Commons');
	}
}
?>