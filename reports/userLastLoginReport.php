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
$select="select username,last_login from users order by username";
$results = $db_doc->queryAll($select);
//dbErr($results);
if(!file_exists("{$DEFS['TMP_DIR']}/userLastLogin")) {
    mkdir("{$DEFS['TMP_DIR']}/userLastLogin");
		allowWebWrite ("{$DEFS['TMP_DIR']}/userLastLogin", $DEFS, 0777);
} else {
    if (file_exists("{$DEFS['TMP_DIR']}/userLastLogin/userLastLogin.csv"))
        unlink("{$DEFS['TMP_DIR']}/userLastLogin/userLastLogin.csv");
}
$fp=fopen("{$DEFS['TMP_DIR']}/userLastLogin/userLastLogin.csv","w");
//echo "Username,Last Login\n";
fwrite($fp,"Username,Last Login\n");

foreach ($results as $result) {
//	echo $result['username'].",".$result['last_login']."\n";
	fwrite($fp,$result['username'].",".$result['last_login']."\n");
}
fclose($fp);
downloadFile("{$DEFS['TMP_DIR']}/userLastLogin","userLastLogin.csv", true, true);
?>