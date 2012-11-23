<?php
defined('APP_NAME') or exit('No permission resources.');
import('@.Util.Admin');
class KeylinkAction extends BaseAction {
	function __construct() {
		$this->db = M('Keylink');
		parent::__construct();
	}
	
	function init () {
		$page = $_GET['page'] ? intval($_GET['page']) : '1';
		$infos = $this->db->order('keylinkid DESC')->page($page ,'20')->select();
		$pages = $this->db->pages;	
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Keylink&a=add\', title:\''.L('add_keylink').'\', width:\'450\', height:\'130\'}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('add_keylink'));
		include Admin::adminTpl('keylink_list');
	}
	
	/**
	 * 验证数据有效性
	 */
	public function publicName() {
			$word = isset($_GET['word']) && trim($_GET['word']) ? (CHARSET == 'gbk' ? iconv('utf-8', 'gbk', trim($_GET['word'])) : trim($_GET['word'])) : exit('0');
			//修改检测
			$keylinkid = isset($_GET['keylinkid']) && intval($_GET['keylinkid']) ? intval($_GET['keylinkid']) : '';
	 		$data = array();
			if ($keylinkid) {
				$data = $this->db->where(array('keylinkid'=>$keylinkid))->field('word')->find();
				if (!empty($data) && $data['word'] == $word) {
					exit('1');
				}
			}
			//添加检测
			if ($this->db->where(array('word'=>$word))->field('keylinkid')->find()) {
				exit('0');
				} else {
				exit('1');
			}
		}
		
	/**
	 * 关联词添加
	 */
	function add() {
		if(isset($_POST['dosubmit'])){
				if(empty($_POST['info']['word']) || empty($_POST['info']['url']))return false;
				$this->db->data($_POST['info'])->add();
				$this->publicCacheFile();//更新缓存 
				showmessage(L('operation_success'),'?m=Admin&c=Keylink&a=add','', 'add');
			}else{
				$show_validator = $show_scroll = $show_header = true;
				include Admin::adminTpl('keylink_add');
		 }	 
	} 
	
	/**
	 * 关联词修改
	 */
	function edit() {
		if(isset($_POST['dosubmit'])){
			$keylinkid = intval($_GET['keylinkid']);
			if(empty($_POST['info']['word']) || empty($_POST['info']['url']))return false;
 			$this->db->data($_POST['info'])->where(array('keylinkid'=>$keylinkid))->save();
			$this->publicCacheFile();//更新缓存
			showmessage(L('operation_success'),'?m=Admin&c=Keylink&a=edit','', 'edit');
		}else{
			$show_validator = $show_scroll = $show_header = true;
			$info = $this->db->where(array('keylinkid'=>$_GET['keylinkid']))->find();
			if(!$info) showmessage(L('specified_word_not_exist'));
 			extract($info);
			include Admin::adminTpl('keylink_edit');
		}	 
	}
	/**
	 * 关联词删除
	 */
	function delete() {
 		if(is_array($_POST['keylinkid'])){
			foreach($_POST['keylinkid'] as $keylinkid_arr) {
				$this->db->where(array('keylinkid'=>$keylinkid_arr))->delete();
			}
			$this->publicCacheFile();//更新缓存
			showmessage(L('operation_success'),'?m=Admin&c=Keylink');	
		} else {
			$keylinkid = intval($_GET['keylinkid']);
			if($keylinkid < 1) return false;
			$result = $this->db->where(array('keylinkid'=>$keylinkid))->delete();
			$this->publicCacheFile();//更新缓存
			if($result){
				showmessage(L('operation_success'),'?m=Admin&c=Keylink');
			}else {
				showmessage(L("operation_failure"),'?m=Admin&c=Keylink');
			}
		}
	}
	/**
	 * 生成缓存
	 */
	public function publicCacheFile() {
		$infos = $this->db->field('word,url')->order('keylinkid ASC')->select();
		$datas = $rs = array();
		foreach($infos as $r) {
			$rs[0] = $r['word'];
			$rs[1] = $r['url'];
			$datas[] = $rs;
		}
		cache('keylink', $datas, 'Commons');
		return true;
 	}
}
?>