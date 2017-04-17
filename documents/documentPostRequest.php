<?php
include_once '../check_login.php';
include_once 'documents.php';
include_once 'documentActions.php';
include_once '../lib/xmlParser.php';
include_once '../lib/xmlObj.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {

	$xmlStr = file_get_contents('php://input');
	$func = '';
	$entriesArr = array ();
	xmlGetFuncArgs ($xmlStr, $entriesArr, $func);
	if ($func) {
		$func ($entriesArr, $user, $db_doc, $db_object);
	}
	setSessionUser($user);
} else {
	//logUserOut();
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue('LOGOUT',1);
	$xmlObj->setHeader();
}
?>
