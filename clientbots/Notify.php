<?php
	chdir("C:\\treeno\\treeno\\clientbots\\");
	include '../db/db_common.php';
	$dept = $_GET['dept'];
	$doc_id = $_GET['doc_id'];
	$cab = $_GET['cab'];
	error_log("Node:Notify that goods are ordered:".$dept."\n".$doc_id."\n".$cab."\n");
	$db_doc = getDbObject("docutron");
	$db_dept = getDbObject($dept);
	$select = "SELECT departmentid FROM departments where real_name='".$cab."'";
	error_log("in custom:".$select."\n");
	$cabinetID = $db_dept->queryOne( $select );
	$select = "select requisitioner from ".$cab." where doc_id=".$doc_id;
	error_log($select."\n");
	$wfOwner = $db_dept->queryOne( $select );
	$select = "SELECT id FROM wf_documents where doc_id=".$doc_id." and cab='".$cab."' and status='IN PROGRESS' order by id desc";
	error_log($select."\n");
	$wfID = $db_dept->queryOne( $select );
	$update = "update wf_documents set owner='".$wfOwner."' where id=".$wfID;
	error_log($update."\n");
	$db_dept->queryAll( $update );
	$db_dept->disconnect();
	$db_doc->disconnect();
?>