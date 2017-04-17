<?php
require_once '../check_login.php';
require_once '../lib/mime.php';
require_once '../lib/settings.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin()) {
    $cab = $_GET['cab'];
	$doc_id = $_GET['doc_id'];
	$fileID = $_GET['fileID'];
    $location = getTableInfo($db_object,$cab,array('location'),array('doc_id'=>(int)$doc_id),'queryOne');
	$whereArr = array('id' => (int)$fileID);
	$row = getTableInfo($db_object,$cab."_files",array('filename','subfolder'),$whereArr,'queryRow');
	
	$path = $DEFS['DATA_DIR']."/".str_replace(" ","/",$location);
	if($row['subfolder']) {
		$path .= "/".$row['subfolder'];
	}
	$path .= "/".$row['filename'];
    if(file_exists($path)) {
        downloadFile(dirname($path), basename($path), false, false);
    } else {
        echo "File Does Not Exist: $path";
    }
} else {
    logUserOut();
}
?>
