<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
vendor('Pc.Form');
class RoleAction extends BaseAction {
	private $db, $privDb;
	function __construct() {
		parent::__construct();
		$this->db = M('AdminRole');
		$this->privDb = M('AdminRolePriv');
		$this->op = import('@.Admin.Util.RoleOp');
	}
	
	/**
	 * 角色管理列表
	 */
	public function init() {
		$infos = $this->db->order('listorder DESC, roleid DESC')->select();
		
		include Admin::adminTpl('role_list');
	}
	
	/**
	 * 添加角色
	 */
	public function add() {
		if(isset($_POST['dosubmit'])) {
			if(!is_array($_POST['info']) || empty($_POST['info']['rolename'])){
				showmessage(L('operation_failure'));
			}
			if($this->op->checkname($_POST['info']['rolename'])){
				showmessage(L('role_duplicate'));
			}
			$insert_id = $this->db->data($_POST['info'])->add();
			$this->_cache();
			if($insert_id){
				showmessage(L('operation_success'),'?m=Admin&c=Role&a=init');
			}
		} else {
			include Admin::adminTpl('role_add');
		}
		
	}
	
	/**
	 * 编辑角色
	 */
	public function edit() {
		if(isset($_POST['dosubmit'])) {
			$_POST['roleid'] = intval($_POST['roleid']);
			if(!is_array($_POST['info']) || empty($_POST['info']['rolename'])){
				showmessage(L('operation_failure'));
			}
			$this->db->data($_POST['info'])->where(array('roleid'=>$_POST['roleid']))->save();
			$this->_cache();
			showmessage(L('operation_success'),'?m=Admin&c=Role');
		} else {					
			$info = $this->db->where(array('roleid'=>$_GET['roleid']))->find();
			extract($info);		
			include Admin::adminTpl('role_edit');		
		}
	}
	
	/**
	 * 删除角色
	 */
	public function delete() {
		$roleid = intval($_GET['roleid']);
		if($roleid == '1') showmessage(L('this_object_not_del'), HTTP_REFERER);
		$this->db->where(array('roleid'=>$roleid))->delete();
		$this->privDb->where(array('roleid'=>$roleid))->delete();
		$this->_cache();
		showmessage(L('role_del_success'));
	}
	/**
	 * 更新角色排序
	 */
	public function listorder() {
		if(isset($_POST['dosubmit'])) {
			foreach($_POST['listorders'] as $roleid => $listorder) {
				$this->db->data(array('listorder'=>$listorder))->where(array('roleid'=>$roleid))->save();
			}
			showmessage(L('operation_success'));
		} else {
			showmessage(L('operation_failure'));
		}
	}
	
	/**
	 * 角色权限设置
	 */
	public function rolePriv() {
		$this->menuDb = M('Menu');
		$siteid = $siteid ? $siteid : Admin::getSiteid();
		if(isset($_POST['dosubmit'])){
			if (is_array($_POST['menuid']) && count($_POST['menuid']) > 0) {
			
				$this->privDb->where(array('roleid'=>$_POST['roleid'],'siteid'=>$_POST['siteid']))->delete();
				$menuinfo = $this->menuDb->field('`id`,`m`,`c`,`a`,`data`')->select();
				foreach ($menuinfo as $_v) $menu_info[$_v[id]] = $_v;
				foreach($_POST['menuid'] as $menuid){
					$info = array();
					$info = $this->op->getMenuinfo(intval($menuid),$menu_info);
					$info['roleid'] = $_POST['roleid'];
					$info['siteid'] = $_POST['siteid'];
					$this->privDb->data($info)->add();
				}
			} else {
				$this->privDb->where(array('roleid'=>$_POST['roleid'],'siteid'=>$_POST['siteid']))->delete();
			}
			$this->_cache();	
			showmessage(L('operation_success'), HTTP_REFERER);

		} else {
			$siteid = intval($_GET['siteid']);
			$roleid = intval($_GET['roleid']);
			if ($siteid) {
				$menu = vendor('Pc.Tree','',1);
				$menu->icon = array('│ ','├─ ','└─ ');
				$menu->nbsp = '&nbsp;&nbsp;&nbsp;';
				$result = $this->menuDb->select();
				$priv_data = $this->privDb->select(); //获取权限表数据
				$modules = 'Admin,Announce,Vote,System';
				foreach ($result as $n=>$t) {
					$result[$n]['cname'] = L($t['name']);
					$result[$n]['checked'] = ($this->op->isChecked($t,$_GET['roleid'],$siteid, $priv_data))? ' checked' : '';
					$result[$n]['level'] = $this->op->getLevel($t['id'],$result);
					$result[$n]['parentid_node'] = ($t['parentid'])? ' class="child-of-node-'.$t['parentid'].'"' : '';
				}
				$str  = "<tr id='node-\$id' \$parentid_node>
							<td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$cname</td>
						</tr>";
			
				$menu->init($result);
				$categorys = $menu->getTree(0, $str);
			}
			$show_header = true;
			$show_scroll = true;
			include Admin::adminTpl('role_priv');
		}
	}
	
	public function privSetting() {
		$sites = import('Sites');
		$sites_list = $sites->getList();
		$roleid = intval($_GET['roleid']);
		include Admin::adminTpl('role_priv_setting');
		
	}

