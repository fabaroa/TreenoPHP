<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';

if($logged_in==1 && strcmp($user->username,"")!=0) 
{	
	$path = isset($_GET['folder'])? $_GET['folder']:'';
	$tab = isset($_GET['subfolder'])? $_GET['subfolder']:'';
	$filename = isset($_GET['filename'])? $_GET['filename']:'';
	
	$realFilename = '';
	$delete = 0;
	$download = 0;
	
	$newfilename = str_replace('.pdf', '_dsv1.pdf',  strtolower($filename));
	
	$bFileExists = false;
	if( file_exists( $path."/".$tab.'/'.$newfilename )) 
	{		
		$bFileExists = true;
		$filename = $newfilename;
	}
	else if(file_exists( $path."/".$tab.'/'.$filename ))
	{
		$bFileExists = true;		
	}

	if( $bFileExists) 
	{
		$info = ($delete and $download)?"downloaded page":"viewed page";	
		$user->audit("viewed page","Page: $path/$filename",$db_object);		
		downloadFile($path."/".$tab, $filename, $download, $delete, $realFilename);
	} 
	else 
	{
		echo "File Does Not Exist <br>$path/$tab/$filename";
	}	
	
	$db_object->disconnect ();
	setSessionUser($user);
} 
else
{
	logUserOut();
}
?>