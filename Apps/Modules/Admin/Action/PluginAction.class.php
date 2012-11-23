<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
vendor('Pc.Form');
load('plugin');
class PluginAction extends BaseAction {
	private $db,$db_var;
	function __construct() {
		parent::__construct();
		$this->db = M('Plugin');
		$this->dbVar = M('PluginVar');
		
	}
	
	/**
	 * 应用配置信息
	 */
	public function init() {
		$show_validator = true;
		$show_dialog = true;
		if($pluginfo = $this->db->order('disable DESC,listorder DESC')->select()) {
			foreach ($pluginfo as $_k=>$_r) {
				if(file_exists(APP_PATH.'Plugin/'.$_r['dir'].'/'.$_r['dir'].'.class.php')){
					$pluginfo[$_k]['url'] = 'plugin.php?id='.$_r['dir'];
				} else {
					$pluginfo[$_k]['url'] = '';
				}
  			 	$pluginfo[$_k]['dir'] = $_r['dir'].'/';	
			}		
		}
		
		include Admin::adminTpl('plugin_list');
	}
	
	/**
	 * 应用导入\安装
	 */
	 
	public function import() {
		if(!isset($_GET['dir'])) {
			$plugnum = 1;
			$installsdir = array();
			if($installs_pluginfo = $this->db->select()) {
				foreach ($installs_pluginfo as $_r) {
	  			 	$installsdir[] = $_r['dir'];	
				}		
			}	
			$pluginsdir = dir(APP_PATH.'Plugin');
			while (false !== ($entry = $pluginsdir->read())) {
				$config_file = '';
				$plugin_data = array();
				if(!in_array($entry, array('.', '..')) && is_dir(APP_PATH.'Plugin/'.$entry) && !in_array($entry, $installsdir) && !$this->db->where(array('identification'=>$entry))->find()) {
					$config_file = APP_PATH.'Plugin/'.$entry.'/Plugin'.$entry.'.cfg.php';
					if(file_exists($config_file)) {
						$plugin_data = @require($config_file);					
		  			 	$pluginfo[$plugnum]['name'] = $plugin_data['plugin']['name'];
		  			 	$pluginfo[$plugnum]['version'] = $plugin_data['plugin']['version'];
		  			 	$pluginfo[$plugnum]['copyright'] = $plugin_data['plugin']['copyright'];
		  			 	$pluginfo[$plugnum]['dir'] = $entry;
		  			 	$plugnum++;
					}
				}
			}		
			include Admin::adminTpl('plugin_list_import');
		} else {
			$dir = trim($_GET['dir']);
			$license = 0;
			$config_file = APP_PATH.'Plugin/'.$dir.'/plugin_'.$dir.'.cfg.php';
			if(file_exists($config_file)) {
				$plugin_data = @require($config_file);
				$license = ($plugin_data['license'] == '' || !isset($plugin_data['license'])) ? 0 : 1;
			}
			if(empty($_GET['license']) && $license) {
				$submit_url = '?m=Admin&c=Plugin&a=import&dir='.$dir.'&license=1&pc_hash='. $_SESSION['pc_hash'].'&menuid='.$_GET['menuid'];
			} else {
				$submit_url = '?m=Admin&c=Plugin&a=install&dir='.$dir.'&pc_hash='. $_SESSION['pc_hash'].'&menuid='.$_GET['menuid'];
			}	
				$show_header = 0;
			include Admin::adminTpl('plugin_import_confirm');
		}
	}
	/**
	 * 应用删除程序
	 */
	public function delete() {
		if(isset($_POST['dosubmit'])) {
			$pluginid = intval($_POST['pluginid']);
			$plugin_data =  $this->db->where(array('pluginid'=>$pluginid))->find();
			$op_status = FALSE;	
			$dir = $plugin_data['dir'];
			$config_file = APP_PATH.'Plugin/'.$dir.'/Plugin'.$dir.'.cfg.php';	
			if(file_exists($config_file)) {
				$plugin_data = @require($config_file);
			}		
			$filename = APP_PATH.'Plugin/'.$dir.'/'.$plugin_data['plugin']['uninstallfile'];
			if(file_exists($filename)) {
				@include_once $filename;
			} else {
				showmessage(L('plugin_lacks_uninstall_file','','plugin'),HTTP_REFERER);
			}
			if($op_status) {
				$this->db->where(array('pluginid'=>$pluginid))->delete();
				$this->dbVar->where(array('pluginid'=>$pluginid))->delete();
				cache($dir,NULL,'Plugins');
				cache($dir.'_var',NULL,'Plugins');
				$this->setHookCache();
				if($plugin_data['plugin']['iframe']) {
					load('dir');
					if(!dir_delete(APP_PATH.'plugin/'.$dir)) {
						showmessage(L('plugin_uninstall_success_no_delete','','plugin'),'?m=Admin&c=Plugin');
					}
				}
				showmessage(L('plugin_uninstall_success','','plugin'),'?m=Admin&c=Plugin');
			} else {
				showmessage(L('plugin_uninstall_fail','','plugin'),'?m=Admin&c=Plugin');
			}	
		} else {
			$show_header = 0;
			$pluginid = intval($_GET['pluginid']);
			$plugin_data =  $this->db->where(array('pluginid'=>$pluginid))->find();
			include Admin::adminTpl('plugin_delete_confirm');			
		}

	}
	
