<?php

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/audit.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 )
{
	$cab = $_GET['cab'];
	$doc_id = $_GET['doc_id'];
	$tab = $_GET['tab'];
	$filename = $_GET['name'];
	$temp_table = $_GET['temp_table'];

	$whereArr = array('doc_id'=>(int)$doc_id);
	$result = getTableInfo($db_object,$cab,array(),$whereArr);
	$res = $result->fetchRow();
	$loc = $res['location'];
	$loc = str_replace(" ", "/", $loc);
	$relativePath = "{$DEFS['DATA_DIR']}/$loc/";

	if( strcmp($tab, "main") == 0 )
		$filepath = $relativePath.$filename;
	else
		$filepath = $relativePath."$tab/$filename";

	//get auto-increment id from $cab_files
	$whereArr = array(
		"doc_id"		=> (int)$doc_id,
		"parent_filename"	=> $filename,
		"display"		=> 1,
		"deleted"		=> 0
			 );
	if($tab != "main") {
		$whereArr['subfolder'] = $tab;
	} else {
		$whereArr['subfolder'] = 'IS NULL';
	}
	$result = getTableInfo($db_object,$cab."_files",array(),$whereArr);
		
	$id = $result->fetchRow();
	$cab_files_id = $id['id'];

	//update temptable
	deleteTableInfo($db_object,$temp_table,array('result_id'=>(int)$cab_files_id));

	$updateArr = array();
	$updateArr['deleted'] = 1;
	$updateArr['display'] = 0;
	$whereArr = array();
	$whereArr['doc_id'] = (int)$doc_id; 
	if($tab != "main") {
		$whereArr['subfolder'] = $tab;
	} else {
		$whereArr['subfolder'] = 'IS NULL';
	}
	$whereArr['filename'] = $filename;
	updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);
	$auditMessage = getFolderAuditStr($db_object, $cab, $doc_id);
	$user->audit("file deleted", "$filename, Folder: $auditMessage from cabinet: $cab, Doc ID: $doc_id");
	echo"<script>";
	echo"parent.mainFrame.window.location = \"file_search_results.php?cab=$cab&temp_table=$temp_table\";";
	echo"</script>";


	setSessionUser($user);

}
else{//we want to log them out

echo<<<ENERGIE
	<html>
	<body bgcolor="#FFFFFF">
	<script>
		document.onload = top.window.location = "../logout.php";
	</script>
	</body>
	</html>
ENERGIE;
}
?>
