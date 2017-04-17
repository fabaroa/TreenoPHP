<?php
include_once '../lib/fileFuncs.php';

function installHTML() {
	global $DEFS;
	echo "\nStarting install of DMS Software.\n\n";
	if(file_exists($DEFS['DOC_DIR'])) {
		$filename = realpath($DEFS['DOC_DIR'].'/../').'/.html';
		$filename .= date("D-M-j-G-i-s-Y");
		rename($DEFS['DOC_DIR'], $filename);
		echo "Backup of {$DEFS['DOC_DIR']} directory to $filename.\n";
	} else {
		echo "No html directory to backup.\n";
	}
	echo "Copying html to {$DEFS['DOC_DIR']}.\n";
	mkdir($DEFS['DOC_DIR']);
	copyDir('..', $DEFS['DOC_DIR']);
	echo "Changing permissions of /var/www/html to 755.\n";
	chmodDir($DEFS['DOC_DIR'], 0755);
	echo "Changing ownership of /var/www/html\n";
	allowWebWrite ($DEFS['DOC_DIR'], $DEFS);
	echo "Cleaning up install directory.\n";
	$prefix = $DEFS['DOC_DIR'].'/install/';
	$dh = opendir($prefix);
	while ($file = readdir($dh)) {
		if($file != '.' and $file != '..' and $file != 'sql') {
			if(is_file($prefix.$file)) {
				unlink($prefix.$file);
			} else {
				delDir($prefix.$file);
			}
		} 
	}
	echo "\nFinished copying software.\n\n";
}
?>
