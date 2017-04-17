<?PHP
include_once '../db/db_common.php';
include_once '../lib/webServices.php';

$department = "client_files";
$cabinetID = 3;
$cabinetName = "random_cabinets";
$doc_id = 1;
$fileId = 8;
$username = "admin";
$tab = "";
$db = getDbObject($department);
$db_docutron = getDbObject('docutron');
$nodeName = "SIGNATURE";
$tabID = 24; //main2
$tabname = "main2";

/*
echo "GetTablist: ";
print_r(GetTabList($db, $cabinetID, $doc_id, $username));
echo "\n\n";
*/

/*
echo "GetTabFileList: ";
print_r(GetTabFileList($db, $cabinetID, $doc_id, $username, $tab));
echo "\n\n";
$tab = "main2";
print_r(GetTabFileList($db, $cabinetID, $doc_id, $username, $tab));
echo "\n\n";
*/

/*
//Works, returns [0] => 1
echo "getUserWFIDs: ";
print_r(getUserWFIDs($db_docutron, $username, $nodeName));
echo "\n\n";
*/


//tested
/*$wf_todo_id = 1;
echo "getTodoId: ";
print_r(getTodoId($db_docutron, $wf_todo_id));
echo "\n\n";
*/

/*
$wf_doc_id = 1;
echo "getWf_doc: ";
print_r(getWf_doc($db, $wf_doc_id));
echo "\n\n";
*/

/*
echo "getFolderInfo: ";
$res = getFolderInfo($db, $cabinetName, $doc_id);
while( $row = $res->fetchRow() )
	print_r($row);
echo "\n\n";
*/

/*
echo "getFileQuery: ";
$res = getFileQuery($db, $cabinetName, $fileId);
print_r($res);
echo "\n\n";
*/
/*
$wf_todo_id = 1;
echo "finishWorkflow: ";
$res = finishWorkflow($db_docutron, $wf_todo_id, $username);
if($res)
	echo "true";
else
	echo "false";
echo "\n\n";
*/

/*
echo "Copy Documents";
$srcArray = array("department" => $department, "cabinetID" => $cabinetID, "fileID" => $fileId, "docID" => $doc_id);
$destArr = array("department" => $department, "cabinetID" => $cabinetID,
                    "docID" => $doc_id, "tabID" => $tabID);
$success = copyDocuments($username, $srcArray, $destArr, $db_doc, $db, $db);
if($success)
	echo "Copy Documents: True\n";
else
	echo "Copy Documents: False\n";
echo "\n";
*/

/*
echo "uploadFile to Folder\n";
$docID = 6;
$tabID = 0;
$filename = "testFile.txt";
$encodedFile = file_get_contents("a.txt");
print_r( uploadFileToFolder($username, $department, $cabinetID, $docID, $tabID, $filename, $order, $encodedFile, $db_docutron, $db) );
echo "\n\n";
*/

/*
echo "getFilesFromCabinets: ";
print_r( getFilesFromCabinet($db, $cabinetName, $doc_id, "") );
echo "\n\n";
*/

/*
echo "getCabinetIndiceNames: ";
print_r( getCabinetIndiceNames($db, $cabinetName) );
echo "\n\n";
*/

/*
$indiceArr = array('one' => 'hello');
echo "createCabinetFolder:\n";
$res = createCabinetFolder($department, $cabinetID, $indiceArr, $username, $db_doc, $db);
echo "doc_id: $res\n\n";
*/

/*
echo "updateCabinetFolder: ";
$db_dept = getDbObject('client_files');
$cabinetID = 1;
$tempDoc_id = 70;
$indiceArr = array('company' => 'hello world', 'name' => 'abc');
$username = "admin";
$res = updateCabinetFolder($db_dept, $cabinetID, $tempDoc_id, $indiceArr, $username);
if($res)
	echo "True";
else
	echo "False";
echo "\n\n";
*/

/*
echo "getCabinetList: ";
print_r( getCabinetList($username, $db) );
echo "\n\n";
*/

/*
echo "getDepartmentList:";
print_r( getDepartmentList($db_docutron, $username) );
echo "\n\n";
*/

/*
echo "searchTopLevel: ";
$searchStr = "hello";
print_r( searchTopLevel($db, $searchStr, $username)  );
echo "\n\n";
*/

/*
echo "searchCabinet: ";
$searchArr = array('one' => '', 'two' => 'dos', 'three' => '');
print_r( searchCabinet($department, $cabinetID, $searchArr, $username) );
echo "\n\n";
*/

/*
$tempTable = "DHpTJrzsxUAU";
echo "getResultSet: ";
$startIndex = 0;
$limit = 5;
print_r(getResultSet($department, $cabinetID, $tempTable, $startIndex, $limit, $username ) );
echo "\n\n";
*/

/*
echo "getCabinetIndexFields: ";
print_r( getCabinetIndexFields($department, $cabinetID, $username) );
echo "\n\n";
*/

