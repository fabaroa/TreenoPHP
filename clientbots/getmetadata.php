<?php
	chdir("C:\\treeno\\treeno\\clientbots\\");
	include '../db/db_common.php';
	//getmetadata.php?dept=client_files&doc_id=8&cab=UCU_Member_File
	$dept = $_GET['dept'];
	$doc_id = $_GET['doc_id'];
	$cab = $_GET['cab'];
	$values = explode("|",trim(file_get_contents("C:\\treeno\\config\\nodeMetaData.cfg")));
	
	$db_dept = getDbObject($dept);
	$select = "SELECT ".$values[0]." from ".$values[1]." where doc_id=".$doc_id;
	$value = $db_dept->queryone( $select );
	$db_dept->disconnect();
	echo "noteToBeAdded=".$values[0]."=".$value;
?>