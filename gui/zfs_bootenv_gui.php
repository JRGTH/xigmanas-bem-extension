<?php
/*
	zfs_bootenv_gui.php

	Copyright (c) 2018 - 2019 Jose Rivera (JoseMR)
    All rights reserved.

	Portions of XigmaNAS® (https://www.xigmanas.com).
	Copyright (c) 2018 XigmaNAS® <info@xigmanas.com>.
	XigmaNAS® is a registered trademark of Michael Zoon (zoon01@xigmanas.com).
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

$sphere_scriptname = basename(__FILE__);
$sphere_scriptname_child = 'zfs_bootenv_edit_gui.php';
$sphere_header = 'Location: '.$sphere_scriptname;
$sphere_header_parent = $sphere_header;
$sphere_array = [];
$sphere_record = [];
$checkbox_member_name = 'checkbox_member_array';
$checkbox_member_array = [];
$checkbox_member_record = [];
$gt_record_add = gtext('Create BE');
$gt_record_mod = gtext('Edit BE');
$gt_selection_delete = gtext('Delete Selected Boot Environments');
$gt_record_inf = gtext('Information');
$gt_selection_delete_confirm = gtext('Do you want to delete selected boot environment(s)?');
$img_path = [
	'add' => 'images/add.png',
	'mod' => 'images/edit.png',
	'del' => 'images/delete.png',
	'loc' => 'images/locked.png',
	'unl' => 'images/unlocked.png',
	'mai' => 'images/maintain.png',
	'inf' => 'images/info.png',
	'ena' => 'images/status_enabled.png',
	'dis' => 'images/status_disabled.png',
	'mup' => 'images/up.png',
	'mdn' => 'images/down.png'
];

function get_zfs_be() {
	$result = [];
	$cmd = 'beadm list -H 2>&1';
	mwexec2($cmd,$rawdata);
	foreach($rawdata as $line):
		$a = preg_split('/\t/',$line);
		$r = [];
		$name = $a[0];
		$r['bename'] = $name;
		// the following regex splits the be name.
		if(preg_match('/^([^\/\@]+)(\/([^\@]+))?\@(.*)$/', $name)):
			$r['be'] = $m[1];
			$r['name'] = $m[1];
		else:
			$r['be'] = 'unknown'; // XXX
			$r['name'] = 'unknown'; // XXX
			$r['stat'] = $a[1];
			$r['mount'] = $a[2];
			$r['name'] = $name;
		endif;
		$r['used'] = $a[3];
		$r['creation'] = $a[4];
		$r['epoch']   = strtotime($r['creation']); // Convert the datetime string to epoch.
		$result[] = $r;
	endforeach;
	return $result;
}
$a_bename = get_zfs_be();

if(isset($_SESSION['filter_time'])):
	$filter_time = $_SESSION['filter_time'];
else:
	$filter_time = '1week';
endif;
$l_filter_time = [
    '1week' => sprintf(gettext('%d week'),1),
    '2weeks' => sprintf(gettext('%d weeks'),2),
    '30days' => sprintf(gettext('%d days'),30),
    '60days' => sprintf(gettext('%d days'),60),
    '90days' => sprintf(gettext('%d days'),90),
    '180days' => sprintf(gettext('%d days'),180),
    '0' => gettext('All')
];

function get_zfs_be_filter($bootenvs,$filter) {
	if($filter['time'] == 0):
		return $bootenvs;
	else:
		$now = time() / 86400;
		$now *= 86400;
		$f_time = strtotime(sprintf('-%s',$filter['time']),$now);
		$result = [];
		foreach($bootenvs as $bootenv):
			if($bootenv['epoch'] >= $f_time):
				$result[] = $bootenv;
			endif;
		endforeach;
	endif;
	return $result;
}
$sphere_array = get_zfs_be_filter($a_bename,['time' => $filter_time]);

if($_POST):
	if(isset($_POST['filter']) && $_POST['filter']):
		$_SESSION['filter_time'] = $_POST['filter_time'];
		header($sphere_header);
		exit;
	endif;
	if(isset($_POST['apply']) && $_POST['apply']):
		$ret = array('output' => [], 'retval' => 0);
		if(!file_exists($d_sysrebootreqd_path)):
			// Process notifications
			$ret = zfs_updatenotify_process($sphere_notifier, $sphere_notifier_processor);
		endif;
		$savemsg = get_std_save_message($ret['retval']);
		if($ret['retval'] == 0):
			updatenotify_delete($sphere_notifier);
			header($sphere_header);
			exit;
		endif;
		updatenotify_delete($sphere_notifier);
		$errormsg = implode("\n", $ret['output']);
	endif;

	if(isset($_POST['delete_selected_be']) && $_POST['delete_selected_be']):
		$checkbox_member_array = isset($_POST[$checkbox_member_name]) ? $_POST[$checkbox_member_name] : [];
		foreach($checkbox_member_array as $checkbox_member_record):
			if(false !== ($index = array_search_ex($checkbox_member_record, $sphere_array, 'bename'))):
				if(!isset($sphere_array[$index]['protected'])):
					$cmd = ("/usr/local/sbin/beadm destroy -F {$checkbox_member_record}");
					$return_val = mwexec($cmd);
					if($return_val == 0):
						//$savemsg .= gtext("Boot Environment(s) deleted successfully.");
						header($sphere_header);
					else:
						$errormsg .= gtext("Failed to delete Boot Environment(s).");
					endif;
				endif;
			endif;
		endforeach;
	endif;
endif;

$pgtitle = [gtext("Extensions"), gtext('Boot Environments')];
include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load", function() {
	// Init action buttons
	$("#delete_selected_be").click(function () {
		return confirm('<?=$gt_selection_delete_confirm;?>');
	});
	// Disable action buttons.
	disableactionbuttons(true);

	// Init member checkboxes
	$("input[name='<?=$checkbox_member_name;?>[]']").click(function() {
		controlactionbuttons(this, '<?=$checkbox_member_name;?>[]');
	});
	// Init spinner onsubmit()
	$("#iform").submit(function() { spinner(); });
	$(".spin").click(function() { spinner(); });
});
function disableactionbuttons(ab_disable) {
	$("#activate_selected_be").prop("disabled", ab_disable);
	$("#activate_reboot_selected_be").prop("disabled", ab_disable);
	$("#delete_selected_be").prop("disabled", ab_disable);
}

function controlactionbuttons(ego, triggerbyname) {
	var a_trigger = document.getElementsByName(triggerbyname);
	var n_trigger = a_trigger.length;
	var ab_disable = true;
	var i = 0;
	for (; i < n_trigger; i++) {
		if (a_trigger[i].type == 'checkbox') {
			if (a_trigger[i].checked) {
				ab_disable = false;
				break;
			}
		}
	}
	disableactionbuttons(ab_disable);
}
//]]>
</script>
<?php
$document = new co_DOMDocument();
$document->
	add_area_tabnav()->
		push()->
		add_tabnav_upper()->
			ins_tabnav_record('zfs_bootenv_gui.php',gettext('Boot Environments'))->
			ins_tabnav_record('zfs_bootenv_info_gui.php',gettext('Information'))->
			ins_tabnav_record('zfs_bootenv_maintain_gui.php',gettext('Maintenance'));
$document->render();
?>
<form action="zfs_bootenv_gui.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
<?php
	if(!empty($errormsg)):
		print_error_box($errormsg);
	endif;
	if(!empty($savemsg)):
		print_info_box($savemsg);
	endif;
	if(updatenotify_exists($sphere_notifier)):
		print_config_change_box();
	endif;
?>
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			html_titleline2(gettext('Filter'));
?>
		</thead>
		<tbody>
<?php
			html_combobox2('filter_time', gettext('Age'), $filter_time, $l_filter_time, '');
?>
		</tbody>
	</table>
	<div id="submit">
		<input type="submit" class="formbtn" id="filter" name="filter" value="<?=gtext('Apply Filter');?>"/>
	</div>
	<table class="area_data_selection">
		<colgroup>
			<col style="width:5%">
			<col style="width:35%">
			<col style="width:5%">
			<col style="width:35%">
			<col style="width:10%">
			<col style="width:10%">
			<col style="width:10%">
		</colgroup>
		<thead>
<?php
			html_separator2();
			html_titleline2(gettext('Overview'), 7);
?>
			<tr>
				<th class="lhelc"><?=gtext('Select');?></th>
				<th class="lhell"><?=sprintf('%1$s (%2$d/%3$d)', gtext('Boot Environment'), count($sphere_array), count($a_bename));?></th>
				<th class="lhell"><?=gtext('Status');?></th>
				<th class="lhell"><?=gtext('Mountpoint');?></th>
				<th class="lhell"><?=gtext('Used');?></th>
				<th class="lhell"><?=gtext('Create Date');?></th>
				<th class="lhebl"><?=gtext('Toolbox');?></th>
			</tr>
		</thead>
		<tbody>
<?php
			foreach ($sphere_array as $sphere_record):
				$notificationmode = updatenotify_get_mode($sphere_notifier, $identifier);
				$notdirty = (UPDATENOTIFY_MODE_DIRTY != $notificationmode) && (UPDATENOTIFY_MODE_DIRTY_CONFIG != $notificationmode);
				$notprotected = !isset($sphere_record['protected']);
?>
				<tr>
					<td class="lcelc">
<?php
						if ($notdirty && $notprotected):
?>
							<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$sphere_record['bename'];?>" id="<?=$sphere_record['bename'];?>"/>
<?php
						else:
?>
							<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$sphere_record['bename'];?>" id="<?=$sphere_record['bename'];?>" disabled="disabled"/>
<?php
						endif;
?>
					</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['name']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['stat']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['mount']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['used']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['creation']);?>&nbsp;</td>
					<td class="lcebld">
						<table class="area_data_selection_toolbox"><tbody><tr>
							<td>
<?php
								if($notdirty && $notprotected):
?>
									<a href="<?=$sphere_scriptname_child;?>?bename=<?=urlencode($sphere_record['bename']);?>"><img src="<?=$img_path['mod'];?>" title="<?=$gt_record_mod;?>" alt="<?=$gt_record_mod;?>"  class="spin oneemhigh"/></a>
<?php
								else:
									if ($notprotected):
?>
										<img src="<?=$img_path['del'];?>" title="<?=$gt_record_del;?>" alt="<?=$gt_record_del;?>"/>
<?php
									else:
?>
										<img src="<?=$img_path['loc'];?>" title="<?=$gt_record_loc;?>" alt="<?=$gt_record_loc;?>"/>
<?php
									endif;
								endif;
?>
							</td>
							<td></td>
							<td>
								<a href="zfs_bootenv_info_gui.php?uuid=<?=urlencode($sphere_record['bename']);?>"><img src="<?=$g_img['inf'];?>" title="<?=$gt_record_inf?>" alt="<?=$gt_record_inf?>"/></a>
							</td>
						</tr></tbody></table>
					</td>
				</tr>
<?php
			endforeach;
?>
		</tbody>
		<tfoot>
			<tr>
				<td class="lcenl" colspan="6"></td>
				<td class="lceadd">
					<a href="zfs_bootenv_add_gui.php"><img src="<?=$img_path['add'];?>" title="<?=$gt_record_add;?>" border="0" alt="<?=$gt_record_add;?>" class="spin oneemhigh"/></a>
				</td>
			</tr>
		</tfoot>
	</table>
	<div id="submit">
		<input name="delete_selected_be" id="delete_selected_be" type="submit" class="formbtn" value="<?=$gt_selection_delete;?>"/>
	</div>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<?php
include 'fend.inc';
