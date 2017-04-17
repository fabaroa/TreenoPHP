<?php
include_once '../check_login.php';
include_once '../lib/mime.php';

$username= $user->username;

$files=$_GET['files'];
$temp_table=$_GET['temp_table'];
$is_files=$_GET['is_files'];
$path=$DEFS['DATA_DIR'];

//print_r($_GET);
if (isset($_POST['yes'])) {
		echo"<script>top.leftFrame1.window.location='sendISO.php'</script>";

	//check where to redirect
	if($temp_table!="") {
		if($is_files==1) {
			echo"<script>document.onload=window.location='../energie/file_search_results.php?mess=ISO(s) have been written&temp_table=$temp_table'</script>";
		} else {
			echo"<script>document.onload=window.location='../energie/searchResults.php?mess=ISO(s) have been written&table=$temp_table'</script>";
		}
	} else {
		echo "<script>document.onload=window.location='backupCabinet.php?mess=ISO(s) successfully created'</script>";
	}

}
else if (isset($_POST['no'])) {
	$tmpDir = $DEFS['TMP_DIR'].'/docutron/'.$user->username.'/cd_backup/';
	for ($i=1;$i<=intval($files);$i++){
		$isoFile = $tmpDir . 'disk'.$i.'.iso';
		if(file_exists ($isoFile))
			unlink ($isoFile);
	}
	$zipFile = $tmpDir . 'cd_backup.zip';
	if(file_exists ($zipFile))
		unlink ( $zipFile);
	//check where to redirect
	if($temp_table!="") {
		if($is_files==1) {
			echo"<script>document.onload=window.location='../energie/file_search_results.php?mess=ISO(s) cancelled by user&temp_table=$temp_table'</script>";
		} else {
			echo"<script>document.onload=window.location='../energie/searchResults.php?mess=ISO(s) cancelled by user&table=$temp_table'</script>";
		}
	} else {
		echo"<script>document.onload=window.location='backupCabinet.php?mess=ISO Download Cancelled By User'</script>";
	}
}
else
{
echo<<<ENERGIE
<html>
<head>
<title>CD Backup</title>
</head>
<body>
<form name ="download" method="POST" action="confirm_backup.php?files=$files&temp_table=$temp_table&is_files=$is_files">
<center>Would you like to download these $files ISO files?<br>
<input type="submit" name="yes" value="yes">&nbsp;
<input type="submit" name="no" value="no"><br>
</center>
</form>
</body>
</html>
ENERGIE;
}

	setSessionUser($user);

?>
