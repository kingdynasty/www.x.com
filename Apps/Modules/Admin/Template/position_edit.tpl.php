<?php
defined('IN_ADMIN') or exit('No permission resources.');
include Admin::adminTpl('header');?>
<script type="text/javascript">
<!--
$(function(){
	$.formValidator.initConfig({autotip:true,formid:"myform",onerror:function(msg){}});
	$("#name").formValidator({onshow:"<?php echo L('input').L('posid_name')?>",onfocus:"<?php echo L('posid_name').L('not_empty')?>"}).inputValidator({min:1,max:999,onerror:"<?php echo L('posid_name').L('not_empty')?>"});
	$("#maxnum").formValidator({onshow:"<?php echo L('input').L('maxnum')?>",onfocus:"<?php echo L('maxnum').L('not_empty')?>"}).inputValidator({min:1,onerror:"<?php echo L('maxnum').L('not_empty')?>"}).regexValidator({datatype:'enum',regexp:'intege1',onerror:'<?php echo L('maxnum').L('not_empty')?>'}).defaultPassed();;		
})
//-->
</script>
<div class="pad_10">
<div class="common-form">
<form name="myform" action="?m=Admin&c=Position&a=edit" method="post" id="myform">
<input type="hidden" name="posid" value="<?php echo $posid?>"></input>
<table width="100%" class="table_form">
<tr>
<td  width="80"><?php echo L('posid_name')?></td> 
<td><input type="text" name="info[name]" class="input-text" value="<?php echo $name?>" id="name"></input></td>
</tr>
<tr>
<td><?php echo L('posid_modelid')?></td> 
<td><?php echo Form::select($modelinfo,$modelid,'name="info[modelid]" onchange="category_load(this);"', L('choose_model'));?></td>
</tr>
<tr>
<td><?php echo L('posid_catid')?></td> 
<td id="load_catid"><?php echo Form::selectCategory('',$catid,'name="info[catid]"',L('please_select_parent_category'));?></td>
</tr>

<tr>
<td><?php echo L('listorder')?></td> 
<td><input type="text" name="info[listorder]" class="input-text" size="5" value="<?php echo $listorder?>"></input></td>
</tr> 
<tr>
<td><?php echo L('maxnum')?></td> 
<td><input type="text" name="info[maxnum]" id="maxnum" class="input-text" size="5" value="<?php echo $maxnum?>"></input><?php echo L('posid_num')?></td>
</tr> 
<tr>
<td><?php echo L('extention_name')?></td> 
<td><input type="text" name="info[extention]" id="extention" class="input-text" size="30" value="<?php echo $extention?>"></input></td>
</tr> 
</table>

    <div class="bk15"></div>
    <input name="dosubmit" type="submit" value="<?php echo L('submit')?>" class="dialog" id="dosubmit">
</form>
<div class="explain-col">
<?php echo L('position_tips')?><br/>
<?php echo L('extention_name_tips')?>
</div>
</div>
</div>
</body>
</html>
<script type="text/javascript">
function category_load(obj)
{
	var modelid = $(obj).attr('value');
	$.get('?m=Admin&c=Position&a=publicCategoryLoad&modelid='+modelid,function(data){
			$('#load_catid').html(data);
		  });
}
</script>


