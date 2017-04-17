<?php
/**
 * routeDocuments3
 * 
 * Route documents from Scan directory directly where they need to go based
 * on a barcode value. DO NOT RUN MANUALLY - docutronRunning.php should always
 * run this.
 * 
 * @package DMS
 * @subpackage routeDocuments3
 */
/**
 * Report all errors
 */
error_reporting(E_ALL);

chdir(dirname(__FILE__));
/**
 * PEAR::DB Connection
 */
require_once '../db/db_common.php';
require_once '../lib/synchronizeBots.php';

/**
 * RouteDocuments
 */
require_once '../lib/routeDocuments.inc.php';

/**
 * ScannedBatch BarcodeScannedBatch
 */
require_once '../lib/barcodeScannedBatch.inc.php';

/**
 * DataObject_user
 */
require_once '../DataObjects/DataObject.inc.php';

/**
 * $DEFS
 */
require_once '../lib/settings.php';

/**
 * getTableInfo(), deleteTableInfo(), addToWorkflow(), and getCabinetInfo()
 */
require_once '../lib/utility.php';

/**
 * copyFiles()
 */
require_once '../lib/indexing2.php';

/**
 * createFolderInCabinet()
 */
require_once '../lib/cabinets.php';

/**
 * class stateNode
 */
require_once '../workflow/node.inc.php';

/**
 * delDir()
 */
require_once '../lib/fileFuncs.php';

/**
 * Barcode::getRealCabinetName(), Barcode::getRealDepartmentName()
 */
require_once '../lib/barcode.inc.php';

/**
 * Indexing::makeUnique()
 */
require_once '../lib/indexing.inc.php';

/**
 * getMimeType()
 */
require_once '../lib/mime.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to start ".$DEFS['DOC_DIR']."/tools/routeDocuments3.php");
	die();
}
$db_doc->disconnect();

$fd = fopen ($DEFS['TMP_DIR'].'/routeDocuments3.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);
 
$dirListen = $DEFS['DATA_DIR'].'/Scan';
safeCheckDir ($dirListen);
safeCheckDir ($DEFS['DATA_DIR'].'/splitPDF');

$baseMemUsage = getMemUsage($DEFS);
$memUsage = $baseMemUsage;
$i = 0;
$routeDocs = new RouteDocuments($DEFS);
while($memUsage < 10 * $baseMemUsage) {
	$myDirs = array ();
	$dh = safeOpenDir($dirListen);
	$myEntry = readdir($dh);
	while($myEntry !== false) {
		if ($myEntry != '.' and $myEntry != '..') {
			$myDirs[] = $dirListen.'/'.$myEntry;
		}
		$myEntry = readdir($dh);
	}
	closedir($dh);
	$myDirs =& Indexing::orderByTime($myDirs);
	foreach ($myDirs as $dirName) {
		$myBatches =& $routeDocs->getBatches($dirName);
		foreach($myBatches as $myBatch) {
			$myBatch->routeBatch();
		}
	}
	$myBatches = null;
	sleep(1);
	$i++;
	if ($i == 250) {
		$memUsage = getMemUsage($DEFS);
		$i = 0;
	}
}
$routeDocs->db_doc->disconnect();
?>
