<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/settings.php';
include_once '../lib/exportFuncs.php';

if($logged_in == 1 && strcmp($user->username, "") != 0) {
	if(isset($_FILES['finput']) && strcmp($_FILES['finput']['name'], '') != 0) {
		$ext = getExtension($_FILES['finput']['name']);
		if($ext == "json") {
			$tmpPath = $DEFS['TMP_DIR']."/".$_FILES['finput']['name'];
			if(move_uploaded_file($_FILES['finput']['tmp_name'], "$tmpPath")) {
				importSystem($user->db_name,$tmpPath, $user);	
			}
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Treeno Import</title>
	<script>
		function toggleImport() {
			if(parent.getMessages) {
				parent.getMessages();
			}
		}
	</script>
</head>
<body onload="toggleImport()">
</body>
</html>
<?php

	setSessionUser($user);
} else {
	logUserOut();
}
?>
