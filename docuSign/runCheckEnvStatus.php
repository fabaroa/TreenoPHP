<?php
chdir(dirname(__FILE__));

//$incPath = ini_get('include_path');
ini_set('include_path', 'C:\Treeno\php\pear');
//require_once '../../php/PEAR/MDB2.php';
require_once '../db/db_common.php';
require_once '../lib/settings.php';
require_once '../lib/synchronizeBots.php';
require_once '../lib/licenseFuncs.php';

$isWin = substr(PHP_OS, 0, 3) == 'WIN';

$db_doc = getDbObject('docutron');

$botFile = '/docuSign/checkEnvStatus.php';
$pidFile = 'checkEnvStatus.pid';

$needStart = false;
if (file_exists ($DEFS['TMP_DIR'].'/'.$pidFile)) 
{
	$pid = file_get_contents($DEFS['TMP_DIR'].'/'.$pidFile);
	if (!isRunning ($pid, $DEFS)) 
	{
		$needStart = true;
		error_log("Need to restart ".$botFile);
		unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
	} 
	else 
	{
		error_log('Process whose id defined in '.$DEFS['TMP_DIR'].'/'.$pidFile.'is already running.');
		if(!isValidLicense($db_doc)) 
		{
			if($isWin) 
			{
				shell_exec($DEFS['TASKKILL_EXE'] . ' /F /PID '.$pid);
			} 
			else 
			{
				shell_exec('kill -9 '.$pid);
			}
			unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
		}
	}
} 
else
{
	error_log("Need to restart ".$botFile." as ".$DEFS['TMP_DIR'].'/'.$pidFile." doesn\'t exist.");
	$needStart = true;
}

if ($needStart) 
{
	$cmd = '';
	if ($isWin) 
	{
		$cmd = $DEFS['BGRUN_EXE'] . ' ' . escapeshellarg ($DEFS['PHP_EXE']) . ' ' .
			escapeshellarg ($DEFS['DOC_DIR'].$botFile) .
			' > NUL 2>&1';
	} 
	else 
	{
		$cmd =  escapeshellarg ($DEFS['PHP_EXE']) . ' -q ' .
					escapeshellarg ($DEFS['DOC_DIR'].$botFile) . 
					' > /dev/null 2>&1 &';
	}	
	shell_exec ($cmd);
	error_log ('Started shell_exec('.$cmd.')'); 
}

?>
