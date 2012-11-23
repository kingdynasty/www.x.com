<?php
/**
 * 
 * 更新缓存类
 *
 */

defined('APP_NAME') or exit('No permission resources.');
class CacheApi {
	
	private $db;
	
	public function __construct() {
		$this->db = '';
		$this->siteid = get_siteid();
	}
	
	/**
	 * 更新缓存
	 * @param string $model 方法名
	 * @param string $param 参数
	 */
	public function cache($model = '', $param = '') {
		if (file_exists(APP_PATH.'model/'.$model.'_model.class.php')) {
			$this->db = PcBase::loadModel($model.'_model');
			if ($param) {
				$this->$model($param);
			} else {
				$this->$model();
			}
		} else {
			$this->$model();
		}
	}
	
	
	/**
	 * 更新站点缓存方法
	 */
	public function cacheSite() {
		$site = PcBase::loadAppClass('sites', 'admin');
		$site->setCache();
	}
	
	/**
	 * 更新栏目缓存方法
	 */
	public function category() {
		$categorys = array();
		$models = cache('model','Commons');
		foreach ($models as $modelid=>$model) {
			$datas = $this->db->pcSelect(array('modelid'=>$modelid),'catid,type,items',10000);
			$array = array();
			foreach ($datas as $r) {
				if($r['type']==0) $array[$r['catid']] = $r['items'];
			}
			cache('category_items_'.$modelid, $array,'Commons');
		}
		$array = array();
		$categorys = $this->db->pcSelect('`module`=\'content\'','catid,siteid',20000,'listorder ASC');
		foreach ($categorys as $r) {
			$array[$r['catid']] = $r['siteid'];
		}
		cache('category_content',$array,'Commons');
		$categorys = $this->categorys = array();
		$this->categorys = $this->db->pcSelect(array('siteid'=>$this->siteid, 'module'=>'content'),'*',10000,'listorder ASC');
		foreach($this->categorys as $r) {
			unset($r['module']);
			$setting = string2array($r['setting']);
			$r['create_to_html_root'] = $setting['create_to_html_root'];
			$r['ishtml'] = $setting['ishtml'];
			$r['content_ishtml'] = $setting['content_ishtml'];
			$r['category_ruleid'] = $setting['category_ruleid'];
			$r['show_ruleid'] = $setting['show_ruleid'];
			$r['workflowid'] = $setting['workflowid'];
			$r['isdomain'] = '0';
			if(!preg_match('/^(http|https):\/\//', $r['url'])) {
				$r['url'] = siteurl($r['siteid']).$r['url'];
			} elseif ($r['ishtml']) {
				$r['isdomain'] = '1';
			}
			$categorys[$r['catid']] = $r;
		}
		cache('category_content_'.$this->siteid,$categorys,'Commons');
		return true;
	}
	
	/**
	 * 更新下载服务器缓存方法
	 */
	public function downservers () {
		$infos = $this->db->select();
		foreach ($infos as $info){
			$servers[$info['id']] = $info;
		}
		cache('downservers', $servers,'Commons');
		return $infos;
	}
	
	/**
	 * 更新敏感词缓存方法
	 */
	public function badword() {
		$infos = $this->db->pcSelect('','badid,badword','','badid ASC');
		cache('badword', $infos, 'Commons');
		return true;
	}
	
	/**
	 * 更新ip禁止缓存方法
	 */
	public function ipbanned() {
		$infos = $this->db->pcSelect('', '`ip`,`expires`', '', 'ipbannedid desc');
		cache('ipbanned', $infos, 'Commons');
		return true;
	}
	
	/**
	 * 更新关联链接缓存方法
	 */
	public function keylink() {
		$infos = $this->db->pcSelect('','word,url','','keylinkid ASC');
		$datas = $rs = array();
		foreach($infos as $r) {
			$rs[0] = $r['word'];
			$rs[1] = $r['url'];
			$datas[] = $rs;
		}
		cache('keylink', $datas, 'Commons');
		return true;
	}
	
