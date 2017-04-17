<?php
define("CHECK_VALID", "yes");
include_once '../check_login.php';
include_once '../classuser.inc';

include_once '../lib/versioning.php';



echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Audit Display</title>
</head>
<body>
<?php
if($logged_in == 1 && strcmp($user->username, "") != 0) {
	$pAudit = urldecode($_GET['pAudit']);
	$vers = urldecode($_GET['vers']);
	$user->audit('displayed file', "$pAudit, Version: $vers");
} else {
	echo "<script type=\"text/javascript\">\n";
	echo "document.onload = top.window.location = \"../logout.php\";\n";
	echo "</script>\n";
}


	setSessionUser($user);
?>
</body>
</html>
