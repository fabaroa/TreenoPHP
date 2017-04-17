<?php
include_once '../publishing/publishSearch.php';
include_once '../check_login.php';
include_once 'xmlObj.php';
include_once '../settings/settings.php';
include_once '../lib/xmlParser.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {
	$xmlStr = file_get_contents('php://input');
	$entryArr = array ();
	$func = '';
	xmlGetFuncArgs ($xmlStr, $entryArr, $func);	
	$func($entryArr,$user, $db_doc,$db_object);

	setSessionUser($user);
} else {
	//logUserOut();
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue('LOGOUT',1);
	$xmlObj->setHeader();
}
?>
