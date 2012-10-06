<?php
defined('APP_NAME') or exit('No permission resources.');
import('@.Util.Admin');

class PhpssoAction extends BaseAction {
	function __construct() {
		parent::__construct();
	}
	
	function menu() {
	}
	
	
	function public_menu_left() {
		$setting = pc_base::load_config('system');

		include $this->admin_tpl('phpsso');
	}
}
?>