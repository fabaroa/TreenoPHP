<?php
require_once '../check_login.php';
require_once '../lib/mime.php';

if ($logged_in and $user->username) {
	$batch = $_GET['batch'];
	$file = $_GET['file'];
	$inboxPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/personalInbox/'.$user->username.'/'.$batch;
	downloadFile($inboxPath, $file, false, false, $file);
}

?>
