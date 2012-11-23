<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);

class AdminAnnounceAction extends BaseAction {

	private $db; public $username;
	public function __construct() {
		parent::__construct();
		//if (!module_exists(MODULE_NAME)) showmessage(L('module_not_exists'));
		$this->username = cookie('admin_username');
		$this->db = M('Announce');
	}
	
	public function init() {
		//公告列表
		$sql = '';
		$_GET['status'] = $_GET['status'] ? intval($_GET['status']) : 1;
		$sql = '`siteid`=\''.Admin::getSiteid().'\'';
		switch($_GET['s']) {
			case '1': $sql .= ' AND `passed`=\'1\' AND (`endtime` >= \''.date('Y-m-d').'\' or `endtime`=\'0000-00-00\')'; break;
			case '2': $sql .= ' AND `passed`=\'0\''; break;
			case '3': $sql .= ' AND `passed`=\'1\' AND `endtime`!=\'0000-00-00\' AND `endtime` <\''.date('Y-m-d').'\' '; break;
		}
		$page = max(intval($_GET['page']), 1);
		$data = $this->db->where($sql)->order('`aid` DESC')->page($page)->select();
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Announce&c=AdminAnnounce&a=add\', title:\''.L('announce_add').'\', width:\'700\', height:\'500\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('announce_add'));
		include Admin::adminTpl('announce_list');
	}
	
	/**
	 * 添加公告
	 */
	public function add() {
		if(isset($_POST['dosubmit'])) {
			$_POST['announce'] = $this->check($_POST['announce']);
			if($this->db->data($_POST['announce'])->add()) showmessage(L('announcement_successful_added'), HTTP_REFERER, '', 'add');
		} else {
			//获取站点模板信息
			load('@.Admin.function');
			$siteid = Admin::getSiteid();
			$template_list = template_list($siteid, 0);
			$site = import('Sites');
			$info = $site->getById($siteid);
			foreach ($template_list as $k=>$v) {
				$template_list[$v['dirname']] = $v['name'] ? $v['name'] : $v['dirname'];
				unset($template_list[$k]);
			}
			$show_header = $show_validator = $show_scroll = 1;
			vendor('Pc.Form');
			include Admin::adminTpl('announce_add');
		}
	}
	
	/**
	 * 修改公告
	 */
	public function edit() {
		$_GET['aid'] = intval($_GET['aid']);
		if(!$_GET['aid']) showmessage(L('illegal_operation'));
		if(isset($_POST['dosubmit'])) {
			$_POST['announce'] = $this->check($_POST['announce'], 'edit');
			if($this->db->data($_POST['announce'])->where(array('aid' => $_GET['aid']))->save()) showmessage(L('announced_a'), HTTP_REFERER, '', 'edit');
		} else {
			$where = array('aid' => $_GET['aid']);
			$an_info = $this->db->where($where)->find();
			vendor('Pc.Form');
			//获取站点模板信息
			load('@.Admin.function');
			$template_list = template_list($this->siteid, 0);
			foreach ($template_list as $k=>$v) {
				$template_list[$v['dirname']] = $v['name'] ? $v['name'] : $v['dirname'];
				unset($template_list[$k]);
			}
			$show_header = $show_validator = $show_scroll = 1;
			include Admin::adminTpl('announce_edit');
		}
	}
	
	/**
	 * ajax检测公告标题是否重复
	 */
	public function publicCheckTitle() {
		if (!$_GET['title']) exit(0);
		if (CHARSET=='gbk') {
			$_GET['title'] = iconv('UTF-8', 'GBK', $_GET['title']);
		}
		$title = $_GET['title'];
		if ($_GET['aid']) {
			$r = $this->db->where(array('aid' => $_GET['aid']))->find();
			if ($r['title'] == $title) {
				exit('1');
			}
		}
		$r = $this->db->where(array('siteid' => Admin::getSiteid(), 'title' => $title))->field('aid')->find();
		if($r['aid']) {
			exit('0');
		} else {
			exit('1');
		}
	}
	
	/**
	 * 批量修改公告状态 使其成为审核、未审核状态
	 */
	public function publicApproval($aid = 0) {
		if((!isset($_POST['aid']) || empty($_POST['aid'])) && !$aid) {
			showmessage(L('illegal_operation'));
		} else {
			if(is_array($_POST['aid']) && !$aid) {
				array_map(array($this, 'public_approval'), $_POST['aid']);
				showmessage(L('announce_passed'), HTTP_REFERER);
			} elseif($aid) {
				$aid = intval($aid);
				$this->db->data(array('passed' => $_GET['passed']))->where(array('aid' => $aid))->save();
				return true;
			}
		}
	}
	
	/**
	 * 批量删除公告
	 */
	public function delete($aid = 0) {
		if((!isset($_POST['aid']) || empty($_POST['aid'])) && !$aid) {
			showmessage(L('illegal_operation'));
		} else {
			if(is_array($_POST['aid']) && !$aid) {
				array_map(array($this, 'delete'), $_POST['aid']);
				showmessage(L('announce_deleted'), HTTP_REFERER);
			} elseif($aid) {
				$aid = intval($aid);
				$this->db->delete(array('aid' => $aid));
			}
		}
	}
	
	/**
	 * 验证表单数据
	 * @param  		array 		$data 表单数组数据
	 * @param  		string 		$a 当表单为添加数据时，自动补上缺失的数据。
	 * @return 		array 		验证后的数据
	 */
	private function check($data = array(), $a = 'add') {
		if($data['title']=='') showmessage(L('title_cannot_empty'));
		if($data['content']=='') showmessage(L('announcements_cannot_be_empty'));
		$r = $this->db->where(array('title' => $data['title']))->find();
		if (strtotime($data['endtime'])<strtotime($data['starttime'])) {
			$data['endtime'] = '';
		}
		if ($a=='add') {
			if (is_array($r) && !empty($r)) {
				showmessage(L('announce_exist'), HTTP_REFERER);
			}
			$data['siteid'] = Admin::getSiteid();
			$data['addtime'] = $GLOBALS['_beginTime'];
			$data['username'] = $this->username;
			if ($data['starttime'] == '') $announce['starttime'] = date('Y-m-d');
		} else {
			if ($r['aid'] && ($r['aid']!=$_GET['aid'])) {
				showmessage(L('announce_exist'), HTTP_REFERER);
			}
		}
		return $data;
	}
}
?>