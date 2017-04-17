<?php
include_once('../db/db_common.php');
include_once('../db/db_engine.php');
include_once('../documents/documents.php');
include_once('../lib/webServices.php');
//Create new SOAP client with cached WSDL file defined from above
//$a = new SoapClient('https://10.1.10.211/webservices2/docutronWS2.php?wsdl',array('trace'=>true));
//Login through web services
//$message = $a->Login('admin', md5('admin'));
//extract the passKey from the returned login message
//$pass = $message->message;
$cab = '1';
$cabinetName = "Accounting";
$department = 'client_files';
/*
function CreateCabinetFolder2($pass, $cab, $department){
	//file info is as follows:
	global $a;
	$indices = array(array('index'=>'ssn', 'value'=>'123456'), array('index'=>'first_name', 'value'=>'chuckles'));

	set_time_limit(500);
	$params = array($pass, $department, $cab, $indices);
	try{
		$ret = $a->CreateCabinetFolder( $pass, $department, $cab, $indices );
		return $ret;
	} catch(Exception $e) {
		print("Create Cabinet Received Error: ");
		print( $e );
		die();
	}
	//print_r( $a->__getLastRequest() );
	//print_r( $a->__getLastResponse() );

}

function CreateCabinetDocument($pass, $cabinetName, $department)
{
	global $a;
	$indices = array(array('index'=>'Description', 'value'=>'This is a test'), array('index'=>'Date', 'value'=>'10/29/2009'));
	$enArr = array(
				'cabinet'=>$cabinetName, //this is the cabinet name we are putting the document in
				'doc_id'=> 112, //
				'document_table_name'=> 'document62' , //this is where the document type lives	
				'field_count' => 0 //this is how many fields the document type has.  Start with zero
	);
	//now we have the indices array, using that array we populate the field_count and the key=>field pairs
	foreach($indices as $row)
	{
		$count = $enArr['field_count'];
		$enArr['key'.$count] 	= $row['index'];
		$enArr['field'.$count]	= $row['value'];

		$enArr['field_count']++;
	}
	return $a->CreateDocumentInCabinet($pass, $enArr, $department);
}
*/
//$docID = CreateCabinetFolder($pass, $cab, $department);
//$UploadedFile = $a->UploadFileToFolder($pass, $department, $cab, $docID, 0, 'soap.txt', '/var/www/html/tools/soap.txt');

//die("file: $UploadedFile was successful.");
//print("Documents Information: ".print_r($a->GetDocumentTypeList($pass, $department), true));
//echo('<br />');
//print("File Information: ".print_r($a->GetDetailedDocumentList($pass, $department, $cab, '112')));

//This is the part of the show when we get all the workflow definitions by a single user.
$allArbCabs = $arbList = $allIndexNames =array(); 
$massiveArr = getUserWorkflowTodoList (getDbObject('docutron'), 'admin', $arbList, $allArbCabs, $allIndexNames);
$db_doc = getDbObject('docutron');
$db_dept = getDbObject('client_files');

echo print_r($massiveArr, true);

$wf_document = getTodoID($db_doc, 126);
$wf_doc_id = $wf_document['wf_document_id'];
$workflowNode = getWorkflowNode($db_doc, $db_dept, 126);
$nodeID = $workflowNode['node_id'];

workflowReject('client_files', 'admin',$wf_doc_id, $nodeID);

echo "<br /><br /><br />";
$massiveArr = getUserWorkflowTodoList (getDbObject('docutron'), 'admin', $arbList, $allArbCabs, $allIndexNames);

echo print_r($massiveArr, true);