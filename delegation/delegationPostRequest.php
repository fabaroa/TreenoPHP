<?php
include_once '../check_login.php';
include_once 'delegation.php';
include_once '../lib/xmlParser.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {
	$xmlStr = file_get_contents('php://input');
	$entriesArr = array ();
	$func = '';
	xmlGetFuncArgs ($xmlStr, $entriesArr, $func);
	$func($entriesArr,$user);

	setSessionUser($user);
} else {
	logUserOut();
}
?>
