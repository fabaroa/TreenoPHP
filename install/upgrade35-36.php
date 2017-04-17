<?
include_once '../db/db_common.php';
include_once '../db/db_engine.php';
include_once '../lib/tables.php';
include_once '../lib/utility.php';

$db_doc = getDbObject ('docutron');
addDocumentSettingsTables();
addPublishingTables();
updateWorkflow();
addFullTextIndex();
addDocumentsTable();
addDocumentDeletedColumn();
addInboxDelegationTables($db_doc);
//addGLVarCustom($db_doc);
addCenteraModules($db_doc);
httpdConfReminder();
$db_doc->disconnect ();
echo "IF this machine is running mysql 4.0 or later,\n" . 
	"add to the my.cnf file under the [mysqld] section\n" .
	"query_cache_size=50000000\n" .
	"for ~50 MB of query caching\n";

shell_exec("pear install -sa HTTP_Client");
function addCenteraModules($db_doc){
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$arr = array(	'arb_name' => 'Centera', 
						'real_name' => 'centera', 
						'dir' => 'centera', 
						'enabled' => 0, 
						'department' => $department );
		$db_doc->extended->autoExecute('modules',$arr);
	}
}
function updateWorkflow($db_doc) {
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		echo $department."\n";
		updateTableInfo($db_dept,'wf_documents',array('file_id' => -1), array('file_id' => 0));
		$db_dept->disconnect ();
	}
}

function addDocumentSettingsTables($db_doc) {
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

	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		
		foreach($allQueries as $query) {
			$res = $db_dept->query($query);
			dbErr($res);
		}
		$db_dept->disconnect ();
	}
}

function addPublishingTables($db_doc) {
	$indexArr = array ();
	$queryArr = array ();
	$allQueries = array();

	$queryArr['publishing'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"email VARCHAR(255) NOT NULL DEFAULT ''",
		"password VARCHAR(255) NOT NULL DEFAULT ''",
		"status VARCHAR(255) NOT NULL DEFAULT ''",
		'expiration '.DATETIME,
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		'date_added '.DATETIME,
		"list_id INT NOT NULL DEFAULT 0", 
		"upload INT NOT NULL DEFAULT 0",
		"reset_password INT NOT NULL DEFAULT 0"
	);
	$indexArr[] = 'pub_email_idx ON publishing(email)';
	$indexArr[] = 'pub_dep_idx ON publishing(department)';

	$queryArr['user_search_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT NOT NULL DEFAULT 0", 
		"psid INT NOT NULL DEFAULT 0"
	);
	$indexArr[] = 'usl_lid_idx ON user_search_list(list_id)';
	
	$queryArr['publish_search'] = array(
		'psid '.AUTOINC,
		'PRIMARY KEY (psid)',
		"name VARCHAR(255) NOT NULL DEFAULT ''",
		"type VARCHAR(255) NOT NULL DEFAULT ''",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"cabinet VARCHAR(255) NOT NULL DEFAULT ''",
		"field VARCHAR(255) NOT NULL DEFAULT ''",
		"term VARCHAR(255) NOT NULL DEFAULT ''",
		"doc_id INT NOT NULL DEFAULT 0", 
		"document_id INT NOT NULL DEFAULT 0",
		"file_id INT NOT NULL DEFAULT 0",
		'expiration '.DATETIME,
		"enabled INT NOT NULL DEFAULT 0",
		"wf_def_id INT NOT NULL DEFAULT 0",
		"owner VARCHAR(255) NOT NULL DEFAULT ''"
	);

	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}

	foreach($allQueries as $query) {
		$res = $db_doc->query($query);
		dbErr($res);
	}

	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	$insertArr = array(	'arb_name' => 'Publishing', 
						'real_name' => 'publishing', 
						'dir' => 'publishing', 
						'enabled' => 0 ); 
	foreach($allDepts as $department) {
		$insertArr['department'] = $department;
		$res = $db_doc->extended->autoExecute('modules', $insertArr);
		dbErr($res);
	}
}

function addDocumentsTable($db_doc) {
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
	
	$queryArr['document_permissions'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"permission_id INT DEFAULT 0",
		"group_list_id INT DEFAULT 0",
		"user_list_id INT DEFAULT 0",
	);
	$indexArr[] = 'dp_perm_id_idx ON document_permissions(permission_id)';

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

	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
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
		$db_dept->disconnect ();
	}
	echo "please run addDocIDKeys.php in screen when this uprgrade script is done";
}

function addDocumentDeletedColumn($db_doc) {
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		$docCol = getTableInfo($db_dept,'document_type_defs',array('document_table_name'),array(),'queryCol');
		foreach($docCol AS $document) {
			alterTable($db_dept, $document, 'ADD', 'deleted SMALLINT NOT NULL DEFAULT 0');
		}
		$db_dept->disconnect ();
	}
}

function addInboxDelegationTables($db_doc) { 
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

	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		foreach($allQueries as $query) {
			$res = $db_dept->query($query);
			dbErr($res);
		}
		$db_dept->disconnect ();
	}
}

function addFullTextIndex($db_doc) {
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		$cabList = getTableInfo($db_dept,'departments',array('real_name'),array(),'queryCol');
		foreach($cabList AS $cab) {
			alterTable($db_dept,$cab.'_files','ADD','FULLTEXT(ocr_context)');	
			alterTable($db_dept,$cab.'_files','MODIFY','notes MEDIUMTEXT');	
			alterTable($db_dept,$cab.'_files','ADD','FULLTEXT(notes)');	
			alterTable($db_dept,$cab.'_files','ADD','ca_hash VARCHAR(65)');	
		}
		$db_dept->disconnect ();
	}

}

function httpdConfReminder() {
	echo 'Please remove the word "Indexes" from the '."\n" .
		'/etc/httpd/conf/httpd.conf file and restart httpd. ie "Options Indexes FollowSymLinks"'."\n";
}

function addGLVarCustom($db_doc)
{
//	alterTable($db_doc, 'barcode_reconciliation', 'ADD', 'delete_barcode SMALLINT DEFAULT 0');
//	alterTable($db_doc, 'barcode_reconciliation', 'ADD', 'split_type VARCHAR(10) DEFAULT ""');
//	alterTable($db_doc, 'barcode_reconciliation', 'ADD', 'compress SMALLINT DEFAULT 0');
//	updateTableInfo($db_doc, 'barcode_reconciliation', array('delete_barcode' => 1, 'compress' => 1), array());
	$departments = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($departments as $dept) {
		$db_dept = getDbObject($dept);
//		alterTable($db_dept, 'barcode_history', 'ADD', 'delete_barcode SMALLINT DEFAULT 0');
//		alterTable($db_dept, 'barcode_history', 'ADD', 'split_type VARCHAR(10) DEFAULT ""');
//		alterTable($db_dept, 'barcode_history', 'ADD', 'compress SMALLINT DEFAULT 0');
		updateTableInfo($db_dept, 'barcode_history', array('delete_barcode' => 1, 'compress' => 1), array());
		$db_dept->disconnect ();
	}
}
?>
