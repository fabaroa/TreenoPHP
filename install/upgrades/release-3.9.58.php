<?php
include_once '../../db/db_common.php';
include_once '../../db/db_engine.php';
include_once '../../lib/utility.php';

$db_doc = getDbObject('docutron');
$sArr = array('real_department');
$wArr = array();
$depList = getTableInfo($db_doc,'licenses',$sArr,$wArr,'queryCol');
foreach($depList AS $dep) {
	$db_dept = getDbObject($dep);
	$tableDef = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"cabinet VARCHAR(255) NOT NULL DEFAULT ''",
		"index1 VARCHAR(255) NOT NULL DEFAULT ''",
		"search VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$db_dept->query("CREATE TABLE cabinet_filters(".implode(', ', $tableDef).')');
}
?>
