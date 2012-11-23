<?php
@set_time_limit(0);
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);

class DatabaseAction extends BaseAction {
	private $db;
	function __construct() {
		parent::__construct();
		$this->userid = $_SESSION['userid'];		
		vendor('Pc.Form');
		load('dir');	
	}
	/**
	 * 数据库导出
	 */
	public function export() {
		$database = PcBase::loadConfig('database');
		$dosubmit = isset($_POST['dosubmit']) ? $_POST['dosubmit'] : $_GET['dosubmit'];
		if($dosubmit) {
			if($_GET['pdo_select']=='' && $_POST['pdo_select'] =='') showmessage(L('select_pdo'));
			$tables = $_POST['tables'] ? $_POST['tables'] : $_GET['tables'];
			$sqlcharset = $_POST['sqlcharset'] ? $_POST['sqlcharset'] :$_GET['sqlcharset'];
			$sqlcompat = $_POST['sqlcompat'] ? $_POST['sqlcompat'] : $_GET['sqlcompat'];
			$sizelimit = $_POST['sizelimit'] ? $_POST['sizelimit'] : $_GET['sizelimit'];
			$fileid = $_POST['fileid'] ? $_POST['fileid'] : trim($_GET['fileid']);
			$random = $_POST['random'] ? $_POST['random'] : trim($_GET['random']);
			$tableid = $_POST['tableid'] ? $_POST['tableid'] : trim($_GET['tableid']);
			$startfrom = $_POST['startfrom'] ? $_POST['startfrom'] : trim($_GET['startfrom']);
			$tabletype = $_POST['tabletype'] ? $_POST['tabletype'] : trim($_GET['tabletype']);
			$this->pdoName = $_POST['pdo_select'] ? $_POST['pdo_select'] : trim($_GET['pdo_select']);			
			$this->db = db_factory::getInstance($database)->getDatabase($this->pdoName);
			$r = $this->db->version();
			$this->exportDatabase($tables,$sqlcompat,$sqlcharset,$sizelimit,$action,$fileid,$random,$tableid,$startfrom,$tabletype);
		} else {
			foreach($database as $name=>$value) {
				$pdos[$name] = $value['database'].'['.$value['hostname'].']';
			}
			if($_GET['pdoname']) {
				delcache('bakup_tables','Commons');
				$pdo_name = trim($_GET['pdoname']);
				$r = array();
				$db = db_factory::getInstance($database)->getDatabase($pdo_name);
				$tbl_show = $db->query("SHOW TABLE STATUS FROM `".$database[$pdo_name]['database']."`");
				while(($rs = $db->fetchNext()) != false) {
					$r[] = $rs;
				}
				$infos = $this->status($r,$database[$pdo_name]['tablepre']);
				$db->freeResult($tbl_show);
			}
			include Admin::adminTpl('database_export');			
		}
	}
	
	/**
	 * 数据库导入
	 */
	public function import() {
		$database = PcBase::loadConfig('database');
		if($_GET['dosubmit']) {
			$admin_founders = explode(',',C('admin_founders'));
			if(!in_array($this->userid,$admin_founders)) {
				showmessage(L('only_fonder_operation'));
			}			
			$this->pdoName = $_GET['pdoname'];
			$pre = trim($_GET['pre']);
			$this->fileid = trim($_GET['fileid']);
			$this->dbCharset = $database[$this->pdoName]['charset'];
			$this->tablePrefix = $database[$pdo_name]['tablepre'];
			$this->db = db_factory::getInstance($database)->getDatabase($this->pdoName);
			$this->importDatabase($pre);
		} else {
			$$pdos = $others = array();
			foreach($database as $name=>$value) {
				$pdos[$name] = $value['database'].'['.$value['hostname'].']';
			}
			$pdoname = $_GET['pdoname'] ? $_GET['pdoname'] : key($pdos);
			$sqlfiles = glob(CACHE_PATH.'bakup/'.$pdoname.'/*.sql');
			if(is_array($sqlfiles)) {
				asort($sqlfiles);
				$prepre = '';
				$info = $infos = $other = $others = array();
				foreach($sqlfiles as $id=>$sqlfile) {
					if(preg_match("/(phpcmstables_[0-9]{8}_[0-9a-z]{4}_)([0-9]+)\.sql/i",basename($sqlfile),$num)) {
						$info['filename'] = basename($sqlfile);
						$info['filesize'] = round(filesize($sqlfile)/(1024*1024), 2);
						$info['maketime'] = date('Y-m-d H:i:s', filemtime($sqlfile));
						$info['pre'] = $num[1];
						$info['number'] = $num[2];
						if(!$id) $prebgcolor = '#CFEFFF';
						if($info['pre'] == $prepre) {
						 $info['bgcolor'] = $prebgcolor;
						} else {
						 $info['bgcolor'] = $prebgcolor == '#CFEFFF' ? '#F1F3F5' : '#CFEFFF';
						}
						$prebgcolor = $info['bgcolor'];
						$prepre = $info['pre'];
						$infos[] = $info;
					} else {
						$other['filename'] = basename($sqlfile);
						$other['filesize'] = round(filesize($sqlfile)/(1024*1024),2);
						$other['maketime'] = date('Y-m-d H:i:s',filemtime($sqlfile));
						$others[] = $other;
					}
				}
			}
			$show_validator = true;
			include Admin::adminTpl('database_import');
		}
	}
	
