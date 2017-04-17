<?php
//Should be run once an hour.
chdir(dirname(__FILE__));
require_once '../db/db_common.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to start ".$DEFS['DOC_DIR']."/bots/deleteTempTable.php");
	die();
}

$deptArr = getTableInfo($db_doc, 'licenses', array ('real_department'),
	array (), 'queryCol');

$db_doc->disconnect ();

$sqlTime = date('Y-m-d G:i:s');
$time = time();

foreach ($deptArr as $dbName) {
	$db_object = getDbObject($dbName);
	$tempTables = getTableInfo($db_object, 'temp_tables', array (),
		array ("expire_time<'$sqlTime'"), 'queryAll');

	foreach ($tempTables as $results) {
		$tempTime = $results['expire_time'];
		if (getdbType() == 'db2') {
			$tempTime = substr($tempTime, 0, strrpos($tempTime, "."));
		}
		if (strtotime($tempTime) < $time) {
			$table = $results['table_name'];
			$query = 'DROP TABLE '.$table;
			$res = $db_object->query($query);
			$whereArr = array ('table_name' => $table);
			deleteTableInfo($db_object, 'temp_tables', $whereArr);
		}
	}
	$db_object->disconnect ();
}
?>
