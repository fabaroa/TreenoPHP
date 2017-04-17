<?php

chdir ('..');
include_once '../db/db_common.php';
include_once '../lib/settingsList.inc.php';

$db_doc = getDbObject ('docutron');
$depList = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
$dbType = getDbType ();

foreach($depList AS $dep) {
	$db_dept = getDbObject($dep);
	$settingsList = new settingsList($db_doc, $dep, $db_dept);
	$settingsList->markDisabled(0, 'publishFolder');
	$settingsList->markDisabled(0, 'publishDocument');
	$settingsList->commitChanges();
	if ($dbType == 'mssql') {
		dropConstraints ($db_dept, 'odbc_mapping', 'odbc_trans');
		$query = 'ALTER TABLE odbc_mapping ALTER COLUMN odbc_trans VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
	} else {
		$query = 'ALTER TABLE odbc_mapping CHANGE odbc_trans odbc_trans VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
	}
}
?>
