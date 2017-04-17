<?php
include_once '../db/db_common.php';
include_once '../db/db_engine.php';
include_once '../lib/tables.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';

if(isset($argv[1])) {
	$department = $argv[1];
} else {
	die("must pass a department\n");
}

$db_dept = getDbObject($department);
updateWorkflow($db_dept);
addDocumentSettingsTables($db_dept);
addDocumentsTable($db_dept);
addDocumentDeletedColumn($db_dept);
addInboxDelegationTables($db_dept);
addFullTextIndex($db_dept);
adjustQuota($department);
$db_dept->disconnect ();

function updateWorkflow($db_dept) {
	updateTableInfo($db_dept,'wf_documents',array('file_id' => -1), array('file_id' => 0));
}

function addDocumentSettingsTables($db_dept) {
	$indexArr = array ();
	$queryArr = array ();
	$allQueries = array();
	
	$queryArr['document_settings'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT DEFAULT 0",
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"k VARCHAR(255) NOT NULL DEFAULT ''",
		"value VARCHAR(255) NOT NULL DEFAULT ''"
	);

	$queryArr['document_settings_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT DEFAULT 0",
		"document_id INT DEFAULT 0"
	);
	$indexArr[] = 'dsl_list_id_idx ON document_settings_list(list_id)';

	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}

	foreach($allQueries as $query) {
		$res = $db_dept->query($query);
		dbErr($res);
	}
}

function addDocumentsTable($db_dept) {
	$indexArr = array ();
	$queryArr = array ();
	$allQueries = array();
	
	$queryArr['document_type_defs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_type_name VARCHAR(255) NOT NULL DEFAULT ''",
		"document_table_name VARCHAR(255) NOT NULL DEFAULT ''",
		'enable INT default 0',
		'permissions_id INT default 0',
	);
	$indexArr[] = 'dtd_document_table_name_idx ON document_type_defs(document_table_name)';
	$indexArr[] = 'dtd_permissions_id_idx ON document_type_defs(permissions_id)';

	$queryArr['document_field_defs_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_table_name VARCHAR(255) NOT NULL DEFAULT ''",
		"real_field_name VARCHAR(255) NOT NULL DEFAULT ''",
		"arb_field_name VARCHAR(255) NOT NULL DEFAULT ''",
		"ordering INT NOT NULL DEFAULT 0"
	);

	$queryArr['document_permission_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'user_permissions_id INT default 0',
		'group_permissions_id INT default 0',
	);
	$indexArr[] = 'dpl_user_permissions_id_idx ON document_permission_list(user_permissions_id)';
	$indexArr[] = 'dpl_group_permissions_id_idx ON document_permission_list(group_permissions_id)';
	
	$queryArr['document_field_value_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_defs_list_id INT NOT NULL DEFAULT 0", 
		"document_id INT NOT NULL DEFAULT 0", 
		"document_field_defs_list_id INT NOT NULL DEFAULT 0",
		"document_field_value VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'dfvl_document_defs_list_id_idx ON document_field_value_list(document_defs_list_id)';
	$indexArr[] = 'dfvl_document_id_idx ON document_field_value_list(document_id)';
	$indexArr[] = 'dfvl_document_field_defs_list_id_idx ON document_field_value_list(document_field_defs_list_id)';
	$indexArr[] = 'dfvl_document_field_value_idx ON document_field_value_list(document_field_value)';

	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}

	$cabCol = getTableInfo($db_dept,'departments',array('real_name'),array(),'queryCol');
	foreach($cabCol AS $cabinet) {
		$query = "ALTER TABLE ".$cabinet."_files ADD document_id INT DEFAULT 0";
		$res = $db_dept->query($query);
		dbErr($res);

		$query = "ALTER TABLE ".$cabinet."_files ADD document_table_name VARCHAR(255) NOT NULL DEFAULT ''";
		$res = $db_dept->query($query);
		dbErr($res);
	}
	
	foreach($allQueries as $query) {
		$res = $db_dept->query($query);
		dbErr($res);
	}
	echo "please run addDocIDKeys.php in screen when this uprgrade script is done";
}

function addDocumentDeletedColumn($db_dept) {
	$docCol = getTableInfo($db_dept,'document_type_defs',array('document_table_name'),array(),'queryCol');
	foreach($docCol AS $document) {
		alterTable($db_dept, $document, 'ADD', 'deleted SMALLINT NOT NULL DEFAULT 0');
	}
}

function addInboxDelegationTables($db_dept) { 
	$queryArr = array();
	$indexArr = array();
	$queryArr['inbox_delegation'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"delegate_username VARCHAR(100) NOT NULL DEFAULT ''",
		"delegate_owner VARCHAR(100) NOT NULL DEFAULT ''",
		"list_id INT DEFAULT 0",
		"status VARCHAR(100) NOT NULL DEFAULT ''",
		"comments VARCHAR(255) NOT NULL DEFAULT ''",
		'dtime '.DATETIME
	);

	$queryArr['inbox_delegation_history'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"delegate_id INT DEFAULT 0",
		"delegate_username VARCHAR(100) NOT NULL DEFAULT ''",
		"delegate_owner VARCHAR(100) NOT NULL DEFAULT ''",
		"folder VARCHAR(255) NOT NULL DEFAULT ''",
		"filename VARCHAR(255) NOT NULL DEFAULT ''",
		'date_delegated '.DATETIME,
		'date_completed '.DATETIME,
		"status VARCHAR(100) NOT NULL DEFAULT ''",
		"comments VARCHAR(255) NOT NULL DEFAULT ''",
		"action VARCHAR(255) NOT NULL DEFAULT ''"
	);
	$indexArr[] = 'inbdel_del_id_idx ON inbox_delegation_history(delegate_id)';
	$indexArr[] = 'inbdel_del_un ON inbox_delegation_history(delegate_username)';
	$indexArr[] = 'inbdel_del_owner ON inbox_delegation_history(delegate_owner)';

	$queryArr['inbox_delegation_file_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT DEFAULT 0",
		"folder VARCHAR(255) NOT NULL DEFAULT ''",
		"filename VARCHAR(255) NOT NULL DEFAULT ''"
	);
	$indexArr[] = 'inbdel_list_id_idx ON inbox_delegation_file_list(list_id)';

	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(", ", $tableDef).")";
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}

	foreach($allQueries as $query) {
		$res = $db_dept->query($query);
		dbErr($res);
	}
}

function addFullTextIndex($db_dept) {
	$cabList = getTableInfo($db_dept,'departments',array('real_name'),array(),'queryCol');
	foreach($cabList AS $cab) {
		alterTable($db_dept,$cab.'_files','ADD','FULLTEXT(ocr_context)');	
		alterTable($db_dept,$cab.'_files','MODIFY','notes MEDIUMTEXT');	
		alterTable($db_dept,$cab.'_files','ADD','FULLTEXT(notes)');	
		alterTable($db_dept,$cab.'_files','ADD','ca_hash VARCHAR(65)');	
	}
}

function adjustQuota($department) {
	global $DEFS;

	$path = $DEFS['DATA_DIR']."/".$department;
	$depQuota = shell_exec('du -s '.$path);
	$depQuota = explode("\t",$depQuota);
	$quota = (double)$depQuota[0] * 1024;

	$db_doc = getDbObject('docutron');	
	$uArr = array('quota_used' => (double)$quota);
	$wArr = array('real_department' => $department);
	updateTableInfo($db_doc,'licenses',$uArr,$wArr);
	$db_doc->disconnect ();
}
?>
