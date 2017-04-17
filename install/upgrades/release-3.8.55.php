<?php
// $Id: release-3.8.55.php 13898 2010-05-27 18:42:06Z acavedon $

chdir("..");
include_once '../db/db_common.php';
include_once '../lib/settings.php';
include_once '../lib/install.php';
include_once '../lib/utility.php';

if(isSet($DEFS['PORTAL'])) {
	$HOST = "http://{$DEFS['PORTAL']}/portal/login.php";
} else {
	$HOST = "http://{$DEFS['HOST']}/portal/login.php";
}
writeToDMSDefs("PORTAL",$HOST);

$db_doc = getDbObject('docutron');
$insertArr = array (
	"id ".AUTOINC,
	"PRIMARY KEY (id)",
	"cabinet_id INT NOT NULL DEFAULT 0",
	"document_table_name VARCHAR(255) NULL",
	"field_name VARCHAR(255) NOT NULL",
	"required INT DEFAULT 0",
	"regex VARCHAR(255) DEFAULT ''",
	"display VARCHAR(255) DEFAULT ''"
);
$indexArr = array();
$indexArr[] = 'ff_cab_id_idx ON field_format(cabinet_id)';
$indexArr[] = 'ff_doc_name_idx ON field_format(document_table_name)';
$indexArr[] = 'ff_field_name_idx ON field_format(field_name)';

$sArr = array('real_department');
$depList = getTableInfo($db_doc,'licenses',$sArr,array(),'queryCol');
foreach($depList AS $dep) {
	$db_dept = getDbObject($dep);

	$query = "CREATE TABLE field_format (".implode(', ', $insertArr).')';
	$res = $db_dept->query($query);	
	dbErr($res);

	foreach($indexArr AS $ind) {
		$query = "CREATE INDEX ".$ind;
		$res = $db_dept->query($query);	
		dbErr($res);
	}
}
?>
