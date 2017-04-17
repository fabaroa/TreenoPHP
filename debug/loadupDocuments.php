<?php
include_once 'db/db_common.php';
include_once 'lib/utility.php';
include_once 'classuser.inc';
$db_dept = getDbObject('client_files1');

createDocumentFieldValueList($db_dept);
function createDocumentFieldValueList($db_dept) {
	$queryArr = array();
	$queryArr['document_field_value_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_defs_list_id INT NOT NULL DEFAULT 0", 
		"INDEX(document_defs_list_id)",
		"document_id INT NOT NULL DEFAULT 0", 
		"INDEX(document_id)",
		"document_field_defs_list_id INT NOT NULL DEFAULT 0",
		"INDEX(document_field_defs_list_id)",
		"document_field_value VARCHAR(255) NOT NULL DEFAULT ''",
		"INDEX(document_field_value)"
	);

	foreach($queryArr as $table => $tableDef) {
		$query = "CREATE TABLE $table (".implode(', ', $tableDef).')';
		$res = $db_dept->query($query);
		dbErr($res);
	}
}

populateDocumentsTables($db_dept);
$db_dept->disconnect ();
function populateDocumentsTables($db_dept) {

	for($i=1;$i<6;$i++) {
		$insertArr = array(	'document_type_name'	=> 'Invoices'.$i,
							'document_table_name'	=> 'document'.$i,
							'enable'				=> 1);
		$res = $db_dept->extended->autoExecute('document_type_defs',$insertArr);	
		dbErr($res);
		
		$queryArr = array(
			'id '.AUTOINC,
			'PRIMARY KEY (id)',
			"cab_name VARCHAR(255) NOT NULL DEFAULT ''",
			'doc_id INT NOT NULL DEFAULT 0',
			'file_id INT NOT NULL DEFAULT 0',
			'date_created '.DATETIME,
			'date_modified '.DATETIME,
			"created_by VARCHAR(255) NOT NULL DEFAULT ''"
		);
		$query = "CREATE TABLE document".$i." (".implode(', ', $queryArr).')';
		$res = $db_dept->query($query);
		dbErr($res);

		for($j=1;$j<6;$j++) {
			$insertArr = array(	'document_table_name'	=> 'document'.$i,
								'real_field_name'		=> 'f'.$j,
								'arb_field_name'		=> 'field'.$j);
			$res = $db_dept->extended->autoExecute('document_field_defs_list',$insertArr);	
			dbErr($res);
		}

		for($k=1;$k<200001;$k++) {
			$insertArr = array(	'cab_name'	=> 'cabinet'.$i,
								'doc_id'	=> rand(1,100000));
			$res = $db_dept->extended->autoExecute('document'.$i,$insertArr);	
			dbErr($res);

			for($l=1;$l<6;$l++) {
				$insertArr = array(	'document_defs_list_id'			=> (int)$i,
									'document_id'					=> (int)$k,
									'document_field_defs_list_id'	=> (int)($l+(($i-1)*10)),
									'document_field_value'			=> getrandstring());
				$res = $db_dept->extended->autoExecute('document_field_value_list',$insertArr);	
				dbErr($res);
			}
		}
	}
}
?>
