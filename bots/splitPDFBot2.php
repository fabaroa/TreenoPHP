<?php
/**
 * splitPDFBot2
 * 
 * Split PDFs into batches
 */

chdir(dirname(__FILE__));
require_once '../lib/settings.php';
require_once '../lib/PDF.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/indexing.inc.php';
require_once '../lib/synchronizeBots.php';
require_once '../db/db_common.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to start ".$DEFS['DOC_DIR']."/bots/splitPDFBot2.php");
	die();
}

$fd = fopen ($DEFS['TMP_DIR'].'/splitPDFBot2.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);
if (!isset($DEFS['FIRSTFILE'])) {
	$errStr = "Need a value for FIRSTFILE in DMS.DEFS";
	error_log ($errStr);
	die();
}

$splitDir = $DEFS['DATA_DIR'].'/splitPDF';
$destDir = $DEFS['DATA_DIR'].'/Scan';


safeCheckDir ($splitDir);

safeCheckDir ($destDir);

$firstFile = $DEFS['FIRSTFILE'];

if($firstFile == 1) {
	$firstFile = true;
} else {
	$firstFile = false;
}

$splitBatch = new SplitBatch($firstFile);

$baseMemUsage = getMemUsage($DEFS);
$memUsage = $baseMemUsage;
$i = 0;

while($memUsage < 10 * $baseMemUsage) {
	$dh = safeOpenDir($splitDir);
	$myEntry = readdir($dh);
	$myDirs = array ();
	while($myEntry !== false) {
		if($myEntry != '.' and $myEntry != '..') {
			$myDirs[] = $splitDir.'/'.$myEntry;
		}
		$myEntry = readdir($dh);
	}
	closedir($dh);
	$myDirs =& Indexing::orderByTime($myDirs);
	foreach ($myDirs as $myEntry) {
		if(is_file($myEntry)) {
			$splitBatch->split($myEntry);
		} else {
			$destLoc = Indexing::makeUnique ($destDir.'/'.basename($myEntry));
			@rename($myEntry, $destLoc);
		}
	}
	sleep (1);
	$i++;
	if ($i == 250) {
		$memUsage = getMemUsage($DEFS);
		$i = 0;
	}
}

class SplitBatch {
	var $firstFile;
	var $db_docInfo;
	var $dbObjects;
	
	function splitBatch($firstFile) {
		$this->firstFile = $firstFile;
		$this->db_docInfo = new stdClass();
		$this->db_docInfo->time = 0;
		$this->db_docInfo->db = null;
		$this->dbObjects = array ();
	}
	
	function split($batchFile) {
		global $DEFS;
		if(splitMultiPage($batchFile, $this->db_docInfo, $this->dbObjects, $this->firstFile)) {
				unlink($batchFile);
		} else {
			global $DEFS;
			$destFile = Indexing::makeUnique($DEFS['DATA_DIR'].'/client_files/personalInbox/admin/' . basename($batchFile));
			$errStr = 'Error splitting PDF: '.$batchFile . '. Moving it to '.$destFile;
			error_log($errStr);
			if(!rename($batchFile, $destFile)) {
				die();
			}
		}
	}
	
}

?>