	/**
	 * 备份文件下载
	 */
	public function publicDown() {
		$admin_founders = explode(',',C('admin_founders'));
		if(!in_array($this->userid,$admin_founders)) {
			showmessage(L('only_fonder_operation'));
		}	
		$datadir = $_GET['pdoname'];
		$filename = $_GET['filename'];
		$fileext = fileext($filename);
		if($fileext != 'sql') {
			showmessage(L('only_sql_down'));
		}
		file_down(CACHE_PATH.'bakup/'.$datadir.'/'.$filename);
	}
	
	/**
	 * 数据库修复、优化
	 */
	public function publicRepair() {
		$database = PcBase::loadConfig('database');
		$tables = $_POST['tables'] ? $_POST['tables'] : trim($_GET['tables']);
		$operation = trim($_GET['operation']);
		$pdo_name = trim($_GET['pdo_name']);
		$this->db = db_factory::getInstance($database)->getDatabase($pdo_name);
		$tables = is_array($tables) ? implode(',',$tables) : $tables;
		if($tables && in_array($operation,array('repair','optimize'))) {
			$this->db->query("$operation TABLE $tables");
			showmessage(L('operation_success'),'?m=Admin&c=Database&a=export&pdoname='.$pdo_name);
		} elseif ($tables && $operation == 'showcreat') {						
			$this->db->query("SHOW CREATE TABLE $tables");
			$structure = $this->db->fetchNext();
			$structure = $structure['Create Table'];
			$show_header = true;
			include Admin::adminTpl('database_structure');					
		} else {
			showmessage(L('select_tbl'),'?m=Admin&c=Database&a=export&pdoname='.$pdo_name);
		}
	}
	
	/**
	 * 备份文件删除
	 */
	public function delete() {
		$filenames = $_POST['filenames'];
		$pdo_name = $_GET['pdoname'];
		$bakfile_path = CACHE_PATH.'bakup/'.$pdo_name.'/';
		if($filenames) {
			if(is_array($filenames)) {
				foreach($filenames as $filename) {
					if(fileext($filename)=='sql') {
						@unlink($bakfile_path.$filename);
					}
				}
				showmessage(L('operation_success'),'?m=Admin&c=Database&a=import&pdoname='.$pdo_name);
			} else {
				if(fileext($filenames)=='sql') {
					@unlink($bakfile_path.$filename);
					showmessage(L('operation_success'),'?m=Admin&c=Database&a=import&pdoname='.$pdo_name);
				}
			}
		} else {
			showmessage(L('select_delfile'));	
		}				
	}
	/**
	 * 获取数据表
	 * @param unknown_type 数据表数组
	 * @param unknown_type 表前缀
	 */
	private function status($tables,$tablepre) {
		$phpcms = array();
		$other = array();
		foreach($tables as $table) {
			$name = $table['Name'];
			$row = array('name'=>$name,'rows'=>$table['Rows'],'size'=>$table['Data_length']+$row['Index_length'],'engine'=>$table['Engine'],'data_free'=>$table['Data_free'],'collation'=>$table['Collation']);
			if(strpos($name, $tablepre) === 0) {
				$phpcms[] = $row;
			} else {
				$other[] = $row;
			}				
		}
		return array('phpcmstables'=>$phpcms, 'othertables'=>$other);
	}
	
