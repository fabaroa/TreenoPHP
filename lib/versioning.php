<?php
// $Id: versioning.php 14979 2013-04-01 18:43:57Z cz $

include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/notes.php';
include_once '../lib/quota.php';
include_once '../lib/fileFuncs.php';
include_once '../centera/centera.php';
include_once '../modules/modules.php';

function getVersionedFilesArray($cabinetName, $db_object, $doc_id) {
	$vArr = array ();
	$Name = $cabinetName."_files";
	$whereArr = array('doc_id='.(int)$doc_id,'id!=parent_id');
	$res = getTableInfo($db_object,$Name,array('id','parent_id','redaction_id'),$whereArr);
	while ($row = $res->fetchRow()) {
		if($row['parent_id'] != 0) {
			if ($row['redaction_id'] > 0) {
				$vArr[$row['id']] = 0;
			} else {
				$vArr[$row['id']] = 1;
			}
		}
	}
	return $vArr;
}

function makeVersioned($cabinetName, $fileID, $db_object) {
	updateTableInfo ($db_object, $cabinetName.'_files',
		array ('parent_id' => 'id'), array ('id' => (int) $fileID));
}

function isFileVersioned($cabinetName, $fileID, $db_object) {
	$parentID = getParentID($cabinetName, $fileID, $db_object);
	return (numberOfVersions($cabinetName, $parentID, $db_object) > 1);
}

//Looks up the field 'who_locked', and if it is not NULL, it is locked, return
//true. Else, return false.
function isLocked($cabinetName, $fileID, $db_object) {
	$parentID = getTableInfo($db_object, $cabinetName.'_files',
		array('parent_id'), array('id' => (int) $fileID), 'queryOne');
		
	$whoLocked = getTableInfo($db_object, $cabinetName.'_files',
		array('who_locked'), array('id' => (int) $parentID), 'queryOne');
		
	if ($whoLocked)
		return true;
	return false;
}

//Calls the isLocked() function and if it is not, lock the file to r/w
//checkouts. This happens as an atomic operation in the table so that race
//conditions will not occur.
// Returns true if could grab the lock, false it it was already locked
function checkAndSetLock($cabinetName, $parentID, $db_object, $username) {
	$returnVal = true;
	lockTables($db_object, array($cabinetName.'_files'));
	if (!isLocked($cabinetName, $parentID, $db_object)) {
   		$updateArr = array('who_locked'=>$username,'date_locked'=>date('Y-m-d G:i:s'));
		$whereArr = array("id"=>$parentID);
		updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
	} else
		$returnVal = false;
	//MySQL syntax
	unlockTables($db_object);
	return $returnVal;
}

//Unlocks a file by the user that was previously locked.
function unLock($cabinetName, $parentID, $db_object) {
   		$updateArr = array('who_locked'=>'NULL','date_locked'=>'NULL');
		$whereArr = array("id"=>$parentID);
		updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr,1);
	return true;
}

// return who locked the given file
function whoLocked($cabinetName, $parentID, $db_object) {
	$whoLocked = getTableInfo($db_object, $cabinetName.'_files',
		array('who_locked'), array('id' => (int) $parentID), 'queryOne');

	return $whoLocked;
}

//Takes a fileID and compares it to the parentID, if same, true, else false.
function isParent($cabinetName, $fileID, $db_object) {
	$parentID = getTableInfo($db_object, $cabinetName.'_files',
		array('parent_id'), array('id' => (int) $fileID), 'queryOne');
	return $parentID === $fileID;
}

// Get the name that should be displayed from the parent and file name
function getDisplayName($parent, $file) {
	$extPos = strrpos($parent, '.');
	if ($extPos !== false) {
		$dispName = substr($parent, 0, $extPos);
		$cvExtPos = strrpos($file, '.');
		$dispName .= substr($file, $cvExtPos);
	} else
		$dispName = $parent;

	return $dispName;
}

function getOldestVersion($cabinetName, $parentID, $db_object) {
	return getTableInfo($db_object, $cabinetName.'_files',
		array('id', 'v_major', 'v_minor'), 
		array('deleted' => 0, 'id' => (int) $parentID), 'queryOne', 
		array('v_major' => 'ASC', 'v_minor' => 'ASC'));
}

function deleteParent($cabinetName, $parentID, $db_object) {
	
	updateTableInfo($db_object,$cabinetName."_files",array('deleted' => 1), array('id'=>(int)$parentID));

	$newParentID = getOldestVersion($cabinetName, $parentID, $db_object);

   	$updateArr = array('parent_id'=>(int)$newParentID);
	$whereArr = array("parent_id"=>$parentID);
	updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
	return true;
}

function checkOut($cabinetName, $parentID, $db_object, $access, $username) {
	if ($access == 'rw')
		checkAndSetLock($cabinetName, $parentID, $db_object, $username);
}

