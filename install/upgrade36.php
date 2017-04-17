<?php 
include_once '../db/db_common.php';
include_once '../db/db_engine.php';
include_once '../lib/tables.php';
include_once '../lib/utility.php';
include_once '../lib/settingsList.inc.php';

//addSettings();
//addDocumentPermissionList();
//changeLoginMsg();
//addTodoColumns();
//keyFilesTables();
addWorkflowDefsCol();

function keyFilesTables()
{
	echo "Keying parent_id in the files tables\n";
	$db_dept = getDbObject('client_files');
	$cabinetList = getTableInfo($db_dept, 'departments', array('real_name'), array(), 'queryCol');
	foreach($cabinetList AS $cabinet) {
		alterTable($db_dept, $cabinet."_files", 'ADD KEY', '(parent_id)');
	}
	echo "Finished keying parent_id\n";
}

//Adds the wf_def_id column to the wf_todo table
function addWorkflowDefsCol() {
	$db_doc = getDBObject('docutron');
	alterTable($db_doc, 'wf_todo', 'ADD',"wf_def_id","INT NOT NULL DEFAULT 0");

	$sArr = array('id','wf_document_id','department');
	$todoList = getTableInfo($db_doc,'wf_todo',$sArr,array(),'getAssoc');
	foreach($todoList AS $id => $itemInfo) {
		$db_dept = getDbObject($itemInfo['department']);
		$def_id = getTableInfo($db_dept,'wf_documents',array('wf_def_id'),array('id'=>$itemInfo['wf_document_id']),'queryOne');

		$uArr = array('wf_def_id' => $def_id);
		$wArr = array('id' => $id);
		updateTableInfo($db_doc,'wf_todo',$uArr,$wArr);
	}
}

function addNFSTables() {
	$queryArr = array ();
	$allQueries = array();
	
	$queryArr['unreconciled_nfs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"nfs_id INT DEFAULT 0",
	);

	$queryArr['reconciled_nfs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"nfs_id INT DEFAULT 0",
	);

	$queryArr['nfs_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"date ".DATETIME,
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"doc_id INT DEFAULT 0",
		"file_id INT DEFAULT 0",
		"user VARCHAR(255) NOT NULL DEFAULT ''",
		"rec_date ".DATETIME,
	);

	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	$db_doc = getDbObject('docutron');
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		
		foreach($allQueries as $query) {
			$res = $db_dept->query($query);
			dbErr($res);
		}
	}
}



//Adds the priority column to the wf_documents table
function addTodoColumns()
{
	echo "In addTodoColumns()\n";
	$db_doc = getDBObject('docutron');
	$columns = array( 
		"priority" => "int NULL DEFAULT 0", 
		"notes" => TEXT16M, 
		"date" => DATE 
	);

	foreach($columns AS $column => $columnInfo) {
		alterTable($db_doc, 'wf_todo', 'ADD', $column, $columnInfo);
		echo " Column $column $columnInfo added to wf_todo\n";
	}
	echo "Finished addTodoColumns\n";
}

//Test to see if the columns exist in the db table
function getMissingColumns($db_dept, $department, $table, $columns)
{
	$retColumns = array();
	$db_columns = array();
	$tableInfo = $db_dept->tableInfo($table);
	for($i = 0; $i < sizeof($tableInfo); $i++) {
		$db_columns[] = $tableInfo[$i]['name'];
	}

	foreach($columns AS $column) {
		if( in_array($column, $db_columns) ) {
			echo " Column $column already exists in $table for department $department\n";
		} else {
			$retColumns[] = $column;
		}
	}
	return $retColumns;
}

function changeLoginMsg(){
	$db_doc = getDbObject('docutron');
	$db_doc->query( "update language set english='Incorrect Username and or Password' where k='Username Does Not Exist'" );
	$db_doc->query( "update language set english='Incorrect Username and or Password' where k='Incorrect Password'" );
}

function addSettings() {
	$db_doc = getDbObject('docutron');
	$licenseObject = getLicensesInfo( $db_doc );
	while( $row = $licenseObject->fetchRow() ) {
		$db_dept = getDbObject ($row['real_department']);
		$settingsList = new settingsList($db_doc, $row['real_department'], $db_dept);
		$settingsList->markDisabled(0, 'globalEditFolder');
		$settingsList->markDisabled(0, 'modifyImage');
		$settingsList->commitChanges();
		$db_dept->disconnect ();
	}
	$db_doc->disconnect ();
}

function addDocumentPermissionList() {
	$indexArr = array ();
	$queryArr = array ();
	$allQueries = array();
	
	$queryArr['document_permissions'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"permission_id INT DEFAULT 0",
		"group_list_id INT DEFAULT 0",
		"user_list_id INT DEFAULT 0",
	);

	$indexArr[] = 'dp_perm_id_idx ON document_permissions(permission_id)';

	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}

	$db_doc = getDbObject('docutron');
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db_dept = getDbObject($department);
		
		foreach($allQueries as $query) {
			$res = $db_dept->query($query);
			dbErr($res);
		}
	}

}

?>