	/**
	 * 应用安装
	 */	
	public function install() {
		$op_status = FALSE;
		$dir = trim($_GET['dir']);
		$config_file = APP_PATH.'Plugin/'.$dir.'/plugin_'.$dir.'.cfg.php';		
		if(file_exists($config_file)) {
			$plugin_data = @require($config_file);
		} else {
			showmessage(L('plugin_config_not_exist','','plugin'));
		}
		$app_status  = app_validity_check($plugin_data['appid']);
		if($app_status != 2){
			$app_msg = $app_status == '' ? L('plugin_not_exist_or_pending','','plugin') : ($app_status == 0 || $app_status == 1 ? L('plugin_developing','','plugin') : L('plugin_be_locked','','plugin'));
			showmessage($app_msg);
		}
		if($plugin_data['version'] && $plugin_data['version']!=C('pc_version')) {
			showmessage(L('plugin_incompatible','','plugin'));
		}
		
		if($plugin_data['dir'] == '' || $plugin_data['identification'] == '' || $plugin_data['identification']!=$plugin_data['dir']) {
			showmessage(L('plugin_lack_of_necessary_configuration_items','','plugin'));
		}
		
		if(!pluginkey_check($plugin_data['identification'])) {
			showmessage(L('plugin_illegal_id','','plugin'));
		}
		if(is_array($plugin_data['plugin_var'])) {
			foreach($plugin_data['plugin_var'] as $config) {
				if(!pluginkey_check($config['fieldname'])) {
					showmessage(L('plugin_illegal_variable','','plugin'));
				}
			}
		}
		if($this->db->where(array('identification'=>$plugin_data['identification']))->find()) {
			showmessage(L('plugin_duplication_name','','plugin'));
		};				
		$filename = APP_PATH.'Plugin/'.$dir.'/'.$plugin_data['plugin']['installfile'];
		
		if(file_exists($filename)) {
			@include_once $filename;
		} 
		
		if($op_status) {	
			//向插件表中插入数据
			
			$plugin = array('name'=>new_addslashes($plugin_data['plugin']['name']),'identification'=>$plugin_data['identification'],'appid'=>$plugin_data['appid'],'description'=>new_addslashes($plugin_data['plugin']['description']),'dir'=>$plugin_data['dir'],'copyright'=>new_addslashes($plugin_data['plugin']['copyright']),'setting'=>array2string($plugin_data['plugin']['setting']),'iframe'=>array2string($plugin_data['plugin']['iframe']),'version'=>$plugin_data['plugin']['version'],'disable'=>'0');
			
			$pluginid = $this->db->data($plugin)->add();
			
			//向插件变量表中插入数据
			if(is_array($plugin_data['plugin_var'])) {
				foreach($plugin_data['plugin_var'] as $config) {
					$plugin_var = array();
					$plugin_var['pluginid'] = $pluginid;
					foreach($config as $_k => $_v) {
						if(!in_array($_k, array('title','description','fieldname','fieldtype','setting','listorder','value','formattribute'))) continue;
						if($_k == 'setting') $_v = array2string($_v);
						$plugin_var[$_k] = $_v;
					}
					$this->dbVar->data($plugin_var)->add();				
				}
			}		
			plugin_install_stat($plugin_data['appid']);
			cache($plugin_data['identification'], $plugin,'plugins');
			$this->setVarCache($pluginid);
			showmessage(L('plugin_install_success','','plugin'),'?m=Admin&c=Plugin');
		} else {
			showmessage(L('plugin_install_fail','','plugin'),'?m=Admin&c=Plugin');
		}
	}	
	
