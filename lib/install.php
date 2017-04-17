<?php


function writeToDMSDefs($def, $value, $overwrite = true) { 
	echo "\nSetting [$def] to [$value] in /etc/opt/docutron/DMS.DEFS\n";
	$file = '';
	if(file_exists('/etc/opt/docutron/DMS.DEFS')) {
		$file = '/etc/opt/docutron/DMS.DEFS';
	} elseif (file_exists ('/etc/docutron/DMS.DEFS')) {
		$file = '/etc/docutron/DMS.DEFS';
	} else {
		$file = 'c:/docutron/DMS.DEFS';
	}
	$lines = file($file);
	$fp = fopen($file, "w+");

	$written = false;
	foreach($lines as $myLine) {
		if(strpos($myLine, $def) === 0) {
			if ($overwrite) {
				fwrite($fp, "$def=$value\n");
			}
			$written = true;
		} else {
			fwrite($fp, $myLine);
		}
	}

	if($written == false) { 
		fwrite($fp, "$def=$value\n");
	}
	
	fclose($fp);
}

function versionGreater($a, $b) {
	$versA = explode('.', $a);
	$versB = explode('.', $b);
	if($versA[0] > $versB[0]) {
		return true;
	} elseif($versA[0] < $versB[0]) {
		return false;
	} elseif ($versA[1] > $versB[1]) {
		return true;
	} elseif ($versA[1] < $versB[1]) {
		return false;
	} elseif ($versA[2] > $versB[2]) {
		return true;
	} else {
		return false;
	}
}

function runUpgrades($DEFS, $currentVersion) {
	$allFiles = array ();
	$upDir = $DEFS['DOC_DIR'] . '/install/upgrades';
	$dh = opendir($upDir);
	$myEntry = readdir($dh);
	while($myEntry !== false) {
		if(is_file($upDir . '/' . $myEntry)) {
			if(substr($myEntry, 0, 8) == 'release-') {
				$allFiles[] = $myEntry;
			}
		}
		$myEntry = readdir($dh);
	}
	closedir($dh);
	usort($allFiles, 'strnatcasecmp');
	$output = '';
	foreach($allFiles as $myFile) {
		$version = str_replace('.php', '', str_replace('release-', '', $myFile));
		if(versionGreater($version,  $currentVersion)) {
			$cmd = $DEFS['PHP_EXE'] . ' ' . $upDir . '/' . $myFile . ' 2>&1';
			chdir($DEFS['DOC_DIR'] . '/install/upgrades');
			$output .= shell_exec($cmd);
		}
	}
	return $output;
}

function restartServices($DEFS) {
	if(substr(PHP_OS, 0, 3) == 'WIN') {
		$args = $DEFS['TASKLIST_EXE'] . ' /FO CSV /NH /FI "IMAGENAME eq php.exe"';
		$output = trim(shell_exec($args));
		$fd = tmpfile();
		fwrite($fd, $output);
		fseek($fd, 0);
		while($pidInfo = fgetcsv($fd)) {
			if($pidInfo[1] != getmypid()) {
				$cmd = $DEFS['TASKKILL_EXE'] . ' /F /PID ' . $pidInfo[1];
				shell_exec($cmd);
			}
		}
		$cmd = $DEFS['PHP_EXE'] . ' ' . $DEFS['DOC_DIR'] . '/bots/docutronRunning.php';
		shell_exec($cmd);
	} else {
		$args = 'ps ax | grep ph[p] | awk \'{print $1}\'';
		$myArgs = explode("\n", trim(shell_exec($args)));
		foreach($myArgs as $myPid) {
			$myPid2 = trim($myPid);
			if($myPid2 != getmypid()) {
				$cmd = 'kill -9 ' . $myPid2;
				shell_exec($cmd);
			}
		}
		$cmd = $DEFS['PHP_EXE'] . ' ' . $DEFS['DOC_DIR'] . '/bots/docutronRunning.php 2>&1';
		shell_exec($cmd);
	}
}

function isRunningThroughWeb() {
	return (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']);
}

?>
