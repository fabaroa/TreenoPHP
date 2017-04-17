<?php
include '../db/db_common.php';
//debug centera to /var/log/httpd/php_errors
$error_log1=false;
$echo_out = true;
//array to store multiple db connections
//path is where the unlinker checks for the ca files with their full paths in them
$path = '/tmp/docutron/centeraunlinker';
$seconds = 600;
//if the dir does not exist create it
if( !is_dir( '/tmp/docutron' ) )
	//create directory with 777 perms
	mkdir( '/tmp/docutron', 0777 );
//if the centeraunlinker directory doesn't exist create it
if( !is_dir( $path )){
	//create directory with 777 perms
	mkdir( $path, 0777 );

}
//clear the stat cache...reads the directory file instead of the cache
clearstatcache();
//open the path with the centera unlink files in it
$dh = opendir( $path );
//declare variables
$arr = array();//for files in the path
$farr = array();//if the files in the path have multiple lines they get put in this file array
while( $str = readdir( $dh )){
	//read in files
	if( $str!='.' and $str != '..' ){
		$arr[] = $path."/".$str;
	}
}
//create instance of the centera hash checker
$hashcheck = new CenteraHashChecker();
print_r( $arr );
//go through each file that was read in
foreach( $arr as $f ){
	//read in multiple lines if they exist
	$farr = file( $f );
	//get the current time
	$ct = time();
	//get the file time
	$st = stat($f);
	//put it in a variable
	$ftime = $st['mtime'];
	//error flag
	$errorflag=false;
	//if the file life time is greater than the seconds then delete it.  
	//error flag set to false meaning no errors yet for this file
	$secs = $ct - $ftime;
	error_log1( "$f is $secs old" );
	if( ($ct - $ftime ) > $seconds  ){
		//if there are multiple files in the the CA file this will delete them
		//this is for future development of clustering files in one CA clip file
		foreach( $farr as $fpath ){
			//before unlinking each path we need to check in the database that the has is in the DB
			//checkpath($f)
			$pathArr = explode( ' ', $fpath );
			//the file should contain 2 pieces 1. the filename 2. serialized dbinfo
			if( sizeof( $pathArr ) == 1 ){
				//log that their is an error
				error_log1( $fpath." ".$f. " something is missing" );
error_log1( "******************************************************************\n" );
error_log1( "SOMETHING WRONG WITH $f THERE SHOULD BE MORE THAN ONE PIECE\n" );
error_log1( "******************************************************************\n" );
				//do not unlink
				$errorflag = true;
			}elseif(!$errorflag){
				//get the array from the file to check the db
				$dbinfo = unserialize($pathArr[sizeof($pathArr)-1]);
error_log1( print_r($dbinfo,true) );
				//call the check db function with the hash value
					$pathArr = explode( " ", $fpath );
					array_pop( $pathArr );
					$fpath = implode( ' ', $pathArr );//put the spaces back in...important if this is windows.
				if( $hashcheck->checkHash($dbinfo['db_name'], $dbinfo['cab'], $f, $fpath ) ){
					if( sizeof( $fpath )==0 ){
						//something wrong with format of file
						//move it to the centeraerror directory /tmp/docutron/centeraerror
						$errorflag=true;
						error_log1( "format error with file $f" );
						rename( $f, '/tmp/docutron/centeraerror/'.basename( $f ) );
						//skip the rest of the loop
						continue;
					}
					error_log1( "unlinking $fpath" );
					if( file_exists( $fpath )){

						if( !unlink( $fpath )){
							error_log1( "unlinking $fpath failed" );
//							$errorflag=true;
						}
					}else{
						$errorflag=true;
						error_log1( $fpath." does not exist" );
					}
				}else{
					//check if the hash already exists for this file
					//this can happen if the person double clicks the process file for redaction
					//build the location
					//get the doc_id
					//check the filename with the subfolder
					//if it has a valid hash it is good
					//the hash check failed set the errorflag to true
					error_log1( "hash check failed for $f" );
					$errorflag=true;
				}
			}
		}
		//if no errorflag unlink the file
		if( !$errorflag ){
			error_log1( "unlinking $f");
			unlink( $f );
		}
	} 
}
function error_log1( $error ){
	global $error_log1,$echo_out;
	if( $error_log1 ){
		print_r( $error );
		echo "\n";
		error_log( $error );
	}
}
class CenteraHashChecker{
	var $dbArr = array();
	function CenteraHashChecker(){
	}
	function checkHash($db, $cab, $cahash, $filename ){
		$fArr = explode("\.",basename( $filename ));
		$adminRedact = $fArr[sizeof( $fArr )-1];
		//check for adminRedacted files
		//these ca_hashes are not stored in the db.  The are stored in a file with the extension of ca_hash with the file id
		if( $adminRedact=='adminRedacted' ){
			$hashArr = file( $filename.".ca_hash" );
			//line has a format of ca_hash,fileid
			$newHash = explode( ',', $hashArr[0] );
			//use basename because cahash is a file with a full path
			return $newHash[0] == basename($cahash);
		}elseif(!$this->dbArr[$db]){
			$this->dbArr[$db] = getDbObject($db);
		}
		$res = $this->dbArr[$db]->queryAll("SELECT id FROM $cab"."_files WHERE ca_hash='".basename($cahash)."'");
		return sizeof($res);
	}
}
?>
