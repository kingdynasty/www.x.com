<?php
defined('APP_NAME') or exit('No permission resources.');

//定义在后台
define('IN_ADMIN',true);
class AdminOp {
	public function __construct() {
		$this->db = M('Admin');
	}
	/*
	 * 修改密码
	 */
	public function editPassword($userid, $password){
		$userid = intval($userid);
		if($userid < 1) return false;
		if(!is_password($password))
		{
			showmessage(L('pwd_incorrect'));
			return false;
		}
		$passwordinfo = password($password);
		return $this->db->data($passwordinfo)->where(array('userid'=>$userid))->save();
	}
	/*
	 * 检查用户名重名
	 */	
	public function checkname($username) {
		$username =  trim($username);
		if ($this->db->where(array('username'=>$username))->field('userid')->find()){
			return false;
		}
		return true;
	}	
}
?>