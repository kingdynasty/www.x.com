<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
class IpbannedAction extends BaseAction {
	function __construct() {
		$this->db = M('Ipbanned');
		vendor('Pc.Form');
		parent::__construct();
	}
	
	function init () {
		$page = $_GET['page'] ? $_GET['page'] : '1';
		$infos = array();
		$infos = $this->db->order('ipbannedid DESC')->page($page ,'20')->select();
		$pages = $this->db->pages;	
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Ipbanned&a=add\', title:\''.L('add_ipbanned').'\', width:\'450\', height:\'300\'}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('add_ipbanned'));
		include Admin::adminTpl('ipbanned_list');
	}
	
	/**
	 * 验证数据有效性
	 */
	public function publicName() {
		$ip = isset($_GET['ip']) && trim($_GET['ip']) ? (CHARSET == 'gbk' ? iconv('utf-8', 'gbk', trim($_GET['ip'])) : trim($_GET['ip'])) : exit('0');
 		//添加判断IP是否重复
		if ($this->db->where(array('ip'=>$ip))->field('ipbannedid')->find()) {
			exit('0');
		} else {
			exit('1');
		}
	}
		
	/**
	 * IP添加
	 */
	function add() {
		if(isset($_POST['dosubmit'])){
  			$_POST['info']['expires']=strtotime($_POST['info']['expires']);
			$this->db->data($_POST['info'])->add();
			$this->publicCacheFile();//更新缓存 
			showmessage(L('operation_success'),'?m=Admin&c=Ipbanned&a=add','', 'add');
		}else{
			$show_validator = $show_scroll = $show_header = true;
	 		include Admin::adminTpl('ipbanned_add');
		}	 
	} 
	 
	/**
	 * IP删除
	 */
	function delete() {
 		if(is_array($_POST['ipbannedid'])){
			foreach($_POST['ipbannedid'] as $ipbannedid_arr) {
				$this->db->where(array('ipbannedid'=>$ipbannedid_arr))->delete();
			}
			$this->publicCacheFile();//更新缓存 
			showmessage(L('operation_success'),'?m=Admin&c=Ipbanned');	
		} else {
			$ipbannedid = intval($_GET['ipbannedid']);
			if($ipbannedid < 1) return false;
			$result = $this->db->where(array('ipbannedid'=>$ipbannedid))->delete();
			$this->publicCacheFile();//更新缓存 
			if($result){
				showmessage(L('operation_success'),'?m=Admin&c=Ipbanned');
			} else {
				showmessage(L("operation_failure"),'?m=Admin&c=Ipbanned');
			}
		}
	}
	
	/**
	 * IP搜索
	 */
	public function searchIp() {
		$where = '';
		if($_GET['search']) extract($_GET['search']);
		if($ip){
			$where .= $where ?  " AND ip LIKE '%$ip%'" : " ip LIKE '%$ip%'";
		}
		$page = max(intval($_GET['page']),1);
		$infos = $this->db->where($where)->order($order = 'ipbannedid DESC')->page($page, $pages = '2')->select();
		$pages = $this->db->pages;
  		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Ipbanned&a=add\', title:\''.L('add_ipbanned').'\', width:\'450\', height:\'300\'}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('add_ipbanned'));
		include Admin::adminTpl('ip_search_list');
	} 
	
	/**
	 * 生成缓存
	 */
	public function publicCacheFile() {
		$infos = $this->db->field('ip,expires')->order('ipbannedid desc')->select();
		cache('ipbanned', $infos, 'Commons');
		return true;
 	}
}
?>