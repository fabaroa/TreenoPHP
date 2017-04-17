<?php
// $Id: treenoServices.php 15121 2015-03-05 16:29:00Z root $

/**
 * treenoServices.php
 * 
 * This file contains all of the web services calls (new API) for User and Admin 
 * type functions. The functions are broken up into a neat hierarchical sections.
 * If you add new functionality, please keep things neat and orderly.
 * 
 * Section markers: -dept-, -cab-, -folder-, -document-, -doc type-, -tab-, -file-,
 * 		-note-, -versioning-, -search-, -publishing-, -reports-
 * 
 * @package treenoServices
 */

include_once '../lib/utility.php';
include_once '../departments/depfuncs.php';
require_once '../db/db_common.php';
require_once '../db/db_engine.php';
include_once '../departments/depfuncs.php';
include_once '../lib/cabinets.php';
require_once '../lib/crypt.php';
include_once '../settings/settings.php';
include_once '../lib/searchLib.php';
include_once '../search/fileSearch.php';
include_once '../lib/random.php';
//include_once '../lib/versioning.php'; // for getParentID() - conflicts
include_once '../lib/treenoServicesFuncs.php';
//include_once '../lib/PDF2.php';
//include_once '../lib/PDF.php';
include_once '../lib/sagWS.php';

/** getGlobals - PRIVATE
 * This function checks the validity of the passKey. 
 * On failure return 'ret' = false, 'msg' = error message
 * On success return 'ret' = true, 'date' = array containing (user name, docutron DB id,
 * 		department DB id, internal department name, internal cabinet name, cabinet id number)
 */
function getGlobals($passKey, $deptDisplayName=NULL, $cabDisplayName=NULL) {
	list($retVal, $userName) = checkKey($passKey);
	if(! $retVal) {
		$message = 'Invalid or Expired PassKey. Please Try Login Again.';
		return array('ret'=>false, 'msg'=>$message);
	}
	$dataArr = array(); // init
	
	// pointer to core database
	$db_doc = getDbObject('docutron');
	if(!isValidLicense($db_doc)) {
		error_log("getGlobals(): INVALID LICENSE!");
		die("INVALID LICENSE - no access to docutron db");
	}
	
	// check that department exists
	if(! is_null($deptDisplayName)) {
		$query = "SELECT arb_department, real_department 
				  FROM users, db_list, licenses 
				  WHERE arb_department = '".str_replace("'","''",$deptDisplayName)."' AND username='".$userName.
				  "' AND db_list_id=list_id AND db_name=real_department";
		$results = $db_doc->queryAll($query);
		if(! $results) {
			$message = 'Not a valid department for this user';
			return array('ret'=>false, 'msg'=>$message);
		}
	
		// pointer to department
		$deptInternalName = $results[0]['real_department'];
		if(! $deptInternalName)
			die("ERROR: Source department ($deptDisplayName) not found. User GUI to create it.\n");
		$db_dept = getDbObject($deptInternalName);

		// Always check isValidLicense against docutron
		//error_log("getGlobals() ??? call isValidLicense for db_dept: ".$db_dept->database_name);	
		//if(! isValidLicense($db_dept)) {
		//	die("INVALID LICENSE - no access to db ($deptDisplayName).\n");
		//}
	} else {
		// dept not supplied
		$db_dept          = NULL;
		$deptInternalName = NULL;
	}	// end if(department)

	// look up cabinet information
	if(! is_null($cabDisplayName)) {
		$query = "SELECT departmentid, real_name 
				  FROM departments 
				  WHERE departmentname = '".$cabDisplayName."' AND deleted=0";
		$results = $db_dept->queryAll($query);
		
		$cabInternalName   = $results[0]['real_name'];
		$cabInternalNameID = $results[0]['departmentid'];
		$cabinetName       = hasAccess($db_dept, $userName, $cabInternalNameID, false);
		if($cabinetName === false) {
			$message = 'Not a valid cabinet for this user';
			return array('ret'=>false, 'msg'=>$message);
	    }
	} else {
		// cab not supplied
		$cabInternalName   = NULL;
		$cabInternalNameID = 0;
	}

	$dataArr = array();
	$dataArr = array($userName, $db_doc, $db_dept, $deptInternalName, 
					 $cabInternalName, $cabInternalNameID);

	return array('ret'=>true, 'data'=>$dataArr);
}	// end getGlobals()

/**
*  Description: used to log into Treeno (services). The generated passcode is good for 24 hours or until this login object is destroyed
* @param string $userName users login name
* @param string $password users password
* @return int|bool index key to web services class; failure = false
* @example ../examples/login.php
*/
function login($userName, $password) {
	// TODO: services side of login - FUTURE
	
}


/*
 * -dept-
 */ 

/**
 *  Description: used to get a list of available departments. Note: the first department listed is the DEFAULT department
 * @param string $passKey login passKey
 * @return array 'ret' = true, 'data' = array of int client_files_number => string deptDisplayName pairs; 
 * 'ret' = false. 'data' = error message
 * @example ../examples/getDepartmentList.php
 */
function getDepartmentList($passKey) {
	// validate session 
	$retVal = getGlobals($passKey, NULL, NULL);
	if($retVal['ret'] === true) {
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	
	// use existing treeno calls to get the list of departments for this user
	$licensesArr = getLicensesInfo($db_doc, 'real_department', 'arb_department', true);
	$allUserDepArr = getUserDepartmentInfo($db_doc, array_keys($licensesArr));

	// get the default department
	$userListId 	 = getTableInfo($db_doc, 'users', array('db_list_id'),
									array('username'=>$userName), 'queryOne');
	$defaultRealDept = getTableInfo($db_doc, 'db_list', array('db_name'), 
									array('list_id'=>$userListId, 'default_dept'=>'1'),
									'queryOne');	
	$defaultDept     = getTableInfo($db_doc, 'licenses', array('arb_department'),
									array('real_department'=>$defaultRealDept),
									'queryOne');
	// strip off 'client_files' for the number at the end
	$defaultID       = substr($defaultRealDept, strlen("client_files"));
	$defaultID = $defaultID ? $defaultID : 0;

	
	// fill in the return list of departments (default first)
	$retArr = array();
	$retArr[$defaultID] = $defaultDept;
	
	foreach($allUserDepArr as $dept => $userArr) {
		if($userArr[$userName] == 'yes') {
			// don't include the default dept again
			if(0 != strcmp($licensesArr[$dept], $defaultDept)) {
				$id          = substr($dept, strlen("client_files"));
				$id = $id ? $id : 0;
				$retArr[$id] = $licensesArr[$dept];
			}
		}
	}

	return array('ret'=>true, 'data'=>$retArr);
	
}	// end getDepartmentList()
	
/**
 *  Description: add a new department (must be >=dept admin) - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName including previous versions
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function addDepartment($passKey, $deptDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, NULL);
	if($retVal['ret'] == true) {
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end addDepartment()
	
/**
 *  Description: rename a department (must be >=dept admin) - FUTURE
 * @param string $passKey login passKey
 * @param string $srcDeptDisplayName new name of the department
 * @param string $destDeptDisplayName new name of the department
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function editDepartment($passKay, $srcDeptDisplayName, $destDestDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $srcDeptDisplayName, NULL);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end editDepartment()
	
/**
 *  Description: delete a department (must be >=dept admin) - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName including previous versions
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteDepartment($passKey, $deptDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, NULL);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end deleteDepartment()
	

/*
 * -cab-
 */ 

/**
 *  Description: looks up a list of cabinets and their access level (RW,RO or None) for that user
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @return array 'ret' = true, array(departmentid[array(departmentname, real_name)]); 
 * 'ret' = false, 'msg' = error message
 * @example ../examples/getCabinetList.php
 */
function getCabinetList($passKey, $deptDisplayName) {

	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, NULL);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	
	$cabArr = cabinetList($userName, $db_dept);
	$cabArr = getGUICabList($db_dept, $cabArr);
	$cabList = array();
	if(! is_null($cabArr)) {
		foreach($cabArr as $item) {
			$cabList[] = $item['departmentname'];
		}
	}

	return array('ret'=>true, 'data'=>$cabList);

}	// end getCabinetList()
	
/**
 *  Description: add a new cabinet to the department. (must be >=dept admin) - FUTURE
 * @param string $passkey login passKey
 * @param string $deptDisplayName name of the department
 * @param array $fieldNamesArr array of field names
 * @param string $docTypeSupported Folder View or Document View?
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function addCabinet($passKey, $deptDisplayName, $fieldNamesArr, $docTypeSupported) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, NULL);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end addCabinet()
	
/**
 *  Description: rename a cabinet. (must be >=dept admin) - FUTURE
 * @param string $passkey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $oldCabName old cabinet name
 * @param string $newCabName new cabinet name
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function editCabinet($passKey, $deptDisplayName, $oldCabName, $newCabName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $oldCabName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
}
	
/**
 *  Description: delete a cabinet. (must be >=dept admin) - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteCabinet($passKey, $deptDisplayName, $cabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end deleteCabinet()
	
/**
 *  Description: copy the contents of one cabinet to another. (must be >=dept admin) - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $srcCabDisplayName name of the source cabinet
 * @param string $destCabDisplayName name of the destination cabinet
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function copyCabinet($passKey, $deptDisplayName, $srcCabDisplayName, $destCabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $srcCabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	$retVal = getGlobals($passKey, $deptDisplayName, $destCabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");

}	// end copyCabinet()
	
/*
 * -folder-
 */ 
	
/**
 *  Description: get a list of all folders in a specific cabinet
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $startID folder ID to start with (0 = from the beginning - default)
 * @param int $count maximum number of folders to return (0 = all - default) 
 * @return array 'ret' = true, 'data' = array of the folderIDs; 'ret' = false, 'msg' = error message
 * @example 
 */
function getFolderList($passKey, $deptDisplayName, $cabDisplayName, $startID=0, $count=0) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// get the list of folders in this cabinet
	$list = getTableInfo($db_dept, $cabInternalName, array(),
						 array('deleted' => 0), 'queryAll', 
						 array('doc_id'=>'ASC'), $startID, $count);

	return array('ret'=>true, 'data'=>$list);
	
}	// end getFolderList()
	
/**
 *  Description: get the number of folders in a specific cabinet
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet 
 * @return array 'ret' = true, 'data' = int total_number_of_folders; 'ret' = false, 'msg' = error message
 * @example 
 */
function getFolderCount($passKey, $deptDisplayName, $cabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// get the total number of folders
	$query = "SELECT COUNT(*) $cabInternalName WHERE deleted = 0";
	$count = $db_dept->query($query);
	dbErr($count);
	
	return array('ret'=>true, 'data'=>$count);
	
}	// end getFolderCount()

/**
 *  Description: add a new folder to a specific cabinet
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param array $indiceArr associative array of field keys and values for the new folder
 * @return array 'ret' = true, 'data' = int docID; 'ret' = false, 'msg' = error message
 * @example 
 */
function addFolder($passKey, $deptDisplayName, $cabDisplayName, $indiceArr) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	
	// init 
	$message = "";
	$docID   = 0;
	
	// global settings object
	$gblStt = new GblStt($deptInternalName, $db_doc);
	
	// We know the cabinet exists (checked in cabInfo()
	$temp_table = '';

/*
 * We do this checking on the front end (TreenoV4) now... several cases to check for. We can
 * write a special web service call if anyone needs this check.
 */
//	// Are we creating a new folder everytime? or using one already created? It depends
//	// on the 'file_into_existing' setting
//	$docID = false;
//	$enabled = $gblStt->get("file_into_existing");
//	if($enabled == 1) {
//
//		$docID = (int)checkFolderExists($deptInternalName, $cabInternalName,  
//									    $indiceArr, $db_doc, $db_dept);
//	}

	$keys = array_keys($indiceArr);
	$vals = array_values($indiceArr);
//	if(! $docID) {
		// actually create the folder
		$docID = (int)createFolderInCabinet($db_dept, $gblStt, $db_doc, 
											$userName, $deptInternalName, 
											$cabInternalName, array_values($indiceArr), 
											array_keys($indiceArr), $temp_table);
//	}

	return array('ret'=>true, 'data'=>$docID);

}	// end addFolder()
	
