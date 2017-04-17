<?php
require_once '../db/db_common.php';
	global $DEFS;
	echo $DEFS['DB_HOST']."\n";
	echo $DEFS['DB_TYPE']."\n";
	echo $DEFS['DB_USER']."\n";
	echo $DEFS['DB_PASS']."\n";
	$db_doc = getDbObject ('docutron');
	//$db_dept = getDbObject ($dept);
	
?>