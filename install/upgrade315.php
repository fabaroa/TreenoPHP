<?php
include_once '../db/db_common.php';
include_once '../lib/settingsList.inc.php';

$db_doc = getDbObject ('docutron');

updateSystemSettings($db_doc);
alterLicensesTable($db_doc);
addInboxRecyclebin($db_doc);
loadHelp($db_doc);
fixODBCMapping($db_doc);
fixWFNodesTable($db_doc);
addGLVarCustom($db_doc);
addToSettings($db_doc);

$db_doc->disconnect ();

function addToSettings($db_doc)
{
	$departments = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($departments as $dept) {
		$sett = new GblStt( $dept, $db_doc );
		$compareCols = $sett->get( "compareCols" );
		if( $compareCols == "" ) {
			$sett->set( "compareCols", "-1", $dept  );
		}
	}
}

function addGLVarCustom($db_doc)
{
	alterTable($db_doc, 'barcode_reconciliation', 'ADD', 'delete_barcode SMALLINT DEFAULT 0');
	alterTable($db_doc, 'barcode_reconciliation', 'ADD', 'split_type VARCHAR(10) DEFAULT ""');
	alterTable($db_doc, 'barcode_reconciliation', 'ADD', 'compress SMALLINT DEFAULT 0');
	updateTableInfo($db_doc, 'barcode_reconciliation', array('delete_barcode' => 1, 'compress' => 1), array());
	$departments = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($departments as $dept) {
		$db_dept = getDbObject($dept);
		alterTable($db_dept, 'barcode_history', 'ADD', 'delete_barcode SMALLINT DEFAULT 0');
		alterTable($db_dept, 'barcode_history', 'ADD', 'split_type VARCHAR(10) DEFAULT ""');
		alterTable($db_dept, 'barcode_history', 'ADD', 'compress SMALLINT DEFAULT 0');
		updateTableInfo($db_doc, 'barcode_reconciliation', array('delete_barcode' => 1, 'compress' => 1), array());
		$db_dept->disconnect ();
	}
}

function fixWFNodesTable($db_doc) {
	$licenseObject = getLicensesInfo( $db_doc );
	while( $row = $licenseObject->fetchRow() ) {
		$client_files = $row['real_department'];
		$db_dept = getDbObject( $client_files );

		$query = "ALTER TABLE wf_nodes ADD message_alert INT NOT NULL DEFAULT 0";
		$db_dept->query( $query );
		$db_dept->disconnect ();
	}
}

function updateSystemSettings($db_doc) {
	$licenseObject = getLicensesInfo( $db_doc );
	while( $row = $licenseObject->fetchRow() ) {
		$db_dept = getDbObject ($row['real_department']);
		$settingsList = new settingsList($db_doc, $row['real_department'], $db_dept);
		$settingsList->markEnabled(0, 'editFolder');
		$settingsList->commitChanges();
		$db_dept->disconnect ();
	}
}

