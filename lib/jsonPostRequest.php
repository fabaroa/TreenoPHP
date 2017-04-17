<?php
include_once '../check_login.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {
	$json = json_decode(file_get_contents('php://input'),true);

	if(isSet($json['include'])) {
		include_once '../'.$json['include'];
		unset($json['include']);
	}

	if(isSet($json['functionCall'])) {
		$func = $json['functionCall'];
		unset($json['functionCall']);
		
		if(empty($json)) {
			$func($user,$db_object,$db_doc);
		} else {
			$func($json,$user,$db_object,$db_doc);
		}
	}

	setSessionUser($user);
} else {
	logUserOut();
}
?>