	/**
	 * 更新联动菜单缓存方法
	 */
	public function linkage() {
		$infos = $this->db->pcSelect(array('keyid'=>0));
		foreach ($infos as $r) {
			$linkageid = intval($r['linkageid']);
			$r = $this->db->where(array('linkageid'=>$linkageid))->field('name,siteid,style')->find();
			$info['title'] = $r['name'];
			$info['style'] = $r['style'];
			$info['siteid'] = $r['siteid'];
			$info['data'] = $this->submenulist($linkageid);
			cache($linkageid, $info,'linkage');
		}
		return true;
	}
	
	/**
	 * 子菜单列表
	 * @param intval $keyid 菜单id
	 */
	public function submenulist($keyid=0) {
		$keyid = intval($keyid);
		$datas = array();
		$where = ($keyid > 0) ? array('keyid'=>$keyid) : '';
		$result = $this->db->pcSelect($where,'*','','listorder ,linkageid');
		foreach($result as $r) {
			$datas[$r['linkageid']] = $r;
		}
		return $datas;
	}
	
	/**
	 * 更新推荐位缓存方法
	 */
	public function position () {
		$infos = $this->db->pcSelect('','*',1000,'listorder DESC');
		foreach ($infos as $info){
			$positions[$info['posid']] = $info;
		}
		cache('position', $positions,'Commons');
		return $infos;
	}
	
	/**
	 * 更新投票配置
	 */
	public function voteSetting() {
		$m_db = M('Module');
		$data = $m_db->pcSelect(array('module'=>'vote'));
		$setting = string2array($data[0]['setting']);
		cache('vote', $setting, 'Commons');
	}
	
	/**
	 * 更新友情链接配置
	 */
	public function linkSetting() {
		$m_db = M('Module');
		$data = $m_db->pcSelect(array('module'=>'link'));
		$setting = string2array($data[0]['setting']);
		cache('link', $setting, 'Commons');
	}
	
	/**
	 * 更新管理员角色缓存方法
	 */
	public function adminRole() {
		$infos = $this->db->pcSelect(array('disabled'=>'0'), $data = '`roleid`,`rolename`', '', 'roleid ASC');
		foreach ($infos as $info){
			$role[$info['roleid']] = $info['rolename'];
		}
		$this->cacheSiteid($role);
		cache('role', $role,'Commons');
		return $infos;
	}
	
	/**
	 * 更新管理员角色缓存方法
	 */
	public function cacheSiteid($role) {
		$priv_db = M('AdminRolePriv');
		$sitelist = array();
		foreach($role as $n=>$r) {
			$sitelists = $priv_db->pcSelect(array('roleid'=>$n),'siteid', '', 'siteid');
			foreach($sitelists as $site) {
				foreach($site as $v){
					$sitelist[$n][] = intval($v);
				}
			}
		}
		$sitelist = @array_map("array_unique", $sitelist);
		cache('role_siteid', $sitelist,'Commons');
		return $sitelist;
	}
	
	/**
	 * 更新url规则缓存方法
	 */
	public function urlrule() {
		$datas = $this->db->pcSelect('','*','','','','urlruleid');
		$basic_data = array();
		foreach($datas as $roleid=>$r) {
			$basic_data[$roleid] = $r['urlrule'];;
		}
		cache('urlrules_detail',$datas,'Commons');
		cache('urlrules',$basic_data,'Commons');
	}
	
	/**
	 * 更新模块缓存方法
	 */
	public function module() {
		$modules = array();
		$modules = $this->db->pcSelect(array('disabled'=>0), '*', '', '', '', 'module');
		cache('modules', $modules, 'Commons');
		return true;
	}
	