/**
 *  Description: edit field values for a specific folder. 
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $docID doc_id of the specific folder we are editing
 * @param array $indiceArr associative array of field keys and values for the folder
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function editFolder($passKey, $deptDisplayName, $cabDisplayName, $docID, $indiceArr) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// check that the fields (keys) passed in actually exist
	$colsExist  = getCabinetInfo($db_dept, $cabInternalName);
	$colsPassed = array_keys($indiceArr); 
	foreach($colsPassed as $colPassed) {
		$found = 0;
		foreach($colsExist as $colExist) {
			if(! strcmp($colPassed, $colExist)) {
				$found = 1;
				break;
			}
		}
		if(! $found) {
			$message = "editFolder(): column passed in does not exist in cabinet (".
						$colPassed.")\n";
			return array('ret'=>false, 'msg'=>$message);
		}
		
	}	// end foreach(column)

	// build the update array
	$updateArr = array();
	foreach($indiceArr as $k => $v) {
		$updateArr[$k] = $v;
	}
	
	// where clause
	$whereArr = array('doc_id' => (int)$docID);
	
	// update the field values
	$res = updateTableInfo($db_dept, $cabInternalName, $updateArr, $whereArr);

	return array('ret'=>true);
	
}	// end editFolder()
	
/**
 *  Description markes a specific folder as removed. The force parameter must be used to remove a folder that contains files.
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the source cabinet
 * @param int $folderID folder to be removed
 * @param bool $force do we want to force the removal of a folder that contains files (default = false)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteFolder($passKey, $deptDisplayName, $cabDisplayName, $folderID, $force=false) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// check for files/tabs within folder
	$filesFound = getTableInfo($db_dept, $cabInternalName.'_files', 
							   array('filename'),
							   array('filename' => 'IS NOT NULL', 'doc_id' => $folderID), 
							   'queryOne');

	// mark folder as removed (goes to recycle bin)
	if($filesFound) {
		if(! $force) {
			$message = "deleteFolder(): force not set and files exist.\n";
			return array('ret'=>false, 'msg'=>$message);
		}
	}
	
	// does not return anything but true, even if did not delete folder :(
	updateTableInfo($db_dept, $cabInternalName, 
					array('deleted' => '1'), 
					array('doc_id' => $folderID));
	
	return array('ret'=>true);
	
}	// end deleteFoler()
	
/**
 *  Description: moveFolder() moves a folder to another cabinet, potentually in a different department. (must be >=dept admin) - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the source department
 * @param string $cabDisplayName name of the source cabinet
 * @param string $folderName
 * @param string $destDeptName name of the destination department
 * @param string $destCabName name of the destination cabinet
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function moveFolder($passKey, $srcdeptDisplayName, $srccabDisplayName, $folderName, $destDeptName, $destCabName) {
	// validate session 
	$retVal = getGlobals($passKey, $srcdeptDisplayName, $srccabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
		
}	// end moveFolder()
	

/*
 * -document-
 */ 

/**
 *  Description: get a list of all of the document types associated with a specific folder id.
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $docID folder id from which to get the documents
 * @return array 'ret' = true, 'data' = array of document IDs (keys) and their doc type names (values); 
 * 		'ret' = false, 'msg' = error message
 * @example 
 */
function getDocumentList($passKey, $deptDisplayName, $cabDisplayName, $docID) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	$retArr=getDocumentTypeList($passKey, $deptDisplayName, $cabDisplayName);
	$docTypeList = array('Main');
	if($retArr['ret'] == true) {
		foreach($retArr['data'] as $docType) {
			  $docTypeList[]=$docType['displayName'];
		}
	}
	
	// must be in document view
	/*
	$ret = isDocumentView($passKey, $deptDisplayName, $cabDisplayName);
	if(! $ret['ret']) {
		$message = "getDocumentList(): cabinet not in Document View\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	*/
	//get main
	$oArr   = array('subfolder' => 'ASC');
	$fileMain = getTableInfo($db_dept, $cabInternalName."_files",
				  array('id', 'subfolder'),
				  array('doc_id'   => $docID, 
				  		'deleted'  => '0', 'display' => '1',
				  		'subfolder' => 'IS NULL'),
				  'getAssoc',$oArr);
	//get subfolders
	$oArr   = array('subfolder' => 'ASC');
	$fileInfo = getTableInfo($db_dept, $cabInternalName."_files",
				  array('id', 'subfolder'),
				  array('doc_id'   => $docID, 
				  		'deleted'  => '0', 'display' => '1',
				  		'filename' => 'IS NULL'),
				  'getAssoc',$oArr);
	$retArr = array();
	// make list of document# names
	$sArr = array('document_table_name', 'document_id', 'subfolder', 'id');
	$query   = "SELECT ".implode(",", $sArr)." FROM ".$cabInternalName."_files ";
	$query  .= "WHERE doc_id = $docID AND document_id != 0 AND deleted = 0 ";
	$query  .= "ORDER BY document_table_name ASC, document_id DESC";
	$docList = $db_dept->extended->getAssoc($query, null, array() ,null,
											MDB2_FETCHMODE_DEFAULT, false, true);

	// make associative list of document# and real document type names
	$sArr   = array('document_table_name', 'document_type_name');
	$wArr   = array('enable' => 1);
	$oArr   = array('document_type_name' => 'ASC');
	$docArr = getTableInfo($db_dept, 'document_type_defs', $sArr, $wArr,
						   'getAssoc', $oArr);
	
	// make list of document type id's
	$chkArr = array();
	foreach($docArr AS $k => $d) {
		if(array_key_exists($k, $docList)) {
			// 
			foreach($docList[$k] AS $doc) {
				$docInfo  = array();
				$tableArr = array('document_field_defs_list', 'document_field_value_list');
				$sArr     = array('document_field_value');
				$whereArr = array('document_field_defs_list_id=document_field_defs_list.id',
								  'document_id='.(int)$doc['document_id'],
								  "document_table_name = '$k'" );
				
				$docInfo  = getTableInfo($db_dept, $tableArr, $sArr,
										 $whereArr, 'queryCol');
//				$retArr[$doc['id']] = implode(" ", $docInfo);
				$retArr[] = array($d,$doc['id'],$doc['subfolder']);
				$chkArr[] = $doc['id'];
			}
		}
	}
	foreach($fileMain AS $k => $d) {
		if (!$d) $d="Main";
		if (!in_array($k,$chkArr)) $retArr[] = array($d,$k,$d);
	}
	foreach($fileInfo AS $k => $d) {
		if (!$d) $d="Main";
		if (!in_array($k,$chkArr)) $retArr[] = array($d,$k,$d);
	}
	sort($retArr);
	$returnARR=array();
	foreach($retArr as $Arr) {
		if (in_array($Arr[0], $docTypeList)) {
			$returnARR[]=$Arr[1]."~".$Arr[0]."~".$Arr[2];
		}
	}
	// list is complete
	return array('ret'=>true, 'data' => $returnARR);
	
}	// end getDoumentList()

/**
 *  Description: get the list of document type (pre-configured) index definition values. 
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param string $filtered document definiations are on a department level, 
 * 		but you can choose to filter on the login cabinet (default = false)
 * @return array 'ret' = true, 'data' = array of index field names (realName, arbName, indicies, definitions); 
 * 		'ret' = false, 'msg' = error message
 * @example 
 */
function getDocumentDefinitionList($passKey, $deptDisplayName, $cabDisplayName, $filtered=false) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// get the list of document types in this department
	$sArr   = array('id', 'document_table_name', 'document_type_name');
	$wArr   = array('enable' => 1);
	$oArr   = array('document_type_name' => 'ASC');
	$docArr = getTableInfo($db_dept, 'document_type_defs',
						   $sArr, $wArr, 'getAssoc', $oArr);
	
	// create the cabinet filtered doc type list
	if($filtered) {
		$filteredList = getDocumentFilters($cabInteral, 'filter', $db_dept);
	} else {
		$filteredList = array();
	}

	// are we getting all doc type indexes
	if (count($filteredList)) {
		$fetchAll = false;
	} else {
		$fetchAll = true;
	}

	// init
	$definitions  = array();
	$defsList     = array();
	$userInGroups = getGroupsForUser($db_dept, $userName);
	
	// get the list of document definition types defined in DB
	$tmpList = getTableInfo($db_dept, 'definition_types',
							array('document_type_id', 'document_type_field', 'definition'),
							array(), 'getAll', 
							array('document_type_id'    => 'ASC',
								  'document_type_field' => 'ASC', 
								  'definition'          => 'ASC')
							);

	// peel through the list of document definitions
	foreach($tmpList as $docDefs) {
		$docTypeID = $docDefs['document_type_id'];
		if(! isset($defsList[$docTypeID])) {
			$defsList[$docTypeID] = array();
		}
		if(! isset($defsList[$docTypeID][$docDefs['document_type_field']])) {
			// init to empty if no doc defs are set
			$defsList[$docTypeID][$docDefs['document_type_field']] = array();
		}
		// add to the list of doc def names
		$defsList[$docTypeID][$docDefs['document_type_field']][] = $docDefs['definition'];
	}

	// cycle through each document type
	foreach($docArr AS $k => $d) {
		$groupArr = getDocumentPermissions($k, $db_dept);
		$inGroup  = array_intersect($groupArr, $userInGroups);
		if(sizeof($groupArr) > 0 && sizeof($inGroup) < 1) {
			// skip doc type if no permissions
			continue;
		}

		// get all of the index field names 
		if($fetchAll || in_array($k, $filteredList)) {
			$docInfo  = array();
			$tableArr = array('document_field_defs_list');
			$sArr     = array('real_field_name', 'arb_field_name');
			$whereArr = array('document_table_name' => $d['document_table_name']);
			$oArr     = array('ordering' => 'ASC');
			$docInfo  = getTableInfo($db_dept, $tableArr, $sArr, $whereArr,
									 'getAssoc', array('ordering' => 'ASC'));
			// associate the document#, index field name and definition name
			$myArr    = array('realName' => $d['document_table_name'],
							  'arbName'  => $d['document_type_name'],
							  'indices'  => $docInfo);
			if(isset ($defsList[$k])) {
				$myArr['definitions'] = $defsList[$k];
			} else {
				$myArr['definitions'] = array ();
			}
			$definitions[$k] = $myArr;
		}
	}	// end foreach(all document types)

	return array('ret'=>true, 'data'=>$definitions);
	
}	// end getDocumentDefinitionList()

/**
 *  Description: adds a new document to a folder
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID doc id for the folder to add this document to
 * @param string $documentName document of specific type to create (doc type must already exist)
 * @param array $indexes associative array of indexes and values
 * @return array 'ret' = true, 'data' = subFolderID; 'ret' = false, 'msg' = error message
 * @example 
 */
