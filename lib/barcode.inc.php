<?php
/**
 * Barcode utility class
 * 
 * @package DMS
 */
/**
 * getTableInfo(), getWFDefsInfo()
 */
require_once '../lib/utility.php';

/**
 * @package Barcode
 */
class Barcode {
	
	/**
	 * @param object
	 * @param string
	 * @param string
	 * @param string
	 * @param int
	 * @param string
	 */
	function createBarcode($db, $db_doc, $userName, $dbName, $cabinet, $docID,
		$workflowName = '',$tab = null) {
			
		$departmentID = Barcode::getDepartmentID($dbName);
		$cabinetID = Barcode::getCabinetID($db, $cabinet);
		if($tab || $tab != "Main") {
			$tabID = Barcode::getSubfolderID($db,$cabinet,$docID,$tab);	
		}
		if($workflowName) {
			$userID = Barcode::getUserID($db_doc, $userName);
			$workflowInfo = getWFDefsInfo($db, $workflowName);
			$workflowID = $workflowInfo[1];
			$barcode = 'WF '.$departmentID.' '.$cabinetID.' '.$docID.' ';
			if($tab) {
				$barcode .= $tabID.' ';
			}
			$barcode .= $workflowID.' '.$userID;
		} else {
			$barcode = $departmentID.' '.$cabinetID.' '.$docID;
			if($tab) {
				$barcode .= ' '.$tabID;
			}
		}
		return $barcode."\n".$userName;
	}
	
	/**
	 * Get the cabinet name from the ID.
	 * 
	 * @param object PEAR::DB department object
	 * @param int $cabinetID ID of cabinet from departments table.
	 * @return string cabinet name
	 */
	function getRealCabinetName($db, $cabinetID) {
		return getTableInfo($db, 'departments', array('real_name'),
			array('departmentid' => $cabinetID), 'queryOne');
			
	}

	/**
	 * Get the ID from the cabinet name.
	 * 
	 * @param object PEAR::DB department object
	 * @param int $cabinet real name of cabinet.
	 * @return int cabinetID
	 */	
	function getCabinetID($db, $cabinet) {
		return (int) getTableInfo($db, 'departments', array('departmentid'),
			array('real_name' => $cabinet), 'queryOne');
					
	}
	
	/**
	 * Get the department name from the department id.
	 * 
	 * @param int $departmentID ID of department (from string parsing)
	 * @return string real department name
	 */
	 function getRealDepartmentName($departmentID) {
	 	$dept = 'client_files';
		if($departmentID > 0) {
			$dept .= $departmentID;
		}
		return $dept;	 	
	 }

	/**
	 * Get the department id from the department name.
	 * 
	 * @param string $department department name
	 * @return int ID of department
	 */
	 function getDepartmentID($department) {
	 	$match = array ();
		preg_match('/[0-9].*/', $department, $match);
		if($match) {
			$dbID = (int) $match[0];
		} else {
			$dbID = 0;
		}
		
		return $dbID;
	 }
	 
	/**
	 * Get the user name from the user ID.
	 * 
	 * @param int $userID ID of user from users table.
	 * @return string user name 
	 */
	function getUserName($db, $userID) {
		return getTableInfo($db, 'users', array('username'),
			array('id' => $userID), 'queryOne');
			
	}
	
	/**
	 * Get the user name from the user ID.
	 * 
	 * @param string $userName user name
	 * @return int ID of user from users table.
	 */
	function getUserID($db, $userName) {
		return (int) getTableInfo($db, 'users', array('id'),
			array('username' => $userName), 'queryOne');

	}

	function getSubfolderID($db,$cab,$doc_id,$tab) {
		return (int) getTableInfo($db,$cab."_files",array('id'),
			array('doc_id' => (int)$doc_id, 'subfolder' => $tab, 'filename' => 'IS NULL'),'queryOne');
	}
}

?>
