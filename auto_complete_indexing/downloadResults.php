<?php
include_once '../check_login.php';
include_once '../lib/mime.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
	downloadFile($_GET['path'],$_GET['filename'],true,true);	
}
?>
