<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);

class ModuleAction extends BaseAction {
	private $db;
	
	public function __construct() {
		$this->db = M('Module');
		parent::__construct();
	}
	
	public function init() {
		$dirs = $module = $dirs_arr = $directory = array();
		$dirs = glob(APP_PATH.'/Modules/*');
		foreach ($dirs as $d) {
			if (is_dir($d)) {
				$d = basename($d);
				$dirs_arr[] = $d;
			}
		}
		define('INSTALL', true);
		$modules = $this->db->key('module')->select();
		$total = count($dirs_arr);
		$dirs_arr = array_chunk($dirs_arr, 20, true);
		$page = max(intval($_GET['page']), 1);
		$pages = pages($total, $page, 20);
		$directory = $dirs_arr[intval($page-1)];
		include Admin::adminTpl('module_list');
	}
	
	/**
	 * 模块安装
	 */
	public function install() {
		$this->module = $_POST['module'] ? $_POST['module'] : $_GET['module'];
		$module_api = import('@.Admin.Util.ModuleApi');
		if (!$module_api->check($this->module)) showmessage($module_api->errorMsg, 'blank');
		if ($_POST['dosubmit']) {
			if ($module_api->install()) showmessage(L('success_module_install').L('update_cache'), '?m=Admin&c=Module&a=cache&pc_hash='.$_SESSION['pc_hash']);
			else showmesage($module_api->errorMsg, HTTP_REFERER);
		} else {
			include APP_PATH.'Modules/'.$this->module.'/Install/config.inc.php';
			include Admin::adminTpl('module_config');
		}
	}
	
	/**
	 * 模块卸载
	 */
	public function uninstall() {
		if(!isset($_GET['module']) || empty($_GET['module'])) showmessage(L('illegal_parameters'));
		
		$module_api = import('@.Admin.Util.ModuleApi');
		if(!$module_api->uninstall($_GET['module'])) showmessage($module_api->errorMsg, 'blank');
		else showmessage(L('uninstall_success'), '?m=Admin&c=Module&a=cache&pc_hash='.$_SESSION['pc_hash']);
	}
	
	/**
	 * 更新模块缓存
	 */
	public function cache() {
		echo '<script type="text/javascript">parent.right.location.href = \'?m=Admin&c=CacheAll&a=init&pc_hash='.$_SESSION['pc_hash'].'\';window.top.art.dialog({id:\'install\'}).close();</script>';
		//showmessage(L('update_cache').L('success'), '', '', 'install');
	}
}
?>