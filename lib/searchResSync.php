<?php
require_once '../check_login.php';
require_once '../lib/cabinets.php';
require_once '../settings/settings.php';
require_once '../lib/xmlObj.php';

if ($logged_in and $user->username) {
	switch ($_GET['func']) {
		case 'createSearchResFolder':
			createSearchResFolder($_GET['cabinet'], $user, $db_doc,
				$db_object);
			break;
		case 'getUnSyncedFolders':
			getUnSyncedFolders($_GET['cabinet'], $_GET['searchVal'],
				$user, $db_doc, $db_object);
			break;
		case 'syncFolders':
			syncFolders($user, $db_object, $db_doc, $_GET['cabinet']); 
			break;
	}
}


function createSearchResFolder ($cabinet, $user, $db_doc, $db_dept) {
	$tmpArr = $_SESSION['searchResArray'];
	$whereArr = array ();
	foreach ($tmpArr as $k => $v) {
		if ($v) {
			if ($v{0} == '"' && $v{strlen($v) - 1} == '"') {
				$whereArr[$k] = substr ($v, 1, strlen($v) - 2);
			} else {
				$whereArr[$k] = $v;
			}
		}
	}
	$doc_id = searchAndCreateFolder ($user, $cabinet, $db_dept, $db_doc, $whereArr);
}

function getUnSyncedFolders ($cabinet, $searchVal, $user, $db_doc, $db_dept) {
	$gblStt = new Gblstt($user->db_name,$db_doc);

	$resultSets = array ();
	$cabInfos = array ();
	$isSyncCab = false;
	if ($cabinet == $gblStt->get('sync_cabinet')) {
		$isSyncCab = true;
		foreach ($user->cabArr as $realCab => $arbCab) {
			$cabInfos[$realCab] = getCabinetInfo($db_dept, $realCab);
			if (in_array($gblStt->get('sync_field'), $cabInfos[$realCab])) {
				$rows = searchSyncField($db_dept, 
					$realCab, $gblStt->get('sync_field'), $searchVal,
					$cabInfos[$realCab]);
				if ($rows) {
					$resultSets[$realCab] = $rows;
				}
			}
		}
	} else {
		$isSyncCab = false;
		$cabInfos[$cabinet] = getCabinetInfo($db_dept, $cabinet);
		if (in_array($gblStt->get('sync_field'), $cabInfos[$cabinet])) {
			$rows = searchSyncField($db_dept, $cabinet, $gblStt->get('sync_field'),
				$searchVal, $cabInfos[$cabinet]);
			if ($rows) {
				$resultSets[$cabinet] = $rows;
			}
		}
	}

	$syncCab = 0;
	if ($isSyncCab) {
		$syncCab = 1;
	}

	$xmlObj = new xml('searchResSync');
	$xmlObj->createKeyAndValue('sync_cabinet',$syncCab);
	foreach ($resultSets as $myCab => $rows) {
		$parentEl = $xmlObj->createKeyAndValue('result_set');
		$xmlObj->createKeyAndValue('cabinet',$myCab,array(),$parentEl);
		$xmlObj->createKeyAndValue('display',$user->cabArr[$myCab],array(),$parentEl);
		$indParent = $xmlObj->createKeyAndValue('indices',NULL,array(),$parentEl);
		foreach ($cabInfos[$myCab] as $myIndex) {
			$xmlObj->createKeyAndValue('index',$myIndex,array(),$indParent);
		}
		foreach ($rows as $myRow) {
			$attArr = array('doc_id'=>$myRow['doc_id']);
			$folderParent = $xmlObj->createKeyAndValue('folder',NULL,$attArr,$parentEl);
			foreach ($cabInfos[$myCab] as $myIndex) {
				$xmlObj->createKeyAndValue($myIndex,$myRow[$myIndex],array(),$folderParent);
			}
		}
	}
	$xmlObj->setHeader();
}

function syncFolders ($user, $db_dept, $db_doc, $cabinet) {
	$gblStt = new Gblstt($user->db_name,$db_doc);

	$searchArr = array ();
	$tmpArr = $_SESSION['searchResArray'];
	foreach ($tmpArr as $k => $v) {
		if ($v) {
			if ($v{0} == '"' && $v{strlen($v) - 1} == '"') {
				$searchArr[$k] = substr ($v, 1, strlen($v) - 2);
			} else {
				$searchArr[$k] = $v;
			}
		}
	}
	$xmlStr = file_get_contents ('php://input');
	$xmlDoc = new DOMDocument ();
	$xmlDoc->loadXML($xmlStr);
	if ($gblStt->get('sync_cabinet') == $cabinet) {
		$isSync = true;
	} else {
		$isSync = false;
	}
	$updateSets = $xmlDoc->getElementsByTagName('update_set');
	for ($i = 0; $i < $updateSets->length; $i++) {
		$mySet = $updateSets->item($i);
		$myEl = $mySet->getElementsByTagName('cabinet');
		$myCab = $myEl->item(0);
		$myCab = $myCab->nodeValue;
		$updateFolders = $mySet->getElementsByTagName('doc_id');
		$myFolder = $updateFolders->item(0);
		$docID = $myFolder->nodeValue;
		$updateArr = array ();
		if ($isSync) {
			$updateArr[$gblStt->get('sync_field')] = 
				$searchArr[$gblStt->get('sync_field')];
		} else {
			$updateArr = searchSpecialAutoComplete($user,
				$myCab, $db_dept, $db_doc, $searchArr);
			unset ($updateArr[$gblStt->get('sync_field')]);
		}
		updateTableInfo($db_dept, $myCab, $updateArr,
			array ('doc_id' => $docID));
	}
}

function searchSyncField ($db_dept, $cabinet, $searchField, $searchVal, $indices) {
	$selArr = $indices;
	$selArr[] = 'doc_id';
	if($searchVal{0} == '"' && $searchVal{strlen($searchVal)-1} == '"') {
		$searchVal = substr($searchVal,1,strlen($searchVal)-2);
	}
	$whereArr = array ($searchField => $searchVal, 'deleted' => 0);
	return getTableInfo ($db_dept, $cabinet, $selArr, $whereArr, 'queryAll');
}

?>
