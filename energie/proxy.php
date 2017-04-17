<?php
//$Id: proxy.php 14651 2012-02-02 21:59:26Z acavedon $

include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../classuser.inc';
require_once 'HTTP/Client.php';

if(!session_id()) {
    session_start();
}

$xmlStr = file_get_contents('php://input');
error_log("Proxy received data: ".$xmlStr);

try{
 	$domDoc = new DOMDocument ();
	$domDoc->loadXML ($xmlStr);
	$elmUsername = $domDoc->getElementsByTagName('username');
	$tmp = $elmUsername->item(0);
	$legUser = strtolower($tmp->nodeValue);
	$elmPassword = $domDoc->getElementsByTagName('password');
	$tmp = $elmPassword->item(0);
	$legPasswd = $tmp->nodeValue;
	
	$department = $domDoc->getElementsByTagName('department');
	if($department) {
		$tmp = $department->item(0);
		$department = $tmp->nodeValue;
	} else {
 		unset ($department);
	}

	$elmReqPage = $domDoc->getElementsByTagName('reqPage');
	if($elmReqPage->length > 0) {
		$tmp = $elmReqPage->item(0);
		$reqPage = $tmp->nodeValue;
		error_log("reqPage: ".$reqPage);
	}	
}catch( Exception $e ){
	error_log( "no xml string passed to proxy" );
}

$client = new HTTP_Client();

if(isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $userName = $user->username;
        $password = $user->password;
} else {
        $userName = $legUser;
        $password = $legPasswd;
}

$data = "<doc><username>$userName</username>";
$data .= "<password>$password</password>";
$data .= "<department>$department</department>";
$data .= "<reqPage>$reqPage</reqPage>";
$data .= "<cabinet>Whatever</cabinet>";
$data .= "</doc>";
$url = "http://10.1.10.51/login.php?legint=1";
$client->post( $url, $data, true );
$cookie = array_pop($client->_cookieManager->_cookies);
setcookie('PHPSESSID', $cookie['value'], 0, '/');
$response = $client->currentResponse();

//$headers = $response['headers'];
//error_log('response header: '.print_r($headers,true));

//$hkeys = array_keys( $headers );
//foreach( $hkeys as $key )
//{
//	header( "$key: {$headers[$key]}" );
//}
//print_r( $headers );
$body = $response['body'];
//error_log('response body: '.print_r($body,true));
print_r( $body );



?>
