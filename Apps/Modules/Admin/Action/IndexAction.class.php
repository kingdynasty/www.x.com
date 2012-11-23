<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin');//如果不初始化，则其内部class也无法检查执行
class IndexAction extends BaseAction {
	public function _initialize() {
		$this->db = M('Admin');
		$this->menuDb = M('Menu');
		$this->panelDb = M('AdminPanel');
	}
	
	public function init () {
		$userid = $_SESSION['userid'];
		$admin_username = cookie('admin_username');
		$roles = cache('role','Commons');
		$rolename = $roles[$_SESSION['roleid']];
		$Sites = import('Sites');
		$sitelist = $Sites->getList($_SESSION['roleid']);
		$currentsite = Admin::getSiteInfo(cookie('siteid'));
		/*管理员收藏栏*/
		$adminpanel = $this->panelDb->where(array('userid'=>$userid))->limit(20)->order('datetime')->select();
		include Admin::adminTpl('index');
		$this->mytrace();
	}
	
	public function login() {
		if(isset($_GET['dosubmit'])) {
			
			//不为口令卡验证
			if (!isset($_GET['card'])) {
				$username = isset($_POST['username']) ? trim($_POST['username']) : showmessage(L('nameerror'),HTTP_REFERER);
				$code = isset($_POST['code']) && trim($_POST['code']) ? trim($_POST['code']) : showmessage(L('input_code'), HTTP_REFERER);
				if ($_SESSION['code'] != strtolower($code)) {
					showmessage(L('code_error'), HTTP_REFERER);
				}
			} else { //口令卡验证
				if (!isset($_SESSION['card_verif']) || $_SESSION['card_verif'] != 1) {
					showmessage(L('your_password_card_is_not_validate'), '?m=Admin&c=Index&a=publicCard');
				}
				$username = $_SESSION['card_username'] ? $_SESSION['card_username'] :  showmessage(L('nameerror'),HTTP_REFERER);
			}
			
			//密码错误剩余重试次数
			$this->timesDb = M('Times');			
			$rtime = $this->timesDb->where(array('username'=>$username,'isadmin'=>1))->find();
			$maxloginfailedtimes = cache('common','Commons');
			$maxloginfailedtimes = (int)$maxloginfailedtimes['maxloginfailedtimes'];
			if($rtime['times'] >= $maxloginfailedtimes) {
				$minute = 60-floor(($GLOBALS['_beginTime']-$rtime['logintime'])/60);
				trace($maxloginfailedtimes,'$maxloginfailedtimes');
				$this->mytrace();
				showmessage(L('wait_1_hour',array('minute'=>$minute)));
			}
			//查询帐号
			$r = $this->db->where(array('username'=>$username))->find();
			if(!$r) showmessage(L('user_not_exist'),'?m=Admin&c=Index&a=login');
			$password = md5(md5(trim((!isset($_GET['card']) ? $_POST['password'] : $_SESSION['card_password']))).$r['encrypt']);
			
			if($r['password'] != $password) {
				$ip = ip();
				if($rtime && $rtime['times'] < $maxloginfailedtimes) {
					$times = $maxloginfailedtimes-intval($rtime['times']);
					$this->timesDb->data(array('ip'=>$ip,'isadmin'=>1,'times'=>'+=1'))->where(array('username'=>$username))->save();
				} else {
					$this->timesDb->where(array('username'=>$username,'isadmin'=>1))->delete();
					$this->timesDb->data(array('username'=>$username,'ip'=>$ip,'isadmin'=>1,'logintime'=>$GLOBALS['_beginTime'],'times'=>1))->add();
					$times = $maxloginfailedtimes;
				}
				showmessage(L('password_error',array('times'=>$times)),'?m=Admin&c=Index&a=login',3000);
			}
			$this->timesDb->where(array('username'=>$username))->delete();
			
			//查看是否使用口令卡
			if (!isset($_GET['card']) && $r['card'] && C('safe_card') == 1) {
				$_SESSION['card_username'] = $username;
				$_SESSION['card_password'] = $_POST['password'];
				header("location:?m=Admin&c=Index&a=publicCard");
				exit;
			} elseif (isset($_GET['card']) && C('safe_card') == 1 && $r['card']) {//对口令卡进行验证
				isset($_SESSION['card_username']) ? $_SESSION['card_username'] = '' : '';
				isset($_SESSION['card_password']) ? $_SESSION['card_password'] = '' : '';
				isset($_SESSION['card_password']) ? $_SESSION['card_verif'] = '' : '';
			}
			
			$this->db->data(array('lastloginip'=>ip(),'lastlogintime'=>$GLOBALS['_beginTime']))->where(array('userid'=>$r['userid']))->save();
			$_SESSION['userid'] = $r['userid'];
			$_SESSION['roleid'] = $r['roleid'];
			$_SESSION['pc_hash'] = random(6,'abcdefghigklmnopqrstuvwxwyABCDEFGHIGKLMNOPQRSTUVWXWY0123456789');
			$_SESSION['lock_screen'] = 0;
			$default_siteid = Admin::returnSiteid();
			$cookie_time = $GLOBALS['_beginTime']+86400*30;
			cookie('admin_username',$username,$cookie_time);
			cookie('siteid', $default_siteid,$cookie_time);
			cookie('userid', $r['userid'],$cookie_time);
			cookie('admin_email', $r['email'],$cookie_time);
			showmessage(L('login_success'),'?m=Admin&c=Index');
		} else {
			vendor('Pc.Form');			
			include Admin::adminTpl('login');
		}
	}
	
