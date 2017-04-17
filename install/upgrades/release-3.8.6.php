<?php

chdir('..');
include_once '../db/db_common.php';

$dbType = getDbType ();
$db_doc = getDbObject ('docutron');

if ($dbType == 'mssql') {
	dropConstraints ($db_doc, 'publish_search_list', 'field');
	$query = 'ALTER TABLE publish_search_list ALTER COLUMN field VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);

	dropConstraints ($db_doc, 'publish_search_list', 'term');
	$query = 'ALTER TABLE publish_search_list ALTER COLUMN term VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
} else {
	$query = 'ALTER TABLE publish_search_list CHANGE field field VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);

	$query = 'ALTER TABLE publish_search_list CHANGE term term VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
}
