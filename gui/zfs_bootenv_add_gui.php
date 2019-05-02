<?php
/*
	zfs_bootenv_add_gui.php

	Copyright (c) 2018 - 2019 Jose Rivera (JoseMR)
    All rights reserved.

	Portions of XigmaNAS (https://www.xigmanas.com).
	Copyright (c) 2018 XigmaNAS <info@xigmanas.com>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once 'zfs.inc';
require_once("zfs_bootenv_gui-lib.inc");

$pgtitle = [gtext("Extensions"), gtext('Boot Environment'),gtext('Add')];

if(!$pconfig['bename']):
	$pconfig['bename'] = 'bootenv';
endif;

if($_POST):
	unset($input_errors);
	$pconfig = $_POST;
	if(isset($_POST['Cancel']) && $_POST['Cancel']):
		header('Location: zfs_bootenv_gui.php');
		exit;
	endif;
	if(!$pconfig['bename']):
		$pconfig['bename'] = 'bootenv';
	endif;
	if(isset($_POST['create_new_be']) && $_POST['create_new_be']):
		$prefix = $pconfig['bename'];
		if ($_POST['dateadd']):
			$date = (strftime('-%Y-%m-%d-%H%M%S'));
		else:
			$date = "";
		endif;
		$cmd1 = ("/usr/local/sbin/beadm create {$prefix}{$date}");
		$cmd2 = ("/usr/local/sbin/beadm create {$prefix}{$date} && /usr/local/sbin/beadm activate {$prefix}{$date}");
		if ($_POST['activate']):
			unset($output,$retval);mwexec2($cmd2,$output,$retval);
			if($retval == 0):
				//$savemsg .= gtext("Boot Environment created and activated successfully.");
				header('Location: zfs_bootenv_gui.php');
				exit;
			else:
				$errormsg .= gtext("Failed to create and/or activate Boot Environment.");
			endif;
		else:
			unset($output,$retval);mwexec2($cmd1,$output,$retval);
			if($retval == 0):
				//$savemsg .= gtext("Boot Environment created successfully.");
				header('Location: zfs_bootenv_gui.php');
				exit;
			else:
				$errormsg .= gtext("Failed to create Boot Environment.");
			endif;
		endif;
	endif;
endif;

include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load",function() {
	$("#iform").submit(function() { spinner(); });
	$(".spin").click(function() { spinner(); });
});
//]]>
</script>
<?php
$document = new co_DOMDocument();
$document->
	add_area_tabnav()->
		push()->
		add_tabnav_upper()->
			ins_tabnav_record('zfs_bootenv_gui.php',gettext('Boot Environments'),gettext('Reload page'),true)->
			ins_tabnav_record('zfs_bootenv_info_gui.php',gettext('Information'),gettext('Reload page'),true)->
			ins_tabnav_record('zfs_bootenv_maintain_gui.php',gettext('Maintenance'),gettext('Reload page'),true);
$document->render();
?>
<form action="zfs_bootenv_add_gui.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
<?php
	if(!empty($errormsg)):
		print_error_box($errormsg);
	endif;
	if(!empty($input_errors)):
		print_input_errors($input_errors);
	endif;
	if(file_exists($d_sysrebootreqd_path)):
		print_info_box(get_std_save_message(0));
	endif;
?>
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			html_titleline2(gettext('Boot Environment'));
?>
		</thead>
		<tbody>
<?php
			html_inputbox2('bename',gettext('Name'),$pconfig['bename'],'',true,20);
			html_checkbox2('activate',gettext('Activate'),!empty($pconfig['activate']) ? true : false,gettext('Activate Boot Environment after creation.'),'',false);
			html_checkbox2('dateadd',gettext('Date'),!empty($pconfig['dateadd']) ? true : true,gettext('Append the date in the following format: BENAME-XXXX-XX-XX-XXXXXX.'),'',false);

?>
		</tbody>
	</table>
	<div id="submit">
		<input name="create_new_be" type="submit" class="formbtn" value="<?=gtext('Add');?>"/>
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext('Cancel');?>" />
		
	</div>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<?php
include 'fend.inc';
?>
