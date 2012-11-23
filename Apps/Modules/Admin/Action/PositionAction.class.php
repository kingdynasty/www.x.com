<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
vendor('Pc.Form');
class PositionAction extends BaseAction {
	private $db, $dbData, $dbContent;
	function __construct() {
		parent::__construct();
		$this->db = M('Position');
		$this->dbData = M('PositionData');
		$this->dbContent = D('Content');			
		$this->sites = import('Sites');
	}
	
	public function init() {
			$infos = array();
			$where = '';
			$current_siteid = Admin::getSiteid();
			$category = cache('category_content_'.$current_siteid,'Commons');
			$model = cache('model','Commons');
			$where = "`siteid`='$current_siteid' OR `siteid`='0'";
			$page = $_GET['page'] ? $_GET['page'] : '1';
			$infos = $this->db->where($where)->order('listorder DESC,posid DESC')->page($page, 20)->select();
			$pages = $this->db->pages;
			$show_dialog = true;
			$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Position&a=add\', title:\''.L('posid_add').'\', width:\'500\', height:\'360\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('posid_add'));
			include Admin::adminTpl('position_list');
	}
	
	/**
	 * 推荐位添加
	 */
	public function add() {
		if(isset($_POST['dosubmit'])) {
			if(!is_array($_POST['info']) || empty($_POST['info']['name'])){
				showmessage(L('operation_failure'));
			}
			$_POST['info']['siteid'] = intval($_POST['info']['modelid']) ? get_siteid() : 0;
			$_POST['info']['listorder'] = intval($_POST['info']['listorder']);
			$_POST['info']['maxnum'] = intval($_POST['info']['maxnum']);
			$insert_id = $this->db->data($_POST['info'])->add();
			$this->_set_cache();
			if($insert_id){
				showmessage(L('operation_success'), '', '', 'add');
			}
		} else {
			vendor('Pc.Form','',0);
			$this->sitemodelDb = D('Sitemodel');
			$sitemodel = $sitemodel = array();
			$sitemodel = cache('model','Commons');
			foreach($sitemodel as $value){
				if($value['siteid'] == get_siteid())$modelinfo[$value['modelid']]=$value['name'];
			}			
			$show_header = $show_validator = true;
			include Admin::adminTpl('position_add');
		}
		
	}
	
	/**
	 * 推荐位编辑
	 */
	public function edit() {
		if(isset($_POST['dosubmit'])) {
			$_POST['posid'] = intval($_POST['posid']);
			if(!is_array($_POST['info']) || empty($_POST['info']['name'])){
				showmessage(L('operation_failure'));
			}
			$_POST['info']['siteid'] = intval($_POST['info']['modelid']) ? get_siteid() : 0;
			$_POST['info']['listorder'] = intval($_POST['info']['listorder']);
			$_POST['info']['maxnum'] = intval($_POST['info']['maxnum']);			
			$this->db->data($_POST['info'])->where(array('posid'=>$_POST['posid']))->save();
			$this->_set_cache();
			showmessage(L('operation_success'), '', '', 'edit');
		} else {
			$info = $this->db->where(array('posid'=>intval($_GET['posid'])))->find();
			extract($info);
			vendor('Pc.Form','',0);
			$this->sitemodelDb = D('Sitemodel');
			$sitemodel = $sitemodel = array();
			$sitemodel = cache('model','Commons');
			foreach($sitemodel as $value){
				if($value['siteid'] == get_siteid())$modelinfo[$value['modelid']]=$value['name'];
			}
			$show_validator = $show_header = $show_scroll = true;
			include Admin::adminTpl('position_edit');
		}

	}
	
	/**
	 * 推荐位删除
	 */
	public function delete() {
		$posid = intval($_GET['posid']);
		$this->db->where(array('posid'=>$posid))->delete();
		$this->_set_cache();
		showmessage(L('posid_del_success'),'?m=Admin&c=Position');
	}
	
	/**
	 * 推荐位排序
	 */
	public function listorder() {
		if(isset($_POST['dosubmit'])) {
			foreach($_POST['listorders'] as $posid => $listorder) {
				$this->db->data(array('listorder'=>$listorder))->where(array('posid'=>$posid))->save();
			}
			$this->_set_cache();
			showmessage(L('operation_success'),'?m=Admin&c=Position');
		} else {
			showmessage(L('operation_failure'),'?m=Admin&c=Position');
		}
	}
	
	/**
	 * 推荐位文章统计
	 * @param $posid 推荐位ID
	 */
	public function contentCount($posid) {
		$posid = intval($posid);
		$where = array('posid'=>$posid);
		$infos = $this->dbData->where($where)->field('count(*) as count')->find();
		return $infos['count'];
	}
	
