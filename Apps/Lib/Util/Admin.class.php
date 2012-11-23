<?php
//定义在后台
define('IN_ADMIN',true);
$LANG = array();
include LANG_PATH . LANG_SET . '/system_menu.lang.php';
L ( $LANG );
class Admin extends Action{
	public $userid;
	public $username;

	public function _initialize() {
		self::checkAdmin();
		self::checkPriv();
		if (!module_exists(MODULE_NAME)) $this->error(L('module_not_exists'));
		self::manageLog();
		self::checkIp();
		self::lockScreen();
		self::checkHash();
		if(C('admin_url') && $_SERVER["HTTP_HOST"]!= C('admin_url')) {
			Header("http/1.1 403 Forbidden");
			exit('No permission resources.');
		}
	}
	
	/**
	 * 判断用户是否已经登陆
	 */
	final public function checkAdmin() {
		if(MODULE_NAME =='Admin' && CONTROLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'publicCard'))) {
			return true;
		} else {		    
			if(!isset($_SESSION['userid']) || !isset($_SESSION['roleid']) || !$_SESSION['userid'] || !$_SESSION['roleid']) Action::error(L('admin_login'),'?m=Admin&c=Index&a=login');
		}
	}

	/**
	 * 加载后台模板
	 * @param string $file 文件名
	 * @param string $m 模型名
	 */
	final public static function adminTpl($file, $m = '') {
		$m = empty($m) ? MODULE_NAME : $m;
		if(empty($m)) return false;
		return APP_PATH.'Modules/'.$m.'/Template/'.$file.'.tpl.php';
	}
	
	/**
	 * 按父ID查找菜单子项
	 * @param integer $parentid   父菜单ID  
	 * @param integer $with_self  是否包括他自己
	 */
	final public static function adminMenu($parentid, $with_self = 0) {
		$parentid = intval($parentid);
		$menudb = M('Menu');
		$result =$menudb->where(array('parentid'=>$parentid,'display'=>1))->limit(1000)->order('listorder ASC')->select();
		if (is_array($result)) {
			$result = array_map ('adapt_name', $result);
			if ($with_self) {
				$result2 [] = $menudb->where ( array ('id' => $parentid ))->find ();
				$result2 = array_map ('adapt_name', $result2);
				$result = array_merge ($result2, $result);
			}
		}
		//权限检查
		if($_SESSION['roleid'] == 1) return $result;
		$array = array();
		$privdb = M('AdminRolePriv');
		$siteid = cookie('siteid');
		foreach($result as $v) {
			$action = $v['a'];
			if(preg_match('/^public/',$action)) {
				$array[] = $v;
			} else {
				if(preg_match('/^ajax([A-za-z]+)/',$action,$_match)) $action = $_match[1];
				$r = $privdb->where(array('m'=>$v['m'],'c'=>$v['c'],'a'=>$action,'roleid'=>$_SESSION['roleid'],'siteid'=>$siteid))->find();
				if($r) $array[] = $v;
			}
		}
		return $array;
	}
	/**
	 * 获取菜单 头部菜单导航
	 * 
	 * @param $parentid 菜单id
	 */
	final public static function submenu($parentid = '', $big_menu = false) {
		if(empty($parentid)) {
			$menudb = M('Menu');
			$r = $menudb->where(array('m'=>MODULE_NAME,'c'=>CONTROLER_NAME,'a'=>ACTION_NAME))->find();
			$parentid = $_GET['menuid'] = $r['id'];
		}
		$array = self::adminMenu($parentid,1);
		$numbers = count($array);
		if($numbers==1 && !$big_menu) return '';
		$string = '';
		$pc_hash = $_SESSION['pc_hash'];
		if (is_array($array)) {
			foreach($array as $_value) {
				if (!isset($_GET['s'])) {
					$classname = MODULE_NAME == $_value['m'] && CONTROLER_NAME == $_value['c'] && ACTION_NAME == $_value['a'] ? 'class="on"' : '';
				} else {
					$_s = !empty($_value['data']) ? str_replace('=', '', strstr($_value['data'], '=')) : '';
					$classname = MODULE_NAME == $_value['m'] && CONTROLER_NAME == $_value['c'] && ACTION_NAME == $_value['a'] && $_GET['s'] == $_s ? 'class="on"' : '';
				}
				if($_value['parentid'] == 0 || $_value['m']=='') continue;
				if($classname) {
					$string .= "<a href='javascript:;' $classname><em>".L($_value['name'])."</em></a><span>|</span>";
				} else {
					$string .= "<a href='?m=".$_value['m']."&c=".$_value['c']."&a=".$_value['a']."&menuid=$parentid&pc_hash=$pc_hash".'&'.$_value['data']."' $classname><em>".L($_value['name'])."</em></a><span>|</span>";
				}
			}
		}
		$string = substr($string,0,-14);
		return $string;
	}
	/**
	 * 当前位置
	 * 
	 * @param $id 菜单id
	 */
	final public static function currentPos($id) {
		$r =M('Menu')->field('id,name,parentid')->where(array('id'=>$id))->find();
		$str = '';
		if($r['parentid']) {
			$str = self::currentPos($r['parentid']);
		}
		return $str.L($r['name']).' > ';
	}
	
	/**
	 * 获取当前的站点ID
	 */
	final public static function getSiteid() {
		return get_siteid();
	}
	
	/**
	 * 获取当前站点信息
	 * @param integer $siteid 站点ID号，为空时取当前站点的信息
	 * @return array
	 */
	final public static function getSiteinfo($siteid = '') {
		if ($siteid == '') $siteid = Admin::getSiteid();
		if (empty($siteid)) return false;
		$Sites = import('Sites');
		return $Sites->getById($siteid);
	}
	
	final public static function returnSiteid() {		
	    $sites = import('Sites');
		$siteid = explode(',',$sites->getRoleSiteid($_SESSION['roleid']));
		return current($siteid);
	}
	/**
	 * 权限判断
	 */
	final public function checkPriv() {
		if(MODULE_NAME =='Admin' && CONTROLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'init', 'publicCard'))) return true;
		if($_SESSION['roleid'] == 1) return true;
		$siteid = cookie('siteid');
		$action = ACTION_NAME;
		if(preg_match('/^public_/',ACTION_NAME)) return true;
		if(preg_match('/^ajax_([a-z]+)_/',ACTION_NAME,$_match)) {
			$action = $_match[1];
		}
		$r =M('AdminRolePriv')->where(array('m'=>MODULE_NAME,'c'=>CONTROLER_NAME,'a'=>$action,'roleid'=>$_SESSION['roleid'],'siteid'=>$siteid))->find();
		if(!$r) $this->error('您没有权限操作该项','blank');
	}

	/**
	 * 
	 * 记录日志 
	 */
	final private function manageLog() {
		//判断是否记录
 		if(C('admin_log')==1){
 			$action = ACTION_NAME;
 			if($action == '' || strchr($action,'public') || $action == 'init' || $action=='publicCurrentPos') {
				return false;
			}else {
				$ip = get_client_ip();
				$username = cookie('admin_username');
				$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
				$time = date('Y-m-d H-i-s',$GLOBALS['_beginTime']);
				$url = '?m='.MODULE_NAME.'&c='.CONTROLER_NAME.'&a='.ACTION_NAME;
				M('Log')->data(array('module'=>MODULE_NAME,'username'=>$username,'userid'=>$userid,'action'=>CONTROLER_NAME, 'querystring'=>$url,'time'=>$time,'ip'=>$ip))->add();
			}
	  	}
	}
	
	/**
	 * 
	 * 后台IP禁止判断 ...
	 */
	final private function checkIp(){
		$this->ipbanned = D('Ipbanned');
		$this->ipbanned->checkIp();
 	}
 	/**
 	 * 检查锁屏状态
 	 */
	final private function lockScreen() {
		if(isset($_SESSION['lock_screen']) && $_SESSION['lock_screen']==1) {
			if(preg_match('/^public/', ACTION_NAME) || (MODULE_NAME == 'Content' && CONTROLER_NAME == 'createHtml') || (MODULE_NAME == 'release') || (ACTION_NAME == 'login') || (MODULE_NAME == 'Search' && CONTROLER_NAME == 'searchAdmin' && ACTION_NAME=='createindex')) return true;
			$this->error(L('admin_login'),'?m=Adminc=Index&a=login');
		}
	}

	/**
 	 * 检查hash值，验证用户数据安全性
 	 */
	final private function checkHash() {
		if(preg_match('/^public/', ACTION_NAME) || MODULE_NAME =='Admin' && CONTROLER_NAME =='Index' || in_array(ACTION_NAME, array('login'))) {
			return true;
		}
		if(isset($_GET['pc_hash']) && $_SESSION['pc_hash'] != '' && ($_SESSION['pc_hash'] == $_GET['pc_hash'])) {
			return true;
		} elseif(isset($_POST['pc_hash']) && $_SESSION['pc_hash'] != '' && ($_SESSION['pc_hash'] == $_POST['pc_hash'])) {
			return true;
		} else {
			$this->error(L('hash_check_false'),HTTP_REFERER);
		}
	}
	/**
	 * 后台信息列表模板
	 * @param string $id 被选中的模板名称
	 * @param string $str form表单中的属性名
	 */
	final public function adminListTemplate($id = '', $str = '') {
	    $templatedir = MODULE_PATH.'/Content/Template/';
	    $pre = 'content_list';
	    $templates = glob($templatedir.$pre.'*.tpl.php');
	    if(empty($templates)) return false;
	    $files = @array_map('basename', $templates);
	    $templates = array();
	    if(is_array($files)) {
	        foreach($files as $file) {
	            $key = substr($file, 0, -8);
	            $templates[$key] = $file;
	        }
	    }
	    ksort($templates);
	    return Form::select($templates, $id, $str,L('please_select'));
	}	
}