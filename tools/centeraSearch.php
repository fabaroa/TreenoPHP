<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../centera/centera.php';
include_once '../settings/settings.php';
/*
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
//first we need an array of cabinets to go through.
$cabList = getTableInfo($db_dept, 'departments', array('real_name'), array('real_name != "Client_Accounts"'), 'queryAll');
//loop through each cabinet, and get all the folders//
foreach($cabList as $cab)
{
	$documents = NULL;
	$documents = getTableInfo($db_dept, $cab['real_name'], array('doc_id', 'location'), array(), 'queryAll');
	//now loop through the folders and get each location.  check the location for files
	// and the _files table for a record to match.
	$content = "\nScanning Cabinet: ".$cab['real_name']."\n";
	fwrite($hashFile, $content);
	foreach($documents as $doc)
	{
		$doc_id = $location = NULL;
		$doc_id = $doc['doc_id'];
		$location = str_replace(" ", "/", $doc['location']);
		
		//query the _files table to find any documents that exist
		//make sure and account for null filenames
		//$file = getTableInfo($db_dept, $cab['real_name'].'_files', array('id', 'subfolder', 'filename', 'ca_hash'), array('doc_id'=>$doc_id, 'filename is not null', 'ca_hash is null'), 'queryAll');
		$file = getTableInfo($db_dept, $cab['real_name'].'_files', array('filename', 'id', 'ca_hash', 'subfolder'), array('doc_id'=>$doc_id, 'filename is not null', 'ca_hash is null'), 'queryAll');
		//foreach file inside the folder check for the files existence and check for a ca_hash
		if(is_array($file) && count($file) > 0)
		{
			foreach($file as $link)
			{
				$id = $ca_hash = $subfolder = $filename = $filepath = NULL;
				
				$id = $link['id'];
				$ca_hash = $link['ca_hash'];
				$subfolder = $link['subfolder'];
				$filename = $link['filename'];
				
				$filepath = "/quorum/$location/".(!is_null($subfolder) ? $subfolder.'/' : '')."$filename";
				$tmp = NULL;
				if(!file_exists($filepath))
				{
					$tmp = "$filepath ".$cab['real_name']."_files, doc_id=".$doc_id.", id=".$id."\n";
					fwrite($hashFile, $tmp);					
				}	
				
				
			}
		}
	}
}

fclose($hashFile);
