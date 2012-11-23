<?php
/**
 *  position_api.class.php 模块安装接口类
 *
 * @copyright			(C) 2005-2010 PHPCMS
 * @license			http://www.phpcms.cn/license/
 * @lastmodify			2010-8-31
 */

defined('APP_NAME') or exit('No permission resources.');

load('dir');
class ModuleApi {
	
	private $db, $mDB, $installdir, $uninstaldir, $module, $isall;
	public $error_msg = '';
	
	public function __construct() {
		$this->db = M('Module');
	}
	
	/**
	 * 模块安装
	 * @param string $module 模块名
	 */
	public function install($module = '') {
		define('INSTALL', true);
		if ($module) $this->module = $module;
		$this->installdir = APP_PATH.'Modules/'.$this->module.'/Install/';
		
		$this->check();
		$models = @require($this->installdir.'model.php');
		if (!is_array($models) || empty($models)) {
			$models = array('module');
		}
		if (!in_array('module', $models)) {
			array_unshift($models, 'module');
		}
		if (is_array($models) && !empty($models)) {
			foreach ($models as $m) {
				$this->mDb = M($m);
				$sql = file_get_contents($this->installdir.$m.'.sql');
				$this->sqlExecute($sql);
			}
		}
		if (file_exists($this->installdir.'extention.inc.php')) {
			$menu_db = M('Menu');
			@include ($this->installdir.'extention.inc.php');
			if(!defined('INSTALL_MODULE')) {
				$file = APP_PATH.'Lang/'.C('default_lang').'/system_menu.lang.php';
				if(file_exists($file)) {
					$content = file_get_contents($file);
					$content = substr($content,0,-2);
					$data = '';
					foreach ($language as $key => $l) {
						if (L($key, '', 'system_menu')==$key) {
							$data .= "\$LANG['".$key."'] = '".$l."';\r\n";
						}
					}
					$data = $content.$data."?>";
					file_put_contents($file,$data);
				} else {
					foreach ($language as $key =>$l) {
						if (L($key, '', 'system_menu')==$key) {
							$data .= "\$LANG['".$key."'] = '".$l."';\r\n";
						}
					}
					$data = "<?"."php\r\n\$data?>";
					file_put_contents($file,$data);
				}
			}
		}

		if(!defined('INSTALL_MODULE')) {
			if (file_exists($this->installdir.'Lang/')) {
				dir_copy($this->installdir.'Lang/', APP_PATH.'Lang/');
			}
			if(file_exists($this->installdir.'Template/')) {
				dir_copy($this->installdir.'Template/', APP_PATH.'Template/'.C('DEFAULT_THEME').'/'.$this->module.'/');
				if (file_exists($this->installdir.'Template/name.inc.php')) {
					$keyid = 'Template|'.C('DEFAULT_THEME').'|'.$this->module;
					$file_explan[$keyid] = include $this->installdir.'Template/name.inc.php';
					$templatepath = APP_PATH.'Template/'.C('DEFAULT_THEME').'/';
					if (file_exists($templatepath.'config.php')) {
						$style_info = include $templatepath.'config.php';
						$style_info['file_explan'] = array_merge($style_info['file_explan'], $file_explan);
						@file_put_contents($templatepath.'config.php', '<?php return '.var_export($style_info, true).';?>');
					}
					unlink(APP_PATH.'Template/'.C('DEFAULT_THEME').'/'.$this->module.'/name.inc.php');
				}
			}
		}
		return true;
	}
	