// This checks in a file in the FILES array to the given cab and parentid
function checkIn($cabinetName, $parentID, $user, $db_object, $db_doc, $DEFS) {
	$fileArr = getCheckInDetails($cabinetName, $parentID, $db_object, $user->username, $_FILES['userfile']['name']);
	if (move_uploaded_file($_FILES['userfile']['tmp_name'], $fileArr['path'])) {
		allowWebWrite ($fileArr['path'], $DEFS);
		return checkInVersion($db_object, $fileArr, $cabinetName, $parentID, $user, $db_doc);
	} else
		return false;
}

// This is used by the checkIn function above to do most of the work
// This is also used by webservices to checking in new versions
function checkInVersion($db_object, $fileArr, $cabinetName, $parentID, $user, $db_doc) {
	global $DEFS;
	$fileStat = stat($fileArr['path']);
	$fileSize = $fileStat[7];
	if(check_enable('centera',$user->db_name)) {
		$fileArr['ca_hash'] = centput($fileArr['path'], $DEFS['CENT_HOST'],$user, $cabinetName);
	}
	$queryArr = $fileArr;
	unset ($queryArr['path']);
	//Timestamps must be null in postgresql, not ''.
	unset ($queryArr['date_to_delete']);
	//New version will not be redacted
	unset ($queryArr['redaction_id']);
	unset ($queryArr['redaction']);
	unset ($queryArr['ocr_context']);
	$queryArr['file_size'] = (int)$fileSize;
	//Add the file size to the quota
	$quotaExceeded = false;
	lockTables($db_doc, array('licenses'));
	if (checkQuota($db_doc, $fileSize, $user->db_name)) {
			$updateArr = array('quota_used'=>'quota_used+'.$fileSize);
			$whereArr = array('real_department'=> $user->db_name);
			updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
	} else {
		$quotaExceeded = true;
	}
	unlockTables($db_doc);
	if ($quotaExceeded) {
		return false;
	}
	$result = $db_object->extended->autoExecute($cabinetName."_files", $queryArr);
	dbErr($result);
	$fileID = getRecentID($cabinetName, $parentID, $db_object);
   	$updateArr = array('display'=>0, 'notes'=> NULL);
	$whereArr = array("parent_id"=>'='.(int)$parentID,'id'=>"<> $fileID");
	updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr,0,1);
	if (!empty($_POST['addednote'])) {
		addNote($fileArr['doc_id'], $fileArr['ordering'], $fileArr['subfolder'], $cabinetName, $user, $fileID, $_POST['addednote'], $db_object);
	}
   	$updateArr = array('who_locked'=>'NULL','date_locked'=>'NULL');
	$whereArr = array('id'=>(int)$parentID);
	updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr,1);

	return $fileArr['v_major'].".".$fileArr['v_minor'];
}

function getRecentID($cabinetName, $parentID, $db_object) {
	return getTableInfo($db_object, $cabinetName.'_files', array('id'),
		array('parent_id' => (int) $parentID, 'deleted' => 0), 'queryOne', 
		array('v_major' => 'DESC', 'v_minor' => 'DESC'), 1);
}

//Helper function which returns the filename from the ID.
function getParentName($cabinetName, $parentID, $db_object) {
	$parentFilename = getTableInfo($db_object, $cabinetName.'_files',
		array('parent_filename'), array('id' => (int) $parentID), 'queryOne');
	
	return $parentFilename;
}

function getParentID($cabinetName, $fileID, $db_object) {
	$parentID = getTableInfo($db_object, $cabinetName.'_files',
		array('parent_id'), array('id' => (int) $fileID, 'deleted' => 0), 'queryOne');
	if($parentID == 0) {
		makeVersioned($cabinetName, $fileID, $db_object);
		$parentID = $fileID;
	}
	return $parentID;
}

function getFileNotes($cabinetName, $fileID, $db_object) {
	$fileNotes = getTableInfo($db_object, $cabinetName.'_files',
		array('notes'), array('id' => (int) $fileID, 'deleted' => 0), 'queryOne');
	error_log("getFileNotes: ".fileNotes );
	return $fileNotes;
}