	public function publicCard() {
		$username = $_SESSION['card_username'] ? $_SESSION['card_username'] :  showmessage(L('nameerror'),HTTP_REFERER);
		$r = $this->db->where(array('username'=>$username))->find();
		if(!$r) showmessage(L('user_not_exist'),'?m=Admin&c=Index&a=login');
		if (isset($_GET['dosubmit'])) {
			import('@.Admin.Util.Card','',0);
			$result = Card::verification($r['card'], $_POST['code'], $_POST['rand']);
			$_SESSION['card_verif'] = 1;
			header("location:?m=Admin&c=Index&a=login&dosubmit=1&card=1");
			exit;
		}
		import('@.Admin.Util.Card','',0);
		$rand = Card::autheRand($r['card']);
		include Admin::adminTpl('login_card');
	}
	
	public function publicLogout() {
		$_SESSION['userid'] = 0;
		$_SESSION['roleid'] = 0;
		cookie('admin_username',null);
		cookie('userid',null);
		
		//退出phpsso
		$phpsso_api_url = C('phpsso_api_url');
		$phpsso_logout = '<script type="text/javascript" src="'.$phpsso_api_url.'/api.php?op=logout" reload="1"></script>';
		
		showmessage(L('logout_success').$phpsso_logout,'?m=Admin&c=Index&a=login');
	}
	
	//左侧菜单
	public function publicMenuLeft() {
		$menuid = intval($_GET['menuid']);
		$datas = Admin::adminMenu($menuid);
		if (isset($_GET['parentid']) && $parentid = intval($_GET['parentid']) ? intval($_GET['parentid']) : 10) {
			foreach($datas as $_value) {
	        	if($parentid==$_value['id']) {
	        		echo '<li id="_M'.$_value['id'].'" class="on top_menu"><a href="javascript:_M('.$_value['id'].',\'?m='.$_value['m'].'&c='.$_value['c'].'&a='.$_value['a'].'\')" hidefocus="true" style="outline:none;">'.L($_value['name']).'</a></li>';
	        		
	        	} else {
	        		echo '<li id="_M'.$_value['id'].'" class="top_menu"><a href="javascript:_M('.$_value['id'].',\'?m='.$_value['m'].'&c='.$_value['c'].'&a='.$_value['a'].'\')"  hidefocus="true" style="outline:none;">'.L($_value['name']).'</a></li>';
	        	}      	
	        }
		} else {
			include Admin::adminTpl('left');
		}
		
	}
	//当前位置
	public function publicCurrentPos() {
		echo admin::currentPos($_GET['menuid']);
		exit;
	}
	
	/**
	 * 设置站点ID COOKIE
	 */
	public function publicSetSiteid() {
		$siteid = isset($_GET['siteid']) && intval($_GET['siteid']) ? intval($_GET['siteid']) : exit('0'); 
		cookie('siteid', $siteid);
		exit('1');
	}
	
	public function publicAjaxAddPanel() {
		$menuid = isset($_POST['menuid']) ? $_POST['menuid'] : exit('0');
		$menuarr = $this->menuDb->where(array('id'=>$menuid))->find();
		$url = '?m='.$menuarr['m'].'&c='.$menuarr['c'].'&a='.$menuarr['a'].'&'.$menuarr['data'];
		$data = array('menuid'=>$menuid, 'userid'=>$_SESSION['userid'], 'name'=>$menuarr['name'], 'url'=>$url, 'datetime'=>$GLOBALS['_beginTime']);
		$this->panelDb->data($data)->add();
		$panelarr = $this->panelDb->where(array('userid'=>$_SESSION['userid']))->order("datetime")->select();
		foreach($panelarr as $v) {
			echo "<span><a onclick='paneladdclass(this);' target='right' href='".$v['url'].'&menuid='.$v['menuid']."&pc_hash=".$_SESSION['pc_hash']."'>".L($v['name'])."</a>  <a class='panel-delete' href='javascript:delete_panel(".$v['menuid'].");'></a></span>";
		}
		exit;
	}
	
