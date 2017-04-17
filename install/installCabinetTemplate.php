<?php
define('DEPARTMENT', 'client_files');
chdir(dirname(__FILE__));

require_once '../db/db_common.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/tables.php';
require_once '../lib/cabinets.php';
require_once '../documents/documents.php';
require_once '../lib/settingsList.inc.php';
require_once 'templates.php';


if($argc == 1) {
	die();
}

if($argv[1] != '1') {
	die();
}
$allCabinets = array ();
$allDocuments = array ();
$templates = array ();
getTemplates($allCabinets, $allDocuments, $templates);

$db_object = getDbObject(DEPARTMENT);
$db_doc = getDbObject('docutron');
$cabinets = array();
$documents = array();

$codes = explode(',', $argv[2]);

foreach($codes as $myCode) {
	foreach($templates[$myCode] as $myCabinet) {
		if(!in_array($myCabinet, $cabinets)) {
			$cabinets[] = $myCabinet;
		}
	}
	
}

$settList = new settingsList($db_doc, DEPARTMENT, $db_object);
foreach($cabinets as $myCabinet) {
	$myCabinetIndices = $allCabinets[$myCabinet]['indices'];
	$cabinetName = str_replace(' ', '_', $myCabinet);
	$myIndices = array ();
	foreach($myCabinetIndices as $myIndex) {
		$myIndices[] = str_replace(' ', '_', $myIndex);
	}
	createFullCabinet($db_object, $db_doc, DEPARTMENT, 
		$cabinetName, $myCabinet,
		$myIndices);
	if(!isset($allCabinets[$myCabinet]['document_view']) ||
			!$allCabinets[$myCabinet]['document_view']) {
		$settList->markDisabled($cabinetName, 'documentView'); 
	}
	if(isset($allCabinets[$myCabinet]['documents'])) {
		$docList = $allCabinets[$myCabinet]['documents'];
		foreach($docList as $docType) {
			if(!isset($documents[$docType])) {
				$documents[$docType] = array ();
			}
			$documents[$docType][] = $cabinetName;
		}
	}
}
$settList->commitChanges();

$filters = array ();
foreach($documents as $docName => $cabList) {
	$myDocIndices = $allDocuments[$docName]['indices'];
	$docTable = addCompleteDocumentType($docName, 
		$myDocIndices, $db_doc, $db_object);
	$documentID = getTableInfo($db_object, 'document_type_defs', array('id'),
		array('document_table_name' => $docTable), 'queryOne');
	foreach($cabList as $myCab) {
		if(!isset($filters[$myCab])) {
			$filters[$myCab] = array();
		}
		$filters[$myCab][] = $documentID;
	}
}

foreach($filters as $myCab => $fList) {
	addDocumentFilter($myCab, $fList, $db_object);
}
?>
