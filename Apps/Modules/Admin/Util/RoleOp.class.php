<?php
defined('APP_NAME') or exit('No permission resources.');

class RoleOp {	
	public function __construct() {
		$this->db = M('AdminRole');
		$this->privDb = M('AdminRolePriv');
	}
	/**
	 * 获取角色中文名称
	 * @param int $roleid 角色ID
	 */
	public function getRolename($roleid) {
		$roleid = intval($roleid);
		$search_field = '`roleid`,`rolename`';
		$info = $this->db->where(array('roleid'=>$roleid))->field($search_field)->find();
		return $info;
	}
		
	/**
	 * 检查角色名称重复
	 * @param $name 角色组名称
	 */
	public function checkname($name) {
		$info = $this->db->where(array('rolename'=>$name))->field('roleid')->find();
		if($info[roleid]){
			return true;
		}
		return false;
	}
	
	/**
	 * 获取菜单表信息
	 * @param int $menuid 菜单ID
	 * @param int $menu_info 菜单数据
	 */
	public function getMenuinfo($menuid,$menu_info) {
		$menuid = intval($menuid);
		unset($menu_info[$menuid][id]);
		return $menu_info[$menuid];
	}
	
	/**
	 *  检查指定菜单是否有权限
	 * @param array $data menu表中数组
	 * @param int $roleid 需要检查的角色ID
	 */
	public function isChecked($data,$roleid,$siteid,$priv_data) {
		$priv_arr = array('m','c','a','data');
		if($data['m'] == '') return false;
		foreach($data as $key=>$value){
			if(!in_array($key,$priv_arr)) unset($data[$key]);
		}
		$data['roleid'] = $roleid;
		$data['siteid'] = $siteid;
		$info = in_array($data, $priv_data);
		if($info){
			return true;
		} else {
			return false;
		}
		
	}
	/**
	 * 是否为设置状态
	 */
	public function isSetting($siteid,$roleid) {
		$siteid = intval($siteid);
		$roleid = intval($roleid);
		$sqls = "`siteid`='$siteid' AND `roleid` = '$roleid' AND `m` != ''";
		$result = $this->privDb->where($sqls)->find();
		return $result ? true : false;
	}
	/**
	 * 获取菜单深度
	 * @param $id
	 * @param $array
	 * @param $i
	 */
	public function getLevel($id,$array=array(),$i=0) {
		foreach($array as $n=>$value){
			if($value['id'] == $id)
			{
				if($value['parentid']== '0') return $i;
				$i++;
				return $this->getLevel($value['parentid'],$array,$i);
			}
		}
	}
}
?>