	/**
	 * 应用升级
	 */		
	public function upgrade() {
		//TODO		
	}
	
	/**
	 * 应用排序
	 */
	public function listorder() {
		if(isset($_POST['dosubmit'])) {
			foreach($_POST['listorders'] as $pluginid => $listorder) {
				$this->db->data(array('listorder'=>$listorder))->where(array('pluginid'=>$pluginid))->save();
			}
			$this->setHookCache();
			showmessage(L('operation_success'),'?m=Admin&c=Plugin');
		} else {
			showmessage(L('operation_failure'),'?m=Admin&c=Plugin');
		}
	}
	

	public function design() {
		
	    if(isset($_POST['dosubmit'])) {
			$data['identification'] = $_POST['info']['identification'];
			$data['realease'] = date('YMd',$GLOBALS['_beginTime']);
			$data['dir'] = $_POST['info']['identification'];
			$data['appid'] = '';
			$data['plugin'] = array(
							'version' => '0.0.2',
							'name' => $_POST['info']['name'],
							'copyright' => $_POST['info']['copyright'],
							'description' => "",
							'installfile' => 'install.php',
							'uninstallfile' => 'uninstall.php',
						);

			
			$filepath = APP_PATH.'Plugin/'.$data['identification'].'/plugin_'.$data['identification'].'.cfg.php';
			load('dir');
			dir_create(dirname($filepath));	
		    $data = "<?php\nreturn ".var_export($data, true).";\n?>";			
			if(C('lock_ex')) {
				$file_size = file_put_contents($filepath, $data, LOCK_EX);
			} else {
				$file_size = file_put_contents($filepath, $data);
			}
			echo 'success';
		} else {
			include Admin::adminTpl('plugin_design');
		}
	}
	/**
	 * 应用中心
	 * Enter description here ...
	 */ 
	public function appcenter() {
		$data = array();
		$p = intval($_GET[p]) ? intval($_GET[p]) : 1;
		$s = 8;
		
		$data = file_get_contents('http://open.phpcms.cn/index.php?m=Open&c=Api&a=getApplist&s='.$s.'&p='.$p);
		$data = array_iconv(json_decode($data, true),'utf-8',CHARSET);
		
		$recommed_data = file_get_contents('http://open.phpcms.cn/index.php?m=Open&c=Api&a=getRecommedApplist&s=5&p=1');
		$recommed_data = array_iconv(json_decode($recommed_data, true),'utf-8',CHARSET);
		
		$focus_data = file_get_contents('http://open.phpcms.cn/index.php?m=Open&c=Api&a=getAppFocus&num=3');
		$focus_data = array_iconv(json_decode($focus_data, true),'utf-8',CHARSET);	
		$pages = $data['pages'];
		$pre_page = $p <= 1 ? 1 : $p - 1;
		$next_page = $p >= $pages ? $pages : $p + 1;
		$pages = '<a class="a1">'.$data['total'].L('plugin_item','','plugin').'</a> <a href="?m=Admin&c=Plugin&a=appcenter&p=1">'.L('plugin_firstpage').'</a> <a href="?m=Admin&c=Plugin&a=appcenter&p='.$pre_page.'">'.L('plugin_prepage').'</a> <a href="?m=Admin&c=Plugin&a=appcenter&p='.$next_page.'">'.L('plugin_nextpage').'</a> <a href="?m=Admin&c=Plugin&a=appcenter&p='.$pages.'">'.L('plugin_lastpage').'</a>';
		$show_header = 1;
		include Admin::adminTpl('plugin_appcenter');
	}
	
