<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
class ReleasePointAction extends BaseAction {
	private $db;
	public $ssl = 0;
	public function __construct() {
		parent::__construct();
		$this->db = M('ReleasePoint');
		if (function_exists('ftp_ssl_connect')) {
			$this->ssl = 1;
		}
	}
	
	public function init() {
		$list = $this->db->select();
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=ReleasePoint&a=add\', title:\''.L('release_point_add').'\', width:\'700\', height:\'500\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('release_point_add'));
		include Admin::adminTpl('release_point_list');
	}
	
	public function add() {
		if (isset($_POST['dosubmit'])) {
			$name = isset($_POST['name']) && trim($_POST['name']) ? trim($_POST['name']) : showmessage(L('release_point_name').L('empty'));
			$host = isset($_POST['host']) && trim($_POST['host']) ? trim($_POST['host']) : showmessage(L('server_address').L('empty'));
			$port = isset($_POST['port']) && intval($_POST['port']) ? intval($_POST['port']) : showmessage(L('server_port').L('empty'));
			$username = isset($_POST['username']) && trim($_POST['username']) ? trim($_POST['username']) : showmessage(L('username').L('empty'));
			$password = isset($_POST['password']) && trim($_POST['password']) ? trim($_POST['password']) : showmessage(L('password').L('empty'));
			$path = isset($_POST['path']) && trim($_POST['path']) ? trim($_POST['path']) : showmessage(L('path').L('empty'));
			$pasv = isset($_POST['pasv']) && trim($_POST['pasv']) ? trim($_POST['pasv']) : 0;
			$ssl = isset($_POST['ssl']) && trim($_POST['ssl']) ? trim($_POST['ssl']) : 0;
			if ($this->db->where(array("name"=>$name))->find()) {
				showmessage(L('release_point_name').L('exists'));
			}
			if ($this->db->data(array('name'=>$name,'host'=>$host,'port'=>$port,'username'=>$username, 'password'=>$password, 'path'=>$path, 'pasv'=>$pasv, 'ssl'=>$ssl))->add()) {
				showmessage(L('operation_success'), '', '', 'add');
			} else {
				showmessage(L('operation_failure'));
			}
		}
		$show_header = $show_validator = true;
		include Admin::adminTpl('release_point_add');
	}
	
	public function edit() {
		$id = isset($_GET['id']) && intval($_GET['id']) ? intval($_GET['id']) : showmessage(L('illegal_parameters'), HTTP_REFERER);
		if ($data = $this->db->where(array('id'=>$id))->find()) {
			if (isset($_POST['dosubmit'])) {
				$name = isset($_POST['name']) && trim($_POST['name']) ? trim($_POST['name']) : showmessage(L('release_point_name').L('empty'));
				$host = isset($_POST['host']) && trim($_POST['host']) ? trim($_POST['host']) : showmessage(L('server_address').L('empty'));
				$port = isset($_POST['port']) && intval($_POST['port']) ? intval($_POST['port']) : showmessage(L('server_port').L('empty'));
				$username = isset($_POST['username']) && trim($_POST['username']) ? trim($_POST['username']) : showmessage(L('username').L('empty'));
				$password = isset($_POST['password']) && trim($_POST['password']) ? trim($_POST['password']) : showmessage(L('password').L('empty'));
				$path = isset($_POST['path']) && trim($_POST['path']) ? trim($_POST['path']) : showmessage(L('path').L('empty'));
				$pasv = isset($_POST['pasv']) && trim($_POST['pasv']) ? trim($_POST['pasv']) : 0;
				$ssl = isset($_POST['ssl']) && trim($_POST['ssl']) ? trim($_POST['ssl']) : 0;
				if ($data['name'] != $name && $this->db->where(array("name"=>$name))->find()) {
					showmessage(L('release_point_name').L('exists'));
				}
				if ($this->db->data(array('name'=>$name,'host'=>$host,'port'=>$port,'username'=>$username, 'password'=>$password, 'path'=>$path, 'pasv'=>$pasv, 'ssl'=>$ssl))->where(array('id'=>$id))->save()) {
					showmessage(L('operation_success'), '', '', 'edit');
				} else {
					showmessage(L('operation_failure'));
				}
			}
			$show_header = $show_validator = true;
			include Admin::adminTpl('release_point_edit');
		} else {
			showmessage(L('notfound'), HTTP_REFERER);
		}

	}
	
	public function publicName() {
		$name = isset($_GET['name']) && trim($_GET['name']) ? (C('charset') == 'gbk' ? iconv('utf-8', 'gbk', trim($_GET['name'])) : trim($_GET['name'])) : exit('0');
		$id = isset($_GET['id']) && intval($_GET['id']) ? intval($_GET['id']) : '';
		$data = array();
		if ($id) {
			$data = $this->db->where(array('id'=>$id))->field('name')->find();
			if (!empty($data) && $data['name'] == $name) {
				exit('1');
			}
		}
		if ($this->db->where(array('name'=>$name))->field('id')->find()) {
			exit('0');
		} else {
			exit('1');
		}
	}
	
	public function del() {
		$id = isset($_GET['id']) && intval($_GET['id']) ? intval($_GET['id']) : showmessage(L('illegal_parameters'), HTTP_REFERER);
		if ($this->db->where(array('id'=>$id))->find()) {
			if ($this->db->delete(array('id'=>$id))) {
				showmessage(L('operation_success'), HTTP_REFERER);
			} else {
				showmessage(L('operation_failure'), HTTP_REFERER);
			}
		} else {
			showmessage(L('notfound'), HTTP_REFERER);
		}
	}
	
	public function publicTestFtp() {
		$host = isset($_GET['host']) && trim($_GET['host']) ? trim($_GET['host']) : exit('0');
		$port = isset($_GET['port']) && intval($_GET['port']) ? intval($_GET['port']) : exit('0');
		$username = isset($_GET['username']) && trim($_GET['username']) ? trim($_GET['username']) : exit('0');
		$password = isset($_GET['password']) && trim($_GET['password']) ? trim($_GET['password']) : exit('0');
		$pasv = isset($_GET['pasv']) && trim($_GET['pasv']) ? trim($_GET['pasv']) : 0;
		$ssl = isset($_GET['ssl']) && trim($_GET['ssl']) ? trim($_GET['ssl']) : 0;
		$ftp = vendor('Pc.Ftps','',1);
		if ($ftp->connect($host, $username, $password, $port, $pasv, $ssl, 25)) {
			if ($ftp->linkTime > 15) {
				exit(L('ftp_connection_a_long_time'));
			}
			exit('1');
		} else {
			exit(L('can_ftp_server_connections'));
		}
	}
}