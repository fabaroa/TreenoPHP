<?php

include_once '../check_login.php';
include_once '../classuser.inc';

if( $logged_in==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
  $cabSuccess     = $trans['Cabinet successfully created'];

  $db_object = $user->getDbObject();
  $cab = $_GET['cab'];	
  $mess = $_GET['default'];
  $userList = getTableInfo($db_object,'access');
  while($user1 = $userList->fetchRow()) {
	$uname = $user1['username'];
    if($user->greaterThanUser($uname) && $user->username!=$uname) { 
		$accessArr = unserialize(base64_decode($user1['access']));
		$access = $_POST[$uname];
		if( array_key_exists( $cab, $accessArr ) ) {
			if($access !== $accessArr[$cab]) {
				$user->audit("$cab cabinet permissions changed", "$uname, +$access");
			}
		}
		$accessArr[$cab] = $access;
		$updateArr = array('access'=>base64_encode(serialize($accessArr)));
		$whereArr = array('username'=>$uname);
		updateTableInfo($db_object,'access',$updateArr,$whereArr);
    }
  }
  if($mess) {
echo<<<ENERGIE
<script>  
    document.onload = parent.mainFrame.window.location = "cabinetAccess.php?default=$mess&mess=Cabinet Permission Successfully Changed";
</script>
ENERGIE;
  } else {
echo<<<ENERGIE
<script> 
    parent.mainFrame.window.location = "newCabinet.php?message=$cabSuccess";
</script>
ENERGIE;
  }
	setSessionUser($user);
} else {
	logUserOut();
}
?>