	/**
	 * 显示应用详情
	 */
	public function appcenterDetail() {
		$data = array();
		$id = intval($_GET['id']);
		$data = file_get_contents('http://open.phpcms.cn/index.php?m=Open&c=Api&a=getDetailByappid&id='.$id);
		$data = array_iconv(json_decode($data, true),'utf-8',CHARSET);
		extract($data);		
		if($appname) {
			include Admin::adminTpl('plugin_appcenter_detail');
		} else {
			showmessage(L('plugin_not_exist_or_pending','','plugin'));
		}
	}
	
	/**
	 * 在线安装
	 */
	public function installOnline() {
		$data = array();
		$id = intval($_GET['id']);
		$data = file_get_contents('http://open.phpcms.cn/index.php?m=Open&c=Api&a=getDetailByappid&id='.$id);
		$data = array_iconv(json_decode($data, true),'utf-8',CHARSET);
		
		//如果为iframe类型应用，无需下载压缩包，之间创建插件文件夹
		if(!empty($data['iframe'])) {
			$appdirname = APP_PATH.'Plugin/'.$data['appenname'];
			if(!file_exists($appdirname)) {
				if(!mkdir($appdirname)) {
					showmessage(L('plugin_mkdir_fail', '', 'plugin'));
				} else {
					//创建安装、配置文件
					$installdata = <<<EOF
<?php 
	defined('APP_NAME') or exit('No permission resources.');
	\$op_status = TRUE;
?>
EOF;
					$uninstallres = @file_put_contents($appdirname.'/uninstall.php', $installdata);
					$installres = @file_put_contents($appdirname.'/install.php', $installdata);
					
					$cfgdata = <<<EOF
<?php
return array (
  'identification' => '$data[appenname]',
  'dir' => '$data[appenname]',
  'appid' => '$data[id]',
  'plugin'=> array(
		  'version' => '1.0',
		  'name' => '$data[appname]',
		  'copyright' => 'phpcms team',
		  'description' =>'$data[description]',
		  'installfile' => 'install.php',
		  'uninstallfile' => 'uninstall.php',
		  'iframe' => array('width'=>'960','height'=>'640','url'=>'$data[iframe]'),		  
	),
   'plugin_var'=> array(   array('title'=>'宽度','description'=>'','fieldname'=>'width','fieldtype'=>'text','value'=>'960','formattribute'=>'style="width:50px"','listorder'=>'1',),		array('title'=>'高度','description'=>'','fieldname'=>'height','fieldtype'=>'text','value'=>'640','formattribute'=>'style="width:50px"','listorder'=>'2',),   
	),	
);
?>				
EOF;
					$cfgres = @file_put_contents($appdirname.'/plugin_'.$data['appenname'].'.cfg.php', $cfgdata);
					
					//检查配置文件是否写入成功
					if($installres*$uninstallres*$cfgres > 0) {
						showmessage(L('plugin_configure_success', '', 'plugin'), 'index.php?m=Admin&c=Plugin&a=import&dir='.$data['appenname']);
					} else {
						showmessage(L('plugin_install_fail', '', 'plugin'));
					}
				}
			} else {
				showmessage(L('plugin_allready_exists', '', 'plugin'));
			}
		} else {	
			//远程压缩包地址
			$upgradezip_url = $data['downurl'];
			if(empty($upgradezip_url)) {
				showmessage(L('download_fail', '', 'plugin'), 'index.php?m=Admin&c=Plugin&a=appcenter');
			}
			
			//创建缓存文件夹
			if(!file_exists(CACHE_PATH.'caches_open')) {
				@mkdir(CACHE_PATH.'caches_open');
			}
			//TODO 保存到本地地址
			$upgradezip_path = CACHE_PATH.'caches_open/'.$data['id'].'.zip';
			//解压路径
			$upgradezip_source_path = CACHE_PATH.'caches_open/'.$data['id'];
				
			//下载压缩包
			@file_put_contents($upgradezip_path, @file_get_contents($upgradezip_url));
			//TODO 解压缩
			PcBase::loadAppClass('pclzip', 'upgrade', 0);
			$archive = new PclZip($upgradezip_path);
	
			if($archive->extract(PCLZIP_OPT_PATH, $upgradezip_source_path, PCLZIP_OPT_REPLACE_NEWER) == 0) {
				die("Error : ".$archive->errorInfo(true));
			}
			//删除压缩包
			@unlink($upgradezip_path);
			
			//拷贝gbk/upload文件夹到根目录
			$copy_from = $upgradezip_source_path.'/'.CHARSET;
			//动态程序路径
			$copy_to_pcpath = APP_PATH.'Plugin';
			//静态程序路径
			$copy_to_staticspath = CMS_PATH.'Statics/Plugin';

			//应用文件夹名称
			$appdirname = $data['appenname'];
	
			$this->copyfailnum = 0;
			$this->copydir($copy_from.'/Apps/Plugin', $copy_to_pcpath, $_GET['cover']);
			$this->copydir($copy_from.'/Statics/Plugin', $copy_to_staticspath, $_GET['cover']);
			$this->deletedir($copy_from);
			//检查文件操作权限，是否复制成功
			if($this->copyfailnum > 0) {
				showmessage(L('download_fail', '', 'plugin'), 'index.php?m=Admin&c=Plugin&a=appcenter');	
			} else {
				showmessage(L('download_success', '', 'plugin'), 'index.php?m=Admin&c=Plugin&a=import&dir='.$appdirname);	
			}
		}
	}
		
