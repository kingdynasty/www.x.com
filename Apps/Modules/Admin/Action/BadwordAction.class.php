<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
class BadwordAction extends BaseAction {
	function __construct() {
		$admin_username = cookie('admin_username');
		$userid = $_SESSION['userid'];
		$this->db = M('Badword');
		parent::__construct();
	}
	
	function init () {
		$page = max(intval($_GET['page']),1);
		$infos = $pages = '';
		$infos = $this->db->order('badid DESC')->page($page, $pages = '13')->select();
		$pages = $this->db->pages;
		$level = array(1=>L('general'),2=>L('danger'));
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Badword&a=add\', title:\''.L('badword_add').'\', width:\'450\', height:\'180\'}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('badword_add'));
		include Admin::adminTpl('badword_list');
	}
	
	
	/**
	 * 敏感词添加
	 */
	function add() {
		if(isset($_POST['dosubmit'])){
	 			$_POST['info']['lastusetime'] = $GLOBALS['_beginTime'];
				$_POST['info']['replaceword'] = str_replace("　","",trim($_POST['replaceword']));
				$_POST['info']['badword'] = str_replace("　","",trim($_POST['badword']));
	 			if(empty($_POST['info']['badword'])) {
					showmessage(L('enter_word'),'?m=Admin&c=Badword&a=add');
					}
				$this->db->data($_POST['info'])->add();
				$this->publicCacheFile();//更新缓存
		 		showmessage(L('operation_success'),'?m=Admin&c=Badword&a=add','', 'add');
		 		}else{
				$show_validator = $show_scroll = $show_header = true; 
				include Admin::adminTpl('badword_add');
			}
	}
	
	public function publicName() {
			$badword = isset($_GET['badword']) && trim($_GET['badword']) ? (C('charset') == 'gbk' ? iconv('utf-8', 'gbk', trim($_GET['badword'])) : trim($_GET['badword'])) : exit('0');
			$badid = isset($_GET['badid']) && intval($_GET['badid']) ? intval($_GET['badid']) : '';
	 		$data = array();
			if ($badid) {
				$data = $this->db->where(array('badid'=>$badid))->field('badword')->find();
				if (!empty($data) && $data['badword'] == $badword) {
					exit('1');
				}
			}
			if ($this->db->where(array('badword'=>$badword))->field('badid')->find()) {
				exit('0');
			} else {
				exit('1');
			}
		}
		
	/**
	 * 敏感词排序
	 */
	function listorder() {
		if(!is_array($_POST['listorders'])) return false;
			foreach($_POST['listorders'] as $badid => $listorder) {
					$this->db->data(array('listorder'=>$listorder))->where(array('badid'=>$badid))->save();
			}
			showmessage(L('operation_success'),'?m=Admin&c=Badword');
	}
	
	/**
	 * 敏感词修改
	 */
	function edit() {
		if(isset($_POST['dosubmit'])){
				$badid = intval($_GET['badid']);
				$_POST['info']['replaceword'] = str_replace("　","",trim($_POST['replaceword']));
				$_POST['info']['badword'] = str_replace("　","",trim($_POST['badword']));
				$this->db->data($_POST['info'])->where(array('badid'=>$badid))->save();
				$this->publicCacheFile();//更新缓存
				showmessage(L('operation_success'),'?m=Admin&c=Badword&a=edit','', 'edit');
			}else{
				$show_validator = $show_scroll = $show_header = true;
				$info = array();
				$info = $this->db->where(array('badid'=>$_GET['badid']))->find();
				if(!$info) showmessage(L('keywords_no_exist'));
	 			extract($info);
				include Admin::adminTpl('badword_edit');
		}	 
	}
	/**
	 * 关键词删除 包含批量删除 单个删除
	 */
	function delete() {
 		if(is_array($_POST['badid'])){
				foreach($_POST['badid'] as $badid_arr) {
					$this->db->where(array('badid'=>$badid_arr))->delete();
				}
				$this->publicCacheFile();//更新缓存
				showmessage(L('operation_success'),'?m=Admin&c=Badword');	
			}else{
				$badid = intval($_GET['badid']);
				if($badid < 1) return false;
				$result = $this->db->where(array('badid'=>$badid))->delete();
				if($result){
					$this->publicCacheFile();//更新缓存
					showmessage(L('operation_success'),'?m=Admin&c=Badword');
					}else {
					showmessage(L("operation_failure"),'?m=Admin&c=Badword');
				}
		}
	}
	
	/**
	 * 导出敏感词为文本 一行一条记录
	 */
	function export() {
		$result = $s = '';
		$result = $this->db->order('badid DESC')->select();
		if(!is_array($result) || empty($result)){
			showmessage('暂无敏感词设置，正在返回！','?m=Admin&c=Badword');
		}
  		foreach($result as $s){
 			extract($s);
			$str .= $badword.','.$replaceword.','.$level."\n";		  
		}
 		$filename = L('export');
		header('Content-Type: text/x-sql');
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		$is_ie = 'IE';
		    if ($is_ie == 'IE') {
		        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		        header('Pragma: public');
		    	} else {
		        header('Pragma: no-cache');
		        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		    }
		echo $str;
		exit();
 	}
	
	/**
	 * 从文本中导入敏感词, 一行一条记录
	 */
	function import(){
		if(isset($_POST['dosubmit'])){
				$arr = $s = $str = $level_arr = '';
				$s = trim($_POST['info']);
			    if(empty($s)) showmessage(L('not_information'),'?m=Admin&c=Badword&a=import');
	 			$arr = explode("\n",$s); 
	 			if(!is_array($arr) || empty($arr)) return false; 
	 			foreach($arr as $s){
			    	$level_arr = array("1","2");
	 				$str = explode(",",$s);
	   				$sql_str = array();
	 				$sql_str['badword'] = $str[0];
	 				$sql_str['replaceword'] = $str[1];
	 				$sql_str['level'] = $str[2];
					$sql_str['lastusetime'] = $GLOBALS['_beginTime'];
					if(!in_array($sql_str['level'],$level_arr)) $sql_str['level'] = '1';
	 				if(empty($sql_str['badword'])){
							continue;
						}else{
							$check_badword = $this->db->where(array('badword'=>$sql_str['badword']))->field('*')->find();
							if($check_badword){
								continue;
							}
							$this->db->data($sql_str)->add();
					}
					
					unset($sql_str,$check_badword);
	 			}
				showmessage(L('operation_success'),'?m=Admin&c=Badword');
 			}else{
			include Admin::adminTpl('badword_import');
		}
	} 
 	
	/**
	 * 生成缓存
	 */
	function publicCacheFile() { 
		$infos = $this->db->where('badid,badword,replaceword,level')->order('badid ASC')->select();
		cache('badword', $infos, 'Commons');
		return true;
 	}
	
}
?>