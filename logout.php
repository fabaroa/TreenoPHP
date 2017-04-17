<?php
require 'check_login.php';	// database connect script.
include_once 'settings/settings.php';
include_once ('classuser.inc');

if (isset ($user)) {
	$settings = new GblStt($user->db_name, $db_doc);
}
//TODO
//add better error checking
//this checks for if the session timed out.
if(isset ($user) and isset( $user->username)) {
	if(!$db_doc) {
		$db_doc = $user->getDBDocObject();
	}
	//if there isnt an entry in the database for the logout, put one there
	if(!isset($_GET['manual'])) {	//don't display expiration message for a manual logout
		$res = getTableInfo($db_doc,'user_security',array(),array('username'=>$user->username));
		if($row = $res->fetchRow()) {
			$status=$row['status'];
			$stored_hash=$row['hash_code'];
	
			if ($stored_hash!=$user->hash_id) { 	//user was logged in from another location
				$message="User Logged In From Another Location";
			} else {
				//remove polling entry to allow another user to simulatenously login
				$whereArr = array('username'=>$user->username);
				deleteTableInfo($db_doc,'user_polls',$whereArr);
				$whereArr = array('username'=>$user->username);
				deleteTableInfo($db_doc,'user_security',$whereArr);
 
				if($status=="1")	//user time expired ???
					$message="Login Expired Message";
				else if($status=="2")	//admin logged you out
					$message="Login Expired Message";
				else if ($status=="3")
					$message="Concurrent Licensing Message";
				else	//dont know why logged out, no message
					$message="";
			}
		} else {
			$message = "Login Expired Message";
		}
			
	} else	{ //clear any logout message -- delete user poll entry
/*
		if ($user->username=='admin') {
			$query='delete from user_polls where current_department="'.$user->dbDeptName.'" and username="'.$user->username.'" limit 1';
			$db_doc->query($query);
		} else {
			$whereArr = array('username'=>$user->username);
			deleteTableInfo($db_doc,'user_polls',$whereArr);
		}
*/		
		$whereArr = array('username'=>$user->username);
		deleteTableInfo($db_doc,'user_polls',$whereArr);
		$whereArr = array('username'=>$user->username,'hash_code'=>$user->hash_id);
		deleteTableInfo($db_doc,'user_security',$whereArr);
		$message="";
	}
} else {
	$message="Login Expired Message";
}
//if a message was sent to this page, relay it
if(isset($_GET['message']) and $_GET['message']!="") {
	$message=$_GET['message'];
} else {			
//check if user has changed their password, if so, set message to pass_changed
if(isset($_SESSION['pass_changed'])){
   $message = $_SESSION['pass_changed'];
   unset($_SESSION['pass_changed']);
}
}
if($message && isset ($user) && isset($user->audit)) {
	$user->audit("user $user->username logged out", $trans[$message]);
} 

unset($_SESSION['user']);
// kill session variables
if (isset ($_SESSION['redirectLogin'])) {
	$redirect = $_SESSION['redirectLogin'];
} else {
	$redirect = '';
}
$_SESSION = array(); // reset session array
//start new session to hold the logout message
$_SESSION['logout_message']=$message;

// redirect them to anywhere you like.
if (isset ($user)) {
	setSessionUser($user);
}
$user = "";
setSessionUser($user, false);
echo<<<ENERGIE
<html>
<head>
<script>
ENERGIE;
	if($redirect) {
echo<<<ENERGIE
		document.onload = window.location = "{$redirect}";
ENERGIE;
	} else {
echo<<<ENERGIE
		document.onload = window.location = "energie/energie.php";
ENERGIE;
	}
echo<<<ENERGIE
</script>
</head></html>
ENERGIE;
?>
