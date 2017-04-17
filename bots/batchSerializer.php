<?php
chdir (dirname (__FILE__));
require_once '../lib/settings.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/indexing.inc.php';
require_once '../db/db_common.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to start ".$DEFS['DOC_DIR']."/bots/batchSerializer.php");
	die();
}

error_reporting (E_ALL);
$fd = fopen ($DEFS['TMP_DIR'].'/batchSerializer.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);
$dirListen = $DEFS['DATA_DIR'].'/serialScan';
safeCheckDir ($dirListen);
$scanDir = $DEFS['DATA_DIR'].'/Scan';
safeCheckDir ($scanDir);

while (true) {
	$myDirs = array ();
	$dh = safeOpenDir ($dirListen);
	$myEntry = readdir ($dh);
	while ($myEntry !== false) {
		if ($myEntry != '.' and $myEntry != '..') {
			$myDirs[] = $dirListen.'/'.$myEntry;
		}
		$myEntry = readdir ($dh);
	}
	closedir ($dh);
	$myDirs = Indexing::orderByTime ($myDirs);
	$allFiles = array ();
	foreach ($myDirs as $dirName) {
		if (is_dir ($dirName)) {
			serializeDir ($dirName, $allFiles);
		} else {
			$allFiles[] = $dirName;
		}
	}
	foreach ($allFiles as $myFile) {
		$fileStr = Indexing::makeUnique ($scanDir . '/' .
			basename ($myFile));
		rename ($myFile, $fileStr);
	}
	sleep (1);
}

function serializeDir ($dirName, &$fileArr) {
	clearstatcache ();
	//if older han 10 seconds
	if (filectime ($dirName) < (time() - 10)) {
		$empty = true;
		$dh = opendir ($dirName);
		$myEntry = readdir ($dh);
		while ($myEntry !== false) {
			if ($myEntry != '.' and $myEntry != '..') {
				$empty = false;
				if (is_dir ($myEntry)) {
					serializeDir ($myEntry, $fileArr);
				} else {
					$fileArr[] = $dirName . '/' . $myEntry;
				}
			}
			$myEntry = readdir ($dh);
		}
		closedir ($dh);
		if ($empty) {
			rmdir ($dirName);
		}
	}
}

?>
