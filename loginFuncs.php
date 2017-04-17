<?php
//$Id: loginFuncs.php 14862 2012-06-27 19:08:24Z cz $
function loginFailed($getArr,$auditMessage,$newUsername=NULL) {
	$redirect = "energie/energie.php?".implode("&",$getArr);
	$tmpUser = new user();
	if($newUsername) {
		$tmpUser->username = $newUsername;
	} else {
		$tmpUser->username = 'admin';
	}
	$tmpUser->db_name = 'client_files';
	$remoteIPAddress = $_SERVER['REMOTE_ADDR'];
	$tmpUser->audit("login",$auditMessage.", from $remoteIPAddress");
	die(header("Location: $redirect"));
}

function systemFullCheck($username,&$db_doc,&$shared) {
	//create temp user
	$tmpUser = new User();
	$tmpUser->username = $username;
	$tmpUser->fillUser();

	//this will return an associative array with the keys as the departments 
	//and the values as an array of usernames logged into each department
	$queryArr = array();
	$query = getSelectQuery('user_polls',array('department','username','shared'),array(),array(),0,0,$queryArr);
	$usedLicenses = $db_doc->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, false, true);

	//total number of global licenses for system
	$globalLicenses = getTableInfo($db_doc,'global_licenses',array('max_licenses'),array(),'queryOne');
	
	//this will return an associative array with the keys as the departments 
	//and the values as an array that contains the number of departmental licenses 
	//and the limit of licenses that can be used for that department 
	$queryArr = array();
	$query = getSelectQuery('licenses',array('real_department','dept_licenses','max'),array(),array(),0,0,$queryArr);
	$availableLicenses = $db_doc->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, false);

	//this will return an array which contains all
	//departments that the user has access to
	$DO_user = DataObject::factory('users', $db_doc);
	$DO_user->get('username',$username);
	$depList = array($DO_user->defaultDept);//this ensures that I check the defaultDB first for licenses
	//$userDepts = array_keys($DO_user->departments);
	//$depList = array_merge($depList, array_values(array_diff($userDepts, $depList)));

	//this will return an associative array with the keys as the departments 
	//and the values as an array of the count of department licenses used 
	//and the count of shared licenses used
	$getArr = array('department','COUNT(id) AS total','SUM(shared) AS shared');
	$deptLicUsage = getTableInfo($db_doc,'user_polls',$getArr,array(),'getAssoc',array(),0,0,array('department'));

	$totalShared = getTableInfo($db_doc,'user_polls',array('SUM(shared)'),array(),'queryOne');
	if(!$totalShared) {
		$totalShared = 0;
	}

	//this will check to see if the user is already logged 
	//in from somewhere else, if so log that user out
	foreach($usedLicenses AS $k => $depArr) {
		foreach($depArr AS $info) {
			$u = $info['username'];
//			if($username!='admin' && $u == $username) {
			if($u == $username) {
				//set user status in user_security to 2
				$updateArr = array('status'=>2);
				$whereArr = array('username'=>$u);
				updateTableInfo($db_doc,'user_security',$updateArr,$whereArr);
				// delete old user from user_polls
				$whereArr = array('username'=>$u);
				deleteTableInfo($db_doc,'user_polls',$whereArr);
				if($info['shared']) {
					$shared = 'shared';
				} else {
					$shared = $k;
				}
				return false;//a license has been freed up for this user
			}
		}
	}

	foreach($depList AS $department) {
		//this will return the number of users logged into the department
		$depLicUsed = 0;
		$depGlobalLicUsed = 0;
		if(array_key_exists($department,$usedLicenses)) {
			foreach($usedLicenses[$department] AS $k => $valArr) {
				if(!$valArr['shared']) {
					$depLicUsed++;
				} else {
					$depGlobalLicUsed++;
				}
			}
		}
		
		//Gets the max column from from the license table
		if($availableLicenses[$department]['max'] == -1) {
			$maxDeptLicAvail = $globalLicenses;  
		} else {
			$maxDeptLicAvail = $availableLicenses[$department]['max'];
		}
		//Gets the dept_licenses from the license table
		$depLicAvail = $availableLicenses[$department]['dept_licenses'];

		if( $depLicUsed < $maxDeptLicAvail ) {
			$shared = $department;
			return false;
		}
		//Returns a department license if available		
/*		if( $depLicUsed < $depLicAvail ) {
			$shared = $department;
			return false;
		//if shared licenses do not exceed the max shared dep limit
		} elseif( ($depGlobalLicUsed < $maxDeptLicAvail) AND 
			//if total shared licenses do not exceed global limit
			($totalShared < $globalLicenses) ) {
			$shared = 'shared';
			return false;
		}
*/
	}

	//this will check to see if the superuser is logging on 
	//if true and the system is full kick off a user
	if($tmpUser->isSuperUser()) {
		foreach($depList AS $department) {
			if(array_key_exists($department,$usedLicenses)) {
				$userToDelete = $usedLicenses[$department][0]['username'];
				//set user status in user_security to 2
				$updateArr = array('status'=>2);
				$whereArr = array('username'=>$userToDelete);
				updateTableInfo($db_doc,'user_security',$updateArr,$whereArr);
				// delete old user from user_polls
				$whereArr = array('username'=>$userToDelete);
				deleteTableInfo($db_doc,'user_polls',$whereArr);
				foreach($usedLicenses[$department] AS $info) {
					if($info['username'] == $userToDelete) {
						if($info['shared']) {
							$shared = 'shared';
						} else {
							$shared = $department;
						}
						break;
					}
				}
				return false;//a license has been freed up for this user
			}
		}
	}
	if($tmpUser->isSuperUser()) {
		if(isset($depList[0])) {
			$shared = $depList[0];
		} else {
			$shared = 'client_files';
		}
		return false;
	}
	return true;
}

function unixTimeStampFromSQLDateTime($strSQLDateTime) {
	$arrDateTime = explode(" ", $strSQLDateTime);
	$arrDate = explode("-", $arrDateTime[0]);
	$arrTime = explode(":", $arrDateTime[1]);
	return mktime($arrTime[0],
				  $arrTime[1],
				  $arrTime[2],
				  $arrDate[1],
				  $arrDate[2],
				  $arrDate[0]);
}

?>
