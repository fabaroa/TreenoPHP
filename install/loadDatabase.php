<?php
chdir(dirname(__FILE__));

require_once '../db/db_common.php';
require_once '../db/db_engine.php';
require_once '../install/installDB.php';


installDB(false, $DEFS, false);
$db = getDbObject ('docutron');
importLanguage ($db);
loadHelp ($db);
$db->disconnect ();

?>
