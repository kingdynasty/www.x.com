<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include Admin::adminTpl('header','admin');
?>
<div class="pad-lr-10">
<form name="myform" id="myform" action="?m=Admin&c=Badword&a=delete" method="post" onsubmit="checkuid();return false;">
<div class="table-list">
 <table width="100%" cellspacing="0">
        <thead>
            <tr>
            <th width="35" align="center"><input type="checkbox" value="" id="check_box" onclick="selectall('badid[]');"></th>
             <th ><?php echo L('badword_name')?></th>
            <th ><?php echo L('badword_replacename')?></th>
            <th width="80"><?php echo L('badword_level')?></th>
            <th width="120"><?php echo L('inputtime')?></th>
             <th width="120"><?php echo L('operations_manage')?></th>
            </tr>
        </thead>
    <tbody>
 <?php
if(is_array($infos)){
foreach($infos as $info){
?>
    <tr>
    <td align="center">
	<input type="checkbox" name="badid[]" value="<?php echo $info['badid']?>">
	</td>
         <td align="center"><span  class="<?php echo $info['style']?>"><?php echo $info['badword']?></span> </td>
        <td align="center"><?php echo $info['replaceword']?></td>
        <td align="center"><?php echo $level[$info['level']]?></td>
        <td align="center"><?php echo $info['lastusetime'] ? date('Y-m-d H:i', $info['lastusetime']):''?></td>
         <td align="center"><a href="javascript:edit(<?php echo $info['badid']?>, '<?php echo new_addslashes($info['badword'])?>')"><?php echo L('edit')?></a> | <a href="javascript:confirmurl('?m=Admin&c=Badword&a=delete&badid=<?php echo $info['badid']?>', '<?php echo L('badword_confirm_del')?>')"><?php echo L('delete')?></a> </td>
    </tr>
<?php
	}
}
?>
</tbody>
 </table>
 <div class="btn">
 <a href="#" onClick="javascript:$('input[type=checkbox]').attr('checked', true)"><?php echo L('selected_all')?></a>/<a href="#" onClick="javascript:$('input[type=checkbox]').attr('checked', false)"><?php echo L('cancel')?></a>
<input type="submit" name="submit" class="button" value="<?php echo L('remove_all_selected')?>" onClick="return confirm('<?php echo L('badword_confom_del')?>')" /> 
</div> 
<div id="pages"><?php echo $pages?></div>
</div>
</form>
</div>
</body>
</html>
<script type="text/javascript">
function edit(id, name) {
	window.top.art.dialog({id:'edit'}).close();
	window.top.art.dialog({title:'<?php echo L('badword_edit')?> '+name+' ',id:'edit',iframe:'?m=Admin&c=Badword&a=edit&badid='+id,width:'450',height:'180'}, function(){var d = window.top.art.dialog({id:'edit'}).data.iframe;var form = d.document.getElementById('dosubmit');form.click();return false;}, function(){window.top.art.dialog({id:'edit'}).close()});
}

function checkuid() {
	var ids='';
	$("input[name='badid[]']:checked").each(function(i, n){
		ids += $(n).val() + ',';
	});
	if(ids=='') {
		window.top.art.dialog({content:'<?php echo L('badword_pleasechose')?>',lock:true,width:'200',height:'50',time:1.5},function(){});
		return false;
	} else {
		myform.submit();
	}
}
</script>

 