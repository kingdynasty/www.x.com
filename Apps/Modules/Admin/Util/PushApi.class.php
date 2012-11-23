<?php
/**
 *  position_api.class.php 推荐至推荐位接口类
 *
 * @copyright			(C) 2005-2010 PHPCMS
 * @license				http://www.phpcms.cn/license/
 * @lastmodify			2010-8-3
 */

defined('APP_NAME') or exit('No permission resources.');

class PushApi {
 	private $db, $posData; //数据调用属性

	public function __construct() {
		$this->db = M('Position');  //加载数据模型
	}

	/**
	 * 推荐位推送修改接口
	 * 适合在文章发布、修改时调用
	 * @param int $id 推荐文章ID
	 * @param int $modelid 模型ID
	 * @param array $posid 推送到的推荐位ID
	 * @param array $data 推送数据
	 * @param int $expiration 过期时间设置
	 * @param int $undel 是否判断推荐位去除情况
	 * @param string $model 调取的数据模型
	 * 调用方式
	 * $push = PcBase::loadAppClass('push_api','admin');
	 * $push->positionUpdate(323, 25, 45, array(20,21), array('title'=>'文章标题','thumb'=>'缩略图路径','inputtime'='时间戳'));
	 */
	public function positionUpdate($id, $modelid, $catid, $posid, $data, $expiration = 0, $undel = 0, $model = 'Content') {
		$arr = $param = array();
		$id = intval($id);
		if($id == '0') return false;
		$modelid = intval($modelid);
		$data['inputtime'] = $data['inputtime'] ? $data['inputtime'] : $GLOBALS['_beginTime'];

		//组装属性参数
		$arr['modelid'] = $modelid;
		$arr['catid'] =  $catid;
		$arr['posid'] =  $posid;
		$arr['dosubmit'] =  '1';

		//组装数据
		$param[0] = $data;
		$param[0]['id'] = $id;
		if ($undel==0) $pos_info = $this->positionDel($catid, $id, $posid);
		return $this->positionList($param, $arr, $expiration, $model) ? true : false;
	}

	/**
	 * 推荐位删除计算
	 * Enter description here ...
	 * @param int $catid 栏目ID
	 * @param int $id 文章id
	 * @param array $input_posid 传入推荐位数组
	 */
	private function positionDel($catid,$id,$input_posid) {
		$array = array();
		$posData = M('PositionData');

		//查找已存在推荐位
		$r = $posData->where(array('id'=>$id,'catid'=>$catid))->field('posid')->select();
		if(!$r) return false;
		foreach ($r as $v) $array[] = $v['posid'];

		//差集计算，需要删除的推荐
		$real_posid = implode(',', array_diff($array,$input_posid));

		if (!$real_posid) return false;

		$sql = "`catid`='$catid' AND `id`='$id' AND `posid` IN ($real_posid)";
		return $posData->where($sql)->delete() ? true : false;
	}

	/**
	 * 判断文章是否被推荐
	 * @param $id
	 * @param $modelid
	 */
	private function contentPos($id, $modelid) {
		$id = intval($id);
		$modelid = intval($modelid);
		if ($id && $modelid) {
			$db_data = M('PositionData');
			$this->dbContent = M('Content');
			$MODEL = cache('model','Commons');
			$this->dbContent->tableName = $this->dbContent->tablePrefix.$MODEL[$modelid]['tablename'];
			$posids = $db_data->where(array('id'=>$id,'modelid'=>$modelid))->find() ? 1 : 0;
			if ($posids==0) $this->dbContent->data(array('posids'=>$posids))->where(array('id'=>$id))->save();
		}
		return true;
	}

