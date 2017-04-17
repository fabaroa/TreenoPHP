<?php
require_once '../check_login.php';
require_once '../lib/mime.php';

if($logged_in and $user->username) {
	$file = $user->userTempDir.$_GET['file'];
	if(isSet($_GET['export'])) {
		downloadFile(dirname($file), basename($file), true, true);
	} else if(is_file($file)) {
		downloadFile(dirname($file), basename($file), false, false);
	} else {
		echo "File Does Not Exist: $file";
	}
} else {
	logUserOut();
}
?>
