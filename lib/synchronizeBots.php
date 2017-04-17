<?php
require_once '../lib/utility.php';
require_once '../settings/settings.php';

function lockPushBot($lockName, $pid, $dbName, $db) {
	lockTables($db, array('settings'));
	$gblStt = new GblStt($dbName, $db);
	$currLock = $gblStt->get($lockName);
	if($currLock) {
		$stillRunning = isRunning ($currLock, $DEFS);
		if($stillRunning) {
			$retVal = false;	
		} else {
			$gblStt->set($lockName, $pid);
			$retVal = true;
		}
	} else {
		$gblStt->set($lockName, $pid);
		$retVal = true;
	}
	unlockTables($db);
	return $retVal;
}

function unlockPushBot($lockName, $dbName, $db) {
	$gblStt = new GblStt($dbName, $db);
	$gblStt->removeKey($lockName);
}

function getMemUsage($DEFS) {
	if (function_exists('memory_get_usage')) {
		return memory_get_usage();
	} elseif (substr (PHP_OS, 0, 3) == 'WIN') {
		// Windows workaround
		$output = array();

		exec($DEFS['TASKLIST_EXE'] . ' /FI "PID eq ' . getmypid() . '" /FO LIST', $output);            
		return substr($output[5], strpos($output[5], ':') + 1);
       }
       return 0;
}

function isRunning ($pid, $DEFS) {
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$output = array ();
		exec ($DEFS['TASKLIST_EXE'] . ' /FI "PID eq ' . $pid . '" /FO TABLE', $output);
		if (count ($output) == 4) {
			return true;
		} else {
			return false;
		}
	} else {
		if(shell_exec('pstree '.$pid)) {
			return true;
		} else {
			shell_exec("kill -9 $pid");	
			return false;
		}
	}
}
?>
