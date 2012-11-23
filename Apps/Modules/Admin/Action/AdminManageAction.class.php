<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
load('@.admin');
class AdminManageAction extends BaseAction {
	private $db,$roleDb;
	function __construct() {
		parent::__construct();
		$this->db = M('Admin');
		$this->roleDb = M('AdminRole');
		$this->op = import('@.Admin.Util.AdminOp');
	}
	
	/**
	 * 管理员管理列表
	 */
	public function init() {
		$userid = $_SESSION['userid'];
		$admin_username = cookie('admin_username');
		$page = max(intval($_GET['page']), 1);
		$infos = $this->db->page($page, 20)->select();
		$pages = $this->db->pages;
		$roles = cache('role','Commons');
		include Admin::adminTpl('admin_list');
	}
	
	/**
	 * 添加管理员
	 */
	public function add() {
		if(isset($_POST['dosubmit'])) {
			$info = array();
			if(!$this->op->checkname($_POST['info']['username'])){
				showmessage(L('admin_already_exists'));
			}
			$info = checkuserinfo($_POST['info']);		
			if(!checkpasswd($info['password'])){
				showmessage(L('pwd_incorrect'));
			}
			$passwordinfo = password($info['password']);
			$info['password'] = $passwordinfo['password'];
			$info['encrypt'] = $passwordinfo['encrypt'];
			
			$admin_fields = array('username', 'email', 'password', 'encrypt','roleid','realname');
			foreach ($info as $k=>$value) {
				if (!in_array($k, $admin_fields)){
					unset($info[$k]);
				}
			}
			if($this->db->data($info)->add()){
				showmessage(L('operation_success'),'?m=Admin&c=AdminManage');
			}
		} else {
			$roles = $this->roleDb->where(array('disabled'=>'0'))->select();
			include Admin::adminTpl('admin_add');
		}
		
	}
	
	/**
	 * 修改管理员
	 */
	public function edit() {
		if(isset($_POST['dosubmit'])) {
			$memberinfo = $info = array();			
			$info = checkuserinfo($_POST['info']);
			if(isset($info['password']) && !empty($info['password']))
			{
				$this->op->editPassword($info['userid'], $info['password']);
			}
			$userid = $info['userid'];
			$admin_fields = array('username', 'email', 'roleid','realname');
			foreach ($info as $k=>$value) {
				if (!in_array($k, $admin_fields)){
					unset($info[$k]);
				}
			}
			$this->db->data($info)->where(array('userid'=>$userid))->save();
			showmessage(L('operation_success'),'','','edit');
		} else {					
			$info = $this->db->where(array('userid'=>$_GET['userid']))->find();
			extract($info);	
			$roles = $this->roleDb->where(array('disabled'=>'0'))->select();	
			$show_header = true;
			include Admin::adminTpl('admin_edit');		
		}
	}
	
	/**
	 * 删除管理员
	 */
	public function delete() {
		$userid = intval($_GET['userid']);
		if($userid == '1') showmessage(L('this_object_not_del'), HTTP_REFERER);
		$this->db->where(array('userid'=>$userid))->delete();
		showmessage(L('admin_cancel_succ'));
	}
	
	/**
	 * 更新管理员状态
	 */
	public function lock(){
		$userid = intval($_GET['userid']);
		$disabled = intval($_GET['disabled']);
		$this->db->data(array('disabled'=>$disabled))->where(array('userid'=>$userid))->save();
		showmessage(L('operation_success'),'?m=Admin&c=AdminManage');
	}
	
