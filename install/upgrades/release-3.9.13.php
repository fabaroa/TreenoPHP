<?php
include_once '../../db/db_common.php';
include_once '../../db/db_engine.php';

$db_doc = getDbObject('docutron');
$tableDef = array(
	'id '.AUTOINC,
	'PRIMARY KEY (id)',
	"wf_id INT NOT NULL DEFAULT 0",
	"department VARCHAR(255) NOT NULL DEFAULT ''",
	"message ".TEXT16M." NULL"
);
$db_doc->query("CREATE TABLE pub_wf_messages(".implode(', ', $tableDef).')');

?>