	public function publicAjaxDeletePanel() {
		$menuid = isset($_POST['menuid']) ? $_POST['menuid'] : exit('0');
		$this->panelDb->where(array('menuid'=>$menuid, 'userid'=>$_SESSION['userid']))->delete();

		$panelarr = $this->panelDb->where(array('userid'=>$_SESSION['userid']))->order("datetime")->select();
		foreach($panelarr as $v) {
			echo "<span><a onclick='paneladdclass(this);' target='right' href='".$v['url']."&pc_hash=".$_SESSION['pc_hash']."'>".L($v['name'])."</a> <a class='panel-delete' href='javascript:delete_panel(".$v['menuid'].");'></a></span>";
		}
		exit;
	}
	public function publicMain() {		
		load('@.admin');
		define('PC_VERSION', C('pc_version'));
		define('PC_RELEASE', C('pc_release'));	
	
		$admin_username = cookie('admin_username');
		$roles = cache('role','Commons');
		$userid = $_SESSION['userid'];
		$rolename = $roles[$_SESSION['roleid']];
		$r = $this->db->where(array('userid'=>$userid))->find();
		$logintime = $r['lastlogintime'];
		$loginip = $r['lastloginip'];
		$sysinfo = get_sysinfo();
		$sysinfo['mysqlv'] = mysql_get_server_info();
		$show_header = $show_pc_hash = 1;
		/*检测框架目录可写性*/
		$pc_writeable = is_writable(APP_PATH.'ThinkPHP.php');
		$common_cache = cache('common','Commons');
		$logsize_warning = errorlog_size() > $common_cache['errorlog_size'] ? '1' : '0';
		$adminpanel = $this->panelDb->where(array('userid'=>$userid))->limit(20)->order('datetime')->select(); 
 		$product_copyright = base64_decode('5LiK5rW355ub5aSn572R57uc5Y+R5bGV5pyJ6ZmQ5YWs5Y+4');
		$architecture = base64_decode('546L5Y+C5Yqg');
		$programmer = base64_decode('546L5Y+C5Yqg44CB6ZmI5a2m5pe644CB546L5a6Y5bqG44CB5byg5LqM5by644CB6YOd5Zu95paw44CB6YOd5bed44CB6LW15a6P5Lyf');
		$designer = base64_decode('6JGj6aOe6b6Z44CB5byg5LqM5by6');
		ob_start();
		include Admin::adminTpl('main');
		$data = ob_get_contents();
		ob_end_clean();
		system_information($data);
	}
	/**
	 * 维持 session 登陆状态
	 */
	public function publicSessionLife() {
		$userid = $_SESSION['userid'];
		return true;
	}
	/**
	 * 锁屏
	 */
	public function publicLockScreen() {
		$_SESSION['lock_screen'] = 1;
	}
	public function publicLoginScreenlock() {
		if(empty($_GET['lock_password'])) showmessage(L('password_can_not_be_empty'));
		//密码错误剩余重试次数
		$this->timesDb = M('Times');
		$username = cookie('admin_username');
		$maxloginfailedtimes = cache('common','Commons');
		$maxloginfailedtimes = (int)$maxloginfailedtimes['maxloginfailedtimes'];
		
		$rtime = $this->timesDb->where(array('username'=>$username,'isadmin'=>1))->find();
		if($rtime['times'] > $maxloginfailedtimes-1) {
			$minute = 60-floor(($GLOBALS['_beginTime']-$rtime['logintime'])/60);
			exit('3');
		}
		//查询帐号
		$r = $this->db->where(array('userid'=>$_SESSION['userid']))->find();
		$password = md5(md5($_GET['lock_password']).$r['encrypt']);
		if($r['password'] != $password) {
			$ip = ip();
			if($rtime && $rtime['times']<$maxloginfailedtimes) {
				$times = $maxloginfailedtimes-intval($rtime['times']);
				$this->timesDb->data(array('ip'=>$ip,'isadmin'=>1,'times'=>'+=1'))->where(array('username'=>$username))->save();
			} else {
				$this->timesDb->data(array('username'=>$username,'ip'=>$ip,'isadmin'=>1,'logintime'=>$GLOBALS['_beginTime'],'times'=>1))->add();
				$times = $maxloginfailedtimes;
			}
			exit('2|'.$times);//密码错误
		}
		$this->timesDb->where(array('username'=>$username))->delete();
		$_SESSION['lock_screen'] = 0;
		exit('1');
	}
	
	//后台站点地图
	public function publicMap() {
		 $array = Admin::adminMenu(0);
		 $menu = array();
		 foreach ($array as $k=>$v) {
		 	$menu[$v['id']] = $v;
		 	$menu[$v['id']]['childmenus'] = Admin::adminMenu($v['id']);
		 }
		 $show_header = true;
		 include Admin::adminTpl('map');
	}
	
	/**
	 * 
	 * 读取盛大接扣获取appid和secretkey
	 */
	public function publicSndaStatus() {
		//引入盛大接口
		if(!strstr(C('snda_status'), '|')) {
			$this->siteDb = M('Site');
			$uuid_arr = $this->siteDb->where(array('siteid'=>1))->field('uuid')->find();
			$uuid = $uuid_arr['uuid'];
			$snda_check_url = "http://open.sdo.com/phpcms?cmsid=".$uuid."&sitedomain=".$_SERVER['SERVER_NAME'];

			$snda_res_json = @file_get_contents($snda_check_url);
			$snda_res = json_decode($snda_res_json, 1);

			if(!isset($snda_res[err]) && !empty($snda_res['appid'])) {
				$appid = $snda_res['appid'];
				$secretkey = $snda_res['secretkey'];
				set_config(array('snda_status'=>$appid.'|'.$secretkey), 'snda');
			}
		}
	}

}
?>