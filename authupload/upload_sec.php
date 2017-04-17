<?php
//error_log("authupload\Upload_sec.php started");
//error_log("Script Owner:".get_current_user());
require_once '../db/db_common.php';
require_once '../lib/settings.php';
require_once '../lib/indexing.inc.php';
require_once '../lib/random.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/licenseFuncs.php';
require_once '../lib/routeFile.inc.php';
require_once '../lib/random.php';
$logerr = true; //false;
$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to upload files from file monitor");
	die();
}
if($_SERVER['REQUEST_METHOD'] == 'PUT') {
		if( isset($_REQUEST['bcinfo'] )){
if ($logerr) $err = fopen("/treeno/logs/upload_sec.txt","a");
			$bcinfo = $_REQUEST['bcinfo'];
			$filename = '';
			if( isset( $_REQUEST['filename'] ) ){
				$filename = $_REQUEST['filename'];
				
			}
if ($logerr) fwrite($err,"Has bcinfo = $bcinfo and filename=$filename\n");
			if( $filename == '' ){
				die( 'bad filename' );
			}
			$user = 'admin';
			if( isset( $_REQUEST['username'] ) ){
				$user = $_REQUEST['username'];
			}
if ($logerr) fwrite($err,"user = $user\n");
			if( $user == '' ){
				die( 'bad username' );
			}
			$dept = 'client_files';
			if( isset( $_REQUEST['department'] ) ){
				$dept = $_REQUEST['department'];
			}
if ($logerr) fwrite($err,"dept = $dept\n");
			$mess = "New File Monitor: ".$dept;
			error_log($mess);
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
if ($logerr) fwrite($err,"routed = $str\n");
if ($logerr) fclose($err);
			if( $str == 'good' ){
				header( 'HTTP/1.1 202 Good File Put' );
				die($str);
			}else{
				header('HTTP/1.1 500 Bad File');
				die($str);		
			}
		}
if ($logerr) $err = fopen("/treeno/logs/upload_sec.txt","a");
if ($logerr) fwrite($err,"no BCInfo\n");
		$s = fopen("php://input", "r");
		if (isset ($DEFS['UPLOAD_TMP'])) {
			$tmpDir = $DEFS['UPLOAD_TMP'];
		} else {
			$tmpDir = $DEFS['TMP_DIR'];
		}
		//$fileName = $tmpDir.'/'.basename($_SERVER['PHP_SELF']);
		//$fileName = Indexing::makeUnique($fileName);
		$fileName = $tmpDir.'/'.uniqid("upload_sec",true);
if ($logerr) fwrite($err,"filename: $fileName\n");
		$fd = fopen($fileName, 'w+');
		while($kb = fread($s, 2048)) {
			fwrite($fd, $kb, 2048);
		}
		fclose($fd);
		fclose($s);
		$tmpDir = getUniqueDirectory($tmpDir).'/';
		if (substr (PHP_OS, 0, 3) == 'WIN') {
			$redir = '';
		} else {
			$redir = '2> /dev/null';
		}
		//error_log("Temp directory:".$tmpDir);
if ($logerr) fwrite($err,"ready to unzip $fileName to $tmpDir\n");
		shell_exec($DEFS['UNZIP_EXE'] . ' -qq ' . $fileName . ' -d ' . $tmpDir . ' ' . $redir);
		$dh = opendir($tmpDir);
		$myEntry = readdir($dh);
		$badRename = false;
		$hasBatches = false;
		while($myEntry !== false and !$badRename) {
//if ($logerr) fwrite($err,"inwhile-$tmpDir--$myEntry\n");
			if ($myEntry !== '.' and $myEntry !== '..') {
//if ($logerr) fwrite($err,"inif,inwhile\n");
				$hasBatches = true;
				$scanDir = $DEFS['DATA_DIR'].'/Scan';
				$scanUpload_secDir = $DEFS['DATA_DIR'].'/ScanUpload_sec';
				if(!file_exists($scanDir)) {
					mkdir($scanDir);
				}
				$newDir = getUniqueDirectory($scanDir); //make directory there as well
				$newDirCopy = getUniqueDirectory($scanUpload_secDir);
				$myOrigFile = $tmpDir.'/'.$myEntry;
				if(is_file($myOrigFile)) {
					copy($myOrigFile, $newDir);
					copy($myOrigFile, $newDirCopy);
				} else {
					//mkdir($newDir); done in make unique
					touch($newDir.'/.lock');
					if(!copyDir($tmpDir.'/'.$myEntry, $newDir)) {
						$badRename = true;
					}
					unlink($newDir.'/.lock');

					//mkdir($newDirCopy);
					touch($newDirCopy.'/.lock');
					if(!copyDir($tmpDir.'/'.$myEntry, $newDirCopy)) {
						$badRename = true;
					}
					unlink($newDirCopy.'/.lock');
				}
if ($logerr) fwrite($err,"$myOrigFile to $newDir and $newDirCopy\n");
			}
			$myEntry = readdir($dh);
		}
if ($logerr) fclose($err);
		if ($badRename or !$hasBatches) {
			header('HTTP/1.1 500 Bad File');
			delDir($tmpDir);
			unlink($fileName);
			die();
		}
		delDir($tmpDir);
		unlink($fileName);
		header( 'HTTP/1.1 202 Good File Put' );
		//echo "good";
}
?>
