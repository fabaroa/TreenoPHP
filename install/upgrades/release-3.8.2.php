<?php

chdir ('..');
include_once '../db/db_common.php';
include_once '../lib/settingsList.inc.php';

$dbType = getDbType ();
$db_doc = getDbObject ('docutron');
$depList = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');

if ($dbType == 'mssql') {
	dropConstraints ($db_doc, 'wf_todo', 'priority');
	$query = 'ALTER TABLE wf_todo ALTER COLUMN priority SMALLINT NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
	dropConstraints ($db_doc, 'wf_todo', 'notes');
	$query = "EXEC sp_rename 'wf_todo.notes', 'notes_old', 'COLUMN'";
	$res = $db_doc->query ($query);
	dbErr($res);
	$query = 'ALTER TABLE wf_todo ADD notes TEXT NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
	$query = 'UPDATE wf_todo SET notes = notes_old';
	$res = $db_doc->query ($query);
	dbErr($res);
	$query = 'ALTER TABLE wf_todo DROP COLUMN notes_old';
	$res = $db_doc->query ($query);
	dbErr($res);
	dropConstraints ($db_doc, 'wf_todo', 'date');
	$query = 'ALTER TABLE wf_todo ALTER COLUMN date DATETIME NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
	dropConstraints ($db_doc, 'barcode_reconciliation', 'barcode_field');
	$query = 'ALTER TABLE barcode_reconciliation ALTER COLUMN barcode_field VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);

	foreach($depList AS $department) {
		$db_dept = getDbObject($department);
		//Fixes the DB error where the user leaves one of the document indices blank
		dropConstraints ($db_dept, 'document_field_value_list', 'document_field_value');
		$query = 'ALTER TABLE document_field_value_list ALTER COLUMN document_field_value VARCHAR(255) NULL';
		$res = $db_dept->query($query);
		dbErr($res);

		dropConstraints ($db_dept, 'wf_history', 'action');
		$query = 'ALTER TABLE wf_history ' .
			'ALTER COLUMN action VARCHAR(50) NULL';
		$res = $db_dept->query ($query);
		dbErr ($res);
		dropConstraints ($db_dept, 'redactions', 'subfolder');
		$query = 'ALTER TABLE redactions ALTER COLUMN subfolder VARCHAR(100) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		dropConstraints ($db_dept, 'odbc_mapping', 'where_op');
		$query = 'ALTER TABLE odbc_mapping ALTER COLUMN where_op VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		dropConstraints ($db_dept, 'odbc_mapping', 'docutron_name');
		$query = 'ALTER TABLE odbc_mapping ALTER COLUMN docutron_name VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		dropConstraints ($db_dept, 'odbc_mapping', 'logical_op');
		$query = 'ALTER TABLE odbc_mapping ALTER COLUMN logical_op VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		dropConstraints ($db_dept, 'odbc_mapping', 'grouping');
		$query = 'ALTER TABLE odbc_mapping ALTER COLUMN grouping VARCHAR(10) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		dropConstraints ($db_dept, 'barcode_history', 'barcode_field');
		$query = 'ALTER TABLE barcode_history ALTER COLUMN barcode_field VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
	}
} else { //for mysql and mysqli
	$query = 'ALTER TABLE barcode_reconciliation CHANGE barcode_field barcode_field VARCHAR(255) NULL';
	$res = $db_doc->query ($query);
	dbErr($res);
	foreach($depList AS $department) {
		$db_dept = getDbObject($department);
		//Fixes the DB error where the user leaves one of the document indices blank
		$query = 'ALTER TABLE document_field_value_list CHANGE document_field_value document_field_value ' .
			'VARCHAR(255) NULL';
		$res = $db_dept->query($query);
		dbErr($res);

		$query = 'ALTER TABLE wf_history ' .
			'CHANGE action action VARCHAR(50) NULL';
		$res = $db_dept->query ($query);
		dbErr ($res);
		$query = 'ALTER TABLE odbc_mapping CHANGE where_op where_op VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		$query = 'ALTER TABLE odbc_mapping CHANGE docutron_name docutron_name VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		$query = 'ALTER TABLE odbc_mapping CHANGE logical_op logical_op VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		$query = 'ALTER TABLE odbc_mapping CHANGE grouping grouping VARCHAR(10) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
		$query = 'ALTER TABLE barcode_history CHANGE barcode_field barcode_field VARCHAR(255) NULL';
		$res = $db_dept->query ($query);
		dbErr($res);
	}
}

$queryArr = array (
	'id '.AUTOINC,
	'PRIMARY KEY (id)',
	'name VARCHAR(255) NOT NULL',
	'connect_string VARCHAR(255) NOT NULL',
	'host VARCHAR(255) NOT NULL',
	'query_user VARCHAR(255) NOT NULL',
	'query_password VARCHAR(255) NULL',
	'active_directory SMALLINT DEFAULT 0'
);

$query = 'CREATE TABLE ldap (' . implode (', ', $queryArr) . ')';
$db_doc->query ($query);

$indices = getTableColumnInfo ($db_doc, 'users');

if (!in_array ('ldap_id', $indices)) {
	$query = 'ALTER TABLE users ADD ldap_id INT NULL DEFAULT 0';
	$res = $db_doc->query ($query);
	dbErr ($res);
}

foreach($depList AS $dep) {
	$db_dept = getDbObject($dep);
	$settingsList = new settingsList($db_doc, $dep, $db_dept);
	$settingsList->markEnabled(0, 'showDocumentCreation');
	$settingsList->commitChanges();
}

?>
