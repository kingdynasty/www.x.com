<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
vendor('Pc.Form');

class UrlruleAction extends BaseAction {
	function __construct() {
		parent::__construct();
		$this->db = M('Urlrule');
		$this->moduleDb = M('Module');
	}
	
	function init () {
		$page = max(intval($_GET['page']),1);
		$infos = $this->db->page($page)->select();
		$pages = $this->db->pages;
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Urlrule&a=add\', title:\''.L('add_urlrule').'\', width:\'750\', height:\'300\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('add_urlrule'));
		$this->publicCacheUrlrule();
		include Admin::adminTpl('urlrule_list');
	}
	function add() {
		if(isset($_POST['dosubmit'])) {
			$this->db->data($_POST['info'])->add();
			$this->publicCacheUrlrule();
			showmessage(L('add_success'),'','','add');
		} else {
			$show_validator = $show_header = '';
			$modules_arr = $this->moduleDb->field('module,name')->select();
			
			$modules = array();
			foreach ($modules_arr as $r) {
				$modules[$r['module']] = $r['name'];
			}
		
			include Admin::adminTpl('urlrule_add');
		}
	}
	function delete() {
		$_GET['urlruleid'] = intval($_GET['urlruleid']);
		$this->db->where(array('urlruleid'=>$_GET['urlruleid']))->delete();
		$this->publicCacheUrlrule();
		showmessage(L('operation_success'),HTTP_REFERER);
	}
	
	function edit() {
		if(isset($_POST['dosubmit'])) {
			$urlruleid = intval($_POST['urlruleid']);
			$this->db->data($_POST['info'])->where(array('urlruleid'=>$urlruleid))->save();
			$this->publicCacheUrlrule();
			showmessage(L('update_success'),'','','edit');
		} else {
			$show_validator = $show_header = '';
			$urlruleid = $_GET['urlruleid'];
			$r = $this->db->where(array('urlruleid'=>$urlruleid))->find();
			extract($r);
			$modules_arr = $this->moduleDb->field('module,name')->select();
			
			$modules = array();
			foreach ($modules_arr as $r) {
				$modules[$r['module']] = $r['name'];
			}
			include Admin::adminTpl('urlrule_edit');
		}
	}
	/**
	 * 更新URL规则
	 */
	public function publicCacheUrlrule() {
		$datas = $this->db->key('urlruleid')->select();
		$basic_data = array();
		foreach($datas as $roleid=>$r) {
			$basic_data[$roleid] = $r['urlrule'];;
		}
		cache('urlrules_detail',$datas,'Commons');
		cache('urlrules',$basic_data,'Commons');
	}
}
?>