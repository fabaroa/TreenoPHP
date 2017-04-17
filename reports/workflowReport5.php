<?php
include_once '../check_login.php'; 
include_once '../classuser.inc';
include_once '../lib/settings.php';

if($logged_in == 1 && strcmp($user->username,"") != 0) {
	$db_doc = getDbObject('docutron');	
	$cmdArr = array($DEFS['PHP_EXE']);
	$cmdArr[] = escapeshellarg($DEFS['DOC_DIR']."/reports/wf_report5.php");
	$cmdArr[] = escapeshellarg($user->username);
	$cmdArr[] = escapeshellarg($user->db_name);
	$insertArr = array ('k'          => 'docDaemon_execute',
                       	'value'      => implode(" ",$cmdArr),
                       	'department' => $user->db_name);
	$res = $db_doc->extended->autoExecute ('settings', $insertArr);
	dbErr($res);	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Folders Without Workflow Report</title>
</head>
<body>
	<div>When the report finishes it will be placed in your personal inbox</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
