<?php
/**
 * 站点对外接口
 * @author chenzhouyu
 *
 */
class Sites {
	//数据库连接
	private $db;
	public function __construct() {
		$this->db = M('Site');
	}
	
	/**
	 * 获取站点列表
	 * @param string $roleid 角色ID 留空为获取所有站点列表
	 */
	public function getList($roleid='') {
		$roleid = intval($roleid);
		if(empty($roleid)) {
			if ($data = cache('sitelist', 'Commons')) {
				return $data;
			} else {
				$this->setCache();
				return $this->db->select();
			}			
		} else {
			$site_arr = $this->getRoleSiteid($roleid);
			$sql = "`siteid` in($site_arr)";
			return $this->db->pcSelect($sql);
		}

	}
	
	/**
	 * 按ID获取站点信息
	 * @param integer $siteid 站点ID号
	 */
	public function getById($siteid) {
		return siteinfo($siteid);
	}
	
	/**
	 * 设置站点缓存
	 */
	public function setCache() {
		$list = $this->db->select();
		$data = array();
		foreach ($list as $key=>$val) {
			$data[$val['siteid']] = $val;
			$data[$val['siteid']]['url'] = $val['domain'] ? $val['domain'] : C('site_path').$val['dirname'].'/';
		}
		cache('sitelist', $data, 'Commons');
	}
	
	/**
	 * PC标签中调用站点列表
	 */
	public function pcTagList() {
		$list = $this->db->pcSelect('', 'siteid,name');
		$sitelist = array(''=>L('please_select_a_site', '', 'admin'));
		foreach ($list as $k=>$v) {
			$sitelist[$v['siteid']] = $v['name'];
		}
		return $sitelist;
	}
	
	/**
	 * 按角色ID获取站点列表
	 * @param string $roleid 角色ID
	 */	
	
	public function getRoleSiteid($roleid) {
		$roleid = intval($roleid);
		if($roleid == 1) {
			$sitelists = $this->getList();
			foreach($sitelists as $v) {
				$sitelist[] = $v['siteid'];
			}
		} else {
			$sitelist = cache('role_siteid', 'Commons');
			$sitelist = $sitelist[$roleid];
		}
		if(is_array($sitelist)) 
		{
			$siteid = implode(',',array_unique($sitelist));
			return $siteid;			
		} else {
			showmessage(L('no_site_permissions'),'?m=Admin&c=Index&a=login');
		}
	}
}