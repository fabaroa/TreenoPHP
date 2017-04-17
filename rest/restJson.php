<?php
require_once '../db/db_common.php';
require_once '../lib/crypt.php';
require_once '../lib/utility.php';

//$db_doc = getDbObject ('docutron');

$args = json_decode(file_get_contents('php://input'), true);

$method = $args['method'];
$params = $args['args'];
$return = array ();

switch($method) {
	case 'login':
		$return = doLogin( $params['userName'], $params['md5Pass']);
		break;
	case 'getPersonalInboxesInfo':
		$return = getPersonalInboxesInfo( $params['passKey'], $params['department']);
		break;
	case 'getUserPermissions':
		$return = getUserPermissions( $params['passKey'], $params['department']);
		break;
	case 'getBarcodeInfo':
		$return = getBarcodeInfo( $params['passKey'], $params['barcode']);
		break;
}
echo json_encode($return);

function doLogin ($userName, $md5Pass) {
	$db_doc = getDbObject ('docutron');
	$retArr = array (
		'logged_in' => false,
		'message' => '',
	);
	$userName = strtolower ($userName);
	$usersInfo = getTableInfo($db_doc, 'users', array(), array('username'
				=> $userName));
	$retXML = '';
	if($row = $usersInfo->fetchRow()) {
		if ($row['ldap_id'] != 0) {
			$myPass = tdDecrypt ($md5Pass);
			if (checkLDAPPassword ($db_doc, $row['ldap_id'], $userName,
					$myPass)) {
				$key = $userName.','.(time() + 86400);
				$message = weakEncrypt($key);
				$retArr['logged_in'] = true;
				$retArr['message'] = $message;
			} else {
				$retArr['logged_in'] = false;
				$retArr['message'] = 'LOGINREQUIRED';
			}
		} else {
			if(strtoupper($row['password']) == strtoupper($md5Pass)) {
				$key = $userName.','.(time() + 86400);
				$message = weakEncrypt($key);
				$retArr['logged_in'] = true;
				$retArr['message'] = $message;
			} else {
				$retArr['logged_in'] = false;
				$retArr['message'] = 'LOGINREQUIRED';
			}
		}
	} else {
		$retArr['logged_in'] = false;
		$retArr['message'] = 'LOGINREQUIRED';
	}
	return $retArr;
}

function getPersonalInboxesInfo( $passKey, $department) {
	//error_log("getPersonalInboxesInfo()");
	list($retVal, $userName) = checkKey($passKey);
	if (!$retVal) {
		return false;
	}
	$db_dept = getDbObject($department);
	$allDeptUsers = getTableInfo($db_dept, 'access', array('username'), array(),
		'queryCol');
	$db_doc = getDbObject ('docutron');
	$allUsers = getTableInfo($db_doc, 'users', array('id', 'username'), 
		array(), 'queryAll');
	$retArr = array();
	$deptID = str_replace('client_files', '', $department);
	if(!$deptID) {
		$deptID = 0;
	}
	foreach($allUsers as $myUser) {
		if(in_array($myUser['username'], $allDeptUsers)) {
			$retArr[$myUser['username']] = "$deptID 0 {$myUser['id']}";
		}
	}
	$db_dept->disconnect();
	return $retArr;
}

