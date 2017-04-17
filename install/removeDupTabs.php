<?php
//This script was created for Sanborn, Head and Associates because there are
//duplicate tab names per folder in some cabinets. This needs to be run after
//the 3.5 upgrade.

require_once '../db/db_common.php';
require_once '../lib/utility.php';

$remDup = new removeDupTabs;

$allDBs = $remDup->getDepartments();

foreach($allDBs as $dbName) {
	$db_dept = getDbObject($dbName);

	$remDup->setDepartment($db_dept);
	$allCabs = $remDup->getCabinets();
	foreach($allCabs as $cabinet) {
		$docIDs = $remDup->getFolders($cabinet);
		foreach($docIDs as $docID) {
			$remDup->removeDuplicateTabs($cabinet, $docID);
		}
	}

	$db_dept->disconnect();
}

$remDup->close();

class removeDupTabs {
	var $db;
	var $db_dept;
	
	function removeDupTabs() {
		$this->db = getDbObject('docutron');
	}
	
	function close() {
		$this->db->disconnect();
	}
	
	function getDepartments() {
		$allDBs = getTableInfo($this->db, 'licenses', array('real_department'), array(), 'queryCol');
		return $allDBs;
	}
	
	function setDepartment($db_dept) {
		$this->db_dept = $db_dept;
	}
	
	function getCabinets() {
		$allCabs = getTableInfo($this->db_dept, 'departments', array('real_name'), array(), 'queryCol');
		return $allCabs;
	}
	
	function getFolders($cabinet) {
		$docIDs = getTableInfo($this->db_dept, $cabinet, array('doc_id'), array(), 'queryCol');
		return $docIDs;
	}
	
	function removeDuplicateTabs($cabinet, $docID) {
		$tabArr = getTableInfo($this->db_dept, $cabinet.'_files', array('id', 'subfolder'), array('doc_id' => (int) $docID, 'filename' => 'IS NULL'), 'getAssoc');
		$idsToRemove = array();
		$existingTabs = array();
		foreach($tabArr as $fileID => $tabName) {
			if(in_array($tabName, $existingTabs)) {
				$idsToRemove[] = $fileID;
			} else {
				$existingTabs[] = $tabName;
			}
		}
		foreach($idsToRemove as $fileID) {
			deleteTableInfo($this->db_dept, $cabinet.'_files', array('id' => (int) $fileID));
		}
	}
}

?>