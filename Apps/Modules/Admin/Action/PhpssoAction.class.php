<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);

class PhpssoAction extends BaseAction {
	function __construct() {
		parent::__construct();
	}
	
	function menu() {
	}
	
	
	function publicMenuLeft() {

		include Admin::adminTpl('phpsso');
	}
}
?>