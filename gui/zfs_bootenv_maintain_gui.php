<?php
/*
	zfs_bootenv_maintain_gui.php

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
require("auth.inc");
require("guiconfig.inc");
require_once 'zfs.inc';
require_once("zfs_bootenv_gui-lib.inc");

$application = "Boot Environments Manager";
$prdname = "beadm";
$pgtitle = array(gtext("Extensions"), "Boot Environments", "Maintenance");

// For NAS4Free 10.x versions.
$return_val = mwexec("/bin/cat /etc/prd.version | cut -d'.' -f1 | /usr/bin/grep '10'", true);
if ($return_val == 0) {
	if (is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) { if (preg_match('/beminit/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
	}
}

// Set default backup directory.
if (1 == mwexec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR='")) {
	if (is_file("{$configfile}")) exec("/usr/sbin/sysrc -f {$configfile} BACKUP_DIR={$rootfolder}/backup");
}
$backup_path = exec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR=' | cut -d'\"' -f2");

if ($_POST) {
	if(isset($_POST['upgrade']) && $_POST['upgrade']):
		$cmd = sprintf('%1$s/beminit -u > %2$s',$rootfolder,$logevent);
		$return_val = 0;
		$output = [];
		exec($cmd,$output,$return_val);
		if($return_val == 0):
			ob_start();
			include("{$logevent}");
			$ausgabe = ob_get_contents();
			ob_end_clean(); 
			$savemsg .= str_replace("\n", "<br />", $ausgabe)."<br />";
		else:
			$input_errors[] = gtext('An error has occurred during upgrade process.');
			$cmd = sprintf('echo %s: %s An error has occurred during upgrade process. >> %s',$date,$application,$logfile);
			exec($cmd);
		endif;
	endif;

	if (isset($_POST['restore']) && $_POST['restore']) {
		$backup_file = ($_POST['backup_path']);
		$bedate = (strftime('-%Y-%m-%d-%H%M%S'));
		$cmd = "{$decompress_method} {$backup_file} | /sbin/zfs receive {$zfs_recvparams} {$zroot}/{$beds}/{$restore_name}{$bedate}";
		unset($output,$retval);mwexec2($cmd,$output,$retval);
		if ($retval == 0) {
			$savemsg .= gtext("Boot Environment [{$backup_file}] restored successfully.");
			exec("echo '{$date}: {$application} {$backup_file} restored successfully' >> {$logfile}");
		}
		else {
			$input_errors[] = gtext("Boot Environment [{$backup_file}] restore failed.");
			exec("echo '{$date}: {$application} {$backup_file} restore failed' >> {$logfile}");
		}
	}

	// Remove only extension related files during cleanup.
	if (isset($_POST['uninstall']) && $_POST['uninstall']) {
		bindtextdomain("xigmanas", $textdomain);
		if (is_link($textdomain_bem)) mwexec("rm -f {$textdomain_bem}", true);
		if (is_dir($confdir)) mwexec("rm -rf {$confdir}", true);
		mwexec("{$rootfolder}/beminit -t", true);
		mwexec("echo 'y' | {$rootfolder}/beminit -d", true);
		
		if (isset($_POST['bedata'])) {
			$uninstall_bedata = "{$backup_path}";
			$uninstall_cmd = "rm -rf {$uninstall_bedata}";
			mwexec($uninstall_cmd, true);
			}

		// Remove postinit cmd in NAS4Free 10.x versions.
		$return_val = mwexec("/bin/cat /etc/prd.version | cut -d'.' -f1 | /usr/bin/grep '10'", true);
			if ($return_val == 0) {
				if (is_array($config['rc']['postinit']) && is_array($config['rc']['postinit']['cmd'])) {
					for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
					if (preg_match('/beminit/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]); }
					++$i;
				}
			}
			write_config();
		}

		// Remove postinit cmd in NAS4Free later versions.
		if (is_array($config['rc']) && is_array($config['rc']['param'])) {
			$postinit_cmd = "{$rootfolder}/beminit";
			$value = $postinit_cmd;
			$sphere_array = &$config['rc']['param'];
			$updateconfigfile = false;
		if (false !== ($index = array_search_ex($value, $sphere_array, 'value'))) {
			unset($sphere_array[$index]);
			$updateconfigfile = true;
		}
		if ($updateconfigfile) {
			write_config();
			$updateconfigfile = false;
		}
	}
	header("Location:index.php");
}

	if (isset($_POST['save']) && $_POST['save']) {
		// Ensure to have NO whitespace & trailing slash.
		$backup_path = rtrim(trim($_POST['backup_path']),'/');
		if ("{$backup_path}" == "") {
			$backup_path = "{$rootfolder}/backup";
			}
		else {
			exec("/usr/sbin/sysrc -f {$configfile} BACKUP_DIR={$backup_path}");
			}
		exec("echo '{$date}: Extension settings saved' >> {$logfile}");
	}
}

// Update some variables.
$backup_path = exec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR=' | cut -d'\"' -f2");
if (!is_dir($backup_path)) mwexec("mkdir -p {$backup_path}", true);

function get_version_beadm() {
		//exec("/usr/local/sbin/pkg info -I {$prdname}", $result);
		exec("/usr/local/sbin/beadm version", $result);
		return ($result[0]);
}

function get_version_ext() {
	global $versionfile;
	exec("/bin/cat {$versionfile}", $result);
	return ($result[0]);
}

if (is_ajax()) {
	$getinfo['beadm'] = get_version_beadm();
	$getinfo['ext'] = get_version_ext();
	render_ajax($getinfo);
}

bindtextdomain("xigmanas", $textdomain);
include("fbegin.inc");
bindtextdomain("xigmanas", $textdomain_bem);
?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 2000, 'zfs_bootenv_maintain_gui.php', null, function(data) {
		$('#getinfo').html(data.info);
		$('#getinfo_pid').html(data.pid);
		$('#getinfo_beadm').html(data.beadm);
		$('#getinfo_ext').html(data.ext);
	});
});
//]]>
</script>
<!-- The Spinner Elements -->
<script src="js/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<script type="text/javascript">
<!--
//-->
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
<form action="zfs_bootenv_maintain_gui.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr><td class="tabcont">
			<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
			<?php if (!empty($savemsg)) print_info_box($savemsg);?>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_titleline(gtext("Summary"));?>
				<?php html_text("installation_directory", gtext("Installation directory"), sprintf(gtext("The extension is installed in %s"), $rootfolder));?>
				<tr>
					<td class="vncellt"><?=gtext("beadm version");?></td>
					<td class="vtable"><span name="getinfo_beadm" id="getinfo_beadm"><?=get_version_beadm()?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><?=gtext("Extension version");?></td>
					<td class="vtable"><span name="getinfo_ext" id="getinfo_ext"><?=get_version_ext()?></span></td>
				</tr>
				<?php html_filechooser("backup_path", gtext("Backup directory"), $backup_path, gtext("Directory to store Boot Environments archive.zfs files, use as file chooser for restoring from file."), $backup_path, true, 60);?>
			</table>
			<div id="submit">
				<input id="save" name="save" type="submit" class="formbtn" title="<?=gtext("Save settings");?>" value="<?=gtext("Save");?>"/>
				<input name="upgrade" type="submit" class="formbtn" title="<?=gtext("Upgrade Extension Packages");?>" value="<?=gtext("Upgrade");?>" />
				<input name="restore" type="submit" class="formbtn" title="<?=gtext("Restore Boot Environment");?>" value="<?=gtext("Restore");?>" />
			</div>
			<div id="remarks">
				<?php html_remark("notes", gtext("Notes"), sprintf(gtext("Make sure the %s directory is a permanent data location with plenty of space."), gtext("backup")));?>
				<?php html_remark("", gtext(""), sprintf(gtext("Some tasks such as backups and restore may render the WebGUI unresponsive until task completes.")));?>
			</div>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_separator();?>
				<?php html_titleline(gtext("Uninstall"));?>
				<?php html_checkbox("bedata", gtext("BE Backups"), false, "<font color='red'>".gtext("Delete previous Boot Environments backups as well during the uninstall process.")."</font>", sprintf(gtext("If not activated the directory %s remains intact on the server."), "{$backup_path}"), false);?>
				<?php html_separator();?>
			</table>
			<div id="submit1">
				<input name="uninstall" type="submit" class="formbtn" title="<?=gtext("Uninstall Extension");?>" value="<?=gtext("Uninstall");?>" onclick="return confirm('<?=gtext("Boot Environments Manager Extension will be completely removed, ready to proceed?");?>')" />
			</div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
//-->
</script>
<?php include("fend.inc");?>