/*
echo "isAutoComplete: ";
$result = isAutoComplete($username, $department, $cabinetID, $db_docutron);
if($result === -1)
	echo "permissions denied";
elseif($result)
	echo "true";
else
	echo "false";
echo "\n\n";
*/
/*
echo "getAutoComplete:\n";
$autoCompleteTerm = "01234";
$cabinetID = 1;
$result = getAutoComplete( $username, $department, $cabinetID, $autoCompleteTerm, $db_docutron);
print_r($result);
echo "\n\n";
*/

/*
//Datatype Definitions tests
echo "datatype Definitions:\n";
$cabinetID = 1;
$ddefs = getDatatypeDefinitions( $username, $department, $cabinetID, $db_docutron );
print_r($ddefs);
echo "\n";

echo "\naddDatatype:\n";
$param = array("hello", "world", "hello world");
$res = addDatatypeDefinitions( $username, $department, $cabinetID, "datedsgfa", $param, $db_docutron );
echo $res."\n";


echo "\nclearDatatype:\n";
$res = clearDatatypeDefinitions( $username, $department, $cabinetID, "dateweg", $db_docutron );
echo $res."\n";


echo "\ndeleteDatatype:\n";
$param = array("dfwf");
$res = deleteDatatypeDefinitions( $username, $department, $cabinetID, "datewaef", $param, $db_docutron );
echo $res."\n";

echo "\ndatatype Definitions:\n";
$cabinetID = 1;
$ddefs = getDatatypeDefinitions( $username, $department, $cabinetID );
print_r($ddefs);
*/
/*
echo "\nInbox:\n";
$username = "admin";
$inboxUser = "admin";
$department = "client_files";
$folder = "";
$fileList = buildInboxPath( $username, $department, $inboxUser, $folder );
echo "Inbox path: ";
print_r($fileList);
echo "\n";
*/
/*
echo "Inbox file list\n";
$fileList = getInboxFileList($username, $department, $inboxUser, $folder);
print_r($fileList);
echo "\n";
*/
/*
echo "\nsafeFileName:\n";
$encodedFile = file_get_contents("a.txt");
print_r($encodedFile);
$path = uploadToInbox($username, $department, $inboxUser, $folder, "Accounts_Payable.txt", $encodedFile);
print_r($path);
echo "\n\n";
*/
/*
echo "getCabinetInfo:\n";
$username="admin";
$department="client_files";
$cabinetID=4;
$result=getCabinetInfoXML($username, $department, $cabinetID, $db_docutron);
print_r($result);
echo "\n\n";
*/
/*
include_once '../lib/folderObj.inc.php';
$a = new folderObj('client_files', 4, 22);
print_r($a);
*/
/*
echo "moveDocumentBetweenCabs:\n";
$userName = "admin";
$department = "client_files";
$cabinetID = 5;
$docID = 1;
$subfolderID = 58;
$destCabinetID = 4;
$destDocID = 33;
$a = moveDocumentBetweenCabs($userName, $department, $cabinetID, $docID, $subfolderID, $destCabinetID, $destDocID, false);
print_r($a);
echo "\n\n";
*/

/*
echo "moveFolderBetweenCabs:\n";
$userName = "admin";
$department = "client_files";
$cabinetID = 5;
$docID = 1;
$destCabinetID = 4;
$a = moveFolderBetweenCabs($userName, $department, $cabinetID, $docID, $destCabinetID, true);
print_r($a);
echo "\n\n";
*/
/*
echo "searchAudit:\n";
$department = "client_files";
$searchTerms = array();
$dateTimeArr = array( "> '2007-02-22'");
$a = searchAudit($department, $searchTerms, $dateTimeArr);
print_r($a);
echo "\n\n";
*/
/*
echo "getImportDirList:\n";
$userName = "admin";
$ret = getImportDirList($userName);
print_r($ret);
*/
/*
echo "importDirectory:\n";
$userName = "admin";
$department = "client_files";
$cabinetID = 5;
$opCabinetID = 7;
$opDocID = 5;
$directory = "1002";
$maxFileSize = 10;
$fileExtensions = array("txt", "rtf");
$generateTemplate = false;
$ret = importDirectory($userName, $department, $cabinetID, $opCabinetID, $opDocID, $maxFileSize, $fileExtensions, $generateTemplate, "move");
print_r($ret);
echo "\n\n";
*/

/*
echo "filenameAdvSearch:\n";
$userName = "admin";
$department = "client_files";
$cabinetID = 1;
$filename = "1.tif";
$res = filenameAdvSearch($userName, $department, $cabinetID, $filename);
print_r($res);
*/
/*
echo "getFileResultSet:\n";
$userName = "admin";
$department = "client_files";
$cabinetID = 1;
$resultID = "kyhqhsapnrldxi";
$startIndex = 0;
$numberToFetch = 50;
$res = getFileResultSet($department, $cabinetID, $resultID, $startIndex, $numberToFetch, $userName);
print_r($res);
*/

echo "checkOut File:\n";
$userName = "admin";
$department = "client_files";
$cabinetID = 1;
$fileID = 417;
$encFileData = '';
$retVal = checkOutFile($userName, $department, $cabinetID, $fileID, $encFileData);
echo "encFileData: $encFileData\n";
?>
