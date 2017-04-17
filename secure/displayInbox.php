<?php
include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../lib/mime.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {
	if (isset ($_GET['name'])) {
		$filename = $_GET['name'];
	} else {
		$filename = '';
	}
	if (isset($_GET['foldname'])) {
		$folder = $_GET['foldname'];
	} else {
		$folder = '';
	}
	if (isset ($_GET['user'])) {
		$personal = $_GET['user'];
	} else {
		$personal = '';
	}
	if (isset ($_GET['delegateID'])) {
		$delegateID = $_GET['delegateID'];
	} else {
		$delegateID = '';
	}

	$filename = urldecode($filename);
	if (isset ($_GET['username'])) {
		$uName = $_GET['username'];
	} else {
		$uName = '';
	}
	if ($delegateID) {
		$db_object = $user->getDbObject();
		$path = $user->getRootPath()."/personalInbox/";
		$sArr = array('delegate_owner','folder','filename');
		$wArr = array('inbox_delegation_file_list.id ='.(int)$delegateID,
					'inbox_delegation_file_list.list_id=inbox_delegation.list_id');
		$tArr = array('inbox_delegation_file_list','inbox_delegation');
		$delegateInfo = getTableInfo($db_object,$tArr,$sArr,$wArr,'queryRow');
		$path .= $delegateInfo['delegate_owner']."/".$delegateInfo['folder'];
		$filename = $delegateInfo['filename'];
	} elseif($personal) { 
		if($folder) { 
			$path = $user->getRootPath()."/personalInbox/$uName/$folder";              
		} else {
			$path = $user->getRootPath()."/personalInbox/$uName";
		}
	} else {
		if($folder) {
			$path = $user->getRootPath()."/inbox/$folder";              
		} else {
			$path = $user->getRootPath()."/inbox";
		}
	}
	$user->audit("page from INBOX read", "$filename from $path read");
	downloadFile($path,$filename, false, false); 

	setSessionUser($user);
} else {
	logUserOut();
}
?>