function changeVersion($parentID, $cabinetName, $fileID, $newVersion, $user, $db_object) {
	global $DEFS;
	$recentID = getRecentID($cabinetName, $parentID, $db_object);
	
//	$newVArray = split("\.", $newVersion);

	list($major,$minor) = explode('[.]',$newVersion);
		
	$oldVerInfo = getTableInfo($db_object, $cabinetName.'_files',
		array('v_major', 'v_minor'), array('id' => (int) $fileID), 'queryRow');
	$oldVer = $oldVerInfo['v_major'].'_'.$oldVerInfo['v_minor'];

   	$updateArr = array('v_major'=>(int)$major,'v_minor'=>(int)$minor);
	$whereArr = array('id'=>(int)$fileID,'deleted'=>0);
	updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);

	if ($fileID === $parentID) {
		$newParentID = getOldestVersion($cabinetName, $parentID, $db_object);
   		$updateArr = array('parent_id'=>(int)$newParentID);
		$whereArr = array('parent_id'=>(int)$parentID);
		updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
		$parentID = $newParentID;
	}
	$newRecentID = getRecentID($cabinetName, $parentID, $db_object);
	if ($recentID !== $newRecentID) {
   		$updateArr = array('display'=>0);
		$whereArr = array('parent_id'=>'='.(int)$parentID,'id'=>"<> $newRecentID");
		updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr,0,1);

   		$updateArr = array('display'=>1);
		$whereArr = array('id'=>(int)$newRecentID);
		updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
	}
	$row = getTableInfo ($db_object, $cabinetName.'_files', array (), array ('id' => (int) $fileID), 'queryRow');
	$pNameArray = explode('\.', $row['parent_filename']);
	$myExt = strtolower(strrchr($row['filename'], "."));
	$newName = $pNameArray[0].'-'.str_replace('.', '_', $newVersion);
	$newName .= $myExt;
	$location = getTableInfo($db_object,$cabinetName,array('location'),array('doc_id'=>(int)$row['doc_id']),'queryOne');
	dbErr($location);
	$path = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $location);
	if ($row['subfolder']) {
		$newName = $row['subfolder'].'/'.$newName;
		$subQ = $row['subfolder'];
	} else {
		$subQ = 'IS NULL'; 
	}
	$i = 0;
	$defNewName = $newName;
	$filesInDir = getTableInfo($db_object, $cabinetName.'_files',
		array('filename'), array('doc_id' => (int)$row['doc_id'],
		'subfolder' => $subQ, 'filename' => 'IS NOT NULL'), 'queryCol');
	
	while (in_array($path.'/'.$newName, $filesInDir)) {
		$newArray = explode('.', $defNewName);
		$ext = $newArray[count($newArray) - 1];
		$withoutExt = array_slice($newArray, 0, count($newArray) - 1);
		$newName = implode('.', $withoutExt)."-$i.$ext";
		$i ++;
	}

	if (strpos($row['filename'], "$pNameArray[0]-$oldVer") !== false) {
		if(check_enable('centera',$user->db_name)) {
			centget($DEFS['CENT_HOST'], $row['ca_hash'], $row['file_size'],$path.'/'.$row['subfolder'].'/'.$row['filename'],$user,$cabinetName);
		}
		rename($path.'/'.$row['subfolder'].'/'.$row['filename'],
			"$path/$newName");
   		$updateArr = array('filename'=>basename($newName));
		if(check_enable('centera',$user->db_name)) {
			//DELETE OLD FILE FROM CENTERA $row['ca_hash']
			$updateArr['ca_hash'] = centput($path.'/'.$newName, $DEFS['CENT_HOST'], $user, $cabinetName);
		}
		$whereArr = array('id'=>(int)$fileID);
		updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
	}

	return $oldVer;
}

function getNewestVersion($cabinet, $docID, $parentID, $db) {
	return getTableInfo($db, $cabinet.'_files',
		array('v_major', 'v_minor', 'id'),
		array(
			'doc_id'	=> (int) $docID,
			'parent_id' => (int) $parentID,
			'display' => 1,
			'deleted' => 0
		), 'queryRow');
}

function getFileVer($cabinet, $fileID, $db) {
	return getTableInfo($db, $cabinet.'_files',
		array('v_major', 'v_minor'),
		array(
			'id' => (int) $fileID
		), 'queryRow');
}

function numberOfVersions($cabinet, $parentID, $db_object) {
	return getTableInfo($db_object, $cabinet.'_files', array('COUNT(*)'),
		array('parent_id' => (int) $parentID, 'deleted' => 0), 'queryOne');
}