	/**
	 * 推荐位文章列表
	 */
	public function publicItem() {	
		if(isset($_POST['dosubmit'])) {
			$items = count($_POST['items']) > 0  ? $_POST['items'] : showmessage(L('posid_select_to_remove'),HTTP_REFERER);
			if(is_array($items)) {
				$sql = array();
				foreach ($items as $item) {
					$_v = explode('-', $item);
					$sql['id'] = $_v[0];
					$sql['modelid']= $_v[1];
					$sql['posid'] = intval($_POST['posid']);
					$this->dbData->delete($sql);
					$this->contentPos($sql['id'],$sql['modelid']);		
				}
			}
			showmessage(L('operation_success'),HTTP_REFERER);
		} else {
			$posid = intval($_GET['posid']);
			$MODEL = cache('model','Commons');
			$siteid = Admin::getSiteid();
			$CATEGORY = cache('category_content_'.$siteid,'Commons');
			$page = $_GET['page'] ? $_GET['page'] : '1';
			$pos_arr = $this->dbData->where(array('posid'=>$posid,'siteid'=>$siteid))->order('listorder DESC')->page($page, $pagesize = 20)->select();
			$pages = $this->dbData->pages;
			$infos = array();
			foreach ($pos_arr as $_k => $_v) {
				$r = string2array($_v['data']);
				$r['catname'] = $CATEGORY[$_v['catid']]['catname'];
				$r['modelid'] = $_v['modelid'];
				$r['posid'] = $_v['posid'];
				$r['id'] = $_v['id'];
				$r['listorder'] = $_v['listorder'];
				$r['catid'] = $_v['catid'];
				$r['url'] = go($_v['catid'], $_v['id']);
				$key = $r['modelid'].'-'.$r['id'];
				$infos[$key] = $r;
				
			}
			$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Admin&c=Position&a=add\', title:\''.L('posid_add').'\', width:\'500\', height:\'300\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', L('posid_add'));			
			include Admin::adminTpl('position_items');			
		}
	}
	/**
	 * 推荐位文章管理
	 */
	public function publicItemManage() {
		if(isset($_POST['dosubmit'])) {
			$posid = intval($_POST['posid']);
			$modelid = intval($_POST['modelid']);	
			$id= intval($_POST['id']);
			$pos_arr = $this->dbData->where(array('id'=>$id,'posid'=>$posid,'modelid'=>$modelid))->find();
			$array = string2array($pos_arr['data']);
			$array['inputtime'] = strtotime($_POST['info']['inputtime']);
			$array['title'] = trim($_POST['info']['title']);
			$array['thumb'] = trim($_POST['info']['thumb']);
			$array['description'] = trim($_POST['info']['description']);
			$thumb = $_POST['info']['thumb'] ? 1 : 0;
			$array = array('data'=>array2string($array),'synedit'=>intval($_POST['synedit']),'thumb'=>$thumb);
			$this->dbData->data($array)->where(array('id'=>$id,'posid'=>$posid,'modelid'=>$modelid))->save();
			showmessage(L('operation_success'),'','','edit');
		} else {
			$posid = intval($_GET['posid']);
			$modelid = intval($_GET['modelid']);	
			$id = intval($_GET['id']);		
			if($posid == 0 || $modelid == 0) showmessage(L('linkage_parameter_error'), HTTP_REFERER);
			$pos_arr = $this->dbData->where(array('id'=>$id,'posid'=>$posid,'modelid'=>$modelid))->find();
			extract(string2array($pos_arr['data']));
			$synedit = $pos_arr['synedit'];
			$show_validator = true;
			$show_header = true;		
			include Admin::adminTpl('position_item_manage');			
		}
	
	}
	/**
	 * 推荐位文章排序
	 */
	public function publicItemListorder() {
		if(isset($_POST['posid'])) {
			foreach($_POST['listorders'] as $_k => $listorder) {
				$pos = array();
				$pos = explode('-', $_k);
				$this->dbData->data(array('listorder'=>$listorder))->where(array('id'=>$pos[1],'catid'=>$pos[0],'posid'=>$_POST['posid']))->save();
			}
			showmessage(L('operation_success'),HTTP_REFERER);
			
		} else {
			showmessage(L('operation_failure'),HTTP_REFERER);
		}
	}
	/**
	 * 推荐位添加栏目加载
	 */
	public function publicCategoryLoad() {
		$modelid = intval($_GET['modelid']);
		vendor('Pc.Form','',0);
		$category = Form::selectCategory('','','name="info[catid]"',L('please_select_parent_category'),$modelid);
		echo $category;
	}
	
	private function _set_cache() {
		$infos = $this->db->limit(1000)->order('listorder DESC')->select();
		$positions = array();
		foreach ($infos as $info){
			$positions[$info['posid']] = $info;
		}
		cache('position', $positions,'Commons');
		return $infos;
	}
	
	private function contentPos($id,$modelid) {
		$id = intval($id);
		$modelid = intval($modelid);
		$MODEL = cache('model','Commons');
		$this->dbContent->tableName = $this->dbContent->tablePrefix.$MODEL[$modelid]['tablename'];		
		$posids = $this->dbData->where(array('id'=>$id,'modelid'=>$modelid))->find() ? 1 : 0;
		return $this->dbContent->data(array('posids'=>$posids))->where(array('id'=>$id))->save();
	}	
}
?>