<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);

class LogAction extends BaseAction {
	function __construct() {
		parent::__construct();
		$this->db = M('Log');
		vendor('Pc.Form','',0);
		$admin_username = cookie('admin_username');//管理员COOKIE
		$userid = $_SESSION['userid'];//登陆USERID　
	}
	
	function init () {
		$page = max(intval($_GET['page']),1);
		$infos = $this->db->order('logid DESC')->page($page, $pages = '13')->select();
		$pages = $this->db->pages;
		//模块数组
		$module_arr = array();
		$modules = cache('modules','Commons');
		$default = L('open_module');
		foreach($modules as $module=>$m) $module_arr[$m['module']] = $m['module'];
 		include Admin::adminTpl('log_list');
	}
		
	/**
	 * 操作日志删除 包含批量删除 单个删除
	 */
	function delete() {
		$week = intval($_GET['week']);
		if($week){
			$where = '';
			$start = $GLOBALS['_beginTime'] - $week*7*24*3600;
			$d = date("Y-m-d",$start); 
 			//$end = strtotime($end_time);
			//$where .= "AND `message_time` >= '$start' AND `message_time` <= '$end' ";
			$where .= "`time` <= '$d'";
			$this->db->where($where)->delete();
			showmessage(L('operation_success'),'?m=Admin&c=Log');
		} else {
			return false;
		}
	}
 		
 	
	/**
	 * 日志搜索
	 */
	public function searchLog() {
 		$where = '';
		extract($_GET['search']);
		if($username){
			$where .= $where ?  " AND username='$username'" : " username='$username'";
		}
		if ($module){
			$where .= $where ?  " AND module='$module'" : " module='$module'";
		}
		if($start_time && $end_time) {
			$start = $start_time;
			$end = $end_time;
			$where .= "AND `time` >= '$start' AND `time` <= '$end' ";
		}
 
		$page = max(intval($_GET['page']),1); 
		$infos = $this->db->where($where)->order($order = 'logid DESC')->page($page, $pages = '12')->select(); 
 		$pages = $this->db->pages;
 		//模块数组
		$module_arr = array();
		$modules = cache('modules','Commons');
		$default = $module ? $module : L('open_module');//未设定则显示 不限模块 ，设定则显示指定的
 		foreach($modules as $module=>$m) $module_arr[$m['module']] = $m['module'];
		
 		include Admin::adminTpl('log_search_list');
	} 
	
}
?>