function addDocument($passKey, $deptDisplayName, $cabDisplayName, $folderID, $documentName, $indexes) {
	// validate session 
	//error_log("addDocument".print_r($indexes,true));
	if (array_key_exists('Creation Date', $indexes)) {
		unset($indexes['Creation Date']);
	}
	//error_log("addDocument 2 ".print_r($indexes,true));
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// init
	$message = "";

	// validate that document type exists
	$docTableName = getTableInfo($db_dept, 'document_type_defs', 
								 array('document_table_name'),
								 array('document_type_name' => $documentName), 
								 'queryOne');
	if(! $docTableName) {
		$message = "addDocumnet(): passed in document type not found ($docTableName)\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	
	// validate that document type indexes exist
	$docIndexNames = getTableInfo($db_dept, 'document_field_defs_list', 
								  array('id','arb_field_name'), 
								  array('document_table_name' => $docTableName),
								  'queryAll');
	
	$docIndexInputs = array_keys($indexes);

	foreach($docIndexInputs as $docIndexInput) {
		
		$found = 0;
		foreach($docIndexNames as $docIndexName) {
			if(! strcmp($docIndexInput, $docIndexName['arb_field_name'])) {
				$found = 1;
				break;
			}
		}
		if(! $found) {
			$message = "addDocument(): passed in index key not found ($docIndexInput)\n";
			return array('ret'=>false, 'msg'=>$message);
		}
	}
	
	// let's go
	$sArr       = array('id', 'document_table_name');
	$whereArr   = array('document_type_name' => $documentName);
	$typeDefsID = getTableInfo($db_dept, 'document_type_defs', $sArr, 
							   $whereArr, 'queryRow');
	$docType    = $typeDefsID['document_table_name'];
	$tabName    = "";
	lockTables($db_dept, array($docTableName, 
			   $cabInternalName.'_files', $cabInternalName));

	// create the tab for the document created
	$subfolderID = createTabForDocument($db_dept, $deptInternalName, 
										$cabInternalName,	$folderID, 
										$documentName, $tabName, $db_doc);

	// inserting an entry in document# table for this cab/doc_id/file_id
	$date      = date('Y-m-d G:i:s');
	$insertArr = array( "cab_name"      => $cabInternalName,
						"doc_id"        => (int)$folderID,
						"file_id"       => (int)$subfolderID,
						"date_created"  => $date,
						"date_modified" => $date,
						"created_by"    => $userName );
	$res = $db_dept->extended->autoExecute($docTableName, $insertArr);
	dbErr($res);
	
	// get the new (next) document ID
	$documentID = getTableInfo($db_dept, $docType, array('MAX(id)'),
							   array(), 'queryOne');
	unlockTables($db_dept);

	// update the new the new subfolder (document) record with new document id and name
	$sArr = array('document_id'         => (int)$documentID,
				  'document_table_name' => $docTableName);
	$whereArr = array('id' => (int)$subfolderID);
	updateTableInfo($db_dept, $cabInternalName.'_files', $sArr, $whereArr);

	// get the index field# and field names
	$sArr     = array('arb_field_name', 'id', 'arb_field_name');
	$whereArr = array('document_table_name' => $docTableName);
	$fieldArr = getTableInfo($db_dept, 'document_field_defs_list',
							 $sArr, $whereArr, 'getAssoc');
	// preparing new entry for document_field_value_list (index data for new document)
	$insertArr = array( "document_defs_list_id"       => (int)$typeDefsID['id'],
						"document_id"                 => (int)$documentID, 
						"document_field_defs_list_id" => '',
						"document_field_value"        => '' );

	foreach($indexes AS $k => $v) {
		$insertArr['document_field_defs_list_id'] = (int)$fieldArr[$k];
		if(strlen($v) > 255) {
			// truncate a string (index value) that is too long
			$v = substr( $v, 0, 251 ). '...';
		}
		// add index value to insert array
		$insertArr['document_field_value'] = $v;
		
		// insert the index value record into document_field_value_list table 
		$res = $db_dept->extended->autoExecute('document_field_value_list', $insertArr);
		dbErr($res);
	}
	
	// send back the new subfolder (document) id
	return array('ret'=>true, 'data'=>$subfolderID);
	
}	// end addDocument()

/**
 *  Description: modify the index values of a specific document in a folder
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $documentID ID of the document to modify (document_id field from <cab>_files)
 * @param array $indexes an associative array of index names and values
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
  * @example 
 */
function editDocument($passKey, $deptDisplayName, $cabDisplayName, $documentID, $fileID, $indexes) {
	// validate session 
	//error_log("editDocument".print_r($indexes,true));
	if (array_key_exists('Creation Date', $indexes)) {
		unset($indexes['Creation Date']);
	}
	//error_log("editDocument 2 ".print_r($indexes,true));
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// get the document# value for indecies validation
	$docTableName = getTableInfo($db_dept, $cabInternalName.'_files',
								 array('document_table_name'), 
								 array('document_id' => $documentID,'id' => $fileID),
								 'queryOne');

	// validate that document type indecies exist
	$docIndexNames = getTableInfo($db_dept, 'document_field_defs_list', 
								  array('arb_field_name'), 
								  array('document_table_name' => $docTableName),
								  'queryAll');
	$docIndexInputs = array_keys($indexes);
	foreach($docIndexInputs as $docIndexInput) {
		$found = 0;
		foreach($docIndexNames as $docIndexName) {
			// see if we can find a match of all index names and the possibles
			if(! strcmp($docIndexInput, $docIndexName['arb_field_name'])) {
				$found = 1;
				break;
			}
		}
		if(! $found) {
			$message ="editDocument(): passed in index key not found ($docIndexInput)\n";
			return array('ret'=>false, 'msg'=>$message);
		}
	}
	
	// update index values
	foreach($indexes as $k => $v) {
		// lookup the index fields id number
		$docFieldID = getTableInfo($db_dept, 'document_field_defs_list',
								   array('id'), 
								   array('document_table_name' => $docTableName, 
								   		 'arb_field_name' => $k), 
								   'queryOne');
		
		// do the update
		$updateArr = array('document_field_value' => $v);
		$whereArr  = array('document_id' => $documentID, 
						   'document_field_defs_list_id' => $docFieldID);
		updateTableInfo($db_dept, 'document_field_value_list', $updateArr, $whereArr);			
	}
		
	return array('ret'=>true);

}	// end editDocument()

/**
 *  Description: delete a document in a folder. 
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param string $folderName 
 * @param int $documentID ID of the document to delete
 * @param bool $force set to true if you want to force the deletion of a document that contains attached files (default = false)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteDocument($passKey, $deptDisplayName, $cabDisplayName, $folderName, $documentID, $force) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// validate that document exists (by getting the doc_id and subfolder)
	$docInfo = array();
	$docInfo = getTableInfo($db_dept, $cabInternalName.'_files',
							array('doc_id', 'subfolder'),
							array('document_id' => documentID),
							'queryOne');

	// use the doc_id and subfolder to look for files
	if($docInfo == array()) {
		$message = "deleteDocument(): document does not exist. Cannot delete (id: $documentID)\n";
		return array('ret'=>false, 'msg'=>$message);
	}

	// is document empty or do we have the force flag? then remove
	$filesExist = getTableInfo($db_dept, $cabInteralName.'_files', 
							   array('filename'),
							   array('subfolder' => $docInfo['subfolder'],
							   		 'doc_id' => $docInfo['doc_id'],
							  		 'filename' => 'IS NOT NULL'),
							   'queryOne');
	if($filesExist && !$force) {
		$message = "deleteDocument(): files exist in document and force not set. Document not removed\n";
		return array('ret'=>false, 'msg'=>$message);
	} else {
		updateTableInfo($db_dept, $cabInternalName.'_files', 
						array('deleted' => '1'), 
						array('subfolder' => $docInfo['subfolder'],
							  'doc_id' => $docInfo['doc_id'],
							  'filename' => 'IS NULL')
						);
	}
	
	return array('ret'=>true);

}	// end deleteDocument()
	

/*
 * -doc type-
 */ 

/**
 *  Description determines whether a cabinet is in document type view or folder view
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function isDocumentView($passKey, $deptDisplayName, $cabDisplayName=NULL) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// set up User class
	$user = new user();
	$user->username = $userName;
	$user->fillUser(NULL, $deptInternalName);
	
	// check the setting
	if($cabDisplayName) {
		$realCabName = getTableInfo($db_dept, "departments", array('real_name'),
									array('departmentname' => $cabDisplayName),
									'queryOne');
		// check passed in cabinet
		$ret = $user->checkSetting('documentView', $realCabName);
	} else {
		// check currently logged in cabinet
		$ret = $user->checkSetting('documentView', $cabInternalName);
	}

	// what are we returning
	if (! $ret) {
		return array('ret'=>false);
	} else {
		return array('ret'=>true);
	}
	
}	// end isDocumentView()

/**
 *  Description: gets the document Types for a department must be >=dept admin
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @return array 'ret' = true, 'data' = array of documentTypeID, docTypeDisplayName, 
 * 		document_type_name, docTypeInternalName, indices, definitions for each doc type; 
 * 		'ret' = false, 'msg' = error message
 * @example 
 */
function getDocumentTypeList($passKey, $deptDisplayName, $cabDisplayName=NULL) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$sArr   = array('id', 'document_type_name', 'document_table_name');
	$wArr   = array('enable' => 1);
	$oArr   = array('document_type_name' => 'ASC');
	$docArr = getTableInfo($db_dept, 'document_type_defs', 
						   $sArr, $wArr, 'getAssoc', $oArr);
	if($cabDisplayName != NULL) {
		// we have a cabinet name to work with
		$query = "SELECT real_name FROM departments WHERE departmentname='".$cabDisplayName.
				 "' AND deleted=0";
		$results = $db_dept->queryAll($query);
		$cabInternalName = $results[0]['real_name'];
		$tArr = array('document_settings', 'document_settings_list');
		$sArr = array('document_id');
		$wArr = array("cab='".$cabInternalName."'", "k='filter'", 
					  "document_settings.list_id=document_settings_list.list_id");
		$filteredList = getTableInfo($db_dept, $tArr, $sArr, $wArr, 'queryCol');
	} else {
		$filteredList = array();
	}

	/*
	if (count($filteredList)) {
		$fetchAll = false;
	} else {
		$fetchAll = true;
	}
	*/
	//by default, only return the document types that have been "filtered" for a cabinet.
	$fetchAll = false;

	$userInGroups = getGroupsForUser($db_dept, $userName);

	$results = array();
	$tmpList = getTableInfo($db_dept, 'definition_types',
							array('document_type_id', 'document_type_field', 'definition'),
							array(), 'getAll', array('document_type_id' => 'ASC',
							'document_type_field' => 'ASC', 'definition' => 'ASC'));
	$defsList = array ();
	// loop through the definition types
	foreach ($tmpList as $docDefs) {
		$docTypeID = $docDefs['document_type_id'];
		if (!isset ($defsList[$docTypeID])) {
			$defsList[$docTypeID] = array ();
		}
		if(! isset($defsList[$docTypeID][$docDefs['document_type_field']])) {
			$defsList[$docTypeID][$docDefs['document_type_field']] = array ();
		}
		$defsList[$docTypeID][$docDefs['document_type_field']][] = $docDefs['definition'];
	}

	// loop through document type definitions
	foreach($docArr AS $k => $d) {
		$groupArr = getDocumentPermissions($k, $db_dept);
		$inGroup  = array_intersect($groupArr, $userInGroups);
		if( sizeof($groupArr) > 0 AND sizeof($inGroup) < 1) {
			continue;
		}

		if ($fetchAll or in_array ($k, $filteredList) ) {
			$docInfo	= array();
			$tableArr	= array('document_field_defs_list');
			$sArr		= array('real_field_name','arb_field_name');
			$whereArr	= array('document_table_name' => $d['document_table_name']);
			$oArr		= array('ordering' => 'ASC');
			$docInfo	= getTableInfo($db_dept, $tableArr, $sArr, $whereArr, 
									   'getAssoc', array('ordering' => 'ASC'));
			$myArr		= array('documentTypeID' 		=> $k, 
								'displayName' 	=> $d['document_type_name'], 
								'internalName'	=> $d['document_table_name'], 
								'indices' 				=> $docInfo);
			if (isset ($defsList[$k])) {
				$myArr['definitions'] = $defsList[$k];
			} else {
				$myArr['definitions'] = array();
			}
			$results[$k] = $myArr;
		}
	}
		
	return array('ret'=>true, 'data'=>$results);
		
}	// end getDocumentTypeList()

/**
*  Description: details of a specific document type 
* @param string $passKey login passKey
* @param string $deptDisplayName name of the department
* @param string $cabDisplayName name of the cabinet
* @param int $folderID
* @return array 'ret' = true, 'data' = array of doc type details: (int tabID, string documentName, 
* 		string documentType, array(string indexName, string displayName, string value))
* 		'ret' = false, 'msg' = error message
* @example 
            <item>
               <tabID xsi:type="xsd:int">104</tabID>
               <documentName xsi:type="xsd:string">45_Day_Screening1</documentName>
               <documentType xsi:type="xsd:string">45 Day Screening</documentType>
               <documentIndices xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType="ns4:DocumentIndex[1]">
                  <item>
                     <indexName xsi:type="xsd:string">f1</indexName>
                     <displayName xsi:type="xsd:string">Screening Date</displayName>
                     <value xsi:type="xsd:string">sss</value>
                  </item>
               </documentIndices>
            </item>
*/
function getDocTypeDetails($passKey, $deptDisplayName, $cabDisplayName, $folderID) {
	// validate session
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName,
		$cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	// here we need to determin if there is a docType or if it is a tab.
	$sArr    = array('document_table_name', 'document_id', 'subfolder','id');
	$query   = "SELECT ".implode(",", $sArr)." FROM ".$cabInternalName."_files ";
	$query  .= "WHERE id = ".$folderID." AND document_id <> 0 AND deleted = 0 ";
	$query  .= "ORDER BY document_table_name ASC, document_id DESC";
	$docList = $db_dept->extended->getAssoc($query, null, array(), null,
	MDB2_FETCHMODE_DEFAULT, false, true);
	if (count($docList)>0){
		dbErr($docList);
		$sArr    = array('document_table_name', 'document_type_name');
		$wArr    = array('enable' => 1);
		$oArr    = array('document_type_name' => 'ASC');
		$docArr  = getTableInfo($db_dept, 'document_type_defs', $sArr, $wArr,
		'getAssoc', $oArr);
		$retArr  = array();
		foreach($docArr AS $k => $d) {
			if(array_key_exists($k, $docList)) {
				foreach($docList[$k] AS $doc) {
					$docInfo  = array();
					$tableArr = array('document_field_defs_list', 'document_field_value_list');
					$sArr     = array('real_field_name', 'arb_field_name', 'document_field_value');
					$whereArr = array('document_field_defs_list_id = document_field_defs_list.id',
					'document_id='.(int)$doc['document_id'],
					"document_table_name = '$k'");
					$oArr     = array ('ordering' => 'ASC');
					$docInfo  = getTableInfo($db_dept, $tableArr, $sArr, $whereArr,
					'queryAll', $oArr);

					//This is to fix a bug in editing document types
					if(! $docInfo) {
						$realIDs = getTableInfo($db_dept, array('document_field_defs_list'),
						array('id'), array('document_table_name' => $k),
						'queryCol');
						$documentTypeID = getTableInfo($db_dept, array('document_type_defs'),
						array('id'), array('document_table_name' => $k),
						'queryOne');
						foreach ($realIDs as $indexID) {
							$queryArr = array (
							'document_defs_list_id'			=> (int) $documentTypeID,
							'document_id'					=> (int) $doc['document_id'],
							'document_field_defs_list_id'	=> (int) $indexID,
							'document_field_value'			=> ''
							);
							$res = $db_dept->extended->autoExecute('document_field_value_list',
							$queryArr);
							dbErr($res);
						}
						$docInfo = array();
						$tableArr = array('document_field_defs_list', 'document_field_value_list');
						$sArr = array('real_field_name', 'arb_field_name', 'document_field_value');
						$whereArr = array('document_field_defs_list_id = document_field_defs_list.id',
						'document_id='.(int)$doc['document_id'],
						"document_table_name = '$k'" );
						$oArr = array('ordering' => 'ASC');
						$docInfo=getTableInfo($db_dept, $tableArr, $sArr, $whereArr,
						'queryAll', $oArr);
					}

					$indArr = array();
					foreach ($docInfo as $indInfo) {
						$indArr[$indInfo['arb_field_name']] = $indInfo['document_field_value'];
					}
					$temp=array();
					$retArr[] = array (
					'documentTypeID' => $doc['document_id'],
					'displayName'		=> $d,
					'internalName'		=> $doc['subfolder'],
					'indices'			=> $indArr,
					'definitions'		=> $temp
					);
				}
			}
		}
	} else {
		$fileInfo = getTableInfo($db_dept, $cabInternalName."_files",
		array('subfolder'),
		array('id'   => $folderID),
		'queryAll');
		$retArr[] = array (
		'documentTypeID' => 0,
		'displayName'		=> $fileInfo[0]['subfolder'],
		'internalName'		=> $fileInfo[0]['subfolder'],
		'indices'			=> array(),
		'definitions'		=> array()
		);
	}

	return array('ret'=>true, 'data'=>$retArr);

}	// end getDocTypeDetails()

