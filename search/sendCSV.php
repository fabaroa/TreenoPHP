<?php
include_once "../check_login.php";
include_once "../lib/mime.php";
include_once "../lib/settings.php";

$username = $user->username;
$department = $user->db_name;

downloadFile("{$DEFS['DATA_DIR']}/$department/$username"."_backup","searchResults.xls", true, true);
setSessionUser( $user );
?>