	/**
	 * 异步方式调用详情
	 * Enter description here ...
	 */
	public function publicAppcenterAjxDetail() {
		$id = intval($_GET['id']);
		$data = file_get_contents('http://open.phpcms.cn/index.php?m=Open&c=Api&a=getDetailByappid&id='.$id);
		//$data = json_decode($data, true);
		echo $_GET['jsoncallback'].'(',$data,')';
		exit;		
	}
	
	/**
	 * 配置应用.
	 */
	public function config() {
		if(isset($_POST['dosubmit'])) {
			$pluginid = intval($_POST['pluginid']);
			foreach ($_POST['info'] as $_k => $_v) {
				 $this->dbVar->data(array('value'=>$_v))->where(array('pluginid'=>$pluginid,'fieldname'=>$_k))->save();
			}
			$this->setVarCache($pluginid);
			showmessage(L('operation_success'),HTTP_REFERER);
		} else {
			$pluginid = intval($_GET['pluginid']);
			$plugin_menus = array();
			$info = $this->db->where(array('pluginid'=>$pluginid))->find();
			extract($info);
			if(!isset($_GET['module'])) {	
				$plugin_menus[] =array('name'=>L('plugin_desc','','plugin'),'url'=>'','status'=>'1');
				if($disable){
					if($info_var = $this->dbVar->select(array('pluginid'=>$pluginid),'*','','listorder ASC,id DESC')) {
						$plugin_menus[] =array('name'=>L('plugin_config','','plugin'),'url'=>'','status'=>'0');
						$form = $this->creatconfigform($info_var);
					}
					$meun_total = count($plugin_menus);;
					$setting = string2array($setting);
					if(is_array($setting)) {
						foreach($setting as $m) {
							$plugin_menus[] = array('name'=>$m['menu'],'extend'=>1,'url'=>$m['name']);
							$mods[] = $m['name'];
						}
					}
				}
				include Admin::adminTpl('plugin_setting');
			} else {
				define('PLUGIN_ID', $identification);
				$plugin_module = trim($_GET['module']);
				$plugin_admin_path = APP_PATH.'plugin/'.$identification.'/plugin_admin.class.php';
				if (file_exists($plugin_admin_path)) {
					include $plugin_admin_path;
					$plugin_admin = new plugin_admin($pluginid);
					call_user_func(array($plugin_admin, $plugin_module));
				}				
			}
		}
	}
	/**
	 * 开启/关闭插件
	 * Enter description here ...
	 */
	public function status() {
		$disable = intval($_GET['disable']);
		$pluginid = intval($_GET['pluginid']);
		$this->db->data(array('disable'=>$disable))->where(array('pluginid'=>$pluginid))->save();
		$this->setCache($pluginid);
		showmessage(L('operation_success'),HTTP_REFERER);
	}
	
