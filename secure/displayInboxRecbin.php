<?php 
require_once '../check_login.php';
require_once '../lib/mime.php';
require_once '../lib/settings.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin()) {
	$db_doc = getDbObject('docutron');
	$id = $_GET['id'];
	$row = getTableInfo($db_doc,'inbox_recyclebin',array(),array('id'=>(int)$id),'queryRow');
	$path = $DEFS['DATA_DIR']."/".$user->db_name."/recyclebin/"; 

	$date = $row['date_deleted'];
	$timestamp = strtotime($date);
	$path .= date("Y-m-d",$timestamp)."/".$row['username']."/";
	$time = date("G-i-s",$timestamp);
	if(!is_dir($path.$time)) {
		$time = date("G:i:s",$timestamp);
	}
	$t = explode(".",$time);
	$path .= $t[0];
	

	if($row['folder']) {
		$path .= "/".$row['folder'];
	}
	$path .= "/".$row['filename'];

	if(file_exists($path)) {
		downloadFile(dirname($path), basename($path), false, false);
	} else {
		echo "File Does Not Exist: ".$row['filename'];
	}
} else {
	logUserOut();
}
?>
