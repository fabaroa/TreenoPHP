<?php
include_once '../lib/install.php';

function installInitialize($db_type = 'mysql') {
	global $DEFS;
	if(!file_exists('/etc/opt/docutron')) {
		if(!mkdir('/etc/opt/docutron')) {
			die("Error creating /etc/opt/docutron!\n");
		}
	}
	if(!copy('../lib/DMS.DEFS', '/etc/opt/docutron/DMS.DEFS')) {
		die("Error copying DMS.DEFS\n");
	}

	include '../lib/settings.php';
	writeToDMSDefs('DB_TYPE', $db_type);
	$DEFS['DB_TYPE'] = $db_type;
	$date = date("D-M-j-G-i-s-Y");
	writeToDMSDefs("INSTALLTIME", $date);
	$DEFS['INSTALLTIME'] = $date;
	echo "\nInitialization complete\n\n";
}
?>
