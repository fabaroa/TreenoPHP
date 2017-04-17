<?php
	chdir("C:\\treeno\\treeno\\clientbots\\");
	include '../db/db_common.php';
	$dept = $_GET['dept'];
	$doc_id = $_GET['doc_id'];
	$cab = $_GET['cab'];
	$db_doc = getDbObject("docutron");
	$db_dept = getDbObject($dept);
	$select = "SELECT username
  FROM [wf_history],[wf_nodes],[wf_documents]
  where [wf_nodes].id=wf_node_id and which_user=4 and wf_document_id=[wf_documents].id and cab = '".$cab."' and doc_id=".$doc_id."
  order by [wf_history].date_time desc";
	error_log("in custom:".$select."\n");
	$username = $db_dept->queryAll( $select );
	$db_dept->disconnect();
	$db_doc->disconnect();
	echo "userToBeAssigned=".$username[0]['username'];
?>