<?php
include_once '../lib/settings.php' ;
include_once 'addScannerUser.php';
include_once 'installNTP.php';

function installConfiguration() {
	global $DEFS;
	addScannerUser();
	installNTP();

	if(isset($DEFS['INSTALLTIME'])) {
		$t = $DEFS['INSTALLTIME'];
	} else {
		$t = date("D-M-j-G-i-s-Y");
	}

	$fd = popen('/usr/bin/crontab -l', 'r');
	$cronOutput = '';
	while(!feof($fd)) {
		$cronOutput .= fread($fd, 2096);
	}
	pclose($fd);
	if(strpos($cronOutput, 'integrityServer') === false) {
		$cronOutput = str_replace( "`", "", $cronOutput );
		shell_exec("echo \"$cronOutput\" | crontab -");
	}

	@mkdir($DEFS['DATA_DIR'].'/Scan');
	allowWebWrite ($DEFS['DATA_DIR'].'/Scan', $DEFS);
	@mkdir($DEFS['DATA_DIR'].'/splitPDF');
	allowWebWrite ($DEFS['DATA_DIR'].'/splitPDF', $DEFS);
	
	echo "\nConfiguration files installed\n\n" ;
}
?>
