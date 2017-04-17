<?php
    require_once('SOAP/Client.php');
	$wsdl = new SOAP_WSDL ('http://localhost/webservices/server.php?wsdl');
echo "<pre>\n";
echo $wsdl->generateProxyCode();
?>
