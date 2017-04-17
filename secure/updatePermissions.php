<?php

include_once '../check_login.php';
include_once '../classuser.inc';

if( $logged_in==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
	//variables that may need to be translated
	$permissionsChanged    = $trans['Permissions Changed'];
	$hasBeenAdded          = $trans['Has Been Added'];    

	$db_object = $user->getDbObject();
	if (isset ($_GET['guest']) and $_GET['guest']) {
		$guest = $_GET['guest'];
	} else {
		$guest = '';
	}
	//This function is located in lib/utility.php
	$cabinet = getTableInfo( $db_object, 'departments', array(), array('deleted' => 0) );
	if (isset ($_GET['u'])) {
		$uid = $_GET['u'];
	} else {
		$uid = '';
	}
		
	if($uid == NULL) { //is it's a newly created user
		$usernameTest = $_GET['username'];
  		$accessObj = getTableInfo($db_object,'access',array(),array('username'=>$usernameTest));
		$accessList = $accessObj->fetchRow();
    } else {
  		$accessObj = getTableInfo($db_object,'access',array(),array('uid'=>(int)$uid));
        $accessList = $accessObj->fetchRow();
        $usernameTest = $accessList['username'];
    }
    
	$currAccess = unserialize(base64_decode($accessList['access']));
	$goodList = '';
	while($cabinetList = $cabinet->fetchRow()) {
        $cab = $cabinetList['real_name'];

		$access = $_POST[$cab];
		if($access == NULL) {
			$access = "none";
		}
        if (strcmp($currAccess[$cab], $access)!=0)
        	$goodList .= "{".$cab.": "."-".$currAccess[$cab].", +".$access."}";
        elseif ($uid == NULL)
        	$goodList .= "{".$cab.": ".$access."}";

		$currAccess[$cab] = $access;
	}
	$updateArr = array('access'=>base64_encode(serialize($currAccess)));
	$whereArr = array('username'=>$usernameTest);
	updateTableInfo($db_object,'access',$updateArr,$whereArr);
        
	if(isset($_POST['admin']) and $_POST['admin'])
		$checked = 1;
	else
		$checked = 0;

	$user->setAdmin( $checked, $user->db_name, $usernameTest );
	$user->audit("user permissions changed", "$usernameTest, Access: $goodList");
	
	if( isset($_GET['guest'] ) ) {
echo<<<ENERGIE
<script>
    document.onload = parent.mainFrame.window.location = "NewAccount.php?guest=$guest&mess=$usernameTest $hasBeenAdded";
</script>
ENERGIE;
	} else {
echo<<<ENERGIE
<script>
    document.onload = parent.mainFrame.window.location = "userAccess.php?mess=$permissionsChanged $usernameTest";
</script>
ENERGIE;
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
