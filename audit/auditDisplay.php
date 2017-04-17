<?php
require_once '../check_login.php';
require_once '../lib/mime.php';

$dump_dir=$_GET['dir'];
$filename=$_POST['file'];

if($logged_in and $user->username) {

downloadFile($dump_dir,$filename,true,false);

	setSessionUser($user);
}
?>