function getBarcodeInfo($passKey, $numBarcode) {
	//error_log("getBarcodeInfo() numBarcode: ".$numBarcode);
	list($retVal, $userName) = checkKey($passKey);
	$retArr = array();
	$retArr["status"] = "unknown";
	if (!$retVal) {
		error_log("Fatal: getBarcodeInfo() invalid passKey: ".$passKey);
		$retArr["status"] = "Fatal: Invalid passKey";
		return $retArr;//false;
	}
	$barcodeID = (int) $numBarcode;	
	if($barcodeID == 0) {
		error_log("Error: getBarcodeInfo() invalid barcode: ".$numBarcode);
		$retArr["status"] = "Error: Invalid barcod";
		return $retArr;//false;
	}
	
	$db_doc = getDbObject ('docutron');
	$select = "select count(*) FROM db_list,users where username='".$userName."' and list_id=users.db_list_id and db_name='client_files156'";
	$result = $db_doc->queryOne($select);
	//try ats
	if ($result>0) $barcodeInfo = getTableInfo($db_doc, 'barcode_reconciliation_ats', array(), array('id' => $barcodeID), 'queryRow');
  if(!isset($barcodeInfo) || !$barcodeInfo) $barcodeInfo = getTableInfo($db_doc, 'barcode_reconciliation', array(), array('id' => $barcodeID), 'queryRow');

	if($barcodeInfo) {
		//error_log("getBarcodeInfo() from 'barcode_reconciliation'");
		$retArr = GetResultsFromBarcodeInfo($barcodeInfo);
	} else {
		if ($result>0) $department = getTableInfo($db_doc, 'barcode_lookup_ats',array ('department'), array ('id' => $barcodeID), 'queryOne');
  	if(!isset($department) || !$department) $department = getTableInfo($db_doc, 'barcode_lookup',array ('department'), array ('id' => $barcodeID), 'queryOne');
		if($department) {
			//error_log("getBarcodeInfo() from 'barcode_history'");
			$db_dept = getDbObject ($department);

			$barcodeInfo = getTableInfo($db_dept , 'barcode_history',
				array (), array ('barcode_rec_id' => $barcodeID), 'queryRow');
			if($barcodeInfo) {
				$retArr = GetResultsFromBarcodeInfo($barcodeInfo);
			}
			else {
					$retArr["status"] = "Error: cannot find barcode from 'barcode_history'";
					error_log("Error: getBarcodeInfo() cannot find barcode(".$barcodeID.") from 'barcode_lookup'");
			}
			$db_dept->disconnect();
		}else {
			$retArr["status"] = "Error: cannot find barcode";
			error_log("Error: getBarcodeInfo() cannot find barcode(".$barcodeID.") from 'barcode_lookup'");
		}
	}
	
	//error_log("getBarcodeInfo() retArr: ".print_r($retArr, true));
	$db_doc->disconnect();
	return $retArr;
}

function GetResultsFromBarcodeInfo($barcodeInfo)
{
	//error_log("GetResultsFromBarcodeInfo() barcodeInfo: ".print_r($barcodeInfo, true));
	$retArr = array();
	$retArr["status"] = "Success";
	$retArr['barcode_info'] = $barcodeInfo['barcode_info'];
	$retArr['username'] = $barcodeInfo['username'];
	$retArr['cab'] = $barcodeInfo['cab'];
	return $retArr;
}


function getUserPermissions($passKey, $department) {
	//error_log("getUserPermissions() department: ".$department);
	$retArr = array();
	$retArr["status"] = "unknown";
	list($retVal, $uname) = checkKey($passKey);
	if (!$retVal) {
		error_log("Error: getUserPermissions() invalid passKey: ".$passKey);
		$retArr["status"] = "Fatal: Invalid passKey";
		return $retArr;//false;
	}
	
	$db_dept = getDbObject($department);
	
	$cabinetInfo = getTableInfo($db_dept, 'departments', array(), array('deleted' => 0));
	$cabArr = array();
	while( $result = $cabinetInfo->fetchRow() ) {
		$cabArr[] = $result['real_name'];
	} 
	//error_log("getUserPermissions() cabArr: ".print_r($cabArr, true));
	if($cabArr)
	{
		if($uname == 'admin') {
			$retArr["all"]= "rw";
		}
		else {
			$rightsInfo = getTableInfo($db_dept,'access',array(),array('username'=>$uname));
			$access = $rightsInfo->fetchRow();
			$accessRights = unserialize(base64_decode($access['access']));

			foreach($accessRights as $cabinet => $rights) {
			$cab = trim($cabinet);
				if(in_array($cab, $cabArr ) ) {
					$retArr[$cab]= $rights;
				}
			}
			//error_log("getUserPermissions() query 'access' retArr: ".print_r($retArr, true));
						
			$groupAccessList = queryAllGroupAccess($db_dept, $uname);
			//error_log("getUserPermissions() groupAccessList: ".print_r($groupAccessList, true));
			foreach( $groupAccessList AS $groupInfo ) {
				$cabinet = $groupInfo['real_name'];			
				if(in_array($cabinet, $cabArr )){
					$gRights = $groupInfo['access'];					
					if(array_key_exists($cabinet, $retArr ) ) {	// overwrite
						if( $gRights == 'rw' || ($gRights == 'ro'  && $$retArr[$cabinet] != 'rw')) {
							$retArr[$cabinet]= $gRights;
						} 
						//error_log("getUserPermissions() groupAccessList: cabinet: ".$cabinet.", gRights: ".$gRights.", retArr[cabinet]: ".$retArr[$cabinet]);
					}
					else {// add
						//error_log("getUserPermissions() groupAccessList: not set cabinet: ".$cabinet);
						$retArr[$cabinet]= $gRights;
					}
				}
			}
		}
	}
	$retArr["status"] = "Success";
	$db_dept->disconnect();
	//error_log("getUserPermissions() retArr: ".print_r($retArr, true));
	return $retArr;
}

?>
