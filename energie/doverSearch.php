<?php
//Custom page for the Town of York. If called with no options, it logs in
//the web* user and displays the cabinets. If called with autosearch= value,
//it does a search on the DOMA_Documents cabinet with the provided value in
//the 'Lot' index.
include_once '../db/db_common.php';
include_once '../classuser.inc';
include_once '../lib/utility.php';
require_once 'HTTP/Client.php';

if( !isSet($_SERVER['HTTPS']) ) {
	die( header("Location: https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) );
}

session_start();
$client = new HTTP_Client();
$autosearch = $_GET['autosearch'];
//get username and password like the web users
if(isset($_SESSION['user'])) {
	$user = $_SESSION['user'];
	$userName = $user->username;
	$password = $user->password;
} else {
	$webuser = getNextUser("client_files");
	$userName = $webuser['username'];
	$password = $webuser['password'];
}
$data = "<doc><username>$userName</username>";
$data .= "<password>$password</password>";
$data .= "<department>client_files</department>";
if(!empty($autosearch)) {
	$data .= "<term index=\"date\" value=\"$autosearch\" />";
	$data .= "<cabinet>Public_Meetings</cabinet>";
}else{
	$data .= "<term index=\"date\" value=\"$autosearch\" />";
	$data .= "<cabinet>Public_Meetings</cabinet>";
}
$data .= "</doc>";
//$url = "http://216.107.210.149/login.php?legint=1";
$url = "http://localhost/login.php?legint=1";
$client->post( $url, $data, true );
$cookie = array_pop($client->_cookieManager->_cookies);
setcookie('PHPSESSID', $cookie['value'], 0, '/');
$response = $client->currentResponse();
//print_r( $response );
$headers = $response['headers'];
$hkeys = array_keys( $headers );
foreach($hkeys as $key) {
	header( "$key: {$headers[$key]}" );
}
$body = $response['body'];
echo $body;

function getNextUser($dept) {
	$db_doc = getDbObject('docutron');
    $db_dept = getDbObject($dept);

    /*
    //retrieve all the web users for that department
    $whereArr = array("username LIKE 'web%'");
    $userArr = array();
    $userArr = getTableInfo($db_dept,'access',array('username'),$whereArr,'getCol');
    */
    $query = "SELECT username FROM access WHERE username LIKE 'web%'";
    $userArr = $db_dept->queryCol($query);

    /*
    //get the total number of licenses allowed for that department
    $whereArr = array("real_department" => $dept);
    $numLicenses = getTableInfo($db_doc,'licenses',array('max'),$whereArr,'getOne');
    */
    $query = "SELECT max FROM licenses WHERE real_department='$dept'";
    $numLicenses = $db_doc->queryOne($query);

	if($numLicenses < 0) {
    	$query = "SELECT max_licenses FROM global_licenses";
    	$numLicenses = $db_doc->queryOne($query);
	}

    //lock table to eliminate race condition
    lockTables($db_doc,array('user_polls','users'));
    //lockTheseTables($db_doc,array('user_polls','users'));
    /*
    //search for all web users logged on
    $whereArr = array("username LIKE 'web%'");
    $userUsedArr = array();
    $userUsedArr = getTableInfo($db_doc,'user_polls',array('username'),$whereArr,'getCol');
    */
    $query = "SELECT username FROM user_polls WHERE username LIKE 'web%'";
    $userUsedArr = $db_doc->queryCol($query);

    /*
    //get the number of users logged onto that department
    $whereArr = array('department'  => $dept);
    $deptLicUsed = getTableInfo($db_doc,'user_polls',array('COUNT(id)'),$whereArr,'getOne');
    */
    $query = "SELECT COUNT(id) FROM user_polls WHERE department='$dept'";
    $deptLicUsed = $db_doc->queryOne($query);

    //verify licenses are available
    if($deptLicUsed < $numLicenses) {
        //sort both arrays
        usort($userArr,'strnatcasecmp');
        usort($userUsedArr,'strnatcasecmp');
        //compare the 2 arrays and get the first username available
		$userNotUsedArr = array_diff($userArr,$userUsedArr);
        //verify that there are web users available
        if(sizeof($userNotUsedArr) > 0) {
            $username = array_pop($userNotUsedArr);

            /*
            //retieve the password for the next available user
            $whereArr = array('username' => $username);
            $passwd = getTableInfo($db_doc,'users',array('password'),$whereArr,'getOne');
            */
            $query = "SELECT password FROM users WHERE username='$username'";
            $passwd = $db_doc->queryOne($query);

            //insert into user_polls as a place holder
            $insertArr = array( "username"              => $username,
                                "ptime"                 => time(),
                                "department"            => $dept,
                                "current_department"    => $dept );
            $res = $db_doc->autoExecute('user_polls',$insertArr);
            unlockTables($db_doc);
            //unlockTheseTables($db_doc);
            return array("username" => $username, "password" => $passwd);
        } else {
            unlockTables($db_doc);
            //unlockTheseTables($db_doc);
            return false;
        }
    } else {
        unlockTables($db_doc);
        //unlockTheseTables($db_doc);
        return false;
    }
}
?>
