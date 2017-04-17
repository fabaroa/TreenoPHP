<?php

require_once '../lib/synchronizeBots.php';
require_once '../lib/utility.php';
require_once '../lib/settings.php';
require_once '../lib/redaction.php';
require_once '../db/db_common.php';

$docutron = 'client_files';
$pushBot = 'redactionBotLock';

$db_doc = getDbObject('docutron');

if(!lockPushBot($pushBot, getmypid(), $docutron, $db_doc)) {
	$db_doc->disconnect ();
	die("already one running\n");
}

$filesToRedact = getTableInfo ($db_doc, 'files_to_redact', array ('id'),
	array (), 'queryCol');

foreach($filesToRedact as $myID) {
	$myFile = array ();
	if(checkAndSetRedact($db_doc, $myID, $myFile)) {
		$db_dept = getDbObject($myFile['department']);
		redactFile($db_dept, $db_doc, $DEFS['DATA_DIR'], $myFile['cabinet'], $myFile['file_id'], $myFile['department']);
		$db_dept->disconnect ();
		redactionDone($db_doc, $myID);
	}
}

unlockPushBot($pushBot, $docutron, $db_doc);
$db_doc->disconnect ();
?>
