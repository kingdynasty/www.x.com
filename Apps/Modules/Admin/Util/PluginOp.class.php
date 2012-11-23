<?php
defined('APP_NAME') or exit('No permission resources.');

//定义在后台
define('IN_ADMIN',true);
class PluginOp {
	private $db,$db_var;
	public function __construct(){
		$this->dbVar = M('PluginVar');
		$this->db = M('PluginVar');
	}
	/**
	 * 插件后台模板加载
	 */	
	public function pluginTpl($file,$identification) {
		return APP_PATH.'Plugin/'.$identification.'/Template/Admin/'.$file.'.tpl.php';
	}
	
	/**
	 * 获取插件自定义变量信息
	 * @param  $pluginid 插件id
	 */
	public function getpluginvar($pluginid){
		if(empty($pluginid)) return flase;
		if($info_var = $this->dbVar->where(array('pluginid'=>$pluginid))->select()) {
			foreach ($info_var as $var) {
				$pluginvar[$var['fieldname']] = $var['value'];
			}
		}
		return 	$pluginvar;	
	}
	
	/**
	 * 获取插件配置
	 * @param  $pluginid 插件id
	 */
	function getplugincfg($pluginid) {
		$info = $this->db->where(array('pluginid'=>$pluginid))->find();
		return $info;
	}
}
?>