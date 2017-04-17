<?php

chdir ('..');
require_once '../db/db_common.php';
require_once '../lib/settingsList.inc.php';

$db_doc = getDbObject ('docutron');

$departments = getTableInfo ($db_doc, 'licenses', array ('real_department'),
	array (), 'queryCol');

foreach ($departments as $myDept) {
	$db_dept = getDbObject ($myDept);
	$settList = new settingsList ($db_doc, $myDept, $db_dept);
	$settList->markEnabled (0, 'addDocument');
	$settList->markEnabled (0, 'editDocument');
}

?>
