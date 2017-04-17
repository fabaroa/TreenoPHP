<?php

chdir ('..');
include_once '../db/db_common.php';

$dbType = getDbType ();
$db_doc = getDbObject ('docutron');

if ($dbType == 'mssql') {
	dropConstraints ($db_doc, 'ldap', 'connect_string');
	$query = 'ALTER TABLE ldap ALTER COLUMN connect_string VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
	dropConstraints ($db_doc, 'ldap', 'query_user');
	$query = 'ALTER TABLE ldap ALTER COLUMN query_user VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
} else {
	$query = 'ALTER TABLE ldap CHANGE connect_string connect_string VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
	$query = 'ALTER TABLE ldap CHANGE query_user query_user VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
}

?>