	/**
	 * 更新模型缓存方法
	 */
	public function sitemodel() {
		define('MODEL_PATH', APP_PATH.'modules/content/fields/');
		define('CACHE_MODEL_PATH', CMS_PATH.'caches/caches_model/caches_data/');
		require MODEL_PATH.'fields.inc.php';
		//更新内容模型类：表单生成、入库、更新、输出
		$classtypes = array('form','input','update','output');
		foreach($classtypes as $classtype) {
			$cache_data = file_get_contents(MODEL_PATH.'content_'.$classtype.'.class.php');
			$cache_data = str_replace('}?>','',$cache_data);
			foreach($fields as $field=>$fieldvalue) {
				if(file_exists(MODEL_PATH.$field.'/'.$classtype.'.inc.php')) {
					$cache_data .= file_get_contents(MODEL_PATH.$field.'/'.$classtype.'.inc.php');
				}
			}
			$cache_data .= "\r\n } \r\n?>";
			file_put_contents(CACHE_MODEL_PATH.'content_'.$classtype.'.class.php',$cache_data);
			chmod(CACHE_MODEL_PATH.'content_'.$classtype.'.class.php',0777);
		}
		//更新模型数据缓存
		$model_array = array();
		$datas = $this->db->pcSelect(array('type'=>0));
		foreach ($datas as $r) {
			$model_array[$r['modelid']] = $r;
			$this->sitemodelField($r['modelid']);
		}
		cache('model', $model_array, 'Commons');
		return true;
	}
	
	/**
	 * 更新模型字段缓存方法
	 */
	public function sitemodelField($modelid) {
		$field_array = array();
		$db = M('SitemodelField');
		$fields = $db->pcSelect(array('modelid'=>$modelid,'disabled'=>0),'*',100,'listorder ASC');
		foreach($fields as $_value) {
			$setting = string2array($_value['setting']);
			$_value = array_merge($_value,$setting);
			$field_array[$_value['field']] = $_value;
		}
		cache('model_field_'.$modelid,$field_array,'model');
		return true;
	}
	
	/**
	 * 更新类别缓存方法
	 */
	public function type($param = '') {
		$datas = array();
		$result_datas = $this->db->pcSelect(array('siteid'=>get_siteid(),'module'=>$param),'*',1000,'listorder ASC,typeid ASC');
		foreach($result_datas as $_key=>$_value) {
			$datas[$_value['typeid']] = $_value;
		}
		if ($param=='search') {
			$this->searchType();
		} else {
			cache('type_'.$param, $datas, 'Commons');
		}
		return true;
	}
	
	/**
	 * 更新工作流缓存方法
	 */
	public function workflow() {
		$datas = array();
		$workflow_datas = $this->db->pcSelect(array('siteid'=>get_siteid()),'*',1000);
		foreach($workflow_datas as $_k=>$_v) {
			$datas[$_v['workflowid']] = $_v;
		}
		cache('workflow_'.get_siteid(),$datas,'Commons');
		return true;
	}
	
	/**
	 * 更新数据源缓存方法
	 */
	public function dbsource() {
		$db = M('Dbsource');
		$list = $db->select();
		$data = array();
		if ($list) {
			foreach ($list as $val) {
				$data[$val['name']] = array('hostname'=>$val['host'].':'.$val['port'], 'database' =>$val['dbname'] , 'db_tablepre'=>$val['dbtablepre'], 'username' =>$val['username'],'password' => $val['password'],'charset'=>$val['charset'],'debug'=>0,'pconnect'=>0,'autoconnect'=>0);
			}
		} else {
			return false;
		}
		return cache('dbsource', $data, 'Commons');
	}
	
	/**
	 * 更新会员组缓存方法
	 */
	public function memberGroup() {
		$grouplist = $this->db->listinfo('', '', 1, 100, 'groupid');
		cache('grouplist', $grouplist,'member');
		return true;
	}
	
	/**
	 * 更新会员配置缓存方法
	 */
	public function memberSetting() {
		$this->db = M('Module');
		$member_setting = $this->db->where(array('module'=>'member'))->field('setting')->find();
		$member_setting = string2array($member_setting['setting']);
		cache('member_setting', $member_setting, 'member');
		return true;
	}
	
