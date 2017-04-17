<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../centera/centera.php';
include_once '../settings/settings.php';
/*
CReated By Brian Charles 10/15/9
This file will loop through all the cabinets on disk, searching for folders that 
have a record in the cabinet table.  If that doc_id has a matching record in the
cabinet_files table, move on.  If there are no records in the table for a matching file,
but there are files on disk, list the cabinet, the doc_id, the location, 
all the subfolders, and all the files inside.  This will give us a place to start rebuilding the
correct file system.
*/
$hashFile = fopen('/tmp/missingFiles', 'w');
//define needed variables//
$db_doc = getDbObject('docutron');
$db_dept = getDbObject('client_files');
//first we need to create a temp table of the audit records table.
//this table will contain only records of files that have been cut.

//get the files cut records.
$auditArr = getTableInfo($db_dept, 'audit', array('info', 'action'), array('action = "Files Cut"'), 'queryAll');
//Loop through each record.
foreach($auditArr as $audit)
{
	$action = $audit['action'];
	$info = $audit['info'];
//FROM: Cabinet: Client Accounts Folder: Brown John Nelson, Ronald V. SSN 354567439 Pacific Life Simple IRA current Filename: 2.TIF TO: Cabinet: Client Accounts Folder: Brown John Nelson, Ronald V. SSN 354567439 Pacific Life IRA current 	
	//we need to extract information from the info.
	//first split on the string 'To:'
	$InfoString = explode("TO:", substr($info, 6));
	$From = explode(" ", $InfoString[0]);
	$To = explode(" ", $InfoString[1]);
	
	//next get the cabinet in the "From:"
	$cabinetFrom = '';
	foreach($From as $val)
	{
		if($val == "Cabinet:")
		{
			//begin the cabinet name.
			$cabinetFrom .= next($From);
			//check the next word for folder
			if(next(next($From)) == "Folder:")
				continue;
			else
				$cabinetFrom .= " " . next(next($From)); 
		}
		if($val == "SSN")
			$ssn = next($From); 
	}
}
//next, we will query the wf_documents table for a list of cab/doc/file
//that have gone through and completed wf 81.

$workFlowFiles = getTableInfo($db_dept, 'wf_documents', array('cab', 'doc_id', 'file_id'), array('wf_def_id'=>81, 'status'=>'COMPLETED'), 'queryAll');

//run through each file and get the cabiinet_files, doc_id location.
foreach($workFlowFiles as $wfFile)
{
	$doc_id = $cab = $file_id = NULL;
	$doc_id = $wfFile['doc_id'];
	$cab = $wfFile['cab'];
	$file_id = $wfFile['file_id'];
	//select the location//
	$Location = getTableInfo($db_dept, $cab, array('location'), array('doc_id'=>$doc_id), 'queryOne');
	//get the subfolder
	$fileDetails = getTableInfo($db_dept, $cab.'_files', array('subfolder', 'filename', 'ca_hash'), array('filename is not null', 'doc_id'=>$doc_id, 'id'=>$file_id), 'queryOne');
	
	$location = str_replace(" ", "/", $Location['location']);
	$subfolder = $fileDetails['subfolder'];
	$filename = $fileDetails['filename'];
	$ca_hash = $fileDetails['ca_hash'];
	
	$filepath = "/quorum/$location/".(!is_null($subfolder) ? $subfolder."/" : "")."$filename";
	
	if(is_null($ca_hash) || empty($ca_hash))
	{
		if(!file_exists($filepath))
		{
			//so we have a location and a file_id, but there is no file there, 
			//and there is no ca_hash.  Where did the file go.  We can check all of these
			//files for moves(copies and cuts).  
			//fwrite($hashFile, "$filepath  ".$cab."_files: doc_id=$doc_id  id=$file_id\n");
			//This is going to rely heavily on the audit table array.  
			
			
		
	}
}

fclose($hashFile);
?>