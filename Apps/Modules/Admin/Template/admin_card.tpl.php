<?php
defined('IN_ADMIN') or exit('No permission resources.');
include Admin::adminTpl('header', 'admin');
?>
<div class="pad_10">
<div class="bk15"></div>

<div class="explain-col">
<?php echo L('card_msg')?>
</div>
<div class="bk15"></div>
<?php 
if (empty($pic_url)) {
	echo '<input type="button" class="button" value="'.L('apply_for_a_password_card').'" onclick="location.href=\'?m=Admin&c=AdminManage&a=creatCard&userid='.$userid.'&pc_hash='.$_SESSION['pc_hash'].'\'">';
} else {
	echo '<input type="button" class="button" value="'.L('the_password_card_binding').'" onclick="location.href=\'?m=Admin&c=AdminManage&a=removeCard&userid='.$userid.'&pc_hash='.$_SESSION['pc_hash'].'\'"><div class="bk15"></div><img src="'.$pic_url.'">';
}
?>
</div>
</body>
</html>
