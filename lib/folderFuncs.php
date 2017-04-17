<?php
// $Id: folderFuncs.php 13908 2010-06-10 13:49:41Z acavedon $
/*
 * This file contains functions specifically related to folders. 
 */

// include functions
include_once '../db/db_common.php';
include_once '../lib/webServices.php';
include_once '../lib/licenseFuncs.php';
include_once '../tools/Globals.php';

/*
 * mergeFoldersD() - Merge 2 folders that are in Document View. Move the 
 *                   directory contents of the second folder into the first 
 *                   folder. The database will point the *_files records to 
 *                   the first folders unique storage directory. 
 * 
 * Parameters:
 * 		$deptName  - db representation of department name
 * 		$cabName   - db representation of cabinet name
 * 		$folder1   - folder to copy into
 * 		$folder2   - folder to copy over
 * 		$fieldName - db field name containing the folder values 
 * 
 * Returns:
 * 		true  - success
 * 		false - some error happened
 * 
 * Notes / Assumptions:
 *		-if there is a naming conflict with subfolder directory names, then the 
 *		 one coming in is renamed with an 'a' placed at the end of the name. The
 *       database reference to this subfolder is also updated.
 */
function mergeFoldersD($deptName, $cabName, $folder1, $folder2, $fieldName) {
	global $DEFS, $TIC, $SLASH, $MV;
	
	// pointer to department
	$db_dept = getDbObject($deptName);
	if(! isValidLicense($db_dept)) {
		//echo "DBG: INVALID LICENSE - no access to '$deptName' db";
		return false;
	}

	// check that the two folders to be merged exist
	$wArr = array($fieldName."=".$TIC.$folder1.$TIC,
	              "deleted=".$TIC."0".$TIC);
	$folder1DocId = getTableInfo($db_dept, $cabName, array('doc_id'), 
	                            $wArr, 'queryOne');
	if(! is_numeric($folder1DocId) ) {
		//echo "DBG: Merge folder ($folder1) does not exist";
		return false;
	}
	
	$wArr = array($fieldName."=".$TIC.$folder2.$TIC,
	              "deleted=".$TIC."0".$TIC);
	$folder2DocId = getTableInfo($db_dept, $cabName, array('doc_id'), 
	                            $wArr, 'queryOne');
	if(! is_numeric($folder2DocId) ) {
		//echo "DBG: Merge folder ($folder2) does not exist";
		return false;
	}
	
	// read in location of F1
	$wArr = array("doc_id=".$TIC.$folder1DocId.$TIC,
	              "deleted=".$TIC."0".$TIC);
	$locationF1 = getTableInfo($db_dept, $cabName, array('location'), $wArr, 'queryOne');
	$tokDB      = strtok($locationF1, " ");
	$tokCab     = strtok(" ");
	$locationF1 = strtok (" ");
	
	// read in location of F2
	$wArr = array("doc_id=".$TIC.$folder2DocId.$TIC,
	              "deleted=".$TIC."0".$TIC);
	$locationF2 = getTableInfo($db_dept, $cabName, array('location'), $wArr, 'queryOne');
	$tokDB      = strtok($locationF2, " ");
	$tokCab     = strtok(" ");
	$locationF2 = strtok (" ");
	
	// read in all subfolders for F1
	$wArr = array("doc_id =".$TIC.$folder1DocId.$TIC,
	              "filename is null");
	$subfoldersF1 = getTableInfo($db_dept, $cabName."_files", array('subfolder'), 
	                             $wArr, 'queryAll');
	
	// read in all subfolders for F2
	$wArr = array("doc_id =".$TIC.$folder2DocId.$TIC,
	              "filename is null");
	$subfoldersF2 = getTableInfo($db_dept, $cabName."_files", array('subfolder'), 
	                             $wArr, 'queryAll');
	
	$pathF1 = $DEFS['DATA_DIR'].$SLASH.$deptName.$SLASH.$cabName.$SLASH.$locationF1;
	$pathF2 = $DEFS['DATA_DIR'].$SLASH.$deptName.$SLASH.$cabName.$SLASH.$locationF2;
	
	// loop through each of the documents associated with this row
	foreach( $subfoldersF2 as $subfolderF2 ) {
		$conflict = 0;
		foreach( $subfoldersF1 as $subfolderF1 ) {
			if(! strcmp($subfolderF1['subfolder'], $subfolderF2['subfolder']) ) {
				// subfolder naming conflict
				$conflict = 1;
				$subfolderF2Old = $subfolderF2['subfolder'];
				$subfolderF2['subfolder'] = $subfolderF2['subfolder']."a";
				$from = $pathF2.$SLASH.$subfolderF2Old;
				$from = str_replace("/", $SLASH, $from);
				$to   = $pathF2.$SLASH.$subfolderF2['subfolder'];
				$to   = str_replace("/", $SLASH, $to);
				$cmd = "$MV ".escapeshellarg($from)." ".escapeshellarg($to);
				//echo "DBG: copy dir ($cmd)\n";
				shell_exec($cmd);
				
			}	// end if(conflict)
			
		}	// end foreach(F1 subfolders)
			
		// copy directory to F1 area
		$from = $pathF2.$SLASH.$subfolderF2['subfolder'];
		$from = str_replace("/", $SLASH, $from);
		$to   = $pathF1.$SLASH;
		$to   = str_replace("/", $SLASH, $to);
		$cmd = "$MV ".escapeshellarg($from)." ".escapeshellarg($to);
		//echo "DBG: copy dir ($cmd)\n";
		shell_exec($cmd);
			
		// modify <cabinet>_files subfolder table entry with new doc_id's
		$uArr = array('doc_id' => $folder1DocId,
		              'subfolder' => $subfolderF2['subfolder']);
		if( $conflict ) {
			$wArr = array('doc_id' => $folder2DocId,
		                  'subfolder' => $subfolderF2Old,
		                  'deleted' => 0);
		} else {
			$wArr = array('doc_id' => $folder2DocId,
		                  'subfolder' => $subfolderF2['subfolder'],
		                  'deleted' => 0);
		}
	
		updateTableInfo($db_dept, $cabName."_files", $uArr, $wArr);
			
	}	// end foreach(F2 subfolders)
	
	// remove old folder2
	$uArr = array('deleted' => 1);
	$wArr = array('doc_id' => $folder2DocId);
	updateTableInfo($db_dept, $cabName, $uArr, $wArr);

	return true;
	
}	// end mergeFoldersD()

?>