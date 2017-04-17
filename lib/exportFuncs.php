<?php
include_once '../lib/departmentExport.php';

function exportSystem($user,$db_dept,$db_doc) {
	global $DEFS;

	$treenoObj = new treenoExport($user->db_name); 
	$treenoObj->export($user->userTempDir);

	echo json_encode(array('filename' => "dt_export.json"));
}

function importSystem($dep,$file, $user) {
	$treenoObj = new treenoImport($dep,$file, $user); 
	$treenoObj->import();
}

function getMessages($user,$db_dept,$db_doc) {
	global $DEFS;

	$msgFile = $user->userTempDir.'/importError.txt';
	if(is_file($msgFile)) {
		$msgArr = file($msgFile,FILE_IGNORE_NEW_LINES);
		echo json_encode(array('messages' => $msgArr));
	} else {
		echo json_encode(array('messages' => array(0 => 'Import finished successfully.  Please log out and log back in to see these changes.')));
	}
}
?>