	/**
	 * 更新会员模型缓存方法
	 */
	public function membermodel() {
		define('MEMBER_MODEL_PATH',APP_PATH.'modules/member/fields/');
		//模型缓存路径
		define('MEMBER_CACHE_MODEL_PATH',CMS_PATH.'caches/caches_model/caches_data/');
		
		$sitemodel_db = D('Sitemodel');
		$data = $sitemodel_db->pcSelect(array('type'=>2), "*", 1000, 'sort', '', 'modelid');
		cache('member_model', $data, 'Commons');
		
		require MEMBER_MODEL_PATH.'fields.inc.php';
		//更新内容模型类：表单生成、入库、更新、输出
		$classtypes = array('form','input','update','output');
		foreach($classtypes as $classtype) {
			$cache_data = file_get_contents(MEMBER_MODEL_PATH.'member_'.$classtype.'.class.php');
			$cache_data = str_replace('}?>','',$cache_data);
			foreach($fields as $field=>$fieldvalue) {
				if(file_exists(MEMBER_MODEL_PATH.$field.'/'.$classtype.'.inc.php')) {
					$cache_data .= file_get_contents(MEMBER_MODEL_PATH.$field.'/'.$classtype.'.inc.php');
				}
			}
			$cache_data .= "\r\n } \r\n?>";
			file_put_contents(MEMBER_CACHE_MODEL_PATH.'member_'.$classtype.'.class.php',$cache_data);
			chmod(MEMBER_CACHE_MODEL_PATH.'member_'.$classtype.'.class.php',0777);
		}
		
		return true;
	}
	
	/**
	 * 更新会员模型字段缓存方法
	 */
	public function memberModelField() {
		$member_model = cache('member_model', 'Commons');
		$this->db = M('SitemodelField');
		foreach ($member_model as $modelid => $m) {
			$field_array = array();
			$fields = $this->db->pcSelect(array('modelid'=>$modelid,'disabled'=>0),'*',100,'listorder ASC');
			foreach($fields as $_value) {
				$setting = string2array($_value['setting']);
				$_value = array_merge($_value,$setting);
				$field_array[$_value['field']] = $_value;
			}
			cache('model_field_'.$modelid,$field_array,'model');
		}
		return true;
	}
	
	/**
	 * 更新搜索配置缓存方法
	 */
	public function searchSetting() {	
		$this->db = M('Module');
		$setting = $this->db->where(array('module'=>'search'))->field('setting')->find();
		$setting = string2array($setting['setting']);
		cache('search', $setting, 'search');
		return true;
	}
	
	/**
	 * 更新搜索类型缓存方法
	 */
	public function searchType() {
		$sitelist = cache('sitelist','Commons');
		foreach ($sitelist as $siteid=>$_v) {
			$datas = $search_model = array();
			$result_datas = $result_datas2 = $this->db->pcSelect(array('siteid'=>$siteid,'module'=>'search'),'*',1000,'listorder ASC');
			foreach($result_datas as $_key=>$_value) {
				if(!$_value['modelid']) continue;
				$datas[$_value['modelid']] = $_value['typeid'];
				$search_model[$_value['modelid']]['typeid'] = $_value['typeid'];
				$search_model[$_value['modelid']]['name'] = $_value['name'];
				$search_model[$_value['modelid']]['sort'] = $_value['listorder'];
			}
			cache('type_model_'.$siteid,$datas,'search');
			$datas = array();	
			foreach($result_datas2 as $_key=>$_value) {
				if($_value['modelid']) continue;
				$datas[$_value['typedir']] = $_value['typeid'];
				$search_model[$_value['typedir']]['typeid'] = $_value['typeid'];
				$search_model[$_value['typedir']]['name'] = $_value['name'];
			}
			cache('type_module_'.$siteid,$datas,'search');
			//搜索header头中使用类型缓存
			cache('search_model_'.$siteid,$search_model,'search');
		}
		return true;
	}
	
	/**
	 * 更新专题缓存方法
	 */
	public function special() {
		$specials = array();
		$result = $this->db->pcSelect(array('disabled'=>0), '`id`, `siteid`, `title`, `url`, `thumb`, `banner`, `ishtml`', '', '`listorder` DESC, `id` DESC');
		foreach($result as $r) {
			$specials[$r['id']] = $r;
		}
		cache('special', $specials, 'Commons');
		return true;
	}
	
