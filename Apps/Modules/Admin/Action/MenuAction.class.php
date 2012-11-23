<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);

class MenuAction extends BaseAction {
	function __construct() {
		parent::__construct();
		$this->db = M('Menu');
	}
	
	function init () {
		$tree = vendor('Pc.Tree','',1);
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$userid = $_SESSION['userid'];
		$admin_username = cookie('admin_username');
		
		$table_name = $this->db->tableName;
	
		$result = $this->db->order('listorder ASC,id DESC')->select();
		$array = array();
		foreach($result as $r) {
			$r['cname'] = L($r['name']);
			$r['str_manage'] = '<a href="?m=Admin&c=Menu&a=add&parentid='.$r['id'].'&menuid='.$_GET['menuid'].'">'.L('add_submenu').'</a> | <a href="?m=Admin&c=Menu&a=edit&id='.$r['id'].'&menuid='.$_GET['menuid'].'">'.L('modify').'</a> | <a href="javascript:confirmurl(\'?m=Admin&c=Menu&a=delete&id='.$r['id'].'&menuid='.$_GET['menuid'].'\',\''.L('confirm',array('message'=>$r['cname'])).'\')">'.L('delete').'</a> ';
			$array[] = $r;
		}

		$str  = "<tr>
					<td align='center'><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input-text-c'></td>
					<td align='center'>\$id</td>
					<td >\$spacer\$cname</td>
					<td align='center'>\$str_manage</td>
				</tr>";
		$tree->init($array);
		$categorys = $tree->getTree(0, $str);
		include Admin::adminTpl('menu');
	}
	function add() {
		if(isset($_POST['dosubmit'])) {
			$this->db->data($_POST['info'])->add();
			//开发过程中用于自动创建语言包
			$file = APP_PATH.'languages/zh-cn/system_menu.lang.php';
			if(file_exists($file)) {
				$content = file_get_contents($file);
				$content = substr($content,0,-2);
				$key = $_POST['info']['name'];
				$data = $content."\$LANG['$key'] = '$_POST[language]';\r\n?>";
				file_put_contents($file,$data);
			} else {
				
				$key = $_POST['info']['name'];
				$data = "<?php\r\n\$LANG['$key'] = '$_POST[language]';\r\n?>";
				file_put_contents($file,$data);
			}
			//结束
			showmessage(L('add_success'));
		} else {
			$show_validator = '';
			$tree = vendor('Pc.Tree','',1);
			$result = $this->db->select();
			$array = array();
			foreach($result as $r) {
				$r['cname'] = L($r['name']);
				$r['selected'] = $r['id'] == $_GET['parentid'] ? 'selected' : '';
				$array[] = $r;
			}
			$str  = "<option value='\$id' \$selected>\$spacer \$cname</option>";
			$tree->init($array);
			$select_categorys = $tree->getTree(0, $str);
			
			include Admin::adminTpl('menu');
		}
	}
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		$this->db->where(array('id'=>$_GET['id']))->delete();
		showmessage(L('operation_success'));
	}
	
	function edit() {
		if(isset($_POST['dosubmit'])) {
			$id = intval($_POST['id']);
			//print_r($_POST['info']);exit;
			$this->db->data($_POST['info'])->where(array('id'=>$id))->save();
			//修改语言文件
			$file = APP_PATH.'Lang/zh-cn/system_menu.lang.php';
			require $file;
			$key = $_POST['info']['name'];
			if(!isset($LANG[$key])) {
				$content = file_get_contents($file);
				$content = substr($content,0,-2);
				$data = $content."\$LANG['$key'] = '$_POST[language]';\r\n?>";
				file_put_contents($file,$data);
			} elseif(isset($LANG[$key]) && $LANG[$key]!=$_POST['language']) {
				$content = file_get_contents($file);
				$content = str_replace($LANG[$key],$_POST['language'],$content);
				file_put_contents($file,$content);
			}
			
			//结束语言文件修改
			showmessage(L('operation_success'));
		} else {
			$show_validator = $array = $r = '';
			$tree = vendor('Pc.Tree','',1);
			$id = intval($_GET['id']);
			$r = $this->db->where(array('id'=>$id))->find();
			if($r) extract($r);
			$result = $this->db->select();
			foreach($result as $r) {
				$r['cname'] = L($r['name']);
				$r['selected'] = $r['id'] == $parentid ? 'selected' : '';
				$array[] = $r;
			}
			$str  = "<option value='\$id' \$selected>\$spacer \$cname</option>";
			$tree->init($array);
			$select_categorys = $tree->getTree(0, $str);
			include Admin::adminTpl('menu');
		}
	}
	
	/**
	 * 排序
	 */
	function listorder() {
		if(isset($_POST['dosubmit'])) {
			foreach($_POST['listorders'] as $id => $listorder) {
				$this->db->data(array('listorder'=>$listorder))->where(array('id'=>$id))->save();
			}
			showmessage(L('operation_success'));
		} else {
			showmessage(L('operation_failure'));
		}
	}
}
?>