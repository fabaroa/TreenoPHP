<?php
//$Id: ajaxIndexingAction.php 14259 2011-01-26 16:54:46Z cz $


include_once '../check_login.php';
include_once '../classuser.inc';

function custom_phpNotice_handler($errno, $errstr, $errfile, $errline) 
{
	throw new Exception($errno.": ".$errstr);
}

function restorePhpNoticeHandler($errno, $errstr, $errfile, $errline) 
{
	return false;
}

function xmlUpdateIndexes($dep, $docid) 
{
	unset($errMsg);
	try
	{
	  	$xmlStr = file_get_contents('php://input');
		if (substr (PHP_VERSION, 0, 1) == '4') 
		{
	 		error_log("PHP version 4 is not supported any more.");
	 		return;
	  	} 
	
	 	$domDoc = new DOMDocument (); 
		$domDoc->loadXML ($xmlStr);
	 	$cab = $domDoc->getElementsByTagName('CABINET');
	 	$cab = $cab->item(0);
		$cab = $cab->nodeValue;
	 	$fieldArr = $domDoc->getElementsByTagName('INDEX');
	 	$tab = $domDoc->getElementsByTagName('TAB');
	 	if ($tab) {
	 		$tab = $tab->item(0);
			$tab = $tab->nodeValue;
	 	} else {
	 		$tab = 'Main';
	 	}
		for ($i = 0; $i < $fieldArr->length; $i++) {
			$index = $fieldArr->item ($i);
	 		$tmpQueryArr[strtolower($index->getAttribute('name'))] =
	 			trim($index->nodeValue);
	 	}
	 	error_log("xmlUpdateIndexes() doc_id = ".$docid.", cab = ".$cab.", dep = ".$dep);
	 	//throw new Exception("test");
	 	$db_object = getDbObject($dep);
	 	updateTableInfo($db_object, $cab, $tmpQueryArr, array('doc_id'=>$docid, 'deleted'=>0));
	}
	catch(Exception $ex)
	{
		$errMsg = "Error ocurred in xmlUpdateIndexes(): ". $ex->getMessage()."; Trace: ".$ex->getTraceAsString();
		error_log($errMsg);
	}
 	
 	$doc = new DomDocument();
	$root = $doc->createElement( "ROOT" );
	$doc->appendChild( $root );
	
	if(isset($errMsg))
	{
		$mErr = $doc->createElement('ERROR');
		$root->appendChild($mErr);
	
		$txtErr = $doc->createTextNode($errMsg);
		$mErr->appendChild($txtErr);
	}
	else
	{
		$msg = "Indexes successfully updated";
		$m = $doc->createElement('MESSAGE');
		$root->appendChild($m);
	
		$text = $doc->createTextNode($msg);
		$m->appendChild($text);
	}

	header('Content-type: text/xml');
	//error_log($doc->savexml());
	echo $doc->savexml();	
}

set_error_handler("custom_phpNotice_handler", E_NOTICE);

if($logged_in and $user->username and isset($_GET['updateIndexes'])) 
{
	$docid =isset($_GET['doc_id'])?$_GET['doc_id']: -1;
	//error_log("updateIndexes");
	xmlUpdateIndexes($user->db_name, $docid);	
	return;
} 

set_error_handler("restorePhpNoticeHandler", E_NOTICE);

?>