/**
 *  Description: gets the complete list of document types for a department - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param string $docType
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function addDocumentType($passKey, $deptDisplayName, $cabDisplayName, $docType) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end addDocumentType()
	
/**
 *  Description: . - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function editDocumentType($passKey, $deptDisplayName, $cabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end editDocumentType()

/**
 *  Description: removes a document type from a department. The force parameter must be used to remove a folder that contains files. - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param string $docType name of the document type to be removed
 * @param bool $force do we want to force the removal of a folder that contains files (default = false)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteDocType($passKey, $deptDisplayName, $cabDisplayName, $docType, $force=false) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end deleteDocType()


/*
 * -tab-
 */ 

/**
*  Description: list of 'saved' tabs for a cabinet that the User has access too.
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @return array 'ret' = true, 'data' = array of saved tab names; 'ret' = false, 'msg' = error message
 * @example 
 */
function getTabList($passKey, $deptDisplayName, $cabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$settings = new GblStt($deptInternalName, $db_doc);
	// saved tabs live in the docutron/settings table
	$result = $settings->get($cabInternalName.'_tabs');
	if($result) {
		$savedTabs = explode(',', $result);
	} else {
		$savedTabs = array();
	}
	// check for group restrictions
	$groupAccess = getTableInfo($db_dept, 'group_tab', 
								array(), array(), 'queryAll');
	$groups      = new groups($db_dept);
	$notShowTab  = array();
	foreach($groupAccess as $myRule) {
		$inGrp = $groups->getMembers($myRule['authorized_group']);
		if( !in_array($userName, $inGrp)) {
			if($cabInternalName == $myRule['cabinet'] and
			!$myRule['doc_id']) {
				$notShowTab[] = $myRule['subfolder'];
			}
		}
	}
	
	$tabs = array ();
	foreach($savedTabs as $myTab) {
		if(!in_array($myTab, $notShowTab)) {
			// load up the array with tab names
			$tabs[] = $myTab;
		}
	}
//	} else {
//		// list of tabs in <cab>_files table
//		$res = getTableInfo($db_dept, $cabInternalName.'_files', 
//							array('parent_filename', 'id', 'file_size', 'subfolder'), 
//							array('doc_id' => (int)$folderID, 'filename' => 'IS NOT NULL',
//								  'display' => 1, 'subfolder' => 'IS NULL', 'deleted' => 0),
//							'query', array('subfolder'=>'ASC'));
//		while($row = $res->fetchRow()) {
//			$row['subfolder'] = 'main';
//			$tabs[] = $row;
//		}
//		$res = getTableInfo($db_dept, $cabInternalName.'_files', 
//							array('parent_filename','id','file_size','subfolder'), 
//							array('doc_id' => (int)$folderID, 'filename' => 'IS NOT NULL',
//								  'display' => 1, 'subfolder' => 'IS NOT NULL', 'deleted' => 0),
//							'query',array('subfolder'=>'ASC'));
//		while($row = $res->fetchRow()) {
//			$tabs[] = $row;
//		}
//	}

	return array('ret'=>true, 'data'=>$tabs);

}	// end getTabList()

/**
 *  Description: creates a tab in folder within a classical view cabinet.
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $docID for the folder to create tab in
 * @param string $tabName name of the tab to be added to the given folder
 * @param bool $saved add as a saved tab (default = false) - admin only
 * @param bool $mkdir should we create the directory if it doesn't exist (default = true)
 * @return array 'ret' = true, 'data' = int subfolderID; 'ret' = false, 'msg' = error message
 * @example 
 */
function addTab($passKey, $deptDisplayName, $cabDisplayName, $docID, $tabName, $saved, $mkdir=true) {
	//cz	
	error_log("AddTab - dept: ".$deptDisplayName.", cab: ".$cabDisplayName.", docID: ".$docID.", tabName: ".$tabName.", saved: ".$saved.", madir: ".$mkdir );

	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	global $DEFS;
	$user = new user();
	
	// must be in document view
	$ret = isDocumentView($passKey, $deptDisplayName, $cabDisplayName);
	//cz	
	error_log("AddTab - isDocumentView: ".$ret['ret']);
	if($ret['ret']) {
		$message = "addTab(): cannot create a tab in Document View.\n";
		return array('ret'=>false, 'msg'=>$message);
	}
 	
	$whereArr = array('doc_id' => (int)$docID);
	$loc = getTableInfo($db_dept, $cabInternalName, 
						array('location'), $whereArr, 'queryOne');
	if(! $loc) {
		$message = "addTab(): location of folder not found.\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	//cz	
	error_log("AddTab - loc: ".$loc);

	// remove/replace all invalid characters (space w/ underscore and @ removed)
	$tabName = str_replace(' ', '_', $tabName);
	$tabName = $user->replaceInvalidCharacters($tabName, "");
	$tabName = str_replace("@", "", $tabName);
	
	// get tabs for this folder
    $whereArr = array('doc_id'   => (int)$docID,
       	              'filename' => 'IS NULL');
    $tabArr = getTableInfo($db_dept, $cabInternalName.'_files',  
    					   array('subfolder'), $whereArr, 'queryCol');

	// be sure that this tab name isn't already being used.
	if(in_array($tabName, $tabArr)) {
		$message = "addTab(): '$tabName' already exists.\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	
	// Update the settings table with saved tabs
	if($saved) {
		if($user->isAdmin() || $user->isDepAdmin()) {
			$value = getTableInfo($db_doc, 'settings',
								  array('value'),
								  array('k' => $cabInternalName.'_tabs',
								  		'department' => $deptInternalName),
								  'queryOne');
								  
			if($value) {
				$value = $value.",".$tabName;
			} else {
				$value = $tabName;
			}
			//cz	
			error_log("AddTab - to update settings table's value: ".$value);
			updateTableInfo($db_doc, 'settings',
							array('value' => $value),
							array('k' => $cabInternalName.'_tabs',
								  'department' => $deptInternalName)); 
		} else {
			$message = "addTab(): need to be admin/deptAdmin to add saved tab.\n";
				//cz	
				error_log($message);

					return array('ret'=>false, 'msg'=>$message);
		}
	}
	
	//cz	
	error_log("AddTab - to insert new tab ".$tabName." into table: ".$cabInternalName.'_files');

	// insert a new <cab>_files entry for this new tab
    $insertArr = array('doc_id'		  => (int)$docID,
                       'subfolder'	  => $tabName,
                       'date_created' => date('Y-m-d G:i:s'),
                       'file_size'	  => 4096 );
    $res = $db_dept->extended->autoExecute($cabInternalName.'_files', $insertArr);
	dbErr($res);
    $whereArr = array('doc_id'    => (int)$docID,
                      'subfolder' => $tabName);
    
    // get the new tabs ID number
    $subfolderID = getTableInfo($db_dept, $cabInternalName.'_files', 
    							array('MAX(id)'), $whereArr, 'queryOne');
    
	if($mkdir) {
	    mkdir($tabName, 0777);
	}

	// update the quota in docutron/licenses table
    $updateArr = array('quota_used' => 'quota_used+4096');
    $whereArr  = array('real_department' => $deptInternalName);
	updateTableInfo($db_doc, 'licenses', $updateArr, $whereArr, 1);
	
    return array('ret'=>true, 'data'=>$subfolderID);

}	// end of addTab()

/**
 *  Description: allows you to change the name of a tab or to toggle its saved/unsaved state. (classical view only)
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $docID for the folder to create tab in
 * @param string $oldTabName original or existing name of the tag
 * @param string $newTabName new name of the tab (use the $oldTabName if you don't want to change the name
 * @param bool $saved set either the tab is supported to be saved or not (default is not saved) - admin only
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function editTab($passKey, $deptDisplayName, $cabDisplayName, $docID, $oldTabName, $newTabName, $saved) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$user = new user();
	
	// must be in document view
	$ret = isDocumentView($passKey, $deptDisplayName, $cabDisplayName);
	if($ret['ret']) {
		$message = "editTab(): cannot create a tab in Document View.\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	
	// verify that the folder directory can be found
	$whereArr = array('doc_id' => (int)$docID);
	$loc = getTableInfo($db_dept, $cabInternalName, 
						array('location'), $whereArr, 'queryOne');
	if(! $loc) {
		$message = "editTab(): location of folder not found.\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	
	// remove/replace all invalid characters - old name (space w/ underscore and @ removed)
	$oldTabName = str_replace(' ', '_', $oldTabName);
	$oldTabName = $user->replaceInvalidCharacters($oldTabName, "");
	$oldTabName = str_replace("@", "", $oldTabName);

	// remove/replace all invalid characters - new name (space w/ underscore and @ removed)
	$newTabName = str_replace(' ', '_', $newTabName);
	$newTabName = $user->replaceInvalidCharacters($newTabName, "");
	$newTabName = str_replace("@", "", $newTabName);
	
	// get tabs for this folder
    $whereArr = array('doc_id'   => (int)$docID,
       	              'filename' => 'IS NULL');
    $tabArr = getTableInfo($db_dept, $cabInternalName.'_files', 
    					   array('subfolder'), $whereArr, 'queryCol');

	// be sure that this tab name isn't already being used, UNLESS the tab names are the same.
	if(strcmp($oldTabName, $newTabName) != 0) {
		if(in_array($newTabName, $tabArr)) {
			$message = "editTab(): '$newTabName' already exists.\n";
			return array('ret'=>false, 'msg'=>$message);
		}
	}
	
	// init
	$found        = 0;
	$pieces       = array();
	$newSavedList = "";
	
	// remove old name from saved list, if it exists there
	$value = getTableInfo($db_doc, 'settings',
						  array('value'),
						  array('k' => $cabInternalName.'_tabs',
						  		'department' => $deptInternalName),
						  'queryOne');
	
	$pieces = explode(",", $value);
	foreach($pieces as $piece) {
		if(! empty($piece)) {
			if(strcmp($piece, $oldTabName) == 0) {
				if($user->isAdmin() || $user->isDepAdmin()) {
					$found = 1;
				} else {
					$message = "editTab(): need to be admin/deptAdmin to remove saved tab.\n";
					return array('ret'=>false, 'msg'=>$message);
				}
			} else {
				$newSavedList .= $piece.",";
			}
		}
	}
	
	// add new name to the saved list, if flag set
	if($saved) {
		if($user->isAdmin() || $user->isDepAdmin()) {
			$found = 1;
			$newSavedList .= $newTabName.",";
		} else {
			$message = "editTab(): need to be admin/deptAdmin to add saved tab.\n";
			return array('ret'=>false, 'msg'=>$message);
		}
	}

	// update settings (saved tabs) table, if list modified
	if($found) {
		updateTableInfo($db_doc, 'settings',
						array('value' => $newSavedList),
						array('k' => $cabInternalName.'_tabs',
							  'department' => $deptInternalName)); 
	}
	
	// update the existing tab references with new name
	updateTableInfo($db_dept, $cabInternalName.'_files', 
					array('subfolder' => $newTabName), 
					array('subfolder' => $oldTabName)
					);
	
	return array('ret'=>true);
	
}	// end editTab()

/**
 *  Description: removes the tab completely from the cabinet, IF none of the instances of where it is being used have any files contained within. - FUTURE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param string $tabName name of the tab to remove
 * @param bool $force remove the tab even if is being used by folders (and can potentially containg files.
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteTab($passKey, $deptDisplayName, $cabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE: cannot currently delete a tab

	return array('ret'=>false, 'msg'=>"FUTURE functionality");

}	// end deleteTab()


/*
 * -file-
 */ 

/**
 *  Description: list of all of the files in a specified folder (all tabs/documents). 
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param string $folderID document ID of the folder to look up files
 * @return array 'ret' = true, 'data' = array of file id and name pairs; 'ret' = false, 'msg' = error message
 * @example 
 */
function getFileList($passKey, $deptDisplayName, $cabDisplayName, $folderID, $includeEmptyTab = false) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}


	if($includeEmptyTab == false) {
		$fileInfo = getTableInfo($db_dept, $cabInternalName."_files",
						  array('id', 'filename', 'subfolder'),
						  array('doc_id'   => $folderID, 
						  		'deleted'  => '0', 'display' => '1',
						  		'filename' => 'IS NOT NULL'),
						  'queryAll');
	}
	else{
		$fileInfo = getTableInfo($db_dept, $cabInternalName."_files",
						  array('id', 'filename', 'subfolder'),
						  array('doc_id'   => $folderID, 
						  		'deleted'  => '0', 'display' => '1'),
						  'queryAll');
	}

	return array('ret'=>true, 'data'=>$fileInfo);

}	// end getFileList()

