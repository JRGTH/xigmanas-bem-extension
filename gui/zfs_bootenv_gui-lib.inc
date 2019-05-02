<?php
/*
	zfs_bootenv_gui-lib.inc

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

// Initialize some variables.
//$rootfolder = dirname($config['rc']['postinit']['cmd'][$i]);
$zroot = "zroot";
$beds= "ROOT";
$restore_name = "restore";
$confdir = "/var/etc/bemconf";
$cwdir = exec("/usr/bin/grep 'INSTALL_DIR=' {$confdir}/conf/bem_config | cut -d'\"' -f2");
$rootfolder = $cwdir;
$configfile = "{$rootfolder}/conf/bem_config";
$versionfile = "{$rootfolder}/version";
$date = strftime('%c');
$logfile = "{$rootfolder}/log/bem_ext.log";
$logevent = "{$rootfolder}/log/bem_last_event.log";
$backup_path = exec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR=' | cut -d'\"' -f2");
$archive_ext_def = "zfs";
$mount_prefix_def = "_BE";
$mountpoint_def = "/mnt/";

// Ensure the root directory is configured.
if ($rootfolder == "") $input_errors[] = gtext("Extension installed with fault");
else {
// Initialize locales.
	$textdomain = "/usr/local/share/locale";
	$textdomain_bem = "/usr/local/share/locale-bem";
	if (!is_link($textdomain_bem)) { mwexec("ln -s {$rootfolder}/locale-bem {$textdomain_bem}", true); }
	bindtextdomain("xigmanas", $textdomain_bem);
}
if (is_file("{$rootfolder}/postinit")) unlink("{$rootfolder}/postinit");

// Ensure the zfs send paramerers are specified.
$zfs_sendparams = exec("/usr/bin/grep 'ZFS_SENDPARAMS=' {$confdir}/conf/bem_config | cut -d'\"' -f2");
if ($zfs_sendparams == "") {
	$zfs_sendparams_def = "";
	exec("/usr/sbin/sysrc -f {$configfile} ZFS_SENDPARAMS='{$zfs_sendparams_def}'");
	$zfs_sendparams = $zfs_sendparams_def;
}

// Ensure the compression method is specified.
$compress_method = exec("/usr/bin/grep 'DEFAULT_COMPRESS=' {$confdir}/conf/bem_config | cut -d'\"' -f2");
if ($compress_method == "") {
	$compress_method_def = "xz -0 -v";
	exec("/usr/sbin/sysrc -f {$configfile} DEFAULT_COMPRESS='{$compress_method_def}'");
	$compress_method = $compress_method_def;
}

// Ensure the zfs recv paramerers are specified.
$zfs_recvparams = exec("/usr/bin/grep 'ZFS_RECVPARAM=' {$confdir}/conf/bem_config | cut -d'\"' -f2");
if ($zfs_recvparams == "") {
	$zfs_recvparams_def = "";
	exec("/usr/sbin/sysrc -f {$configfile} ZFS_RECVPARAM='{$zfs_recvparams_def}'");
	$zfs_recvparams = $zfs_recvparams_def;
}

// Ensure the decompression method is specified.
$decompress_method = exec("/usr/bin/grep 'DEFAULT_DECOMPRESS=' {$confdir}/conf/bem_config | cut -d'\"' -f2");
if ($decompress_method == "") {
	$decompress_method_def = "xz -c -d -v";
	exec("/usr/sbin/sysrc -f {$configfile} DEFAULT_DECOMPRESS='{$decompress_method_def}'");
	$decompress_method = $decompress_method_def;
}

?>
