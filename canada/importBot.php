<?PHP
include_once '../db/db_common.php';
include_once '../canada/readRepo.php';
include_once '../lib/settings.php';
include_once '../lib/indexing.inc.php';

$department = $argv[1];
$cabinet = $argv[2];
$cabinetID = $argv[3];
$directory = $argv[4];
$userName = $argv[5];
$maxFileSize = $argv[6];
$tempTable = $argv[7];
$opCabinet = $argv[8];
$opCabinetID = $argv[9];
$opDocID = $argv[10];
$postAction = $argv[11];

	$db_doc = getDbObject("docutron");
	$db_dept = getDbObject($department);
	$indicesKeys = getCabinetInfo($db_dept, $cabinet);
	$fileExtensions = getTableInfo($db_dept, $tempTable, array('extensions'), array(), 'queryCol');
	list($importLogPath, $templateArr) = parseDirStruct($directory, $department, $cabinet, $cabinetID, $indicesKeys, 
											$userName, $maxFileSize, $fileExtensions, false, $opCabinetID, $opDocID);
	foreach($templateArr AS $folderObj) {
		moveFile($db_doc, $db_dept, $folderObj, $indicesKeys, $userName, $importLogPath);
	}
	postAction($department, $directory, $postAction, $importLogPath);
	updateStatus($db_dept, $opCabinet, $opDocID, "completed");
	uploadLogFile($department, $opCabinetID, $opDocID, $importLogPath, $directory);

//Handles the original directory after the import
//Allows the selection of moving to another directory, deleting, or leaving it as is
function postAction($department, $directory, $postAction, $importLogPath) {
	global $DEFS;
	$fd = fopen($importLogPath, "a+");
	$path = $DEFS['DATA_DIR']."/".$department."/import/".$directory;
	if($department == NULL OR $department == ""
		OR $directory == NULL OR $directory == "") {
		logMessage($fd, date('Y-m-d H:i:s')." Missing department or directory for post action\n");
		return false;
	}

	if( !file_exists($path) ) {
		logMessage($fd, date('Y-m-d H:i:s')." Path does not exist for post action\n");
		return false;
	}

	switch($postAction) {
	case "move":
		moveToCompleteDir($path, $directory, $DEFS, $department);
		logMessage($fd, date('Y-m-d H:i:s')." Moved $directory to the import completed directory\n");
		break;
	case "delete":
		delDir($path);
		logMessage($fd, date('Y-m-d H:i:s')." Permanently deleted $directory\n");
		break;
	case "leave":
		logMessage($fd, date('Y-m-d H:i:s')." $directory was left unchanged in the import directory\n");
		break;
	default:
		logMessage($fd, date('Y-m-d H:i:s')." postAction not recognized; $directory left unchanged in the import directory\n");
		break;
	}
	fclose($fd);
	return true;
}

//Move the given directory to the import completed directory
function moveToCompleteDir($path, $directory, $DEFS, $department) {
	$destPath = $DEFS['DATA_DIR']."/".$department."/importCompleted/";
	if( !file_exists($destPath) ) {
		makeAllDir($destPath);
		allowWebWrite($destPath, $DEFS, 0777);
	}
	$destPath = Indexing::makeUnique($destPath.$directory);

	rename($path, $destPath);
}
?>
