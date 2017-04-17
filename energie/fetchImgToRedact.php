<?php
include_once '../check_login.php';
include_once '../lib/mime.php';
error_reporting(E_ALL);

if($logged_in and $user->username) {
$myHeight = $_GET['h'];
$myWidth = $_GET['w'];
$oldHeight = $_GET['oldH'];
$oldWidth = $_GET['oldW'];

$file = $_SESSION['redactFile'];
//unset($_SESSION['redactFile']);

if(getMimeType($file, $DEFS) == 'image/tiff') {

	shell_exec($DEFS['CONVERT_EXE'] . ' '. escapeshellarg ($file) . ' ' . escapeshellarg ($file.'.jpeg'));
	$jpegStr = file_get_contents ($file.'.jpeg');
	unlink ($file.'.jpeg');
} else {
	$jpegStr = file_get_contents($file);
}
$oldImg = imagecreatefromstring($jpegStr);
$smallImg = imagecreatetruecolor($myWidth, $myHeight);
imagecopyresampled($smallImg, $oldImg, 0, 0, 0, 0, $myWidth, $myHeight, $oldWidth, $oldHeight);
	$color = imagecolorallocate($smallImg, 0, 0, 0);
	imagerectangle($smallImg, 0, 0, $myWidth - 1, $myHeight - 1, $color);
header('Content-type: image/jpeg');
imagejpeg($smallImg);
}
?>