	/**
	 * 接口处理方法
	 * @param array $param 属性 请求时，为模型、栏目数组。提交添加为二维信息数据 。例：array(1=>array('title'=>'多发发送方法', ....))
	 * @param array $arr 参数 表单数据，只在请求添加时传递。 例：array('modelid'=>1, 'catid'=>12);
	 * @param int $expiration 过期时间设置
	 * @param string $model 调取的数据库型名称
	 */
	public function positionList($param = array(), $arr = array(), $expiration = 0, $model = 'content') {
		if ($arr['dosubmit']) {
			if (!$model) {
				$model = 'content';
			} else {
				$model = $model;
			}
			$db = D($model);//TODO D('content')
			$modelid = intval($arr['modelid']);
			$catid = intval($arr['catid']);
			$expiration = intval($expiration)>$GLOBALS['_beginTime'] ? intval($expiration) : 0;
			$db->setModel($modelid);
			$info = $r = array();
			$posData = M('PositionData');
			$position_info = cache('position', 'Commons');
			$fulltext_array = cache('model_field_'.$modelid,'Model');
			if (is_array($arr['posid']) && !empty($arr['posid']) && is_array($param) && !empty($param)) {
				foreach ($arr['posid'] as $pid) {
					$ext = $func_char = '';
					$r = $this->db->where(array('posid'=>$pid))->field('extention')->find(); //检查推荐位是否启用了扩展字段
					$ext = $r['extention'] ? $r['extention'] : '';
					if ($ext) {
						$ext = str_replace(array('\'', '"', ' '), '', $ext);
						$func_char = strpos($ext, '(');
						if ($func_char) {
							$func_name = $param_k = $param_arr = '';
							$func_name = substr($ext, 0, $func_char);
							$param_k = substr($ext, $func_char+1, strrpos($ext, ')')-($func_char+1));
							$param_arr = explode(',', $param_k);
						}
					}
					foreach ($param as $d) {
						$info['id'] = $info['listorder'] = $d['id'];
						$info['catid'] = $catid;
						$info['posid'] = $pid;
						$info['module'] = $model == 'yp_content_model' ? 'yp' : 'content';
						$info['modelid'] = $modelid;
						$fields_arr = $fields_value = '';
						foreach($fulltext_array AS $key=>$value){
							$fields_arr[] = '{'.$key.'}';
							$fields_value[] = $d[$key];
							if($value['isposition']) {
								if ($d[$key]) $info['data'][$key] = $d[$key];
							}
						}
						if ($ext) {
							if ($func_name) {
								foreach ($param_arr as $k => $v) {
									$c_func_name = $c_param = $c_param_arr = $c_func_char = '';
									$c_func_char = strpos($v, '(');
									if ($c_func_char) {
										$c_func_name = substr($v, 0, $c_func_char);
										$c_param = substr($v, $c_func_char+1, strrpos($v, ')')-($c_func_char+1));
										$c_param_arr = explode(',', $c_param);
										$param_arr[$k] = call_user_func_array($c_func_name, $c_param_arr);
									} else {
										$param_arr[$k] = str_replace($fields_arr, $fields_value, $v);
									}
								}
								$info['extention'] = call_user_func_array($func_name, $param_arr);
							} else {
								$info['extention'] = $d[$ext];
							}
						}
						//颜色选择为隐藏域 在这里进行取值
						$info['data']['style'] = $d['style'];
						$info['thumb'] = $info['data']['thumb'] ? 1 : 0;
						$info['siteid'] = get_siteid();
						$info['data'] = array2string($info['data']);
						$info['expiration'] = $expiration;

						if ($r = $posData->where(array('id'=>$d['id'], 'posid'=>$pid, 'catid'=>$info['catid']))->find()) {
							if($r['synedit'] == '0') $posData->data($info)->where(array('id'=>$d['id'], 'posid'=>$pid, 'catid'=>$info['catid']))->save();
						} else {
							$posData->data($info)->add();
						}
						$db->data(array('posids'=>1))->where(array('id'=>$d['id']))->save();
						unset($info);
					}
					$maxnum = $position_info[$pid]['maxnum']+4;
					$r = $posData->where(array('catid'=>$catid, 'posid'=>$pid))->field('id, listorder')->limit($maxnum.',1')->order('listorder DESC, id DESC')->select();
					if ($r && $position_info[$pid]['maxnum']) {
						$listorder = $r[0]['listorder'];
						$where = '`catid`='.$catid.' AND `posid`='.$pid.' AND `listorder`<'.$listorder;
						$result = $posData->where($where)->field('id, modelid')->select();
						foreach ($result as $r) {
							$posData->where(array('id'=>$r['id'], 'posid'=>$pid, 'catid'=>$catid))->delte();
							$this->contentPos($r['id'], $r['modelid']);
						}
					}
				}
			}
			return true;

		} else {
			$infos = $info = array();
			$where = '1';
			$siteid = get_siteid();
			$category = cache('category_content_'.$siteid,'Commons');
			$positions = cache('position', 'Commons');
			if(!empty($positions)) {
				foreach ($positions as $pid => $p) {
					//if ($p['catid']) $catids = array_keys((array)subcat($p['catid'], 0, 1));
					//获取栏目下所有子栏目
					if ($p['catid']) $catids = explode(',',$category[$p['catid']]['arrchildid']);
					if (($p['siteid']==0 || $p['siteid']==$siteid) && ($p['modelid']==0 || $p['modelid']==$param['modelid']) && ($p['catid']==0 || in_array($param['catid'], $catids))) {
						$info[$pid] = $p['name'];
					}
				}
				return array(
					'posid' => array('name'=>L('position'), 'htmltype'=>'checkbox', 'defaultvalue'=>'', 'data'=>$info, 'validator'=>array('min'=>1)),
				);
			}
		}
	}
 }

 ?>