	/**
	 * 设置字段缓存
	 * @param int $pluginid
	 */
	private function setVarCache($pluginid) {
		if($info = $this->dbVar->select(array('pluginid'=>$pluginid))) {
			$plugin_data =  $this->db->where(array('pluginid'=>$pluginid))->find();
			foreach ($info as $_value) {
				$plugin_vars[$_value['fieldname']] = $_value['value'];
			}
			cache($plugin_data['identification'].'_var', $plugin_vars,'plugins');
		}
	}
	
	/**
	 * 设置缓存
	 * @param int $pluginid
	 */
	private function setCache($pluginid) {
		if($info = $this->db->where(array('pluginid'=>$pluginid))->find()) {		
			cache($info['identification'], $info,'plugins');
		}
		$this->setHookCache();
	}

	/**
	 * 设置hook缓存
	 */
	function setHookCache() {
		if($info = $this->db->pcSelect(array('disable'=>1),'*','','listorder DESC')) {
			foreach($info as $i) {
				$id = $i['identification'];
				$hook_file = APP_PATH.'plugin/'.$id.'/hook.class.php';
				if(file_exists($hook_file)) {
					$hook[$i['appid']] = $i['identification'];
				}
			}			
		}
		cache('hook',$hook,'plugins');
	}
	
	/**
	 * 创建配置表单
	 * @param array $data
	 */
	private function creatconfigform($data) {
		if(!is_array($data) || empty($data)) return false;
		foreach ($data as $r) {
			$form .= '<tr><th width="120">'.$r['title'].'</th><td class="y-bg">'.$this->creatfield($r).'</td></tr>';			
		}
		return $form;		
	}
	