	/**
	 * 更新角色状态
	 */
	public function changeStatus(){
		$roleid = intval($_GET['roleid']);
		$disabled = intval($_GET['disabled']);
		$this->db->data(array('disabled'=>$disabled))->where(array('roleid'=>$roleid))->save();
		$this->_cache();
		showmessage(L('operation_success'),'?m=Admin&c=Role');
	}
	/**
	 * 成员管理
	 */
	public function memberManage() {
		$this->adminDb = M('Admin');
		$roleid = intval($_GET['roleid']);
		$roles = cache('role','Commons');
		$infos = $this->adminDb->where(array('roleid'=>$roleid))->select();
		include Admin::adminTpl('admin_list');
	}
		
	/**
	 * 设置栏目权限
	 */
	public function settingCatPriv() {
		$roleid = isset($_GET['roleid']) && intval($_GET['roleid']) ? intval($_GET['roleid']) : showmessage(L('illegal_parameters'), HTTP_REFERER);
		$op = isset($_GET['op']) && intval($_GET['op']) ? intval($_GET['op']) : '';
		switch ($op) {
			case 1:
			$siteid = isset($_GET['siteid']) && intval($_GET['siteid']) ? intval($_GET['siteid']) : showmessage(L('illegal_parameters'), HTTP_REFERER);
			import('@.Admin.Util.RoleCat','',0);
			$category = RoleCat::getCategory($siteid);
			//获取角色当前权限设置
			$priv = RoleCat::getRoleid($roleid, $siteid);
			//加载tree
			$tree = vendor('Pc.Tree','',1);
			$categorys = array();
			foreach ($category as $k=>$v) {
				if ($v['type'] == 1) {
					$v['disabled'] = 'disabled';
					$v['init_check'] = '';
					$v['add_check'] = '';
					$v['delete_check'] = '';
					$v['listorder_check'] = '';
					$v['push_check'] = '';
					$v['move_check'] = '';
				} else {
					$v['disabled'] = '';
					
					$v['add_check'] = isset($priv[$v['catid']]['add']) ? 'checked' : '';
					$v['delete_check'] = isset($priv[$v['catid']]['delete']) ? 'checked' : '';
					$v['listorder_check'] = isset($priv[$v['catid']]['listorder']) ? 'checked' : '';
					$v['push_check'] = isset($priv[$v['catid']]['push']) ? 'checked' : '';
					$v['move_check'] = isset($priv[$v['catid']]['remove']) ? 'checked' : '';
					$v['edit_check'] = isset($priv[$v['catid']]['edit']) ? 'checked' : '';
				}
				$v['init_check'] = isset($priv[$v['catid']]['init']) ? 'checked' : '';
				$category[$k] = $v;
			}
			$show_header = true;
			$str = "<tr>
					<td align='center'><input type='checkbox'  value='1' onclick='select_all(\$catid, this)' ></td>
				  <td>\$spacer\$catname</td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$init_check  value='init' ></td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$disabled \$add_check value='add' ></td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$disabled \$edit_check value='edit' ></td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$disabled \$delete_check  value='delete' ></td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$disabled \$listorder_check value='listorder' ></td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$disabled \$push_check value='push' ></td>
				  <td align='center'><input type='checkbox' name='priv[\$catid][]' \$disabled \$move_check value='remove' ></td>
			  </tr>";
			
			$tree->init($category);
			$categorys = $tree->getTree(0, $str);
			include Admin::adminTpl('role_cat_priv_list');
		break;
		
		case 2:
			$siteid = isset($_GET['siteid']) && intval($_GET['siteid']) ? intval($_GET['siteid']) : showmessage(L('illegal_parameters'), HTTP_REFERER);
			import('@.Admin.Util.RoleCat', '', 0);
			RoleCat::updataPriv($roleid, $siteid, $_POST['priv']);
			showmessage(L('operation_success'),'?m=Admin&c=Role&a=init', '', 'edit');
			break;
		
		default:
			$sites = import('Sites');
			$sites_list = $sites->getList();
			include Admin::adminTpl('role_cat_priv');
		break;
		}
	}	
	/**
	 * 角色缓存
	 */
	private function _cache() {

		$infos = $this->db->where(array('disabled'=>'0'))->field('`roleid`,`rolename`')->order('roleid ASC')->select();
		$role = array();
		foreach ($infos as $info){
			$role[$info['roleid']] = $info['rolename'];
		}
		$this->_cache_siteid($role);
		cache('role', $role,'Commons');
		return $infos;
	}
	
	/**
	 * 缓存站点数据
	 */
	private function _cache_siteid($role) {
		$sitelist = array();
		foreach($role as $n=>$r) {
			$sitelists = $this->privDb->where(array('roleid'=>$n))->field('siteid')->order('siteid')->select();
			foreach($sitelists as $site) {
				foreach($site as $v){
					$sitelist[$n][] = intval($v);
				}
			}
		}
		if(is_array($sitelist)) {
			$sitelist = @array_map("array_unique", $sitelist);
			cache('role_siteid', $sitelist,'Commons');
		}								
		return $sitelist;
	}
	
}
?>