/**
 *  Description: add a file in a specified folder/tab. 
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID ID number of the folder to contains the tab that contains the file
 * @param int $tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
 * @param string $fileNameFull full path to the file to be uploaded 
 * @return array 'ret' = true, 'data' = fileID added; 'ret' = false, 'msg' = error message
 * @example 
 */
function addFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileNameFull) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	global $DEFS;
    // init
	$encodedFile = false;
	$message = "";
	
	// must be in document view
	//$ret = isDocumentView($passKey, $deptDisplayName, $cabDisplayName);
	//if(!$ret) {
	//	$message = "addFile(): cabinet (".$cabDisplayName.") not in document view.\n";
	//	return array('ret'=>false, 'msg'=>$message);
	//}

    // get the contents of the file
	$createDate = date('Y-m-d G:i:s');
	if(is_file($fileNameFull)) {
    	$encodedFile = file_get_contents($fileNameFull);
		$lastModifiedDate = date ("Y-m-d G:i:s", filemtime($fileNameFull));
		$createDate = getFileCreationDate($deptInternalName, $db_doc, $lastModifiedDate);
	} else {
    	$message = "add_file(): filename ($fileNameFull) is not a file or you do not have permissions...\n";
    	return array('ret'=>false, 'msg'=>$message);
    }
    if($encodedFile === false) {
    	$message = "addFile(): unable to find/encode the file ($fileNameFull)\n";
    	return array('ret'=>false, 'msg'=>$message);
    }

	// peel off the filename from the FS path
    $fileName = basename($fileNameFull);
    
	if($tabID > 0) {
       	// name of the tab that we are placing the file into
		$destTab = getTableInfo($db_dept, $cabInternalName.'_files', 
								array('subfolder'), 
								array('id' => (int)$tabID), 
								'queryOne');
	} else {
		// main tab
		$destTab = null;
	}

	// where in the order the file will be placed
    $ordering = getOrderType($deptInternalName, $cabInternalName,
       						 $folderID, $destTab, $userName, 1, 
       						 $db_doc, $db_dept);
    if($ordering == NULL) {
       	// if a file doesn't already exist in this tab, make it #1
        $ordering = 1;
    }

    // get the record elements for this folder
	$result = getTableInfo($db_dept, $cabInternalName, 
						   array(), array('doc_id' => $folderID));
    if(PEAR::isError($result)) {
       	$message = "addDocument(): error finding folders in cabinet (".$cabInternalName.")\n";
        return false;
    }

    // be sure that the fileName is unique
    $newFileName = getSafeFilename($db_dept, $cabInternalName, $folderID, $destTab, $fileName);

    // build up the file system file location
    $location = str_replace(" ", "/", getFolderLocation($db_dept, 
       													$cabInternalName, 
       													$folderID));
	
    $location = $DEFS['DATA_DIR']."/".$location."/".$destTab."/".$newFileName;

    // puts the file on disk
    putContents($location, $encodedFile);

    //Values for placing query into DB
	$res = array();
    $res['filename']        = $newFileName;
    $res['doc_id']          = $folderID;
	if($destTab) {
		$res['subfolder']   = $destTab;
	}
    $res['ordering']        = $ordering;
    $res['date_created']    = $createDate;
    $res['who_indexed']     = $userName;
    $res['parent_filename'] = $newFileName;
    $res['file_size']       = filesize($location);

    // insert file record into DB
    $result = $db_dept->extended->autoExecute($cabInternalName."_files", $res);
    if(PEAR::isError($result)) {
       	$message = "addFile(): error inserting file in cabinet (".$cabInternalName.")\n";
        return array('ret'=>false, 'msg'=>$message);
    }
    	
	// grab the new file ID and return it
	$fileID = 0;
	$fileID = getTableInfo($db_dept, $cabInternalName.'_files', 
						   array('MAX(id)'),
						   array('filename' => $newFileName, 'doc_id' => $folderID), 
						   'queryOne');
	    
	return array('ret'=>true, 'data'=>$fileID);
	
}	// end addFile()

function getFileCreationDate($deptInternalName, $db_doc, $lastModifiedDate) {
	$glbSettings = new GblStt($deptInternalName, $db_doc, $lastModifiedDate);
	$newFileCreationDate = $glbSettings->get('newFileCreationDate');

	$createDate = date('Y-m-d G:i:s');
	if ($newFileCreationDate == 'uploadedDate') {
		$createDate = $lastModifiedDate;
	}

	return $createDate;
} // end getFileCreationDate()
	
/**
 *  Description: rename a specific file in a specified folder/tab. 
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID name of the folder to contain file
 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
 * @param string $fileId id of the file to be renamed
 * @param string $newFileName new name
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function editFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileID, $newFileName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	
	global $DEFS;

	// init
	$message = "";
	
	// check that this is document view
	// must be in document view
	$ret = isDocumentView($passKey, $deptDisplayName, $cabDisplayName);
	if($ret['ret']) {
		$message = "editFile(): cabinet (".$cabDisplayName.") not in document view.\n";
		return array('ret'=>false, 'msg'=>$message);
	}

	$destTab = "";
	if($tabID != 0) {
       	// name of the tab that we are placing the file into
		$destTab = getTableInfo($db_dept, $cabInternalName.'_files', 
								array('subfolder'), 
								array('id' => (int)$tabID), 
								'queryOne');
	} else {
		// main tab
		$destTab = null;
	}
	
	// be sure that the fileName is unique
	$newUniqueName = getSafeFilename($db_dept, $cabInternalName, 
        							 $folderID, $destTab, $newFileName);
	        							
	// build up the file system file location
	$location = str_replace(" ", "/", getFolderLocation($db_dept, 
        												$cabInternalName, 
        												$folderID));
    $oldFileName = getTableInfo($db_dept, $cabInternalName.'_files', 
    							array('filename'),
    							array('id' => $fileID),
    							'queryOne');
	$oldLocation = $DEFS['DATA_DIR']."/".$location."/".$destTab."/".$oldFileName;
	$newLocation = $DEFS['DATA_DIR']."/".$location."/".$destTab."/".$newUniqueName;
	
	// rename the file on disk
	if(file_exists($oldLocation)) {
		if(! rename($oldLocation, $newLocation)) {
			$message = "editFile(): unable to rename file ($oldLocation -> $newLocation) on disk\n";
			return array('ret'=>false, 'msg'=>$message);
		}
	} else {
		$message = "editFile(): original file does not exist ($oldLocation)\n";
		return array('ret'=>false, 'msg'=>$message);
	}
	
	// adjust for table update
	if(is_null($destTab)) {
		$destTab ="IS NULL";
	}
	
	// change the DB entry, after the file is moved on disk
	updateTableInfo($db_dept, $cabInternalName.'_files', 
	        		array('filename'	=> $newUniqueName),
	        		array('id'			=> (int)$fileID,
	        			  'subfolder'	=> $destTab,
	        			  'doc_id'		=> (int)$folderID)
	        		);
	
	return array('ret'=>true);
	
}	// end editFile()

/**
 *  Description: marks a file from a specific folder/tab for deletion.
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $fileID file to mark for deletion
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function deleteFile($passKey, $deptDisplayName, $cabDisplayName, $fileID) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

    // change the DB entry
    updateTableInfo($db_dept, $cabInternalName."_files", 
					array('deleted'	=> '1','display'	=> '0'),
					array('id'		=> $fileID));

	return array('ret'=>true);
		
}	// end deleteFile()
	
/**
 *  Description: downloads a file(s) from a specific folder/tab in a specified format (native, PDF, ZIP).
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID folder containing the file
 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
 * @param array $fileNamesArr file(s) to be downloaded
 * @param string $destPath on disk location to place the file(s)
 * @param string $format format of the file(s) (native, PDF, ZIP)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */

/**
*  Description: move file(s) from a specific folder/tab to another. - FUTURE
* @param int $srcFolderID folder containing the file
* @param int $destFolderID folder containing the file
* @param array $fileNamesArr file to be removed
* @param string $tabName tab containing the file (NULL = main tab)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
* @example 
*/
function moveFiles($passKey, $deptDisplayName, $cabDisplayName, $srcFolderID, $destFolderID, $fileNamesArr, $tabName=NULL) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
//FUTURE:

	return array('ret'=>false, 'msg'=>"FUTURE functionality");
	
}	// end moveFiles()


/*
 * -note-
 */ 

/**
 *  Description: get note(s) from a specific file
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID folder containing the file
 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
 * @param string $fileName file to be removed
 * @return array 'ret' = true, 'data' = $notes (notes for file string); 'ret' = false, 'msg' = error message
 * @example 
 */
function getFileNotes($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// init
	$destTab = "";
	
	if($tabID != 0) {
		// name of the tab that we are placing the file into
		$destTab = getTableInfo($db_dept, $cabInternalName.'_files', 
								array('subfolder'), 
								array('id' => (int)$tabID), 
								'queryOne');
	} else {
		// main tab
		$destTab = "IS NULL";
	}

	$notes = getTableInfo($db_dept, $cabInternalName.'_files',
       					  array('notes'),
       					  array('filename'  => $fileName,
       					  		'subfolder' => $destTab,
       					  		'doc_id'    => $folderID),
       					  'queryOne');

    return array('ret'=>true, 'data'=>$notes);

}	// end getFileNotes()	
	
/**
 *  Description: add note to a specific file
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID folder containing the file
 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
 * @param string $fileName file to be removed
 * @param string $note string message to be attached to the file
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function addFileNote($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileName, $note) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// init
	$destTab = "";
	
	// construct correct format for the new note
	$curDate = date("g:ia M-d-Y");
	$newNote = ',{'.$userName.','.$curDate.','.$note.'}';
	
    if($tabID != 0) {
       	// name of the tab that we are placing the note into
		$destTab = getTableInfo($db_dept, $cabInternalName.'_files', 
								array('subfolder'), 
								array('id' => (int)$tabID), 
								'queryOne');
    } else {
	    // main tab
       	$destTab = "IS NULL";
    }

    // get existing notes (if any)
    $exNotes = getTableInfo($db_dept, $cabInternalName."_files", array('notes'),
    						array('doc_id'=>$folderID, 'subfolder'=>$destTab, 'filename'=>$fileName), 
    						'queryOne');
    
    // concat exiting notes onto new note.
    $newNote .= $exNotes;
    
    // insert the notes
    $updateArr = array('notes'     => $newNote);
    $whereArr  = array('filename'  => $fileName,
       				   'subfolder' => $destTab,
       				   'doc_id'    => $folderID);
    updateTableInfo($db_dept, $cabInternalName.'_files', $updateArr, $whereArr);
        
    return array('ret'=>true);

}	// end addFileNote()

/* editFileNote() - we don't currently support this function... */
	
/* deleteFileNote() - we don't currently support this function... */
		
/*
 * -versioning-
 */ 

/**
 *  Description: check out a specific file for versioning
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $fileID file to be checked out
 * @return array 'ret' = true, 'data' = string containing file data; 'ret' = false, 'msg' = error message
 * @example 
 */
