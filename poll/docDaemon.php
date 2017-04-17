<?php

chdir(dirname(__FILE__));

include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/synchronizeBots.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to start ".$DEFS['DOC_DIR']."/poll/docDaemon.php");
	die();
}

$fd = fopen ($DEFS['TMP_DIR'].'/docDaemon.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);

$baseMemUsage = getMemUsage($DEFS);
$memUsage = $baseMemUsage;

$i = 0;
while ($memUsage < 10 * $baseMemUsage) {
	$maxTime = date("Y-m-d H:i:s",time() - ini_get('session.gc_maxlifetime'));

	$sArr = array('username');
	$wArr = array("logintime < '$maxTime'");
	$loggedInUser = getTableInfo($db_doc,'user_session',$sArr,$wArr,'queryCol');

	foreach ($loggedInUser as $uname) {
		if($uname != "admin"){
			//delete entry from user_polls table as well
			$whereArr = array ('username' => $uname);
			deleteTableInfo($db_doc, 'user_polls', $whereArr);
			deleteTableInfo($db_doc, 'user_security', $whereArr);
		}
	}
	checkExecute($db_doc);

	sleep(15);
	$i++;
	if ($i == 50) {
		$memUsage = getMemUsage($DEFS);
		$i = 0;
	}
}

//checks to write an iso file from CD Backup
function checkExecute($db_doc) {
	//This cannot use the settings object
	$whereArr = array ('k' => 'docDaemon_execute');
	$result = getTableInfo($db_doc, 'settings', array (), $whereArr);
	while ($row = $result->fetchRow()) {
		shell_exec($row['value']);
		$whereArr = array ('id' => (int) $row['id']);
		deleteTableInfo($db_doc, 'settings', $whereArr);
	}
}
?>
