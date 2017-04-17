<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isAdmin()) {
	$cabArr = $_POST['check'];
	for($i=0; $i<sizeof($cabArr); $i++) {
		$db_object = $user->getDbObject();
		$cab = $cabArr[$i];
		lockTables($db_object, array($cab.'_indexing_table'));
		
		$whereArr = array('upForIndexing'=>0);
		deleteTableInfo($db_object,$cab."_indexing_table",$whereArr);

		unlockTables($db_object);
	}
echo<<<ENERGIE
<script>
	document.onload = top.mainFrame.window.location = "indexing.php";
</script>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
