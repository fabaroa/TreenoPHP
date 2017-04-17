<?php
//include '../search/search.php';
//include '../lib/utility.php';
include_once '../lib/versioning.php';
include_once '../lib/settings.php';

//This file searches for a document and sends back a response
//containing the requested document
/*********************** get search parameters *********************/
$department = $_POST['department'];
$cabinet = $_POST['cabinet'];
$fileID = $_POST['fileid'];
/********************** perform search *************************/
//get list of folders for cabinet
$db_object = getDbObject($department);
//$query = "select doc_id, location from $cabinet";
//$cabArr = $db_object->extended->getAssoc($query);

//$query = "select doc_id, subfolder, filename from {$cabinet}_files";
//$query .= " where display=1 and deleted=0 and id=$fileid";
//echo $query ;
//$filesResult = $db_object->query($query);

// get first and only row
//$row = $filesResult->fetchRow();
//$location = $DEFS['DATA_DIR'];
//$location .= "/" . str_replace(' ', '/', $cabArr[$row['doc_id']]);
//if (isset($row['subfolder'])) {
//	$location .= "/" . $row['subfolder'];
//}
//$location .= "/" . $row['filename'];
//$file = $location;

/********************* check out the file *******************/

$parentID = getParentID($cabinet, $fileID, $db_object);
if($parentID == 0) {
	makeVersioned($cabinet, $fileID, $db_object);
	$parentID = $fileID;
}
checkAndSetLock($cabinet, $parentID, $db_object, "admin");
$fileID = getRecentID($cabinet, $parentID, $db_object);
$fileRow = getTableInfo($db_object, $cabinet.'_files', array(), array('id' => (int) $fileID), 'queryRow');

$whereArr = array('doc_id'=>(int)$fileRow['doc_id']);
$result = getTableInfo($db_object,$cabinet,array(),$whereArr);
$row = $result->fetchRow();
$path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/{$row['location']}");
if(isset($fileRow['subfolder']) and $fileRow['subfolder']) {
	$path = $path."/".$fileRow['subfolder'];
}
$file = $path ."/".$fileRow['filename'];
/************ return first file in the list of results **********/
if ( file_exists($file) && is_file($file) ){
    /*************** download the file ***************/
	$fsize = filesize($file);
	header("Content-Type: application/octet-stream");
	header("Content-Length: $fsize");
	header("Content-Disposition: inline filename=$file");
	readfile("$file");
}
else{
	header("Content-Type: text/html");
	echo "File Does Not Exist. Could Not Checkout File.";
}
?>
