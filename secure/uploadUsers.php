<?php
include_once '../check_login.php';
include_once '../lib/settings.php';

if($logged_in == 1 && strcmp($user->username, "") != 0) {
	foreach($_FILES AS $fInfo) {
		$type = $fInfo['type'];
		$ext = "csv";
		if($type == "text/plain") {
			$ext = "txt";
		}
		$dest = $DEFS['TMP_DIR']."/importUsers-$user->username.$ext"; 
		move_uploaded_file($fInfo['tmp_name'],$dest);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Import Users</title>
	<script type="text/javascript" src="../lib/behaviour.js"></script>
	<script type="text/javascript">
		function onUploadCompletion() {
			if(parent.mainFrame.importUsers) {
				parent.mainFrame.importUsers();
			}
		}

		Behaviour.addLoadEvent(
			function() {
				onUploadCompletion();
			}
		); 
	</script>
</head>
<body>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
