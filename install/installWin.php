<?php
/*
if(!file_exists('c:/docutron')) {
	mkdir ("c:/docutron");
}

if(!file_exists('c:/docutron/temp')) {
	mkdir ("c:/docutron/temp");
}

if(!file_exists('c:/docutron/DMS.DEFS')) {
	copy ('conf/WINDMS.DEFS', 'c:/docutron/DMS.DEFS');
}
*/
require_once '../db/db_common.php';
require_once '../db/db_engine.php';
require_once '../install/installDB.php';
installDB(false, $DEFS);
$db = getDbObject ('docutron');
importLanguage ($db);
loadHelp ($db);
$db->disconnect ();
?>
