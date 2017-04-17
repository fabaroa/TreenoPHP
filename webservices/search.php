<?php
//include '../search/search.php';
//include '../lib/utility.php';
include_once '../lib/settings.php';

//This file searches for a document and sends back a response
//containing the requested document
/*********************** get search parameters *********************/
$department = $_POST['department'];
$cabinet = $_POST['cabinet'];
$fileid = $_POST['fileid'];
/*
$fp = fopen("debug.txt", "w+");
fwrite($fp, $department . "\n");
fwrite($fp, $cabinet . "\n");
fwrite($fp, $fileid . "\n");
fclose($fp);
*/
/********************** perform search *************************/
//get list of folders for cabinet
$db_object = getDbObject($department);
$cabArr = getTableInfo($db_object, $cabinet, array('doc_id', 'location'), array(), 'getAssoc');

$filesResult = getTableInfo($db_object, $cabinet.'_files',
	array('doc_id', 'subfolder', 'filename'), 
	array('display' => 1, 'deleted' => 0, 'id' => (int) $fileid));

// get first and only row
$row = $filesResult->fetchRow();
$location = $DEFS['DATA_DIR'];
$location .= "/" . str_replace(' ', '/', $cabArr[$row['doc_id']]);
if (isset($row['subfolder'])) {
	$location .= "/" . $row['subfolder'];
}
$location .= "/" . $row['filename'];
$file = $location;

/************ return first file in the list of results **********/
if ( file_exists($file) && is_file($file) ){
	$fsize = filesize($file);
	header("Content-Type: application/octet-stream");
	header("Content-Length: $fsize");
	header("Content-Disposition: inline filename=$file");
	readfile("$file");
}
else{
	header("Content-Type: text/html");
	echo "File Does Not Exist.";
}
?>