	/**
	 * 更新网站配置方法
	 */
	public function setting() {
		$this->db = M('Module');
		$result = $this->db->where(array('module'=>'admin'))->find();
		$setting = string2array($result['setting']);
		cache('common', $setting,'Commons');
		return true;
	}
	
	/**
	 * 更新数据源模型缓存方法
	 */
	public function database() {
		$module = $M = array();
		$M = cache('modules', 'Commons');
		if (is_array($M)) {
			foreach ($M as $key => $m) {
				if (file_exists(APP_PATH.'modules/'.$key.'/classes/'.$key.'_tag.class.php') && !in_array($key, array('message', 'block'))) {
					$module[$key] = $m['name'];
				}
			}
		}
		$filepath = CACHE_PATH.'configs/';
		$module = "<?php\nreturn ".var_export($module, true).";\n?>";
		return $file_size = C('lock_ex') ? file_put_contents($filepath.'modules.php', $module, LOCK_EX) : file_put_contents($filepath.'modules.php', $module);
	}
	
	/**
	 * 根据数据库记录更新缓存
	 */
	public function cache2database() {
		$cache = M('Cache');
		if (!isset($_GET['pages']) && empty($_GET['pages'])) {
			$r = $cache->where(array())->field('COUNT(*) AS num')->find();
			if ($r['num']) {
				$total = $r['num'];
				$pages = ceil($total/20);
			} else {
				$pages = 1;
			}
		} else {
			$pages = intval($_GET['pages']);
		}
		$currpage = max(intval($_GET['currpage']), 1);
		$offset = ($currpage-1)*20;
		$result = $cache->select(array(), '*', $offset.', 20', 'filename ASC');
		if (is_array($result) && !empty($result)) {
			foreach ($result as $re) {
				if (!file_exists(CACHE_PATH.$re['path'].$re['filename'])) {
					$filesize = C('lock_ex') ? file_put_contents(CACHE_PATH.$re['path'].$re['filename'], $re['data'], LOCK_EX) : file_put_contents(CACHE_PATH.$re['path'].$re['filename'], $re['data']);
				} else {
					continue;
				}
			}
		}
		$currpage++;
		if ($currpage>$pages) {
			return true;
		} else {
			echo '<script type="text/javascript">window.parent.addtext("<li>'.L('part_cache_success').($currpage-1).'/'.$pages.'..........</li>");</script>';
			showmessage(L('part_cache_success'), '?m=Admin&c=CacheAll&a=init&page='.$_GET['page'].'&currpage='.$currpage.'&pages='.$pages.'&dosubmit=1',0);
		}
	}
	
	/**
	 * 更新删除缓存文件方法
	 */
	public function delFile() {
		$path = CMS_PATH.'caches/caches_template/';
		$files = glob($path.'*');
		load('dir');
		if (is_array($files)) {
			foreach ($files as $f) {
				$dir = basename($f);
				if (!in_array($dir, array('block', 'dbsource'))) {
					dir_delete($path.$dir);
				}
			}
		}
		$path = CMS_PATH.'caches/caches_tpl_data/caches_data/';
		$files = glob($path.'*');
		if (is_array($files)) {
			foreach ($files as $f) {
				$dir = basename($f);
				@unlink($path.$dir);
			}
		}
		return true;
	}

	/**
	 * 更新来源缓存方法
	 */
	public function copyfrom() {
		$infos = $this->db->pcSelect('','*','','listorder DESC','','id');
		cache('copyfrom', $infos, 'admin');
		return true;
	}
	/**
	 * 同步视频模型栏目
	 */
	public function videoCategoryTb() {
		if (module_exists('video')) {
			$setting = cache('video', 'video');
			PcBase::loadAppClass('ku6api', 'video', 0);
			$ku6api = new ku6api($setting['sn'], $setting['skey']);
			$ku6api->getCategorys();
		}
		return true;
	}
}