function checkOutFile($passKey, $deptDisplayName, $cabDisplayName, $fileID) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	global $DEFS;
	
	// if we have never versioned this file, set it up
	$parentID = getParentID($cabInternalName, $fileID, $db_dept);
	if($parentID == 0) {
	    makeVersioned($cabInternalName, $fileID, $db_dept);
	    $parentID = $fileID;
	}

	// prepare to version
	$gotlock = checkAndSetLock($cabInternalName, $parentID, $db_dept, 
							   $userName);
	$fileID  = getRecentID($cabInternalName, $parentID, $db_dept);
	$who     = whoLocked($cabInternalName, $parentID, $db_dept);
	
	// Get information for the file name if check out for writing
	if ($gotlock || ($who == $userName)){
		$fileRow  = getTableInfo($db_dept, $cabInternalName.'_files', 
								 array(), array('id' => (int) $fileID), 
								 'queryRow');
		$whereArr = array('doc_id' => (int)$fileRow['doc_id']);
		$result   = getTableInfo($db_dept, $cabInternalName, 
								 array(), $whereArr, 'queryOne');
		$row      = $result->fetchRow();
			
		// creating a path to the file on disk
		$path     = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/{$row['location']}");
		
		if(isset($fileRow['subfolder']) and $fileRow['subfolder']) {
				$path = $path."/".$fileRow['subfolder'];
		}
		
		$file        = $path."/".$fileRow['filename'];
		$encFileData = file_get_contents($file);
		
		return array('ret'=>true, 'data'=>$encFileData);
	}

	$message = "checkOutFile(): shouldn't have gotten here... error\n";
	return array('ret'=>false, 'msg'=>'$messsage'); // shouldn't get here
	
}	// end checkOutFile()

/**
 *  Description: cancel the check out a specific file for versioning
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $fileID file to be checked out
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function cancelCheckout($passKey, $deptDisplayName, $cabDisplayName, $fileID) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$parentID = getParentID($cabInternalName, $fileID, $db_dept);
	unLock($cabInternalName, $parentID, $db_dept);
	unFreezeFile($cabInternalName, $parentID, $userName, $db_dept);
	
	return array('ret'=>true);
	
}	// end cancelCheckout()

/**
 *  Description: check in a specific file for versioning
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $folderID folder containing the file
 * @param string $fileName full path to the file to be checked in
 * @param string $encFileData encoded file contents
 * @return array 'ret' = true, 'data' version number; 'ret' = false, 'msg' = error message
 * @example 
 */
function checkInFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $fileName, $encFileData) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);

	if ($cabinet !== false) {
		$parentID = getParentID($cabinet, $fileID, $db_dept);
		$fileID   = getRecentID($cabinet, $parentID, $db_dept);
		$who      = whoLocked($cabinet, $parentID, $db_dept);
		
		// Get information for the file name if check out for writing
		if($who == $userName) {
			// fill in all of the database field values
			$fileArr = getCheckInDetails($cabinet, $parentID,
				 						 $db_dept, 
										 $userName, $fileName);

			// write new contents to this file version to the FS
			if (file_put_contents($fileArr['path'], $encFileData)) {
				$user = new user();
				$user->username = $userName;
				$user->db_name = $department;
				
				// actually do the database updates
				$version = checkInVersion($db_dept, $fileArr, 
										  $cabinet, $parentID, $user, 
										  $db_doc);
				// false if quota is exceeded
				if($version == false) {
					return array('ret'=>false, 'msg'=>'checkInFile(): quota is exceeded.\n');
				} else {
					return array('ret'=>true, 'data'=>$version);
				}
			}
		}
	}	// end if(cab exists)
	
	return array('ret'=>false, 'msg'=>'checkInFile(): should not have gotten here.\n'); // shouldn't get here
	
}	// end checkInFile()

/**
 *  Description: return the current version of a specific file
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $doc_id folder that we are working with
 * @param string $subFolder tab within folder (exactly as in the DB)
 * @param string $fileName file we are looking for
 * @return array 'ret' = true, 'data' = current version number; 'ret' = false, 'msg' = error message
 * @example 
 */
function getCurrentVersion($passKey, $deptDisplayName, $cabDisplayName, $doc_id, $subFolder, $fileName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// display=1 is the latest version
	$sArr = array('v_major', 'v_minor');
	if($subFolder == NULL || strcmp($subFolder, "Main") == 0) {
		$wArr = array("doc_id='".$doc_id."'", 
					  "display='1'", 
					  "parent_filename='".$fileName."'",
					  "subfolder IS NULL", 
					  "deleted='0'");
	} else {
		$wArr = array("doc_id='".$doc_id."'", 
					  "display='1'", 
					  "parent_filename='".$fileName."'", 
					  "subfolder='".$subFolder."'", 
					  "deleted='0'");
	}

	$vArr = getTableInfo($db_dept, $cabInternalName.'_files',
						 $sArr, $wArr, 'queryRow');
						 
//	$query = 'SELECT v_major, v_minor FROM '.$cabInternalName.'_files WHERE doc_id='.
//			 $doc_id.' AND display=1 AND deleted=0 AND parent_filename='.$fileName.
//			 ' AND subfolder LIKE "'.$subFolder.'%"';
//error_log("get Current Version query ($query)\n");
	$version = $vArr['v_major'].".".$vArr['v_minor'];
	
	return array('ret'=>true, 'data'=>$version);
		
}	// end getCurrentVersion()
	
/**
 *  Description: return a list of all version numbers for a specific file
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $doc_id document type id for file that we are working with
 * @param string $subFolder tab within folder (exactly as in the DB)
 * @param string $fileName file we are looking for
 * @return array 'ret' = true, 'data' list all version numbers; 'ret' = false, 'msg' = error message
 * @example 
 */
function getVersionList($passKey, $deptDisplayName, $cabDisplayName, $doc_id, $subFolder, $fileName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$versions = array();
	$sArr = array('v_major', 'v_minor');
	if($subFolder == NULL || strcmp($subFolder, "Main") == 0) {
		$wArr = array("doc_id='".$doc_id."'", 
					  "parent_filename='".$fileName."'",
					  "subfolder IS NULL", 
					  "deleted='0'");
	} else {
		$wArr = array("doc_id='".$doc_id."'", 
					  "parent_filename='".$fileName."'",
					  "subfolder='".$subFolder."'", 
					  "deleted='0'");
	}
	
	// get all of the versions from the DB
	$versions = getTableInfo($db_dept, $cabInternalName.'_files', 
							 array('v_major', 'v_minor'), $wArr, 
							 'queryAll', array('v_major' => 'DESC', 'v_minor' => 'DESC'));

	// build the return array of versions
	$retVersions = array();
	foreach($versions as $version) {
		$retVersions[] = $version['v_major'].".".$version['v_minor'];
	}
	
	return array('ret'=>true, 'data'=>$retVersions);
							 		
}	// end getVersionList()

/**
 *  Description: change the version of a specific file
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the cabinet
 * @param int $doc_id document type id for file that we are working with
 * @param string $subFolder tab within folder
 * @param string $fileName file we are looking for
 * @param string $oldVersion the old version number for this file (format: <major>.<minor>)
 * @param string $newVersion the new version number for this file (format: <major>.<minor>)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function changeFileVersion($passKey, $deptDisplayName, $cabDisplayName, $doc_id, $subFolder, $fileName, $oldVersion, $newVersion) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	// init - updateTableInfo() only returns true... be sure we get that far
	$ret = false;
	$versions = array();
	
	// Tab name (of main) used in where clauses
	if($subFolder == NULL || strcmp($subFolder, "Main") == 0) {
		$wTab = "subfolder IS NULL"; 
	} else {
		$wTab = "subfolder='".$subFolder."'";
	}
	
	// break the version into it's major and minor pieces
	list($old_v_major, $old_v_minor) = explode(".", $oldVersion);
	list($new_v_major, $new_v_minor) = explode(".", $newVersion);
	
	// check if the old version exists.
	$wArr = array("doc_id='".$doc_id."'", "parent_filename='".$fileName."'", $wTab, 
				  "v_major='".$old_v_major."'", "v_minor='".$old_v_minor."'");
	$existsOldID = getTableInfo($db_dept, $cabInternalName.'_files',
								array('id'), $wArr, 'queryOne');
	if($existsOldID === false) {
		// already exists...
		$message = "changeFileVersion(): existing version does not exist.\n";
		return array('ret'=>false, 'msg'=>$message);
	}

	// check if we are assigning a version that already exists.
	$wArr = array("doc_id='".$doc_id."'", "parent_filename='".$fileName."'", $wTab, 
				  "v_major='".$new_v_major."'", "v_minor='".$new_v_minor."'");
	$existsNewID = getTableInfo($db_dept, $cabInternalName.'_files',
								array('id'), $wArr, 'queryOne');
	if($existsNewID !== false) {
		// already exists...
		$message = "changeFileVersion(): new version already exists.\n";
	}
	
	// check if this new version is the highest, meaning that it is the display default
	$sArr = array('v_major', 'v_minor');
	$wArr = array("doc_id='".$doc_id."'", 
				  "parent_filename='".$fileName."'",
				  $wTab, 
				  "deleted='0'");
	
	// get all of the versions from the DB
	$versions = getTableInfo($db_dept, $cabInternalName.'_files', 
							 array('v_major', 'v_minor', 'id'), $wArr, 
							 'queryAll', array('v_major' => 'DESC', 'v_minor' => 'DESC'));

	// find the highest version
	$highMajor = 0;
	$highMinor = 0;
	$highID    = 0;
	foreach($versions as $version) {
		if( ($version['v_major'] > $highMajor) ||
			($version['v_major'] == $highMajor && $version['v_minor'] > $highMinor) ) 
		{
			$highMajor = $version['v_major'];
			$highMinor = $version['v_minor'];
			$highID    = $version['id'];
		}
	}
	
	// are we replacing the existing 'current' version?
	$newHigh = false;
	if( ($new_v_major > $highMajor) || 
		($new_v_major == $highMajor && $new_v_minor > $highMinor) ) 
	{
		$newHigh = true;
		$retOldDisplay = updateTableInfo($db_dept, $cabInternalName.'_files',
										 array('display'=>0), array('id'=>$highID), 1); 
		$retNewDisplay = updateTableInfo($db_dept, $cabInternalName.'_files',
										 array('display'=>1), array('id'=>$existsOldID), 1);
	}

	// update the current version in db
	$updateArr = array('v_major'=>$new_v_major, 'v_minor'=>$new_v_minor);
	$ret       = updateTableInfo($db_dept, $cabInternalName.'_files', 
								 $updateArr, array('id'=>$existsOldID), 1);
	
	// finish
	if($ret == false) {
		return array('ret'=>false, 'msg'=>"changeFileVersion(): DB update failed.\n");
	} else {
		return array('ret'=>true);
	}

}	// end changeFileVersion()

/*
 * -search-
 */

/**
 *  Description: searches for $search in every field and cabinet within a department.
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $search string to search on
 * @return array 'ret' = true, 'data' = array containing an array of table rows and a count (string $tempTable, int $numOfResults); 'ret' = false, 'msg' = error message
 * @example 
 */
//TODO: efficient?
function searchTopLevel($passKey, $deptDisplayName, $searchStr) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, NULL);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$cabArr = cabinetList($userName, $db_dept, 0);
	$cabsInfo = getTableInfo($db_dept, 'departments');
	$cabAssoc = array ();
	while($row = $cabsInfo->fetchRow()) {
		$cabAssoc[$row['real_name']] = $row['departmentname'];
	}
	$terms = splitOnQuote($db_dept, $searchStr, true);
	$tlsArr = array ();
	$ctArr = array ();

	foreach($cabArr as $myCab) {
		$tempTable = searchTable($db_dept, $myCab, false, $terms);
		$count = getTableInfo($db_dept, $tempTable, array('COUNT(*)'), array(), 'queryOne');
		if ($count) {
			$tlsArr[$cabAssoc[$myCab]] = $tempTable;
			$ctArr[$cabAssoc[$myCab]] = (int)$count;
		}
	}
	
	return array('ret'=>true, 'data'=>array('tempTable' => $tlsArr, 'resultCount' => $ctArr));

}	// end searchTopLevel()

/**
 *  Description: search cabinet by index value
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param array string $searchArr associative array to search by ($columnName => string $searchValue)
 * @return array 'ret' = true, 'data' = array('tempTable'=>$tempTable( table_id | result_id (doc_id),'resultCount'=>$resultCount); 'ret' = false, 'msg' = error message
 * @example array([tempTable]=>mpdiavmtcrigge, [resultCount]=>2)
 */
function searchCabinetIndicies($passKey, $deptDisplayName, $cabDisplayName, $searchArr) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$search    = new search();
	$tempTable = $search->getSearch($cabInternalName, $searchArr, $db_dept);

	$count = getTableInfo($db_dept, $tempTable, array('COUNT(*)'), array(), 'queryOne');
	
	return array('ret'=>true, 'data'=>array('tempTable' => $tempTable, 'resultCount' => (int)$count));	

}	// end searchCabinetIndicies()

