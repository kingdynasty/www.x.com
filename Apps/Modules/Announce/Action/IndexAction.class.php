<?php 
defined('APP_NAME') or exit('No permission resources.');
class index {
	function __construct() {
		$this->db = M('Announce');
	}
	
	public function init() {
		
	}
	
	/**
	 * 展示公告
	 */
	public function show() {
		if(!isset($_GET['aid'])) {
			showmessage(L('illegal_operation'));
		}
		$_GET['aid'] = intval($_GET['aid']);
		$where = '';
		$where .= "`aid`='".$_GET['aid']."'";
		$where .= " AND `passed`='1' AND (`endtime` >= '".date('Y-m-d')."' or `endtime`='0000-00-00')";
		$r = $this->db->where($where)->find();
		if($r['aid']) {
			$this->db->data(array('hits'=>'+=1'))->where(array('aid'=>$r['aid']))->save();
			$template = $r['show_template'] ? $r['show_template'] : 'show';
			extract($r);
			$SEO = seo(get_siteid(), '', $title);
			include template('announce', $template, $r['style']);
		} else {
			showmessage(L('no_exists'));	
		}
	}
}
?>