	/**
	 * 数据库导出方法
	 * @param unknown_type $tables 数据表数据组
	 * @param unknown_type $sqlcompat 数据库兼容类型
	 * @param unknown_type $sqlcharset 数据库字符
	 * @param unknown_type $sizelimit 卷大小
	 * @param unknown_type $action 操作
	 * @param unknown_type $fileid 卷标
	 * @param unknown_type $random 随机字段
	 * @param unknown_type $tableid 
	 * @param unknown_type $startfrom 
	 * @param unknown_type $tabletype 备份数据库类型 （非phpcms数据与phpcms数据）
	 */
	private function exportDatabase($tables,$sqlcompat,$sqlcharset,$sizelimit,$action,$fileid,$random,$tableid,$startfrom,$tabletype) {
		$dumpcharset = $sqlcharset ? $sqlcharset : str_replace('-', '', CHARSET);

		$fileid = ($fileid != '') ? $fileid : 1;		
		if($fileid==1 && $tables) {
			if(!isset($tables) || !is_array($tables)) showmessage(L('select_tbl'));
			$random = mt_rand(1000, 9999);
			cache('bakup_tables',$tables,'Commons');
		} else {
			if(!$tables = cache('bakup_tables','Commons')) showmessage(L('select_tbl'));
		}
		if($this->db->version() > '4.1'){
			if($sqlcharset) {
				$this->db->query("SET NAMES '".$sqlcharset."';\n\n");
			}
			if($sqlcompat == 'MYSQL40') {
				$this->db->query("SET SQL_MODE='MYSQL40'");
			} elseif($sqlcompat == 'MYSQL41') {
				$this->db->query("SET SQL_MODE=''");
			}
		}
		
		$tabledump = '';

		$tableid = ($tableid!= '') ? $tableid - 1 : 0;
		$startfrom = ($startfrom != '') ? intval($startfrom) : 0;
		for($i = $tableid; $i < count($tables) && strlen($tabledump) < $sizelimit * 1000; $i++) {
			global $startrow;
			$offset = 100;
			if(!$startfrom) {
				if($tables[$i]!=DB_PRE.'session') {
					$tabledump .= "DROP TABLE IF EXISTS `$tables[$i]`;\n";
				}
				$createtable = $this->db->query("SHOW CREATE TABLE `$tables[$i]` ");
				$create = $this->db->fetchNext();
				$tabledump .= $create['Create Table'].";\n\n";
				$this->db->freeResult($createtable);
							
				if($sqlcompat == 'MYSQL41' && $this->db->version() < '4.1') {
					$tabledump = preg_replace("/TYPE\=([a-zA-Z0-9]+)/", "ENGINE=\\1 DEFAULT CHARSET=".$dumpcharset, $tabledump);
				}
				if($this->db->version() > '4.1' && $sqlcharset) {
					$tabledump = preg_replace("/(DEFAULT)*\s*CHARSET=[a-zA-Z0-9]+/", "DEFAULT CHARSET=".$sqlcharset, $tabledump);
				}
				if($tables[$i]==DB_PRE.'session') {
					$tabledump = str_replace("CREATE TABLE `".DB_PRE."session`", "CREATE TABLE IF NOT EXISTS `".DB_PRE."session`", $tabledump);
				}
			}

			$numrows = $offset;
			while(strlen($tabledump) < $sizelimit * 1000 && $numrows == $offset) {
				if($tables[$i]==DB_PRE.'session' || $tables[$i]==DB_PRE.'member_cache') break;
				$sql = "SELECT * FROM `$tables[$i]` LIMIT $startfrom, $offset";
				$numfields = $this->db->numFields($sql);
				$numrows = $this->db->numRows($sql);
				$fields_name = $this->db->getFields($tables[$i]);
				$rows = $this->db->query($sql);
				$name = array_keys($fields_name);
				$r = array();
				while ($row = $this->db->fetchNext()) {
					$r[] = $row;
					$comma = "";
					$tabledump .= "INSERT INTO `$tables[$i]` VALUES(";
					for($j = 0; $j < $numfields; $j++) {
						$tabledump .= $comma."'".mysql_escape_string($row[$name[$j]])."'";
						$comma = ",";
					}
					$tabledump .= ");\n";
				}
				$this->db->freeResult($rows);
				$startfrom += $offset;
				
			}
			$tabledump .= "\n";
			$startrow = $startfrom;
			$startfrom = 0;
		}
		if(trim($tabledump)) {
			$tabledump = "# phpcms bakfile\n# version:PHPCMS V9\n# time:".date('Y-m-d H:i:s')."\n# type:phpcms\n# phpcms:http://www.phpcms.cn\n# --------------------------------------------------------\n\n\n".$tabledump;
			$tableid = $i;
			$filename = $tabletype.'_'.date('Ymd').'_'.$random.'_'.$fileid.'.sql';
			$altid = $fileid;
			$fileid++;
			$bakfile_path = CACHE_PATH.'bakup/'.$this->pdoName;
			if(!dir_create($bakfile_path)) {
				showmessage(L('dir_not_be_created'));
			}
			$bakfile = $bakfile_path.'/'.$filename;
			if(!is_writable(CACHE_PATH.'bakup')) showmessage(L('dir_not_be_created'));
			file_put_contents($bakfile, $tabledump);
			@chmod($bakfile, 0777);
			if(!EXECUTION_SQL) $filename = L('bundling').$altid.'#';
			showmessage(L('bakup_file')." $filename ".L('bakup_write_succ'), '?m=Admin&c=Database&a=export&sizelimit='.$sizelimit.'&sqlcompat='.$sqlcompat.'&sqlcharset='.$sqlcharset.'&tableid='.$tableid.'&fileid='.$fileid.'&startfrom='.$startrow.'&random='.$random.'&dosubmit=1&tabletype='.$tabletype.'&allow='.$allow.'&pdo_select='.$this->pdoName);
		} else {
		   $bakfile_path = CACHE_PATH.'bakup/'.$this->pdoName.'/';
		   file_put_contents($bakfile_path.'index.html','');
		   delcache('bakup_tables','Commons');
		   showmessage(L('bakup_succ'),'?m=Admin&c=Database&a=import&pdoname='.$this->pdoName);
		}
	}
	/**
	 * 数据库恢复
	 * @param unknown_type $filename
	 */
	private function importDatabase($filename) {
		if($filename && fileext($filename)=='sql') {
			$filepath = CACHE_PATH.'bakup/'.$this->pdoName.'/'.$filename;
			if(!file_exists($filepath)) showmessage(L('database_sorry')." $filepath ".L('database_not_exist'));
			$sql = file_get_contents($filepath);
			sql_execute($sql);
			showmessage("$filename ".L('data_have_load_to_database'));
		} else {
			$fileid = $this->fileid ? $this->fileid : 1;
			$pre = $filename;
			$filename = $filename.$fileid.'.sql';
			$filepath = CACHE_PATH.'bakup/'.$this->pdoName.'/'.$filename;
			if(file_exists($filepath)) {
				$sql = file_get_contents($filepath);
				$this->sqlExecute($sql);
				$fileid++;
				showmessage(L('bakup_data_file')." $filename ".L('load_success'),"?m=Admin&c=Database&a=import&pdoname=".$this->pdoName."&pre=".$pre."&fileid=".$fileid."&dosubmit=1");
			} else {
				showmessage(L('data_recover_succ'),'?m=Admin&c=Database&a=import');
			}
		}
	}
	
	/**
	 * 执行SQL
	 * @param unknown_type $sql
	 */
 	private function sqlExecute($sql) {
	    $sqls = $this->sqlSplit($sql);
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
	

 	private function sqlSplit($sql) {
		if($this->db->version() > '4.1' && $this->dbCharset) {
			$sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=".$this->dbCharset,$sql);
		}
		if($this->tablePrefix != "phpcms_") $sql = str_replace("`phpcms_", '`'.$this->tablePrefix, $sql);
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
}
?>