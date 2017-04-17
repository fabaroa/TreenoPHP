<?php
require_once 'SOAP/Client.php';

    /* Create a new SOAP client using PEAR::SOAP's SOAP_Client-class: */
    $client = new SOAP_Client('http://localhost/webservices/CheckOutServer.php');
    $clientIn = new SOAP_Client('http://localhost/webservices/CheckInServer.php');
    /* Define the parameters we want to send to the server's helloWorld-function.
       Note that these arguments should be sent as an array: */
    $paramsLogin = array('username'=>'admin', 'password' => '21232F297A57A5A743894A0E4A801FC3');
	$paramsDep = array('username'=>'admin');
    $paramsCab = array('username'=>'admin', 'department' => 'client_files');
	$paramsUpload = array('filename'=>'[-3-3]text.txt', 'username'=>'admin') ;
	$paramsCreateF = array('username'=>'admin', 'department'=>'client_files', 'cabinet'=>'Email', 'docid'=>'130', 'subfolder'=>'newTab2') ;
	$paramsNewValidate = array('department'=>'client_files', 'cabinet'=>'Accounts_Payable', 'folderid'=>'5', 'subfolder' => '', 'filename'=>'Document1.doc' ) ;
	$paramsRequest = array('filename'=>'[-1-5]OfficeScreens-1_1.doc', 'username'=>'steve' ) ;
	$paramsGetDoc = array('department'=>'client_files', 'cabinet'=>'Accounts_Payable', 'file_id'=>'4', 'username'=>'admin') ;
    $paramsFolders = array('department' => 'client_files', 'cabinet' => 'Accounts_Payable','start' => '0', 'amount' => '10');
    $paramsFoldersSearch = array('department' => 'client_files', 'cabinet' => 'Accounts_Payable', 'search'=>'6','start' => '0', 'amount' => '10');
    $paramsFoldersSearchI = array('department' => 'client_files', 'cabinet' => 'Clients', 'search'=>',,,Mc,,,t,,,','start' => '0', 'amount' => '10');
    $paramsFiles = array('department' => 'client_files', 'cabinet'=>'Accounts_Payable', 'doc_id'=>'1', 'subfolder'=> '', 'start'=>'0', 'amount'=>'10');
    $paramsFilesSearch = array('department' => 'client_files', 'cabinet'=>'Accounts_Payable', 'doc_id'=>'1', 'subfolder'=>'', 'search'=>'a', 'start'=>'0', 'amount'=>'10');

    /* Send a request to the server, and store its response in $response: 
	 * Then print it for each one */
	echo"<pre>" ;


/*
	//get default dep
	echo "Get Default dep<br>";
    $response = $client->call('getDefaultDep',$paramsDep,array('namespace'=> 'urn:CheckOutServer'));
	print_r($response);
	echo "<hr>" ;

	// Get cabinets
	echo "Get RW Cab<br>" ;
    $response = $client->call('getRWCabinets',$paramsCab,array('namespace'=> 'urn:CheckOutServer'));
	print_r($response) ;
	//$arr = explode(";;;", $response) ;
	//for($i = 0 ; $i < sizeof($arr) ; $i++){echo $arr[$i]."\n";}
	echo "<hr>" ;
	
	// Get folders
	echo "Get Folders<br>" ;
    $response = $client->call('getFolders',$paramsFolders,array('namespace'=> 'urn:CheckOutServer'));
    print_r($response);
	//$arr = explode(";;;", $response) ;
	//for($i = 0 ; $i < sizeof($arr) ; $i++){echo $arr[$i]."\n";}
	echo "<hr>" ;

	// Get folders with search for 'McCann'
	echo "Get Folders Search for '6'<br>" ;
    $response = $client->call('getFoldersSearch',$paramsFoldersSearch,array('namespace'=> 'urn:CheckOutServer'));
	print_r( $response );
	//$arr = explode(";;;", $response) ;
	//for($i = 0 ; $i < sizeof($arr) ; $i++){echo $arr[$i]."\n";}
	echo "<hr>" ;
	
	// Get folders with search for 'Mc' and 't' in last and first name
	echo "Get Folders Search for 'Mc' and 't' in last and first name<br>" ;
    $response = $client->call('getFoldersSearchIndices',$paramsFoldersSearchI,array('namespace'=> 'urn:CheckOutServer'));
	print_r($response) ;
	//$arr = explode(";;;", $response) ;
	//for($i = 0 ; $i < sizeof($arr) ; $i++){echo $arr[$i]."\n";}
	echo "<hr>" ;
	
	// Get files
	echo "Get files<br>" ;
    $response = $client->call('getFiles',$paramsFiles,array('namespace'=> 'urn:CheckOutServer'));
	print_r($response) ;
	//$arr = explode(";;;", $response) ;
	//for($i = 0 ; $i < sizeof($arr) ; $i++){echo $arr[$i]."\n";}
	echo "<hr>" ;

	// Get files with search for 'NFL'
	echo "Get files with search for 'a' <br>" ;
    $response = $client->call('getFilesSearch',$paramsFilesSearch,array('namespace'=> 'urn:CheckOutServer'));
	print_r($response) ;
	//$arr = explode(";;;", $response) ;
	//for($i = 0 ; $i < sizeof($arr) ; $i++){echo $arr[$i]."\n";}
	echo "<hr>" ;

	echo "getDocument<br>" ;
    $response = $client->call('getDocument',$paramsGetDoc,array('namespace'=> 'urn:CheckOutServer'));
	print_r($response) ;
	echo "<hr>" ;
	echo "Upload Document<br>" ;
    $response = $clientIn->call('requestCheckIn',$paramsUpload,array('namespace'=> 'urn:CheckInServer'));
	print_r($response) ;
	echo "<hr>" ;
*/
	echo "Create Folder<br>" ;
    $response = $clientIn->call('createSubFolder',$paramsCreateF,array('namespace'=> 'urn:CheckInServer'));
	print_r($response) ;
	echo "<hr>" ;
/*
    echo "Request Login<br>";
	$response = $client->call('login', $paramsLogin, array('namespace'=>'urn:CheckOutServer'));
	print_r($response) ;
	echo "<hr>";

    echo "Request New Check In<br>";
	$response = $clientIn->call('requestCheckIn', $paramsRequest, array('namespace'=>'urn:CheckInServer'));
	print_r($response) ;
	echo "<hr>";
*/
/*
    echo "Validate New Check In<br>";
	$response = $clientIn->call('validateNewCheckIn', $paramsNewValidate, array('namespace'=>'urn:CheckInServer'));
	print_r($response) ;
	echo "<hr>";

    $clientWTF = new SOAP_Client('http://localhost/webservices/WorkflowServer.php');
   echo "get Deps<br>";
   $paramsWTF = array('username'=>'admin');
	$response = $clientWTF->call('getDeps', $paramsWTF, array('namespace'=>'urn:WorkflowServer'));
	print_r($response) ;
	echo "<hr>";
*/
?>
