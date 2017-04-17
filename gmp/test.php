<?php
include_once '../db/db_common.php';
include_once '../lib/utility.php';
require_once 'HTTP/Client.php';

$client = new HTTP_Client();
$autosearch = $_GET['autosearch'];
//get username and password like the web users
$data = array( "username"=>'karl47@gmail.com', 
			'password'=>'karl',
			'quota'=>'250',
			'expire_time'=>'36',
			'secret'=>'asdfqwerty' );
$url = "http://demo.docutronsystems.com/gmp/newaccount.php";
$client->post( $url, $data );
$cookie = array_pop($client->_cookieManager->_cookies);

$response = $client->currentResponse();
echo "<pre>";
print_R( $response['body'] );

?>