function fixODBCMapping($db_doc) { 
	$licenseObject = getLicensesInfo( $db_doc );
	while( $row = $licenseObject->fetchRow() ) {
		$client_files = $row['real_department'];
		$db_dept = getDbObject( $client_files );
		$query = "ALTER TABLE odbc_mapping ADD odbc_trans_level INT NOT NULL DEFAULT 0";
		$res = $db_dept->query($query);
		dbErr($res);

		$query = "ALTER TABLE odbc_mapping ADD quoted INT NOT NULL DEFAULT 0";
		$res = $db_dept->query($query);
		dbErr($res);

		$query = "UPDATE odbc_mapping SET docutron_name=LOWER(docutron_name)";
		$db_dept->query($query);
		
		$query = "ALTER TABLE wf_nodes ADD message_alert text NOT NULL";
		$db_dept->query( $query );
		$db_dept->disconnect ();

	}
	$query = "UPDATE settings SET k=LOWER(k) WHERE k LIKE 'dt,client_files%'";
	$db_doc->query($query);
}
function alterLicensesTable($db_doc) {
	if(getdbType() == "mysql") {
		alterTable($db_doc,'licenses','DROP','PRIMARY KEY','');
		alterTable($db_doc,'licenses','ADD','id','INT AUTO_INCREMENT PRIMARY KEY FIRST');
		alterTable($db_doc,'licenses','ADD','dept_licenses','INT NOT NULL DEFAULT 0 AFTER arb_department');
		alterTable($db_doc,'licenses','ADD','restricted_list','INT NOT NULL DEFAULT 0');
		updateTableInfo($db_doc,'licenses',array('dept_licenses' => 'max'), array());
	} else {
		$rArr = getTableInfo($db_doc,'licenses',array(),array(),'queryAll');
		$fp = fopen('/tmp/licenses','a+');
		fwrite($fp,print_r($rArr,true));
		dropTable($db_doc,'licenses');
		$queryArr = array();
		$queryArr['licenses'] = array(	'id '.AUTOINC,
										'PRIMARY KEY (id)',
										"real_department VARCHAR(100) NOT NULL DEFAULT ''",
										"arb_department VARCHAR(100) NOT NULL DEFAULT ''",
										'dept_licenses INT NOT NULL DEFAULT 0',
										'max INT NOT NULL DEFAULT 0',
										'quota_allowed BIGINT NOT NULL DEFAULT 0',
										'quota_used BIGINT NOT NULL DEFAULT 0',
										'restricted_list INT NOT NULL DEFAULT 0' );
		foreach($queryArr as $table => $tableDef) {
			$query = "CREATE TABLE $table (".implode(', ', $tableDef).')';
			$res = $db_doc->query($query);
			dbErr($res);
		}

		foreach($rArr as $vArr) {
			if(array_key_exists('id',$vArr)) {
				$vArr['id'] = (int)$vArr['id'];
			}
			if(array_key_exists('restricted_list',$vArr)) {
				$vArr['restricted_list'] = (int)$vArr['restricted_list'];
			}
			$vArr['dept_licenses'] = (int)$vArr['max'];
			$vArr['quota_allowed'] = (int)$vArr['quota_allowed'];
			$vArr['quota_used'] = (int)$vArr['quota_used'];
			$res = $db_doc->extended->autoExecute('licenses',$vArr);
		}
	}
}

function addInboxRecyclebin($db_doc) {
	$queryArr = array();
	$queryArr['inbox_recyclebin'] = array(	
			'id '.AUTOINC,
			'PRIMARY KEY (id)',
			"username VARCHAR(255) NOT NULL DEFAULT ''",
			"folder VARCHAR(255) NOT NULL DEFAULT ''",
			"filename VARCHAR(255) NOT NULL DEFAULT ''",
			"path VARCHAR(255) NOT NULL DEFAULT ''",
			'date_deleted '.DATETIME,
			"type VARCHAR(255) NOT NULL DEFAULT ''",
			"department VARCHAR(255) NOT NULL DEFAULT ''" );
	foreach($queryArr as $table => $tableDef) {
		$query = "CREATE TABLE $table (".implode(', ', $tableDef).')';
		$res = $db_doc->query($query);
		dbErr($res);
	}
}

function loadHelp($db_doc) {
	$helpFile = 'help.xml';
	$allQueries = array ();
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_file($helpFile);
		$transArr = $xmlDoc->get_elements_by_tagname('info');
	    foreach($transArr as $myTrans) {
			$queryArr = array ();
			$tmp = $myTrans->get_elements_by_tagname('k');
			$queryArr['k'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('language');
			$queryArr['language'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('section');
			$queryArr['section'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('title');
			$queryArr['title'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('description');
			$queryArr['description'] = $tmp[0]->get_content();
			$allQueries[] = $queryArr;
		}
	} else {
		$xml = simplexml_load_file ($helpFile);
		foreach ($xml->info as $myTrans) {
			$allQueries[] = array (
					'k'				=> $myTrans->k[0],
					'language'		=> $myTrans->language[0],
					'section'		=> $myTrans->section[0],
					'title'			=> $myTrans->title[0],
					'description'	=> $myTrans->description[0]
					);
		}
	}
	foreach ($allQueries as $queryArr) {
		$res = $db_doc->extended->autoExecute ('help', $queryArr);
		dbErr ($res);
	}
}
?>