	/**
	 * 检查安装目录
	 * @param string $module 模块名
	 */
	public function check($module = '') {
	define('INSTALL', true);
		if ($module) $this->module = $module;
		if(!$this->module) {
			$this->errorMsg = L('no_module');
			return false;
		}
		if(!defined('INSTALL_MODULE')) {
			if (dir_create(APP_PATH.'Lang/'.C('lang').'/test_create_dir')) {
				sleep(1);
				dir_delete(APP_PATH.'Lang/'.C('lang').'/test_create_dir');
				
			} else {
				$this->errorMsg = L('lang_dir_no_write');
				return false;
			}
		}
		$r = $this->db->where(array('module'=>$this->module))->find();
		if ($r) {
			$this->errorMsg = L('this_module_installed');
			return false;
		}
		if (!$this->installdir) {
			$this->installdir = APP_PATH.'Modules/'.$this->module.'/install/';
		}
		if (!is_dir($this->installdir)) {
			$this->errorMsg = L('install_dir_no_exist');
			return false;
		}
		if (!file_exists($this->installdir.'module.sql')) {
			$this->errorMsg = L('module_sql_no_exist');
			return false;
		}
		$models = @require($this->installdir.'model.php');
		if (is_array($models) && !empty($models)) {
			foreach ($models as $m) {
				if (!file_exists(APP_PATH.'Model/'.$m.'_model.class.php')) {
					$this->errorMsg = $m.L('model_clas_no_exist');
					return false;
				}
				if (!file_exists($this->installdir.$m.'.sql')) {
					$this->errorMsg = $m.L('sql_no_exist');
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * 模块卸载
	 * @param string $module 模块名
	 */
	public function uninstall($module) {
		define('UNINSTALL', true);
		if (!$module) {
			$this->errorMsg = L('illegal_parameters');
			return false;
		}
		$this->module = $module;
		$this->uninstalldir = APP_PATH.'Modules/'.$this->module.'/uninstall/';
		if (!is_dir($this->uninstalldir)) {
			$this->errorMsg = L('uninstall_dir_no_exist');
			return false;
		}
		if (file_exists($this->uninstalldir.'model.php')) {
			$models = @require($this->uninstalldir.'model.php');
			if (is_array($models) && !empty($models)) {
				foreach ($models as $m) {
					if (!file_exists($this->uninstalldir.$m.'.sql')) {
						$this->errorMsg = $this->module.'/Uninstall/'.$m.L('sql_no_exist');
						return false;
					}
				}
			}
		}
		if (is_array($models) && !empty($models)) {
			foreach ($models as $m) {
				$this->mDb = M($m);
				$sql = file_get_contents($this->uninstalldir.$m.'.sql');
				$this->sqlExecute($sql);
			}
		}
		if (file_exists($this->uninstalldir.'extention.inc.php')) {
			@include ($this->uninstalldir.'extention.inc.php');
		}
		if (file_exists(APP_PATH.'Lang/'.C('default_lang').'/'.$this->module.'.lang.php')) {
			@unlink(APP_PATH.'Lang/'.C('default_lang').'/'.$this->module.'.lang.php');
		}
		if (is_dir(APP_PATH.'Template/'.C('DEFAULT_THEME').'/'.$this->module)) {
			@dir_delete(APP_PATH.'Template/'.C('DEFAULT_THEME').'/'.$this->module);
		}
		$templatepath = APP_PATH.'Template/'.C('DEFAULT_THEME').'/';
		if (file_exists($templatepath.'config.php')) {
			$keyid = 'Template|'.C('DEFAULT_THEME').'|'.$this->module;
			$style_info = include $templatepath.'config.php';
			unset($style_info['file_explan'][$keyid]);
			@file_put_contents($templatepath.'config.php', '<?php return '.var_export($style_info, true).';?>');
		}
		$menu_db = M('Menu');
		$menu_db->where(array('m'=>$this->module))->delete();
		$this->db->where(array('module'=>$this->module))->delete();
		return true;
	}
	
	/**
	 * 执行mysql.sql文件，创建数据表等
	 * @param string $sql sql语句
	 */
	private function sqlExecute($sql) {
	    $sqls = $this->sqlSplit($sql);

		if (is_array($sqls)) {
			foreach ($sqls as $sql) {
				if (trim($sql) != '') {
					$this->mDb->query($sql);
				}
			}
		} else {
			$this->mDb->query($sqls);
		}
		return true;
	}
	
	/**
	 * 处理sql语句，执行替换前缀都功能。
	 * @param string $sql 原始的sql，将一些大众的部分替换成私有的
	 */
	private function sqlSplit($sql) {
		$dbcharset = $GLOBALS['dbcharset'];
		if (!$dbcharset) {
			$dbcharset = C('DB_CHARSET');
		}
		if($this->mDb->version() > '4.1' && $dbcharset) {
			$sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=".$dbcharset, $sql);
		}
		if($this->mDb->tablePrefix != "phpcms_") $sql = str_replace("phpcms_", $this->mDb->tablePrefix, $sql);
		$sql = str_replace(array("\r", '2010-9-05'), array("\n", date('Y-m-d')), $sql);
		$ret = array();
		$num = 0;
		$queriesarray = explode(";\n", trim($sql));
		unset($sql);
		foreach ($queriesarray as $query) {
			$ret[$num] = '';
			$queries = explode("\n", trim($query));
			$queries = array_filter($queries);
			foreach ($queries as $query) {
				$str1 = substr($query, 0, 1);
				if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
			}
			$num++;
		}
		return $ret;
	}
}
?>