<?php
require_once '../check_login.php';
require_once '../lib/utility.php';
require_once '../lib/security.php';
if($logged_in and $user->username) {
	$userName = $user->username;
	$dbName = $user->db_name;
	$hashCode = $user->hash_id;
	if(!checkUser($hashCode, $db_doc)) {
		echo "Log Out!";
	} else {
		$inDB = getTableInfo($db_doc,'user_polls',array(),array('username'=>$userName), 'queryRow');
		if(!$inDB) {
					
			echo "Must be logged out!";
		} else {
			$queryArr = array(
				'ptime'		=> time(),
				'strikes'	=> 0
			);
			$res = $db_doc->extended->autoExecute('user_polls',
									$queryArr,
									MDB2_AUTOQUERY_UPDATE,
									"username = '$userName'");
		}
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
