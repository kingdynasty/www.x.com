<?php 
/**
 * 
 * 公告类
 *
 */

defined('APP_NAME') or exit('No permission resources.');

class AnnounceTag {
	private $db;
	
	public function __construct() {
		$this->db = M('Announce');
	}
	
	/**
	 * 公告列表方法
	 * @param array $data 传递过来的参数
	 * @param return array 数据库中取出的数据数组
	 */
	public function lists($data) {
		$where = '1';
		$siteid = $data['siteid'] ? intval($data['siteid']) : get_siteid();
		if ($siteid) $where .= " AND `siteid`='".$siteid."'";
		$where .= ' AND `passed`=\'1\' AND (`endtime` >= \''.date('Y-m-d').'\' or `endtime`=\'0000-00-00\')';
		return $this->db->where($where)->limit($data['limit'])->order('aid DESC')->select();
	}
	
	public function count() {
		
	}
	
	/**
	 * pc标签初始方法
	 */
	public function pcTag() {
		//获取站点
		$sites = import('Sites');
		$sitelist = $sites->pcTagList();
		$result = cache('special', 'Commons');
		if(is_array($result)) {
			$specials = array(L('please_select', '', 'announce'));
			foreach($result as $r) {
				if($r['siteid']!=get_siteid()) continue;
				$specials[$r['id']] = $r['title'];
			}
		}
		return array(
			'action'=>array('lists'=>L('lists', '', 'announce')),
			'lists'=>array(
				'siteid'=>array('name'=>L('sitename', '', 'announce'),'htmltype'=>'input_select', 'defaultvalue'=>get_siteid(), 'data'=>$sitelist),
			),
		);
	}
}
?>