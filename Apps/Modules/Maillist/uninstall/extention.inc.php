 <?php 
defined('APP_NAME') or exit('Access Denied');
defined('UNINSTALL') or exit('Access Denied');
$type_db = pc_base::load_model('type_model');
$typeid = $type_db->delete(array('module'=>'maillist'));
if(!$typeid) return FALSE;