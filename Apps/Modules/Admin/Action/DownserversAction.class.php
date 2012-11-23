<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
vendor('Pc.Form');
class DownserversAction extends BaseAction {
	private $db;
	function __construct() {
		parent::__construct();
		$this->db = M('Downservers');
		$this->sites = import('Sites');
	}
	
	public function init() {
		if(isset($_POST['dosubmit'])) {
			$info['siteurl'] = trim($_POST['info']['siteurl']);
			$info['sitename'] = trim($_POST['info']['sitename']);
			$info['siteid'] = intval($_POST['info']['siteid']);
			if(empty($info['sitename'])) showmessage(L('downserver_not_empty'), HTTP_REFERER);	
			if(empty($info['siteurl']) || !preg_match('/(\w+):\/\/(.+)[^\/]$/i', $info['siteurl'])) showmessage(L('downserver_error'), HTTP_REFERER);
			$insert_id = $this->db->data($info)->add();
			if($insert_id){
				$this->_set_cache();
				showmessage(L('operation_success'), HTTP_REFERER);
			}
		} else {
			$infos =  $sitelist = array();
			$current_siteid = get_siteid();
			$where = "`siteid`='$current_siteid' or `siteid`=''";
			$sitelists = $this->sites->getList();
			if($_SESSION['roleid'] == '1') {
				foreach($sitelists as $key=>$v) $sitelist[$key] = $v['name'];
				$default = L('all_site');
			} else {
				$sitelist[$current_siteid] = $sitelists[$current_siteid]['name'];
				$default = '';
			}			
			$page = $_GET['page'] ? $_GET['page'] : '1';
			$infos = $this->db->where($where)->order('listorder DESC,id DESC')->page($page, $pagesize = 20)->select();
			$pages = $this->db->pages;						
			include Admin::adminTpl('downservers_list');
		}
	}
	
	public function edit() {
		if(isset($_POST['dosubmit'])) {
			$info['siteurl'] = trim($_POST['info']['siteurl']);
			$info['sitename'] = trim($_POST['info']['sitename']);
			$info['siteid'] = intval($_POST['info']['siteid']);
			if(empty($info['sitename'])) showmessage(L('downserver_not_empty'), HTTP_REFERER);	
			if(empty($info['siteurl']) || !preg_match('/(\w+):\/\/(.+)[^\/]$/i', $info['siteurl'])) showmessage(L('downserver_error'), HTTP_REFERER);
			$id = intval(trim($_POST['id']));
			$this->_set_cache();
			$this->db->data($info)->where(array('id'=>$id))->save();
			showmessage(L('operation_success'), '', '', 'edit');
		} else {
			$info =  $sitelist = array();
			$default = '';
			$sitelists = $this->sites->getList();
			if($_SESSION['roleid'] == '1') {
				foreach($sitelists as $key=>$v) $sitelist[$key] = $v['name'];
				$default = L('all_site');
			} else {
				$current_siteid = Admin::getSiteid();
				$sitelist[$current_siteid] = $sitelists[$current_siteid]['name'];
				$default = '';
			}			
			$info = $this->db->where(array('id'=>intval($_GET['id'])))->find();
			extract($info);
			$show_validator = true;
			$show_header = true;
			include Admin::adminTpl('downservers_edit');
		}
	}
	
	public function delete() {
		$id = intval($_GET['id']);
		$this->db->where(array('id'=>$id))->delete();
		$this->_set_cache();
		showmessage(L('downserver_del_success'), HTTP_REFERER);
	}	
	
	/**
	 * 排序
	 */
	public function listorder() {
		if(isset($_POST['dosubmit'])) {
			foreach($_POST['listorders'] as $id => $listorder) {
				$this->db->data(array('listorder'=>$listorder))->where(array('id'=>$id))->save();
			}
			showmessage(L('operation_success'), HTTP_REFERER);
		} else {
			showmessage(L('operation_failure'), HTTP_REFERER);
		}
	}	
	
	private function _set_cache() {
		$infos = $this->db->select();
		foreach ($infos as $info){
			$servers[$info['id']] = $info;
		}
		cache('downservers', $servers,'Commons');
		return $infos;
	}
	
}
?>