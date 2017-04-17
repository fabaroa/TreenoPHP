<?php

$remoteIPAddress = $_SERVER['REMOTE_ADDR'];
//error_mylog("remoteIPAddress: ".$remoteIPAddress);

require_once '../db/db_common.php';
require_once '../lib/settings.php';
require_once '../lib/indexing.inc.php';
require_once '../lib/random.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/licenseFuncs.php';
require_once '../lib/routeFile.inc.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to upload files from file monitor");
	die();
}
if($_SERVER['REQUEST_METHOD'] == 'PUT') {
		//$err = fopen("/tmp/will.txt","w+");
		if( isset($_REQUEST['bcinfo'] )){
			$bcinfo = $_REQUEST['bcinfo'];
			$filename = '';
			if( isset( $_REQUEST['filename'] ) ){
				$filename = $_REQUEST['filename'];
			}
			if( $filename == '' ){
				die( 'bad filename' );
			}
			$user = 'admin';
			if( isset( $_REQUEST['username'] ) ){
				$user = $_REQUEST['username'];
			}
			if( $user == '' ){
				die( 'bad username' );
			}
			$dept = 'client_files';
			if( isset( $_REQUEST['department'] ) ){
				$dept = $_REQUEST['department'];
			}
			if( $dept == '' ){
				die( 'bad dept' );
			}
			$fp = fopen( "php://input", "r" );
			$fileString = '';
			while( !feof($fp) ){
				$fileString.=fgets($fp, 4096);
			}
			fclose($fp);
			$db_dept = getDbObject($dept);
			$fbatch = new RouteFile( $bcinfo, $db_dept, $db_doc, $dept, $filename, $fileString, $user, $DEFS );
			$str = $fbatch->routeBatch();
			if( $str == 'good' ){
				header( 'HTTP/1.1 202 Good File Put' );
				die($str);
			}else{
				header('HTTP/1.1 500 Bad File');
				die($str);		
			}
		}
		$s = fopen("php://input", "r");
		if (isset ($DEFS['UPLOAD_TMP'])) {
			$tmpDir = $DEFS['UPLOAD_TMP'];
		} else {
			$tmpDir = $DEFS['TMP_DIR'];
		}
		$fileName = $tmpDir.'/'.basename($_SERVER['PHP_SELF']);
		//fwrite($err,"filename: $fileName\n");
		$fileName = Indexing::makeUnique($fileName);
		$fd = fopen($fileName, 'w+');
		while($kb = fread($s, 2048)) {
			fwrite($fd, $kb, 2048);
		}
		fclose($fd);
		fclose($s);
		$tmpDir = getUniqueDirectory($tmpDir.'/');
		if (substr (PHP_OS, 0, 3) == 'WIN') {
			$redir = '';
		} else {
			$redir = '2> /dev/null';
		}
		shell_exec($DEFS['UNZIP_EXE'] . ' -qq ' . $fileName . ' -d ' . $tmpDir . ' ' . $redir);
		$dh = opendir($tmpDir);
		$myEntry = readdir($dh);
		$badRename = false;
		$hasBatches = false;
		while($myEntry !== false and !$badRename) {
		//fwrite($err,"inwhile-$tmpDir--$myEntry\n");
			if ($myEntry !== '.' and $myEntry !== '..') {
		//		fwrite($err,"inif,inwhile\n");
				$hasBatches = true;
				$scanDir = $DEFS['DATA_DIR'].'/Scan';
				if(!file_exists($scanDir)) {
					mkdir($scanDir);
				}
				$newDir = Indexing::makeUnique($scanDir . '/' . $myEntry);
				$myOrigFile = $tmpDir.'/'.$myEntry;
				if(is_file($myOrigFile)) {
					copy($myOrigFile, $newDir);
				} else {
					mkdir($newDir);
					touch($newDir.'/.lock');
					if(!copyDir($tmpDir.'/'.$myEntry, $newDir)) {
						$badRename = true;
					}
					unlink($newDir.'/.lock');
				}
			}
			$myEntry = readdir($dh);
		}
		//fclose($err);
		if ($badRename or !$hasBatches) {
			header('HTTP/1.1 500 Bad File');
			delDir($tmpDir);
			unlink($fileName);
			die();
		}
		delDir($tmpDir);
		unlink($fileName);
		header( 'HTTP/1.1 202 Good File Put' );
		echo "good";
}
function error_mylog($message)
{
	$dt = new DateTime();
    //echo $dt->format('Y-m-d H:i:s');
	$dest = "upload.log";
	error_log("[".$dt->format('Y-m-d H:i:s')."] ".$message."\r\n", 3, $dest);
}
?>
