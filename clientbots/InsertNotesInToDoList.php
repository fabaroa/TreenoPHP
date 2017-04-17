<?php
require_once '../db/db_common.php';
require_once '../lib/webServices.php';

	$deptID=0;
	$dept='client_files808';
	$userName="admin";
	$db_doc = getDbObject ('docutron');
	$db_dept = getDbObject ($dept);
	$fp = fopen("InsertNotes.log","a+");

	$wf_document_select = "select * from [wf_documents] where [status]='IN PROGRESS'";
	$documents = $db_dept->queryAll($wf_document_select);
	$y=1+ max(array_keys($documents));
	echo "documents=".$y."\n";
	for($x=0; $x<$y; $x++){
		$id=$documents[$x]['id'];
		echo $x."\n";
		fwrite($fp,$id."\n");
		$wf_history_select="select top 1 notes from wf_history where wf_document_id=".$id." and [action]='accepted'";
		$notes = $db_dept->queryAll($wf_history_select);
		$note = $notes[0]['notes'];
	
		$update_wf_todo="update wf_todo set notes ='".$note."' where department ='".$dept."' and wf_document_id=".$id;
		echo $update_wf_todo."\n\n";
		$db_doc->queryAll($update_wf_todo);
		fwrite($fp,$update_wf_todo."\n\n");
	}


	fclose($fp);
?>