// This function gets information needed for a new check in of a version
function getCheckInDetails($cabinetName, $parentID, $db_object, $userName, $filename) {
	global $DEFS;

	$fileInfo = getTableInfo($db_object, $cabinetName.'_files', array(), array('id' => (int) $parentID), 'queryRow');

	$dateCreated =  date('Y-m-d G:i:s');
	$pNameArray = explode('\.', $fileInfo['parent_filename']);
	$myExt = strtolower(strrchr($filename, "."));
	if (!$myExt) {
		$myExt = $pNameArray[1];
	}
	$newestVersion = getNewestVersion($cabinetName, $fileInfo['doc_id'], $parentID, $db_object);
	$vMajor = $newestVersion['v_major'] + 1;	// New UI
	$vMinor = $newestVersion['v_minor'];// + 1;
	$newName = $pNameArray[0].'-'.$vMajor.'_'.$vMinor;
	$newName .= $myExt;
	$location = getTableInfo($db_object,$cabinetName,array('location'),array('doc_id'=>(int)$fileInfo['doc_id']),'queryOne');
	dbErr($location);
	$path = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $location);
	$tabName = '';
	if ($fileInfo['subfolder']) {
		$tabName = $fileInfo['subfolder'];
		$subQ = $fileInfo['subfolder'];
	} else {
		unset ($fileInfo['subfolder']);
		$subQ = 'IS NULL'; 
	}
	$defNewName = $newName;
	$i = 0;
	$filesInDir = getTableInfo($db_object, $cabinetName.'_files',
		array('filename'), array('doc_id' => (int)$fileInfo['doc_id'],
		'subfolder' => $subQ, 'filename' => 'IS NOT NULL'), 'queryCol');
	
	while (in_array($path.'/'.$newName, $filesInDir)) {
		$newArray = explode('.', $defNewName);
		$ext = $newArray[count($newArray) - 1];
		$withoutExt = array_slice($newArray, 0, count($newArray) - 1);
		$newName = implode('.', $withoutExt)."-$i.$ext";
		$i ++;
	}
	$ordering = $fileInfo['ordering'];
	$docid = $fileInfo['doc_id'];
	$fileInfo['filename'] = $newName;
	$fileInfo['doc_id'] = (int)$docid;
	$fileInfo['path'] = $path.'/'.$tabName.'/'.$newName;
	$fileInfo['date_created'] = $dateCreated;
	$fileInfo['ordering'] = (int)$ordering;
	$fileInfo['v_major'] = (int)$vMajor;
	$fileInfo['v_minor'] = (int)$vMinor;
	$fileInfo['display'] = (int)1;
	$fileInfo['who_indexed'] = $userName;
	$fileInfo['parent_id'] = (int)$parentID;
	unset ($fileInfo['id']);
	unset ($fileInfo['OCR_context']);
	unset ($fileInfo['notes']);
	unset ($fileInfo['who_locked']);
	unset ($fileInfo['date_locked']);
	unset ($fileInfo['deleted']);
	unset ($fileInfo['ca_hash']);
	unset ($fileInfo['timestamp']);
	return $fileInfo;
}

function freezeFile($cabinetName, $parentID, $user, $db_object) {
   	$updateArr = array('who_locked'=>'9FROZEN');
	$whereArr = array('id'=>(int)$parentID);
	updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
}

function unFreezeFile($cabinetName, $parentID, $user, $db_object) {
   	$updateArr = array('who_locked'=>'NULL');
	$whereArr = array('id'=>(int)$parentID);
	updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr,1);
}

function fileIsFrozen($cabinetName, $parentID, $db_object) {
	$whoLocked = getTableInfo($db_object, $cabinetName.'_files',
		array('who_locked'), array('id' => (int) $parentID), 'queryOne');
	if (strcmp($whoLocked, '9FROZEN') == 0)
		return true;
	else
		return false;
}

function getVersionNotes($cabinetName, $fileID, $db_object) {
	$notes = getTableInfo($db_object, $cabinetName.'_files',
		array('notes'), array('id' => (int) $fileID), 'queryOne');

	return $notes;
}
/*
 * Used for Audit
 */
function getFolderName($cabinetName, $docID, $db_object) {
	$whereArr = array('doc_id'=>(int)$docID);
	$result = getTableInfo($db_object,$cabinetName,array(),$whereArr);
	$row = $result->fetchRow();
	$folderName = '';
	if($row) {
		foreach ($row as $value) {
			if($folderName)
				$folderName .= ":".$value;
			else 
				$folderName = $value;
		}
	}
	return $folderName;
}

function getParentAuditStr($cabinetName, $parentID, $db_object) {
	$parentInfo = getTableInfo($db_object, $cabinetName.'_files', array(), array('id' => (int) $parentID), 'queryRow');
	$docID = $parentInfo['doc_id'];
	$folderName = getFolderName($cabinetName, $docID, $db_object);
	if ($parentInfo['subfolder']) {
		$tabInfo = $parentInfo['subfolder'];
		$tabName = $parentInfo['subfolder'];
	} else {
		$tabInfo = "Main";
	}
	$extPos = strrpos($parentInfo['parent_filename'], '.');
	$myPName = substr($parentInfo['parent_filename'], 0, $extPos);
	$parentAudit = "Cabinet: $cabinetName, ";
	$parentAudit .= "Folder: $folderName, ";
	$parentAudit .= "Tab: $tabInfo, ";
	$parentAudit .= "Parent Name: $myPName";
	return $parentAudit;
}
// vi:ai:sw=4:ts=4:noet
?>
