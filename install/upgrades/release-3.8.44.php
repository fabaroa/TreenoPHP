<?php
include_once '../../db/db_common.php';

$db_doc = getDbObject('docutron');

$tableArr = array();
$indexArr = array();

$tableArr ['definition_types'] = array(
	'id '.AUTOINC,
	'PRIMARY KEY (id)',
	"document_type_id INT NOT NULL DEFAULT 0",
	"document_type_field VARCHAR(255) NOT NULL DEFAULT ''",
	"definition VARCHAR(255) NOT NULL DEFAULT ''"
);
$indexArr[] = 'def_docid_idx ON definition_types(document_id)';
	
$sArr = array('real_department');
$depList = array (
		'client_files11',
		'client_files12',
		'client_files13',
		'client_files14',
		'client_files15',
		'client_files16',
		'client_files17',
		'client_files18',
		'client_files19',
		);
foreach($depList AS $dep) {
	$db_dept = getDbObject($dep);
	foreach($tableArr as $table => $tableDef) {
		$db_dept->query("CREATE TABLE $table (".implode(', ', $tableDef).')');
	}

	foreach($indexArr as $subQuery) {
		$db_dept->query('CREATE INDEX '.$subQuery);;
	}
}
?>
