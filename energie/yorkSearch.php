<?php
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../classuser.inc';
require_once 'HTTP/Client.php';

if(!session_id()) {
    session_start();
}

$client = new HTTP_Client();
if (isset ($_GET['autosearch'])) {
	$autosearch = $_GET['autosearch'];
} else {
	$autosearch = '';
}
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
$data .= "<term index=\"new_parcel_id\" value=\"$autosearch\" />";
$data .= "<cabinet>Town_Documents</cabinet>";
$data .= "</doc>";
$url = "http://192.168.11.15/login.php?legint=1";
$client->post( $url, $data, true );
$cookie = array_pop($client->_cookieManager->_cookies);
setcookie('PHPSESSID', $cookie['value'], 0, '/');
$response = $client->currentResponse();

$headers = $response['headers'];
$hkeys = array_keys( $headers );
foreach( $hkeys as $key )
{
	header( "$key: {$headers[$key]}" );
}
//print_r( $headers );
$body = $response['body'];
print_r( $body );

function getNextUser($dept) {

    $db_doc = getDbObject('docutron');
    $db_dept = getDbObject($dept);

    //retrieve all the web users for that department
    $whereArr = array("username LIKE 'web%'");
    $userArr = array();
    $userArr = getTableInfo($db_dept,'access',array('username'),$whereArr,'queryCol');

    //get the total number of licenses allowed for that department
    $whereArr = array("real_department" => $dept);
    $numLicenses = getTableInfo($db_doc,'licenses',array('max'),$whereArr,'queryOne');

    //lock table to eliminate race condition
    lockTables($db_doc,array('user_polls','users'));
    //search for all web users logged on
    $whereArr = array("username LIKE 'web%'");
    $userUsedArr = array();
    $userUsedArr = getTableInfo($db_doc,'user_polls',array('username'),$whereArr,'queryCol');
    //get the number of users logged onto that department
    $whereArr = array('department'  => $dept);
    $deptLicUsed = getTableInfo($db_doc,'user_polls',array('COUNT(id)'),$whereArr,'queryOne');

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

            //retieve the password for the next available user
            $whereArr = array('username' => $username);
            $passwd = getTableInfo($db_doc,'users',array('password'),$whereArr,'queryOne');

            //insert into user_polls as a place holder
            $insertArr = array( "username"              => $username,
                                "ptime"                 => time(),
                                "department"            => $dept,
                                "current_department"    => $dept );
            $res = $db_doc->extended->autoExecute('user_polls',$insertArr);
			dbErr ($res);
            unlockTables($db_doc);

            return array("username" => $username, "password" => $passwd);
        } else {
            unlockTables($db_doc);
            return false;
		}
    } else {
        unlockTables($db_doc);
        return false;
    }
}

?>
