<?php
//include docutron db files
include_once '../db/db_common.php';
include_once '../db/db_engine.php';
include_once '../lib/quota.php';
include_once '../lib/settings.php';
include_once '../lib/settingsList.inc.php';
include_once '../settings/settings.php';
include_once 'gmp.php';
//get post data
/*
$_POST['username'] = 'testkarl7';
$_POST['password'] = 'karl';
$_POST['quota'] = 50; 
$_POST['expiration_time'] = 36;
*/
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

if($check && $ct == 0) {
	$cabinet = 'Documents';
	$tablesArr = array('licenses','quota','modules','settings');
	lockTables($db_doc,$tablesArr);
	$quotaAssigned = getTableInfo($db_doc,'licenses',array('SUM(quota_allowed)'),array(),'queryOne');

	$quotaTotal = getTableInfo($db_doc,'quota',array('size_used'),array(),'queryOne');
	unlockTables($db_doc);
	if($quotaTotal > $quotaAssigned + ($quota * 1024 * 1024)) {
		$department = createDepartment($quota, 1, $db_doc);	
		$db_dep = getDbObject ($department);
		assignDepartmentAdminAccess('admin',$department,$db_doc);
		assignDepartmentAccess($username,$password,$department,$expire_time, $db_doc);
		
		//create the Documents and Instructions cabinet	
		createNewCabinet($db_dep,'Documents',array('Title','Keywords'),1);
		createNewCabinet($db_dep,'Instructions',array('Title','Keywords'),2);

		$location = "client_files Instructions manual";
		$indexArr = array( 	'location'	=> $location,
							'Title' 	=> 'Get My Papers Instructions',
							'Keywords'	=> 'Manual with Screenshots' );
		createNewFolder($db_dep,'Instructions',$indexArr);
		insertFile($db_dep,'Instructions');

		assignAdminCabinetAccess($db_dep,'admin');
		assignCabinetAccess($db_dep,$username);
		createDepartmentTemplate($username,$department,$db_doc, $db_dep);
		$whereArr = array('secret_key'=> $secret, 'username'=> $username);
		deleteTableInfo($db_doc,'secrets',$whereArr);
		$db_dep->disconnect ();
		echo 1;
	} else {
		echo 0;
	}
} else {
	echo 0;
}
$db_doc->disconnect ();
?>
