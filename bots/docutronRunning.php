<?php
chdir(dirname(__FILE__));
require_once '../db/db_common.php';
require_once '../lib/settings.php';
require_once '../lib/synchronizeBots.php';
require_once '../lib/licenseFuncs.php';

$neededBots = array (
		'routeDocuments3.pid'	=> '/tools/routeDocuments3.php',
		'batchSerializer.pid'	=> '/bots/batchSerializer.php',
		'setupIndexingBot2.pid'	=> '/bots/setupIndexingBot2.php',
		'splitPDFBot2.pid'		=> '/bots/splitPDFBot2.php',
		'docDaemon.pid'			=> '/poll/docDaemon.php'
	  );
if( isset($DEFS['DISABLE_SETUPINDEXING']) && $DEFS['DISABLE_SETUPINDEXING']==1){
	unset( $neededBots['setupIndexingBot2.pid'] );
}
if(substr(PHP_OS, 0, 3) == 'WIN') {
	$isWin = true;
} else {
	$isWin = false;
}
$db_doc = getDbObject('docutron');
foreach ($neededBots as $pidFile => $botFile) {
	$needStart = false;
	if (file_exists ($DEFS['TMP_DIR'].'/'.$pidFile)) {
		$pid = file_get_contents($DEFS['TMP_DIR'].'/'.$pidFile);
		if (!isRunning ($pid, $DEFS)) {
			$needStart = true;
			unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
		} else {
			if(!isValidLicense($db_doc)) {
				if($isWin) {
					shell_exec($DEFS['TASKKILL_EXE'] . ' /F /PID '.$pid);
				} else {
					shell_exec('kill -9 '.$pid);
				}
				unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
			}
		}
	} else {
		$needStart = true;
	}

	if ($needStart) {
		if ($isWin) {
			$cmd = $DEFS['BGRUN_EXE'] . ' ' . escapeshellarg ($DEFS['PHP_EXE']) . ' ' .
				escapeshellarg ($DEFS['DOC_DIR'].$botFile) .
				' > NUL 2>&1';
			shell_exec ($cmd);
		} else {
			shell_exec (escapeshellarg ($DEFS['PHP_EXE']) . ' -q ' .
						escapeshellarg ($DEFS['DOC_DIR'].$botFile) . 
						' > /dev/null 2>&1 &');
		}
		error_log ('Started ' . $DEFS['DOC_DIR'].$botFile); 
	}
}

?>
