<?php
include_once '../../db/db_common.php';

$dbType = getDbType ();

if ($dbType == 'mssql') {
	$db_doc = getDbObject ('docutron');
	$dbs = getTableInfo ($db_doc, 'licenses', array ('real_department'),
		array (), 'queryCol');
	foreach ($dbs as $dbName) {
		$db_dept = getDbObject ($dbName);
		$query = 'ALTER TABLE inbox_delegation_history ' .
			'ALTER COLUMN comments VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr ($res);
	}
}



?>
