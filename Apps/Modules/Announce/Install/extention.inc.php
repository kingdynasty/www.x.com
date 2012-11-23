<?php
defined('APP_NAME') or exit('Access Denied');
defined('INSTALL') or exit('Access Denied');

$parentid = $menu_db->data(array('name'=>'announce', 'parentid'=>29, 'm'=>'announce', 'c'=>'admin_announce', 'a'=>'init', 'data'=>'s=1', 'listorder'=>0, 'display'=>'1'))->add();
$menu_db->data(array('name'=>'announce_add', 'parentid'=>$parentid, 'm'=>'announce', 'c'=>'admin_announce', 'a'=>'add', 'data'=>'', 'listorder'=>0, 'display'=>'0'))->add();
$menu_db->data(array('name'=>'edit_announce', 'parentid'=>$parentid, 'm'=>'announce', 'c'=>'admin_announce', 'a'=>'edit', 'data'=>'s=1', 'listorder'=>0, 'display'=>'0'))->add();
$menu_db->data(array('name'=>'check_announce', 'parentid'=>$parentid, 'm'=>'announce', 'c'=>'admin_announce', 'a'=>'init', 'data'=>'s=2', 'listorder'=>0, 'display'=>'1'))->add();
$menu_db->data(array('name'=>'overdue', 'parentid'=>$parentid, 'm'=>'announce', 'c'=>'admin_announce', 'a'=>'init', 'data'=>'s=3', 'listorder'=>0, 'display'=>'1'))->add();
$menu_db->data(array('name'=>'del_announce', 'parentid'=>$parentid, 'm'=>'announce', 'c'=>'admin_announce', 'a'=>'delete', 'data'=>'', 'listorder'=>0, 'display'=>'0'))->add();

$language = array('announce'=>'公告', 'announce_add'=>'添加公告', 'edit_announce'=>'编辑公告', 'check_announce'=>'审核公告', 'overdue'=>'过期公告', 'del_announce'=>'删除公告');
?>