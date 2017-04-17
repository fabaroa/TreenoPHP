<?php

chdir ('..');
include_once '../db/db_common.php';
$db_doc = getDbObject ('docutron');

$queryArr = array (
	'id '.AUTOINC,
	'PRIMARY KEY (id)',
	"list_id INT NOT NULL DEFAULT 0",
	"name VARCHAR(255) NOT NULL DEFAULT ''",
	"value VARCHAR(255) NOT NULL DEFAULT ''",
	'exact SMALLINT DEFAULT 0',
);

$query = 'CREATE TABLE wf_filter_list (' . implode (', ', $queryArr) . ')';
$db_doc->query ($query);

$query = 'CREATE INDEX wflid_idx ON wf_filter_list(list_id)';
$db_doc->query ($query);
?>
