<?php
include 'SOAP/Client.php';
include_once 'AuditSearchInfo.php';
include_once 'CabinetItem.php';
//$a = new SOAP_Client( 'http://192.168.1.62/webservices2/docutronWS2.php?wsdl', true );

/*
$a = new SoapClient(TREENO_WSDL,array('trace'=>true));
echo "before login\n";
$pKey = $a->Login( TREENO_USER, md5(TREENO_PASS));
echo "after login\n";
*/

$a = new SoapClient( 'http://192.168.1.201/webservices2/docutronWS2.php?wsdl', array('trace' =>true ));
$message = $a->Login("admin", md5("admin"));
$pass = $message->message;

$arr = array();
$arr[] = new CabinetItem('doc_id','1');
$mess = $a->SearchCabinet($pass,'client_files',1,$arr);
print_r($mess);
echo $mess->resultID."\n";
$res = $a->GetResultSet($pass,'client_files',1,$mess->resultID,0,0);
print_r($res);
die();

$id = $a->AddWorkflowHistoryAudit( $pass, "client_files", 'admin', 124, "testing", "this is just a test1" );
print_r( $a->__getLastResponse() );
print_r( $a->__getLastRequest() );
die();
//$a->GetWorkflowDefs($pass,'client_files');
//print_r( $a->__getLastResponse() );
//print_r( $a->__getLastRequest() );
//die();
/*
$arr = array( "passKey" => $pass, "department" => "client_files2", "cabinet" => "Email_Archive", "docID" => 124 );
$subfolderList = $a->call( 'GetSubfolderList', $arr );
print_r( $subfolderList );

$arr = array( "passKey" => $pass, "userName" => "admin", "department" => "client_files2" );
$cabList = $a->call( 'GetCabinetList', $arr );
print_r( $cabList );

//$arr = array( "passKey" => $pass, "userName" => "admin" );
//$dirListing = $a->call( 'GetDepartmentList', $arr );
//print_r($dirListing);

//$arr = array( "passKey" => $pass, "userName" => "admin", "nodeName" => "MAS500" );
//$wftodoList = $a->call( 'GetTodoIdArray', $arr );
//print_r( $wftodoList );


//$arr = array( "passKey" => $pass, "wfTodoID" => 122 );
//$getList = $a->call( 'GetTodoItem', $arr );
//print_r($getList);

//$arr = array( "passKey" => $pass, "department" => "client_files2", "cabinet" => "Email_Archive", "fileId" => 245 );
//$file = $a->call( 'GetAttachment', $arr );
//print_r($file);

//$arr = array( "passKey" => $pass, "wfTodoId" => 119 );
//$setwf = $a->call( 'SetFinishWorkFlow', $arr );
//print_r($setwf);
/*

$destCabinet = 3;
$destDocID = 2;
$arr = array("passKey" => $pass, "department" => $department, "userName" => $userName, "fileIDs" => $fileIDs, "cabinetID" => $cabinet, "docID" => $docID, "destCabinetID" => $destCabinet, "destDocID" => $destDocID);
$saveDoc = $a->call( 'SaveDocument', $arr );
print_r($saveDoc);
*/
/*
$cabinetID = 1;
$department = 'client_files';
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID );
$file = $a->call( 'GetDatatypeDefinitions', $arr );
print_r($file);

echo "\n\n";
$dataDefList = array("test", "test1", "test2");
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "cabIndex" => "date", "dataDefList" => $dataDefList );
$file = $a->call( 'AddDatatypeDefinitions', $arr );
print_r($file);

echo "\n\n";
$dataDefList = array("hello");
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "cabIndex" => "date", "dataDefList" => $dataDefList );
$file = $a->call( 'DeleteDatatypeDefinitions', $arr );
print_r($file);

echo "\n\n";
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "cabIndex" => "date" );
$file = $a->call( 'ClearDatatypeDefinitions', $arr );
print_r($file);

echo "\n\n";
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID );
$file = $a->call( 'GetDatatypeDefinitions', $arr );
print_r($file);
*/
/*
$cabinetID = 1;
$department = 'client_files';
$docID = 46;
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "docID" => $docID );
$file = $a->call( 'GetFolderBarcode', $arr );
print_r($file);

$cabinetID = 1;
$department = 'client_files';
$docID = 46;
$tabID = 87;
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "docID" => $docID, "tabID" => $tabID );
$file = $a->call( 'GetSubfolderBarcode', $arr );
print_r($file);
*/
/*
$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department, "inboxUser" => "", "folder" => "");
$file = $a->call( 'GetInboxFileList', $arr );
print_r($file);
*/
/*
$department = "client_files";
$encodedFile = file_get_contents("a.txt");
$arr = array( "passKey" => $pass, "department" => $department, "inboxUser" => "admin", "folder" => "", "filename" => "testing.txt", "encodedFile" => $encodedFile);
$file = $a->call( 'UploadToInbox', $arr );
print_r($file);
*/
/*
$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department, "inboxUser" => "admin", "folder" => "", "filename" => "wregaiwgwio.txt");
$file = $a->call( 'DownloadFromInbox', $arr );
print_r($file);
echo "\n";
*/
/*
$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department, "inboxUser" => "admin", "folder" => "oiu");
$file = $a->call( 'CreateInboxFolder', $arr );
print_r($file);
echo "\n";
*/
/*
$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => "4");
$file = $a->call( 'GetSavedTabs', $arr );
print_r($file);
echo "\n";

$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department);
$file = $a->call( 'GetUploadUsername', $arr );
print_r($file);
echo "\n";

$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department);
$file = $a->call( 'GetUploadPassword', $arr );
print_r($file);
echo "\n";
*/
/*
$department = "client_files";
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => "4");
$file = $a->call( 'GetCabinetInfo', $arr );
print_r($file);
echo "\n";
*/
/*
$department = "client_files";
$cabinetID = 5;
$docID = 3;
$destCabinetID = 4;
$copy = true;
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "docID" => $docID, "destCabinetID" => $destCabinetID, "copy" => $copy );
$file = $a->call( 'MoveFolder', $arr );
print_r($file);
echo "\n\n";
*/
/*
$department = "client_files";
$cabinetID = 4;
$docID = 39;
$subfolderID = 476;
$destCabinetID = 5;
$destDocID = 1;
$copy = false;
$arr = array( "passKey" => $pass, "department" => $department, "cabinetID" => $cabinetID, "docID" => $docID, "subfolderID" => $subfolderID, "destCabinetID" => $destCabinetID, "destDocID" => $destDocID, "copy" => $copy );
$file = $a->call( 'MoveDocument', $arr );
print_r($file);
echo "\n\n";
*/
/*
$department = "client_files";
$searchTerms = array( );
$dateTimeArr = array( new AuditDateTime(">", "2007-02-22 02:53:50"));
//$dateTimeArr = array( new AuditDateTime(">", "2007-02-22") );
$arr = array( "passKey" => $pass, "department" => $department, "searchTerms" => $searchTerms, "dateTime" => $dateTimeArr  );
$file = $a->call( 'SearchAudit', $arr );
print_r($file);
echo "\n\n";
*/
/*
$arr = array( "passKey" => $pass );
$ret = $a->call( 'ListImportDir', $arr );
print_r($ret);
echo "\n\n";
*/

$arr = array( "passKey" => $pass, "department" => "client_files", "cabinetID" => (int)5, "fileID" => (int)5);
$res = $a->call( 'CheckOutFile', $arr );
print_r($res);
echo "\n\n";
?>
