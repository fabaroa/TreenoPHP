<?php
chdir(dirname(__FILE__));
require_once '../lib/install.php';
require_once '../lib/settings.php';

if(!isRunningThroughWeb()) {
	if ($argv[1] == '1') {
		if(versionGreater($argv[3], $argv[2])) {
			exit(0);
		} else {
			exit(1);
		}
	} elseif ($argv[1] == '2') {
		runUpgrades($DEFS, $argv[2]);
	}
}

?>
