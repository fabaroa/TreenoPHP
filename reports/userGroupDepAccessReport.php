<?php
/*-----------------------------------------
 * userAccess.php
 * This page is accessed by:
 *  -choosing the "Change Permissions" item
 *  on the "User Functions" menu in settings
 *  -upon creating a new user (after giving
 *  a name and password, you are sent here)
 *---------------------------------------*/
include_once "../lib/mime.php";
include_once '../classuser.inc';
include_once '../groups/groups.php';
require_once '../check_login.php';

$db_doc = getDbObject( 'docutron' );
$select="select username,db_name,arb_department from users,db_list,licenses where db_list_id=list_id and db_name=real_department order by username,arb_department";
$results = $db_doc->queryAll($select);
//dbErr($results);
if(!file_exists("{$DEFS['TMP_DIR']}/userGroupDepAccess")) {
    mkdir("{$DEFS['TMP_DIR']}/userGroupDepAccess");
		allowWebWrite ("{$DEFS['TMP_DIR']}/userGroupDepAccess", $DEFS, 0777);
} else {
    if (file_exists("{$DEFS['TMP_DIR']}/userGroupDepAccess/userGroupDepAccess.csv"))
        unlink("{$DEFS['TMP_DIR']}/userGroupDepAccess/userGroupDepAccess.csv");
}
$fp=fopen("{$DEFS['TMP_DIR']}/userGroupDepAccess/userGroupDepAccess.csv","w");
//echo "Username,Group,Department\n";
fwrite($fp,"Username,Group,Department\n");

foreach ($results as $result) {
//	print_r($result);	
	$db_dept = getDbObject( $result['db_name'] );
	$query = "SELECT arb_groupname FROM groups,users_in_group,access WHERE access.username='".$result['username']."' AND access.uid=users_in_group.uid AND groups.id=users_in_group.group_id ORDER BY arb_groupname ASC";
//	echo $query."\n";
	$rows = $db_dept->queryAll($query);
	foreach ($rows as $row) {
//		echo $result['username'].",".$row['arb_groupname'].",".$result['arb_department']."\n";
		fwrite($fp,$result['username'].",".$row['arb_groupname'].",".$result['arb_department']."\n");
	}
	$db_dept->disconnect();
}
fclose($fp);
downloadFile("{$DEFS['TMP_DIR']}/userGroupDepAccess","userGroupDepAccess.csv", true, true);
?>