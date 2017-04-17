<?php
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once 'XMLCabinetFuncs.php';

$user_name=$user->username;

	if (isset ($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}
	if (isset ($_GET['DepID'])) {
		$DepID = $_GET['DepID'];
	} else {
		$DepID = '';
	}
  $temp_table =$_GET['temp_table'];
  $is_files=$_GET['is_files'];	//1 if coming from file search results

  $chooseCab      = $trans['Choose Cabinet'];
  $backupCabinet  = $trans['Backup A Cabinet'];
  $toCD           = $trans['to CD'];
  $backingAlready = $trans['A user is backing up a cabinet already.'];
  $tryAgain       = $trans['Try again later'];

echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" type="text/css" href="../lib/style.css">
</head>
<body>
ENERGIE;

 if($DepID&&$DepID!="$chooseCab")		//cabinet has been selected for backup
 {
	$usrStt = new Usrsettings($user->username, $user->db_name);
	$usrStt->removeKey('cd_backup');
	echo <<<ENERGIE
<br>ISO files will be placed in your personal inbox.  
<br>Creating the CD's may take a couple hours depending on the amount of data requested.
<br>ISO files will be prefixed with 4 digit year, 2 digit month, 2 digit day, then a 24 hour timestamp and have the extension .iso.
<br>An example filename would be like 20070101080023disk_1.iso.
<br>Large files will download at the max speed of your internet connection.
ENERGIE;

$cmd = $DEFS['PHP_EXE'] . ' -q ' . 
 	escapeshellarg ($DEFS['DOC_DIR'] . '/bots/cdbackup.php') . ' ' .
 	escapeshellarg ($user->db_name) . ' ' . escapeshellarg ($DepID) . ' ' .
 	escapeshellarg ($user_name) . ' ' . escapeshellarg ($temp_table) . ' ' .
 	escapeshellarg ($is_files) . ' 2>&1 ';
  
if (substr (PHP_OS, 0, 3) == 'WIN') {
 	$cmd = $DEFS['BGRUN_EXE'] . ' ' . $DEFS['CMD_EXE'] . ' /C start /B /LOW ' . $cmd . '> NUL';
} else {
 	$cmd = 'nice -n 20 ' . $cmd . '> /dev/null &';
}

	$db_doc = getDBObject('docutron');
	$key = "docDaemon_execute";
	$insertArr = array(
		"k"				=> $key,
		"value"			=> $cmd,
		"department"	=> $user->db_name
	); 
	$res = $db_doc->extended->autoExecute("settings", $insertArr);
	dbErr($res);
}
	setSessionUser($user);
?>
