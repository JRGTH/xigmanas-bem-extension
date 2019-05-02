<?php
/*
	zfs_bootenv_edit_gui.php

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

if(isset($_GET['uuid'])):
	$uuid = $_GET['uuid'];
endif;
if(isset($_POST['uuid'])):
	$uuid = $_POST['uuid'];
endif;

$pgtitle = [gtext("Extensions"), gtext('Boot Environment'),gtext('Edit')];

if(isset($_GET['bename'])):
	$bootenv = $_GET['bename'];
endif;
if(isset($_POST['bename'])):
	$bootenv = $_POST['bename'];
endif;
$cnid = FALSE;
// This section will be used for future features.
if(isset($bootenv) && !empty($bootenv)):
	$pconfig['uuid'] = uuid();
	$pconfig['bename'] = $bootenv;
	if(preg_match('/^([^\/\@]+)(\/([^\@]+))?\@(.*)$/', $pconfig['bename'], $m)):
		$pconfig['name'] = $m[''];
	else:
		$pconfig['name'] = '';
	endif;
	$pconfig['newname'] = '';
	$pconfig['recursive'] = false;
	$pconfig['action'] = 'activate';
else:
	// not supported
	$pconfig = [];
endif;

if($_POST):
	unset($input_errors);
	$pconfig = $_POST;
	if(isset($_POST['Cancel']) && $_POST['Cancel']):
		header('Location: zfs_bootenv_gui.php');
		exit;
	endif;
	if(isset($_POST['action'])):
		$action = $_POST['action'];
	endif;
	if(empty($action)):
		$input_errors[] = sprintf(gtext("The attribute '%s' is required."), gtext("Action"));
	else:
		switch($action):
			case 'activate':
				// Input validation not required
				if(empty($input_errors)):
					$bootenv = [];
					$bootenv['uuid'] = $_POST['uuid'];
					$bootenv['bename'] = $_POST['bename'];

					$item = $bootenv['bename'];
					$cmd = ("/usr/local/sbin/beadm activate {$item}");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($return_val == 0):
						header('Location: zfs_bootenv_gui.php');
						exit;
					else:
						$errormsg .= gtext("Failed to activate Boot Environment.");
					endif;
				endif;
				break;

			case 'rename':
				// Input validation
				$reqdfields = ['newname'];
				$reqdfieldsn = [gtext('Name')];
				$reqdfieldst = ['string'];
				do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
				do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);
				if(preg_match("/^([^\/\@]+)(\/([^\@]+))?\@(.*)$/", $_POST['newname'])):
					$input_errors[] = sprintf(gtext("The attribute '%s' contains invalid characters."), gtext("Name"));
				endif;
				if(empty($input_errors)):
					$bootenv = [];
					$bootenv['uuid'] = $_POST['uuid'];
					$bootenv['name'] = $_POST['newname'];
					$bootenv['bename'] = $_POST['bename'];
					$bootenv['dateadd'] = isset($_POST['dateadd']) ? true : false;

					$item = $bootenv['bename'];
					$date = strftime('%Y-%m-%d-%H%M%S');
					$newname = $bootenv['name'];
					$cmd1 = ("/usr/local/sbin/beadm rename {$item} {$newname}");
					$cmd2 = ("/usr/local/sbin/beadm rename {$item} {$newname}-{$date}");
					if ($_POST['dateadd']):
						unset($output,$retval);mwexec2($cmd2,$output,$retval);
						if($retval == 0):
							header('Location: zfs_bootenv_gui.php');
							exit;
						else:
							$errormsg .= gtext("Failed to rename Boot Environment.");
						endif;
					else:
						unset($output,$retval);mwexec2($cmd1,$output,$retval);
						if($retval == 0):
							header('Location: zfs_bootenv_gui.php');
							exit;
						else:
							$errormsg .= gtext("Failed to rename Boot Environment.");
						endif;
					endif;
				endif;
				break;

			case 'mount':
				// Input validation not required
				if(empty($input_errors)):
					$bootenv = [];
					$bootenv['uuid'] = $_POST['uuid'];
					$bootenv['bename'] = $_POST['bename'];

					$item = $bootenv['bename'];
					$mountpoint = "{$mountpoint_def}{$item}{$mount_prefix_def}";
					$cmd = ("/usr/local/sbin/beadm mount {$item} {$mountpoint}");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($return_val == 0):
						header('Location: zfs_bootenv_gui.php');
						exit;
					else:
						$errormsg .= gtext("Failed to mount Boot Environment.");
					endif;
				endif;
				break;

			case 'unmount':
				// Input validation not required
				if(empty($input_errors)):
					$bootenv = [];
					$bootenv['uuid'] = $_POST['uuid'];
					$bootenv['bename'] = $_POST['bename'];

					$item = $bootenv['bename'];
					$mountpoint = "{$mountpoint_def}{$item}{$mount_prefix_def}";
					$cmd = ("/usr/local/sbin/beadm unmount {$item} && rm -r {$mountpoint}");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($retval == 0):
						header('Location: zfs_bootenv_gui.php');
						exit;
					else:
						$errormsg .= gtext("Failed to unmount Boot Environment.");
					endif;
				endif;
				break;

			case 'backup':
				// Input validation not required
				if(empty($input_errors)):
					$bootenv = [];
					$bootenv['uuid'] = $_POST['uuid'];
					$bootenv['bename'] = $_POST['bename'];
					$item = $bootenv['bename'];

					// Take a recent snapshot of the boot environment before backup.
					$date = (strftime('-%Y-%m-%d-%H%M%S'));
					$cmd1 = ("/sbin/zfs snapshot zroot/ROOT/{$item}@{$item}{$date}");
					$return_val = mwexec($cmd1);
					if($return_val == 0):
						header('Location: zfs_bootenv_gui.php');
					else:
						$errormsg .= gtext("Failed to snapshot Boot Environment.");
					endif;

					// Perform the boot environment@snapshot backup.
					$cmd2 = ("/sbin/zfs send {$zfs_sendparams} {$zroot}/{$beds}/{$item}@{$item}{$date} | {$compress_method} > {$backup_path}/{$item}{$date}.{$archive_ext_def}");
					unset($output,$retval);mwexec2($cmd2,$output,$retval);
					if($retval == 0):
						header('Location: zfs_bootenv_gui.php');
						exit;
					else:
						$errormsg .= gtext("Failed to backup Boot Environment@snapshot.");
					endif;
				endif;
				break;

			case 'delete':
				// Input validation not required
				if(empty($input_errors)):
					$bootenv = [];
					$bootenv['uuid'] = $_POST['uuid'];
					$bootenv['bename'] = $_POST['bename'];

					$item = $bootenv['bename'];
					$cmd = ("/usr/local/sbin/beadm destroy -F {$item}");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($retval == 0):
						header('Location: zfs_bootenv_gui.php');
						exit;
					else:
						$errormsg .= gtext("Failed to delete Boot Environment.");
					endif;
				endif;
				break;
			default:
				$input_errors[] = sprintf(gtext("The attribute '%s' is invalid."), 'action');
				break;
		endswitch;
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
function enable_change(enable_change) {
	document.iform.name.disabled = !enable_change;
}
function action_change() {
	showElementById('newname_tr','hide');
	showElementById('dateadd_tr','hide');
	var action = document.iform.action.value;
	switch (action) {
		case "activate":
			showElementById('newname_tr','hide');
			showElementById('dateadd_tr','hide');
			break;
		case "rename":
			showElementById('newname_tr','show');
			showElementById('dateadd_tr','show');
			break;
		case "mount":
			showElementById('newname_tr','hide');
			showElementById('dateadd_tr','hide');
			break;
		case "unmount":
			showElementById('newname_tr','hide');
			showElementById('dateadd_tr','hide');
			break;
		case "backup":
			showElementById('newname_tr','hide');
			showElementById('dateadd_tr','hide');
			break;
		case "delete":
			showElementById('newname_tr','hide');
			showElementById('dateadd_tr','hide');
			break;
		default:
			break;
	}
}
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
<form action="zfs_bootenv_edit_gui.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
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
			html_titleline2(gettext('Edit Boot Environment'));
?>
		</thead>
		<tbody>
<?php
			html_text2('bename',gettext('BE'),htmlspecialchars($pconfig['bename']));
			$a_action = [
				'activate' => gettext('Activate'),
				'rename' => gettext('Rename'),
				'mount' => gettext('mount'),
				'unmount' => gettext('unmount'),
				'backup' => gettext('Backup'),
				'delete' => gettext('Delete'),
			];
			html_combobox2('action',gettext('Action'),$pconfig['action'],$a_action,'',true,false,'action_change()');
			html_inputbox2('newname',gettext('Name'),$pconfig['newname'],'',true,30);
			html_checkbox2('dateadd',gettext('Date'),!empty($pconfig['dateadd']) ? true : false,gettext('Append the date in the following format: BENAME-XXXX-XX-XX-XXXXXX.'),'',false);
?>
		</tbody>
	</table>
	<div id="submit">
		<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Execute");?>" onclick="enable_change(true)" />
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
		<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
		<input name="bename" type="hidden" value="<?=$pconfig['bename'];?>" />
		<input name="pool" type="hidden" value="<?=$pconfig['pool'];?>" />
		<input name="name" type="hidden" value="<?=$pconfig['name'];?>" />
	</div>
	<div id="remarks">
		<?php html_remark("note", gtext("Note"), sprintf(gtext("Some tasks such as backups may render the WebGUI unresponsive until task completes.")));?>
	</div>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<script type="text/javascript">
<!--
enable_change(true);
action_change();
//-->
</script>
<?php
include 'fend.inc';
?>
