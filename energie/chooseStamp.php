<?php

require_once '../check_login.php';
require_once '../lib/redaction.php';

if($logged_in and $user->username) {
	$height = 100;
	$width = 200;
	$time = $_GET['time'];
	$topImg = '';
	if(isset($_GET['img'])) {
		$topImg = $_GET['img'];
	}
	if(isset($_GET['doTime']) and $_GET['doTime'] == 1) {
		$timestamp = true;
	} else {
		$timestamp = false;
	}
	$newImg = createStamp($user->db_name, $_GET['user'], $timestamp, $time, $width, $height, $topImg);
	header('Content-type: image/gif');
	imagepng($newImg);
}

?>
