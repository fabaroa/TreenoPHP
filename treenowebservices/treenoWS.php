<?php
require_once '../db/db_common.php';
//require_once '../db/generic_db.php';
require_once 'SOAP/Server.php';
require_once '../treenowebservices/treenoServer.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
//error_log("treenoWS.php calling isValidLicense(): ".$db_doc->database_name);
if(!isValidLicense($db_doc)) {
	error_log("treenoWS.php calling isValidLicense(): ".$db_doc->database_name);
	die();
}

$server = new SOAP_Server;
$server->_auto_translation = true;
$myServer = new treenosoapServer($db_doc);
$server->addObjectMap($myServer, 'urn:TreenoWebServices');
if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD']=='POST') {
    $server->service(file_get_contents('php://input'));
} else {
	require_once 'SOAP/Disco.php';
	$disco = new SOAP_DISCO_Server($server,'TreenoWebServices');
	header("Content-type: text/xml");
	if (isset($_SERVER['QUERY_STRING']) &&
		strcasecmp($_SERVER['QUERY_STRING'],'wsdl')==0) {
		echo $disco->getWSDL();
	} else {
		echo $disco->getDISCO();
	}
}

?>