	/**
	 * 创建配置表单字段
	 * @param array $data
	 */
	private function creatfield($data) {
		extract($data);
		$fielda_array = array('text','radio','checkbox','select','datetime','textarea');
		if(in_array($fieldtype, $fielda_array)) {
			if($fieldtype == 'text') {
				return '<input type="text" name="info['.$fieldname.']" id="'.$fieldname.'" value="'.$value.'" class="input-text" '.$formattribute.' > '.' '.$description;
			} elseif($fieldtype == 'checkbox') {
				return Form::checkbox(string2array($setting),$value,"name='info[$fieldname]' $formattribute",'',$fieldname).' '.$description;
			} elseif($fieldtype == 'radio') {
				return Form::radio(string2array($setting),$value,"name='info[$fieldname]' $formattribute",'',$fieldname).' '.$description;
			}  elseif($fieldtype == 'select') {
				return Form::select(string2array($setting),$value,"name='info[$fieldname]' $formattribute",'',$fieldname).' '.$description;
			} elseif($fieldtype == 'datetime') {
				return Form::date("info[$fieldname]",$value,$isdatetime,1).' '.$description;
			} elseif($fieldtype == 'textarea') {
				return '<textarea name="info['.$fieldname.']" id="'.$fieldname.'" '.$formattribute.'>'.$value.'</textarea>'.' '.$description;
			}
		}
	}
	/**
	 * 执行SQL
	 * @param string $sql 要执行的sql语句
	 */
 	private function _sql_execute($sql) {
	    $sqls = $this->_sql_split($sql);
		if(is_array($sqls)) {
			foreach($sqls as $sql) {
				if(trim($sql) != '') {
					$this->db->query($sql);
				}
			}
		} else {
			$this->db->query($sqls);
		}
		return true;
	}	
	
	/**
	 * 分割SQL语句
	 * @param string $sql 要执行的sql语句
	 */	
 	private function _sql_split($sql) {
		$database = PcBase::loadConfig('database');
		$db_charset = $database['default']['charset'];
		if($this->db->version() > '4.1' && $db_charset) {
			$sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=".$db_charset,$sql);
		}
		$sql = str_replace("\r", "\n", $sql);
		$ret = array();
		$num = 0;
		$queriesarray = explode(";\n", trim($sql));
		unset($sql);
		foreach($queriesarray as $query) {
			$ret[$num] = '';
			$queries = explode("\n", trim($query));
			$queries = array_filter($queries);
			foreach($queries as $query) {
				$str1 = substr($query, 0, 1);
				if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
			}
			$num++;
		}
		return($ret);
	}
				
	private function copydir($dirfrom, $dirto, $cover='') {
	    //如果遇到同名文件无法复制，则直接退出
	    if(is_file($dirto)){
	        die(L('have_no_pri').$dirto);
	    }
	    //如果目录不存在，则建立之
	    if(!file_exists($dirto)){
	        mkdir($dirto);
	    }
	    
	    $handle = opendir($dirfrom); //打开当前目录
    
	    //循环读取文件
	    while(false !== ($file = readdir($handle))) {
	    	if($file != '.' && $file != '..'){ //排除"."和"."
		        //生成源文件名
			    $filefrom = $dirfrom.'/'.$file;
		     	//生成目标文件名
		        $fileto = $dirto.'/'.$file;
		        if(is_dir($filefrom)){ //如果是子目录，则进行递归操作
		            $this->copydir($filefrom, $fileto, $cover);
		        } else { //如果是文件，则直接用copy函数复制
		        	if(!empty($cover)) {
						if(!copy($filefrom, $fileto)) {
							$this->copyfailnum++;
						    echo L('copy').$filefrom.L('to').$fileto.L('failed')."<br />";
						}
		        	} else {
		        		if(fileext($fileto) == 'html' && file_exists($fileto)) {

		        		} else {
		        			if(!copy($filefrom, $fileto)) {
								$this->copyfailnum++;
							    echo L('copy').$filefrom.L('to').$fileto.L('failed')."<br />";
							}
		        		}
		        	}
		        }
	    	}
	    }
	}
	
	private function deletedir($dirname){
	    $result = false;
	    if(! is_dir($dirname)){
	        echo " $dirname is not a dir!";
	        exit(0);
	    }
	    $handle = opendir($dirname); //打开目录
	    while(($file = readdir($handle)) !== false) {
	        if($file != '.' && $file != '..'){ //排除"."和"."
	            $dir = $dirname.'/'.$file;
	            //$dir是目录时递归调用deletedir,是文件则直接删除
	            is_dir($dir) ? $this->deletedir($dir) : unlink($dir);
	        }
	    }
	    closedir($handle);
	    $result = rmdir($dirname) ? true : false;
	    return $result;
	}

}
?>