/**
 *  Description: this is the document search in the gui can include prior results
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param $docTypeDisplayName 
 * @param $searchArr 
 * @param $tempTable 
 * @return array 'ret' = true, 'data' = array(table_id | document_field_value_list_id | cabInternalName | doc_id | file_id ), $count; 'ret' = false, 'msg' = error message
 * @example 
 */
function searchDocumentTypes($passKey, $deptDisplayName, $cabDisplayName=NULL, $docTypeDisplayName, $searchArr, $tempTable=NULL) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$doc_ids  = array();
	$file_ids = array();
	if ($tempTable) {
		$query = "SELECT * FROM ".$tempTable;
		$tempTableResults = $db_dept->queryAll($query);
		if (isset($tempTableResults[0]['doc_id'])) {
			echo "This must be a file result<br>";
			foreach($tempTableResults as $row){
				$doc_ids[]  = $row['doc_id'];
				$file_ids[] = $row['result_id'];
			}
		} else {
			foreach($tempTableResults as $row){
				$doc_ids[] = $row['result_id'];
			}
		}
	}	// end if(tempTable)
	
	// get the document table name
	$query             = "SELECT * FROM document_type_defs WHERE document_type_name='".$docTypeDisplayName."'";
	$results           = $db_dept->queryAll($query);
	$documentTableName = $results[0]['document_table_name'];
	
	$query = "SELECT document_field_value_list.id, cab_name, doc_id, file_id, document_field_defs_list.arb_field_name 
			  FROM ".$documentTableName.", document_field_defs_list, document_field_value_list 
			  WHERE document_table_name = '".$documentTableName."' 
			  AND ".$documentTableName.".id = document_field_value_list.document_id and document_field_defs_list.id = document_field_value_list.document_field_defs_list_id 
			  AND (";
	
	// foreach through searchArr - WHAT KIND OF COMMENT IS THIS??????????
	$flag = 0;		  
	foreach($searchArr as $index => $value) {
		if($flag) {
			$query = $query." AND ";
		}
		$query = $query."(document_field_defs_list.arb_field_name = '".$index."' 
						  AND document_field_value_list.document_field_value LIKE '%".$value."%')";
		$flag  = 1;
	}
	$query = $query.")";
	
	if($cabDisplayName) {
		$query = $query." AND ".$documentTableName.".cab_name='".$cabInternalName."'";
	}
	$results   = $db_dept->queryAll($query);
	$indexArr  = array (
						'table_id '.AUTOINC,
						'PRIMARY KEY (table_id)',
						'document_field_value_list_id INT DEFAULT 0',
						'cabInternalName VARCHAR(255) NULL',
						'doc_id INT DEFAULT 0',
						'file_id INT DEFAULT 0',
						'document_field_defs_list_name VARCHAR(255) NULL'
						);

	$tempTable   = createDynamicTempTable($db_dept,$indexArr);
	$destColumns = array (
						  'document_field_value_list_id',
						  'cabInternalName',
						  'doc_id',
						  'file_id',
						  'document_field_defs_list_name'
						  );
	$query = 'INSERT INTO '.$tempTable.' ';
	if($destColumns) {
		$query .= '('.implode(',', $destColumns).') VALUES ';
	}
	$flag=0;
	foreach($results as $row) {
		if ((in_array($row['file_id'], $file_ids) && count($file_ids)) || (count($file_ids)==0 && count($doc_ids) && in_array($row['doc_id'], $doc_ids)) || count($doc_ids)==0) { //filter out $tempTable
			if ($flag) $query.=",";
			$query .="('".implode("','", $row)."')";
			++$flag;
		}
	}
	$res = $db_dept->query($query);
	
	return array('ret'=>true, 'data'=>array('tempTable'=>$tempTable, 'resultCount'=>(int)$flag));
	
}	// end searchDocumentTypes()

/**
 *  Description: searchDocumentInFolder() in the gui equivelent search on doctype optional document type, cabID, FolderID
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param string $docTypeDisplayName 
 * @param array $searchArr  
 * @param int $docID 
 * @return array 'ret' = true, 'data' = array results from the searchDocumentTypes() function; 'ret' = false, 'msg' = error message
 * @example 
 */
function searchDocumentInFolder($passKey, $deptDisplayName, $cabDisplayName, $docTypeDisplayName, $searchArr, $docID) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$tempTable = createTemporaryTable($db_dept);
	$query     = 'INSERT INTO '.$tempTable.' (result_id) VALUE ('.$docID.')';
	$res       = $db_dept->query($query);
	
	$retVal = array();
	$retVal = searchDocumentTypes($passKey, $deptDisplayName, $cabDisplayName, 
							   $docTypeDisplayName, $searchArr, $tempTable);
						
	return array('ret'=>true, 'data'=>$retVal);
							   
}	// end searchDocumentInFolder()

/**
 *  Description: launches the workflow... code aquired from assignWorkflow(webservices.php)
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param int $wfDefsID workflow definitions ID from wf_defs table
 * @param string $wfOwner user which started the workflow
 * @param int $docID 
 * @param int $tabID 
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function startWF($passKey, $deptDisplayName, $cabDisplayName, $wfDefsID, $wfOwner, $docID, $tabID) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	
	$cabinet = hasAccess($db_dept, $userName, $cabInternalNameID);
	if ($cabinet !== false) {
		$wfDocID = (int)addToWorkflow($db_dept, $wfDefsID, $docID, 
									  $tabID, $cabinet, $wfOwner);
		if ($wfDocID != -1) {
			$stateNode = new stateNode($db_dept, $deptInternalName, 
									   $wfOwner, $wfDocID, $wfDefsID, $cabinet, 
									   $cabinet, $docID, $db_doc);
			$stateNode->notify();
			return array('ret'=>true);
		}
	}	// end if(access to cabinets)
	
	// failure to launch
	return array('ret'=>false, 'msg'=>"startWF(): workflow did not start.\n");

}	// end startWF()
	
/**
 *  Description: launches the workflow... FUTURE
 *  		code aquired from assignWorkflow(webservices.php)
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
// FUTURE:


/*
 * -publishing-
 */ 
// none planned yet...


/*
 * -reports-
 */ 
// none planned yet...


/*
 * -support- not intended to be public
 */

/**
 *  Description: get folder index values for search results - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param array $tempTable
 * @param int $startIndex
 * @param int $numberToFetch
 * @return array 'ret' = true, array(doc_id=>array(index=>value)); 'ret' = false, 'msg' = error message
 * @example array([6]=>Array([index1]=>fred, [index2]=>1345, [index3]=>645233, [index4]=>456456, 
 * 		[index5]=>00456789, [index6]=>000-123456-555, [index7]=>456872, [index8]=>fred was here), 
 * 		[5]=>array([index1]=>test3, [index2]=> , [index3] => , [index4] => , [index5] => , [index6] => ,
 * 		[index7] => [index8] => fred wasn't here));
 */
function getFolderResults($passKey, $deptDisplayName, $cabDisplayName, $tempTable, $startIndex, $numberToFetch) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$result = getTableInfo($db_dept, 
						   array($cabInternalName, $tempTable),  //table
						   array(),  // SELECT *
						   array($cabInternalName.'.doc_id='.$tempTable.'.result_id', 
						   		 'deleted=0'), //WHERE
						   'query', // type of query
						   array('doc_id' => 'DESC'), //ordering
						   $startIndex, //limit
						   $numberToFetch //count
						   );
						   
	$cabIndices = getCabinetInfo($db_dept, $cabInternalName);
	$retArr = array();
	while($row = $result->fetchRow()) {
		$newRow = array ();
		foreach($cabIndices as $index) {
			$newRow[$index] = $row[$index];
		}
		$retArr[$row['doc_id']] = $newRow;
	}
	return array('ret'=>true, 'data'=>$retArr);

}	// end getFolderResults()

/**
 *  Description retrieves field definition including regular expression, date field and dropdowns - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @return array 'ret' = true, array(doc_id=>array(index=>value)); 'ret' = false, 'msg' = error message
 * @example 
 */
function getCabinetDataTypeDefs($passKey, $deptDisplayName, $cabDisplayName) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$autoCompArr = array();
	$settings    = new GblStt($deptInternalName, $db_doc);
	$indices     = getCabinetInfo($db_dept, $cabinetName);

	$sArr      = array('field_name', 'required', 'regex', 'display');
	$wArr      = array('cabinet_id' => $cabInternalNameID);
	$fieldInfo = getTableInfo($db_dept, 'field_format', $sArr, $wArr, 'getAssoc');
	$fInfo     = array();

	foreach($indices As $arrKey => $cabIndex)
	{
		if(isSet($fieldInfo[$cabIndex])) {
			$fInfo[$cabIndex] = array('index' => $cabIndex,
									  'required' => $fieldInfo[$cabIndex]['required'],
									  'regex' => $fieldInfo[$cabIndex]['regex'],
									  'display' => $fieldInfo[$cabIndex]['display']);
		} else {
			$fInfo[$cabIndex] = array('index' => $cabIndex,
									  'required' => 0,
									  'regex' => "",
									  'display' => "");
		}

		$key = "dt, $deptInternalName, $cabInternalNameID, $cabIndex";
		$autoComplete = $settings->get($key);
		if(strcmp($autoComplete, "") != 0) {
 			$fInfo[$cabIndex]['regex'] = 'dropdownValues: '.$autoComplete;
 			$autoCompArr[$cabIndex] = $fInfo[$cabIndex];
		} else {
			$autoCompArr[$cabIndex] = $fInfo[$cabIndex];
		}
	}

	return array('ret'=>true, 'data'=>$autoCompArr);

}	// end getCabinetDataTypeDefs()
	

/*
 * DATA RESULT functions:
 */


/**
 *  Description: this is the fileADVSearch can pass prior search results to limit results - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param string $tempTable
 * @param string $subfolder
 * @param string $date
 * @param string $date2
 * @param string $who
 * @param string $ocr_context
 * @param string $notes
 * @param string $filename
 * @return array 'ret' = true,  array(table_id | result_id(file_id) | doc_id), $numResults; 'ret' = false, 'msg' = error message
 * @example 
 */
function searchCabinetDetails($passKey, $deptDisplayName, $cabDisplayName, $tempTable=NULL, 
							  $subfolder=NULL, $date=NULL, $date2=false, $who=NULL, $context=NULL,
							  $contextbool=false, $notes=NULL, $filename=NULL) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$tmpUsr = new user();
	$tmpUsr->username = $userName;
	$tmpUsr->db_name  = $deptInternalName;

	$subfolder = splitOnQuote($db_dept, $subfolder, true);
	
	if(! $contextbool) {
		$context = splitOnQuote($db_dept, $context, true);
	}
	
	$who         = splitOnQuote($db_dept, $who, true);
	$notes       = splitOnQuote($db_dept, $notes, true);
	$filename    = splitOnQuote($db_dept, $filename, true);
	
	if(! $date2) {
		$date2 = -1;
	}
	
	$search = new fileSearch($tmpUsr);
	$search->findFile($cabInternalName, $filename, $context, $subfolder, $date, 
					  $date2, $who, $notes, $contextbool);
					  
	// now filter out
	if($tempTable) {
		$query = "Select * from ".$tempTable;
		$tempTableResults = $db_dept->queryAll($query);
		
		if(isset($tempTableResults[0]['doc_id'])) {
			$query = "DELETE FROM ".$search->tempTableName." WHERE not exists (SELECT 1 FROM ".
					  $tempTable." WHERE ".$search->tempTableName.".result_id=".
					  $tempTable.".file_id)";
		} else {
			$query = "DELETE FROM ".$search->tempTableName." WHERE not exists (SELECT 1 FROM ".
					  $tempTable." WHERE ".$search->tempTableName.".doc_id=".
					  $tempTable.".result_id)";
		}
		$res = $db_dept->query($query);
	}
	
	$tempTable  = $search->tempTableName;
	$query      = "SELECT count(*) AS qty FROM ".$tempTable;
	$qty        = $db_dept->queryAll($query);
	$numResults = $qty[0]['qty'];
	
	return array('ret'=>true, 'data'=>array('tempTable'=>$tempTable, 'resultCount'=>(int)$numResults));

}	// end searchCabinetDetails()

/**
 *  Description . - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
 * @example 
 */
