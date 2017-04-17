<?php

include_once '../check_login.php';
include_once ( '../classuser.inc');

if( $logged_in==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
        $db_object = $user->getDbObject();

		$cabinet = $_GET['cabinet'];
		$cabinet = trim($cabinet);
		$cabinet = str_replace(" ", "_", $cabinet);

		$users = getTableInfo($db_object,'access');
		while($permissions = $users->fetchRow())
    	{
			$tmp = $_POST[$permissions['username']];
            $access = unserialize(base64_decode($permissions['access']));
            if(strcmp($permissions['username'], "admin") == 0) {
				$access[$cabinet] = 'rw';
			} else {
				$access[$cabinet] = $tmp;
			}
			$updateArr = array('access'=>base64_encode(serialize($access)));
			$whereArr = array('username'=>$username);
			updateTableInfo($db_object,'access',$updateArr,$whereArr);
		}
echo<<<ENERGIE
<script>
    document.onload = parent.mainFrame.window.location = "admin.php";
</script>
ENERGIE;

	setSessionUser($user);
} else {
	logUserOut();
}
?>
