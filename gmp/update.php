<?php
//include docutron db files
include_once '../db/db_common.php';
include_once '../db/db_engine.php';
include_once 'gmp.php';
//get post data
$username = $_POST['username'];
$password = md5( $_POST['password'] );
$quota = $_POST['quota'];
$expire_time = $_POST['expiration_time'];
$secret = $_POST['secret'];
//check the secret
$db_doc = getDbObject('docutron');
$whereArr = array('secret_key'=>$secret, 'username'=>$username);
$check = getTableInfo($db_doc,'secrets',array('secret_key'),$whereArr,'queryOne');

$whereArr = array('username'=>$username);
$ct = getTableInfo($db_doc,'users',array('COUNT(id)'),$whereArr,'queryOne');
if($check && $ct > 0) {
	$department = getDefaultDB($db_doc,$username);
	updateDepartmentInfo($department,$db_doc,$quota);	
	updateUserInfo($username,$expire_time,$password,$db_doc);
	$whereArr = array('secret_key'=>$secret, 'username'=>$username);
	deleteTableInfo($db_doc,'secrets',$whereArr);
	echo 1;
} else {
	echo 0;
}
$db_doc->disconnect ();
?>
