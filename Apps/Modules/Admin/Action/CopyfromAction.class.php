<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
class CopyfromAction extends BaseAction {
	private $db;
	public $siteid;
	function __construct() {
		$this->db = M('Copyfrom');
		vendor('Pc.Form');
		parent::__construct();
		$this->siteid = Admin::getSiteid();
	}
	
	/**
	 * 来源管理列表
	 */
	public function init () {
		$datas = array();
		$datas = $this->db->where(array('siteid'=>$this->siteid))->order('listorder ASC')->page($_GET['page'])->select();
		$pages = $this->db->pages;

		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Copyfrom&a=add\', title:\''.L('add_copyfrom').'\', width:\'580\', height:\'240\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('add_copyfrom'));
		$this->publicCache();
		include Admin::adminTpl('copyfrom_list');
	}
	
	/**
	 * 添加来源
	 */
	public function add() {
		if(isset($_POST['dosubmit'])) {
			$_POST['info'] = $this->check($_POST['info']);
			$this->db->data($_POST['info'])->add();
			showmessage(L('add_success'), '', '', 'add');
		} else {
			$show_header = $show_validator = '';
			
			include Admin::adminTpl('copyfrom_add');
		}
	}
	
	/**
	 * 管理来源
	 */
	public function edit() {
		if(isset($_POST['dosubmit'])) {
			$id = intval($_POST['id']);
			$_POST['info'] = $this->check($_POST['info']);
			$this->db->data($_POST['info'])->where(array('id'=>$id))->save();
			showmessage(L('update_success'), '', '', 'edit');
		} else {
			$show_header = $show_validator = '';
			$id = intval($_GET['id']);
			if (!$id) showmessage(L('illegal_action'));
			$r = $this->db->where(array('id'=>$id, 'siteid'=>$this->siteid))->find();
			if (empty($r)) showmessage(L('illegal_action'));
			extract($r);
			include Admin::adminTpl('copyfrom_edit');
		}
	}
	
	/**
	 * 删除来源
	 */
	public function delete() {
		$_GET['id'] = intval($_GET['id']);
		if (!$_GET['id']) showmessage(L('illegal_action'));
		$this->db->where(array('id'=>$_GET['id'], 'siteid'=>$this->siteid))->delete();
		exit('1');
	}
	
	/**
	 * 检查POST数据
	 * @param array $data 前台POST数据
	 * @return array $data
	 */
	private function check($data = array()) {
		if (!is_array($data) || empty($data)) return array();
		if (!preg_match('/^((http|https):\/\/)?([^\/]+)/i', $data['siteurl'])) showmessage(L('input').L('copyfrom_url'));
		if (empty($data['sitename'])) showmessage(L('input').L('copyfrom_name'));
		if ($data['thumb'] && !preg_match('/^((http|https):\/\/)?([^\/]+)/i', $data['thumb'])) showmessage(L('copyfrom_logo').L('format_incorrect'));
		$data['siteid'] = $this->siteid;
		return $data;
	}
	
	/**
	 * 排序
	 */
	public function listorder() {
		if(isset($_POST['dosubmit'])) {
			foreach($_POST['listorders'] as $id => $listorder) {
				$this->db->data(array('listorder'=>$listorder))->where(array('id'=>$id))->save();
			}
			showmessage(L('operation_success'),HTTP_REFERER);
		} else {
			showmessage(L('operation_failure'));
		}
	}

	/**
	 * 生成缓存
	 */
	public function publicCache() {
		$infos = $this->db->order('listorder DESC')->key('id')->select();
		cache('copyfrom', $infos, 'Admin');
		return true;
 	}
}
?>