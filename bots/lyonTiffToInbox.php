<?php
/******************************
 * This File Is Designed to Scan a
 * directory, looking for .tif files
 * they need to match: 
 * <firstname>_<lastname>_<yyyy>-<mm>-<dd>.tif
 */
/**
 * Stolen From RouteDocuments3:
 * *****************************************************
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
 * GLBSettings
 */
require_once '../settings/settings.php';

/**
 * Documents
 */
require_once '../documents/documents.php';

$db_doc = getDbObject('docutron');
$userName = 'admin';
$user = new user($userName);

//$ php <this-filename.php> <string dept name> <string inboxname(user)> <string cabinet displayname> 

if($argc > 1)
{
	$dept = $argv[1]; //THIS CAN"T BE EMPTY OR IT WON'T WORK
	$inboxname = $argv[2]; //THIS CAN"T BE EMPTY OR IT WON'T WORK
	$cab = $argv[3]; //THIS CAN"T BE EMPTY OR IT WON'T WORK
} else
{
	die("This wont work without a department, cabinet, and inbox set.  FAIL.");
}

$db_dept = getDbObject($dept);
$gblStt = new GblStt($dept, $db_doc);
$dirListen = $DEFS['DATA_DIR'] . "/$dept/personalInbox/$inboxname";
//die($dirListen);
//check for validity
safeCheckDir ($dirListen);

$dh = safeOpenDir($dirListen);

$deptID = str_replace("client_files", "", $dept);
$cabinetInfo = getTableInfo($db_dept, 'departments', array('real_name', 'departmentid'), array('departmentname'=>$cab), 'queryRow');
$cabinetName = $cabinetInfo['real_name'];
$cabinetID = $cabinetInfo['departmentid'];

while(($entry = readdir($dh)) !== false)
{
	//add the the array
	$newFile = null;
	if ($entry != '.' and $entry != '..') 
	{
		$newFile = $dirListen.'/'.$entry;
		//only do something if this file is a tif
		$error = false;
		if(stristr(strtolower($newFile), '.tif'))
		{
			
			//assume noone will use this inbox for anything other than this purpose
			$indices = explode('_', str_replace('.tif', '', $entry));
			if(count($indices) < 3) $error = true;  //not enough values to make this work
			elseif(!stristr($indices[2], '-')) $error = true; //not a vaid date
			
			if($error === true)
			{ 
				error_log("***LyonTiffUpload Failed: Filename - $entry was invalid***\n");
				continue;
			}
			
			$indiceArr = array('first'=>$indices[0], 'last'=>$indices[1], 'dob'=>$indices[2]);
			
			$doc_id = getTableInfo($db_dept, $cabinetName, array('min(doc_id)'), 
				$indiceArr, 'queryOne');
			if(!$doc_id || !is_numeric($doc_id))	
			{
				$temptable = ''; //cause we gotta have something for this?
				//now we create folder, receive doc_id.
				$doc_id = (int) createFolderInCabinet($db_dept, $gblStt, $db_doc, $userName, $dept, $cabinetName, array_values($indiceArr), array_keys($indiceArr), $temp_table);
			}
			$tmpDir = getUniqueDirectory($dirListen);
				
			//get the unique string
			$dirs = explode('/', $tmpDir);
			$unique = $dirs[count($dirs) - 2];
			$barcodeString = "$deptID $cabinetID $doc_id";
			
			
			//create dat file
			$index = fopen($tmpDir.'/INDEX.DAT', 'w');
			fwrite($index, $barcodeString);
			fclose($index);
			
			$tmpFile = "$tmpDir/$entry";
			
			@rename($newFile, "$tmpFile");
			if(file_exists($tmpFile))
			{
				//if the new file exists, remove the original file
				//echo $DEFS['DATA_DIR'].'/Scan/'.$unique;
				@rename($tmpDir, $DEFS['DATA_DIR'].'/Scan/'.$unique);
				
				//rmDir($tmpDir);
				//rename($tmpDir, $DEFS['DATA_DIR'].'/Scan/');
			}
		} 
	}		
}
closedir($dh);
?>