function getTabFileResults($passKey, $deptDisplayName, $cabDisplayName, $subfolder, $tempTable, $startIndex, $numberToFetch) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	$results = getTableInfo($db_dept,
							array($cabInternalName."_files", $tempTable),  //table
							array('id', 'subfolder', 'parent_filename', 'filename', 
								  'file_size', $cabInternalName.'_files.doc_id',
								  'subfolder', 'date_created', 'who_indexed',
								  'ocr_context', 'notes'),  // SELECT columns
							array($cabInternalName.'_files.doc_id = '.$tempTable.'.doc_id', 
								  'deleted = 0', 
								  $cabInternalName.'_files.id = '.$tempTable.'.result_id',
								  $cabInternalName.'_files.subfolder = "'.$subfolder.'"'), //WHERE
							'getAssoc', // type of query
							array(), //ordering
							$startIndex, //limit
							$numberToFetch //count
							);
	
	return array('ret'=>true, 'data'=>$results);

}	// end getTabResults()

/**
 *  Description this will create a downloadable file (pdf or zip) of the files indicated by fileID and 
 *  		put the zip file into the personal inbox of the user logged in. - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param $fileIDArray pass file id string array
 * @return array 'ret' = true, string PDF filename; 'ret' = false, 'msg' = error message
 * @example 
 */
 function downloadFile($passKey, $deptDisplayName, $cabDisplayName, $docID, $tab, $fileIDArray, $destPath, $type) {
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	if (strcmp($type, "pdf") == 0) {
		$retVal2 = getAsPDF($passKey, $deptDisplayName, $cabDisplayName, $fileIDArray);
	}
	if (strcmp($type, "zip") == 0) {
		$fileName = $docID."_".$tab;
		$retVal2 = getAsZip($passKey, $deptDisplayName, $cabDisplayName, $fileIDArray, $fileName);
	}

	return array('ret'=>true, 'data'=>$retVal2['data']);
 }

/**
 *  Description this will create a PDF file of the files indicated by fileID and 
 *  		put the zip file into the personal inbox of the user logged in. - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department
 * @param $fileIDArray pass file array
 * @return array 'ret' = true, string PDF filename; 'ret' = false, 'msg' = error message
 * @example 
 */
///docs/tmp/docutron/fabaroa/xcfqcnsuslhi/45_Day_Screening1/fred1.tif
///docs/tmp/docutron/fabaroa/xcfqcnsuslhi/45_Day_Screening1/fred1.tif
///docs/tmp/docutron/fabaroa/xcfqcnsuslhi/45_Day_Screening1/frednobord.jpg
//$filename = $argv[1];
//$uname = $argv[2];
//$tmpPath = $argv[3];
//$db_name = $argv[4];
function getAsPDF($passKey, $deptDisplayName, $cabDisplayName, $fileIDArray){
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}
	
	global $DEFS;
	$tmpPath  = trim(getUniqueDirectory($DEFS['TMP_DIR'].'/docutron/'.$userName));
	$filename = $tmpPath."getAsPDF.txt";
	$fp       = fopen($filename,"w+");
	//create file
	// from the $fileIDArray get the location of the file
	
	foreach ($fileIDArray as $fileID) {
		$tArr    = array($cabInternalName."_files", $cabInternalName);
		$sArr    = array('location','subfolder','filename');
		$wArr    = array($cabInternalName."_files.doc_id=".$cabInternalName.".doc_id",$cabInternalName."_files.id=".$fileID);
		$results = getTableInfo($db_dept, $tArr, $sArr, $wArr, 'queryAll');
		
//$filename (/docs/tmp/docutron/fabaroa/xcfqcnsuslhi/uzclbfbclhqwjw) contains a list of files(fullpath) to be combined into the pdf. Can only be pdf,tiff or jpg
		$file = $DEFS['DATA_DIR']."/".str_replace(" ","/",$results[0]['location'])."/".$results[0]['subfolder']."/".$results[0]['filename'];
		if(is_file($file)) {
			$path_parts = pathinfo($file);
			$fileEXT    = $path_parts['extension'];
			$invalidExt = false;	
			if(strtolower($fileEXT) == "jpg" || strtolower($fileEXT) == "jpeg") {
				$jpegs = true;
			} elseif(strtolower($fileEXT) == "pdf") {
				$pdfs = true;
			} elseif(strtolower($fileEXT) != "tif" && strtolower($fileEXT) != "tiff") {
				$invalidExt = true;	
			}
			if(! $invalidExt) { //only pdf,tif,jpg
				fwrite($fp,$file."\n");
			}
		}
	}	// end foreach(fileID)
	
	fclose($fp);
	//call createPDF.php $filename $userName $tmpPath $cabInternalName
	$key      = "docDaemon_execute";
	$fileName = $userName."_".date('Y-m-d_H-i-s');
	$fileName = str_replace(":", "_", $fileName);
	$fileName = str_replace("/", "_", $fileName);
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$value = "php -q ".$DEFS['DOC_DIR']."/bots/createPDF.php $filename $userName $tmpPath $deptInternalName";
	} else {
		$value = "nice -17 php -q ".$DEFS['DOC_DIR']."/bots/createPDF.php $filename $userName $tmpPath $deptInternalName";
	}
	$insertArr = array(
		"k"				=> $key,
		"value"			=> $value,
		"department"	=> $deptInternalName
	);
	$res = $db_doc->extended->autoExecute("settings",$insertArr);

	return array('ret'=>true, 'data'=>$fileName."-files.pdf");
	
}	// end getAsPDF()

/**
 *  Description this will create a zip file of the files indicated by fileID and 
 *  	put the zip file into the personal inbox of the user logged in. ***NOTE ISO??? - PRIVATE
 * @param string $passKey login passKey
 * @param string $deptDisplayName name of the department
 * @param string $cabDisplayName name of the department* @param array $fileIDArray
 * @param string $fileName
 * @return array 'ret' = true, 'data' = string zipped filename to be found in inbox; 'ret' = false, 'msg' = error message
 * @example 
 
 */
// *	$results=$treenoService->getFileResults($passKey,$deptDisplayName,$cabDisplayName,$tempTable,0,$numberToFetch);
// * $fileIDArray=array();
// * $fileName="fred";
// * foreach ($results as $index=>$row){
// * 	$fileIDArray[]=$index;
// * 	echo $index."*<br>";
// * }
// * echo "</p><p>***getVersionList <br>";
// * $results=$treenoService->getAsZip($passKey,$deptDisplayName,$cabDisplayName,$fileIDArray,$fileName);
// * echo $results."<br>";
function getAsZip($passKey, $deptDisplayName, $cabDisplayName, $fileIDArray, $fileName){
	// validate session 
	$retVal = getGlobals($passKey, $deptDisplayName, $cabDisplayName);
	if ($retVal['ret'] == true){
		list($userName, $db_doc, $db_dept, $deptInternalName, 
			 $cabInternalName, $cabInternalNameID) = $retVal['data'];
	} else {
		return array('ret'=>false, 'msg'=>$retVal['msg']);
	}

	global $DEFS;

	$tempFileTable = createTemporaryTable($db_dept);
	foreach($fileIDArray AS $fileID) {
		$entry = array("result_id" => (int)$fileID);
		$res   = $db_dept->extended->autoExecute($tempFileTable,$entry);
		dbErr($res);
	}

	$key = "docDaemon_execute";
	$fileName  = $fileName."_".date('Y-m-d_H-i-s');
	$fileName  = str_replace(":", "_", $fileName);
	$fileName  = str_replace("/", "_", $fileName);
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$value = "php -q ".$DEFS['DOC_DIR']."/bots/buildZipDaemon.php $deptInternalName $cabInternalName $tempFileTable $fileName $userName";
	} else {
		$value = "nice -17 php -q ".$DEFS['DOC_DIR']."/bots/buildZipDaemon.php $deptInternalName $cabInternalName $tempFileTable $fileName $userName";
	}
	$insertArr = array(
		"k"				=> $key,
		"value"			=> $value,
		"department"	=> $deptInternalName
	);
	$res = $db_doc->extended->autoExecute("settings",$insertArr);

	return array('ret'=>true, 'data'=>$fileName.".zip");

}	// end getAsZip()

//Returns true if given cabinet is auto_complete enabled
//Returns false if given cabinet is not auto_complete enabled
//Returns -1 if cabinet does not exist or cabinet permissions denied
//  Check for return value === -1, not == -1
function isAutoComplete($userName, $department, $cabinetID, $db_doc)
{
    $db_dept = getDbObject($department);
    $cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
    if($cabinetName !== false) {
        $settings = new GblStt($department, $db_doc);
        $cabIndex = "indexing_$cabinetName";
        $autoComplete = $settings->get($cabIndex);
        if( strcmp($autoComplete, "auto_complete_$cabinetName") == 0 ) {
            return "auto_complete";
		} elseif(	strcmp($autoComplete, "odbc_auto_complete") == 0 ) {
			$sArr = array('lookup_field');
			$wArr = array('cabinet_name' => $cabinetName);
			$lookup = getTableInfo($db_dept,'odbc_auto_complete',$sArr,$wArr,'queryOne');
			$lookupArr = explode(",",$lookup);
			if(count($lookupArr) == 1) {
				return "odbc_auto_complete";
			} else {
				return false;
			}
		} elseif ($autoComplete == 'sagitta_ws_auto_complete') {
			return 'sagitta_ws_auto_complete';
		} else {
            return false;
		}
    }
	$db_dept->disconnect();
    return -1;
}

//Gets the list of indices that have values in the auto_complete table 
function getAutoComplete( $userName, $department, $cabinetID, $autoCompleteTerm, $db_doc )
{
 	global $DEFS;
	if(isSet($DEFS['WS_STRIP_CHARS'])) {
		$badChars = $DEFS['WS_STRIP_CHARS'];
		for($i=0;$i<strlen($badChars);$i++) {
			$autoCompleteTerm = str_replace($badChars{$i},"",$autoCompleteTerm);
		}
	}

    $autoCompleteValues = array();
    $autoComplete = isAutoComplete($userName, $department, $cabinetID, $db_doc);
    if( $autoComplete === -1 )
    {
        //if there no permissions/cabinet, returns -1
        return $autoComplete;
    }
    elseif( $autoComplete === false)
    {
        //if there is not autoComplete for cabinet return empty array
        return $autoCompleteValues;
    }
    elseif( strcmp($autoComplete, "auto_complete") == 0 )
    {
		$db_dept = getDbObject($department);
		//isAutoComplete() already tests for cabinet exists
		$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
		$indices = getCabinetInfo($db_dept, $cabinetName);

		//find where the first column value == $autoCompleteTerm
		$res = getTableInfo($db_dept, 'auto_complete_'.$cabinetName, 
			array(), array($indices[0] => $autoCompleteTerm), 'queryRow');
		$db_dept->disconnect();
        if(PEAR::isError($res))
            return false;

        if($res)
        {
            foreach($indices AS $arrKey => $cabIndex)
                $autoCompleteValues[$cabIndex] = $res[$cabIndex];
        }
        return $autoCompleteValues;
    }
	elseif( strcmp($autoComplete, "odbc_auto_complete") == 0 )
	{
		$db_dept = getDbObject($department);
		//isAutoComplete() already tests for cabinet exists
		$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
		$indices = getCabinetInfo($db_dept, $cabinetName);
	
		$odbcRes = getODBC_auto_complete( $userName, $department, $cabinetID, $autoCompleteTerm, $db_doc, $db_dept );
		if( !is_array($odbcRes) ) {
			return false;
		}

		foreach($indices AS $arrKey => $cabIndex) {
			$autoCompleteValues[$cabIndex] = $odbcRes[$cabIndex];
		}
		return $autoCompleteValues;
	} elseif ($autoComplete == 'sagitta_ws_auto_complete') {
		$db_dept = getDbObject($department);
		$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
		return getSagRow($cabinetName, $autoCompleteTerm, $department);
	}

	//Returns empty array
	return $autoCompleteValues;
}

//Returns the odbc auto complete row from the given value
function getODBC_auto_complete($userName, $department, $cabinetID, $searchTerm, $db_docutron, $db_dept=null )
{
/*	if( !check_enable('searchResODBC', $department) ) {
		return false;
	}
*/
	if( $db_dept == null ) {
		$db_dept = getDbObject($department);
	}
	//isAutoComplete() already tests for cabinet exists
	$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);

	$transInfo = getTableInfo($db_dept, 'odbc_auto_complete',
		array(), array('cabinet_name' => $cabinetName), 'queryRow');
	if($transInfo) {
		$searchVal = $searchTerm;
		if(strpos($searchVal, '"') === 0) {
			$searchVal = substr($searchVal, 1, strlen($searchVal) - 2);
		}

		$db_odbc = getODBCObject($transInfo['connect_id'], $db_docutron);
		if(PEAR::isError($db_odbc)) {
		 	return "Error connecting to ODBC Database!";
		}
		$gblStt = new GblStt ($department, $db_docutron);
		$row = getODBCRow($db_odbc, $searchVal, $cabinetName, $db_dept, '', $department, $gblStt);
		if( is_object( $db_odbc ) )
			$db_odbc->disconnect();
		if($row) {
			return $row;
		} else {
			return array();
		}
	}
}
//}	// end of class treenoServices

?>