	/**
	 * 管理员自助修改密码
	 */
	public function publicEditPwd() {
		$userid = $_SESSION['userid'];
		if(isset($_POST['dosubmit'])) {
			$r = $this->db->where(array('userid'=>$userid))->field('password,encrypt')->find();
			if ( password($_POST['old_password'],$r['encrypt']) !== $r['password'] ) showmessage(L('old_password_wrong'),HTTP_REFERER);
			if(isset($_POST['new_password']) && !empty($_POST['new_password'])) {
				$this->op->editPassword($userid, $_POST['new_password']);
			}
			showmessage(L('password_edit_succ_logout'),'?m=Admin&c=Index&a=publicLogout');			
		} else {
			$info = $this->db->where(array('userid'=>$userid))->find();
			extract($info);
			include Admin::adminTpl('admin_edit_pwd');
		}

	}
	/*
	 * 编辑用户信息
	 */
	public function publicEditInfo() {
		$userid = $_SESSION['userid'];
		if(isset($_POST['dosubmit'])) {
			$admin_fields = array('email','realname','lang');
			$info = array();
			$info = $_POST['info'];
			if(trim($info['lang'])=='') $info['lang'] = 'zh-cn';
			foreach ($info as $k=>$value) {
				if (!in_array($k, $admin_fields)){
					unset($info[$k]);
				}
			}
			$this->db->data($info)->where(array('userid'=>$userid))->save();
			showmessage(L('operation_success'),HTTP_REFERER);			
		} else {
			$info = $this->db->where(array('userid'=>$userid))->find();
			extract($info);
			
			$lang_dirs = glob(APP_PATH.'Lang/*');
			$dir_array = array();
			foreach($lang_dirs as $dirs) {
				$dir_array[] = str_replace(APP_PATH.'Lang/','',$dirs);
			}
			include Admin::adminTpl('admin_edit_info');			
		}	
	
	}
	/**
	 * 异步检测用户名
	 */
	function publicChecknameAjx() {
		$username = isset($_GET['username']) && trim($_GET['username']) ? trim($_GET['username']) : exit(0);
		if ($this->db->where(array('username'=>$username))->field('userid')->find()){
			exit('0');
		}
		exit('1');
	}
	/**
	 * 异步检测密码
	 */
	function publicPasswordAjx() {
		$userid = $_SESSION['userid'];
		$r = array();
		$r = $this->db->where(array('userid'=>$userid))->field('password,encrypt')->find();
		if ( password($_GET['old_password'],$r['encrypt']) == $r['password'] ) {
			exit('1');
		}
		exit('0');
	}
	/**
	 * 异步检测emial合法性
	 */
	function publicEmailAjx() {
		$email = $_GET['email'];
		if ($this->db->where(array('email'=>$email))->field('userid')->find()){
			exit('0');
		}
		exit('1');
	}

	//电子口令卡
	function card() {
		if (C('safe_card') != 1) {
			showmessage(L('your_website_opened_the_card_no_password'));
		}
		$userid = isset($_GET['userid']) && intval($_GET['userid']) ? intval($_GET['userid']) : showmessage(L('user_id_cannot_be_empty'));
		$data = array();
		$data = $this->db->where(array('userid'=>$userid))->field('`card`,`username`')->find();
		if ($data) {
			$pic_url = '';
			if ($data['card']) {
				import('@.Admin.Util.Card', '', 0);
				$pic_url = Card::getPic($data['card']);
			}
			$show_header = true;
			include Admin::adminTpl('admin_card');
		} else {
			showmessage(L('users_were_not_found'));
		}
	}
	
	//绑定电子口令卡
	function creatCard() {
		if (C('safe_card') != 1) {
			showmessage(L('your_website_opened_the_card_no_password'));
		}
		$userid = isset($_GET['userid']) && intval($_GET['userid']) ? intval($_GET['userid']) : showmessage(L('user_id_cannot_be_empty'));
		$data = $card = '';
		if ($data = $this->db->where(array('userid'=>$userid))->field('`card`,`username`')->find()) {
			if (empty($data['card'])) {
				import('@.Admin.Util.Card', '', 0);
				$card = Card::creatCard();
				if ($this->db->data(array('card'=>$card))->where(array('userid'=>$userid))->save()) {
					showmessage(L('password_card_application_success'), '?m=Admin&c=AdminManage&a=card&userid='.$userid);
				} else {
					showmessage(L('a_card_with_a_local_database_please_contact_the_system_administrators'));
				}
			} else {
				showmessage(L('please_lift_the_password_card_binding'),HTTP_REFERER);
			}
		} else {
			showmessage(L('users_were_not_found'));
		}
	}
	
	//解除口令卡绑定
	function removeCard() {
		if (C('safe_card') != 1) {
			showmessage(L('your_website_opened_the_card_no_password'));
		}
		$userid = isset($_GET['userid']) && intval($_GET['userid']) ? intval($_GET['userid']) : showmessage(L('user_id_cannot_be_empty'));
		$data = $result = '';
		if ($data = $this->db->where(array('userid'=>$userid))->field('`card`,`username`,`userid`')->find()) {
			import('@.Admin.Util.Card', '', 0);
			if ($result = Card::removeCard($data['card'])) {
					$this->db->data(array('card'=>''))->where(array('userid'=>$userid))->save();
					showmessage(L('the_binding_success'), '?m=Admin&c=AdminManage&a=card&userid='.$userid);
			}
		} else {
			showmessage(L('users_were_not_found'));
		}
	}	
}
?>