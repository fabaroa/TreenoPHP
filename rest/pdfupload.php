<?php
require_once '../lib/settings.php';
require_once '../db/db_common.php';
require_once '../lib/utility.php';
require_once '../settings/settings.php';
require_once '../lib/cabinets.php';
require_once '../workflow/node.inc.php';

$tmpDir = $DEFS['TMP_DIR'].'/pdf_upload/';
error_log("PDF Form submitted");
if (!file_exists($tmpDir)) {
	mkdir($tmpDir);
}

$str = $_GET['str'];
$batchDir = $tmpDir.'/'.$str;
if(!file_exists($batchDir)) {
	mkdir($batchDir);
}
//error_log("PDF1");
$pdfFile = $batchDir.'/1.pdf';
$xfdfFile = $batchDir.'/out.xfdf';

//if(!empty($_GET['save'])) {
//	$fileName = $pdfFile;
//} else {
	$fileName = $xfdfFile;
//}
//error_log("PDF2");
$fd = fopen($fileName, 'w+');
fwrite($fd, file_get_contents('php://input'));
fclose($fd);

#echo 'Please close this window.';

header('Content-type: application/pdf');
readfile($DEFS['DOC_DIR'].'/images/blank.pdf');
//error_log("PDF3");
$allDone = false;
if($fileName != $pdfFile) {
	for($i = 0; $i < 100; $i++) {
//error_log("XFDF: $xfdfFile");
		if (file_exists($xfdfFile)) {
//error_log("XFDF-exists: $xfdfFile");
			$allDone = true;
			break;
		} else {
			sleep(1);
		}
	}
}
//error_log("PDF4");
if($allDone) {
	$xmlDoc = new DOMDocument();
	$xmlDoc->load($xfdfFile);
	$fields = $xmlDoc->getElementsByTagName('field');
	$fieldArr = array ();
	for($i = 0; $i < $fields->length; $i++) {
		$field = $fields->item($i);
		$value = $field->firstChild;
		if($value->nodeName == 'value') {
			$fieldArr[$field->getAttribute('name')] = $value->nodeValue;
		}
	}
	$db_doc = getDbObject('docutron');
//error_log("PDF5");

	if($fieldArr) {
//		$pdfFile = "/docs/tmp/pdf_upload/test.pdf";
		createFolderAssignAndFile($fieldArr['barcode'], $fieldArr, $batchDir, $xfdfFile, $db_doc);
	}
} elseif ($fileName == $pdfFile) {
//error_log("PDF6bad");
	#echo ' Error Processing File.';
}

function mergePDF($batchDir, $pdfFile, $xfdfFile) {
	$outputName = $batchDir . '/'.$pdfFile;
	$escPdfName = escapeshellarg($batchDir ."/1.pdf");
	$escXfdfName = escapeshellarg($xfdfFile);
	$escOutputName = escapeshellarg($outputName);
	$cmd = "c:/Treeno/apps/bin/pdftk.exe $escPdfName fill_form $escXfdfName output $escOutputName";
error_log("PDfFOrm CMD: $cmd");
	shell_exec($cmd);
	//`$cmd`;
	//error_log($out);
	if(file_exists($outputName)) {
error_log("success");
//		unlink($escPdfName);
//		unlink($pdfFile);
		unlink($xfdfFile);
	}
}

function createFolderAssignAndFile($barcodeNum, $fieldArr, $batchDir, $xfdfFile, $db_doc) {
	global $DEFS;
	$department = '';
	$cabinet = '';
	$barcodeInfo = getRealBarcode($barcodeNum, $department, $cabinet, $db_doc);
	$barcodeArr = explode(' ', $barcodeInfo);
	$departmentID = $barcodeArr[1];
	$cabinetID = $barcodeArr[2];
	$workflowID = $barcodeArr[3];
	$userID = $barcodeArr[4];
	$userName = getTableInfo($db_doc, 'users', array('username'), 
			array('id' => $userID), 'queryOne');
	$db_dept = getDbObject($department);
	$indices = getCabinetInfo($db_dept, $cabinet);
	$gblStt = new GblStt($department, $db_doc);
	$folderIndices = array ();
	foreach($indices as $myIndex) {
		if(isset($fieldArr[$myIndex])) {
			$folderIndices[$myIndex] = $fieldArr[$myIndex];
		}
	}
	$tempTable = '';
	$docID = createFolderInCabinet($db_dept, $gblStt, $db_doc, $userName, $department, $cabinet, 
			array_values($folderIndices), array_keys($folderIndices), $tempTable);
	assignWorkflow_pdfupload($db_doc, $db_dept, $department, $cabinet, $docID, $workflowID, $userName);

//need to get PDF form from PDF_Form_Submitter cabinet
	$formtable = getTableInfo($db_dept, 'departments', array('real_name'),
		array('departmentname' => 'PDF Form Submitter'), 'queryOne');
//error_log("barcode:$barcodeNum");
	$formInfo = getTableInfo($db_dept, $formtable, array(), array('barcode_id' => $barcodeNum, 'deleted' => 0),
		'queryRow');
//error_log($formInfo['location']);
	//get filename and parent filename in case they're versioned
	$formFilenames = getTableInfo($db_dept, $formtable.'_files', array(), 
		array('doc_id' => $formInfo['doc_id'],'deleted' => 0,'display'=>1),'queryRow');

	$formSRC = $DEFS['DATA_DIR']."/".str_replace(' ','/',$formInfo['location'])."/".$formFilenames['filename'];

	//set new pdfFile
	$pdfFile = $formFilenames['parent_filename'];

	$formDST = $batchDir."/1.pdf";

error_log("SRC: $formSRC");
error_log("DST: $formDST");
	if(!copy($formSRC,$formDST)){
		error_log("failed to copy file: $formSRC");
	}else{
		error_log("File copied: $formSRC to $formDST");
	}


	mergePDF($batchDir, $pdfFile, $xfdfFile);
//error_log("mergePDF: $pdfFile");
	unlink($batchDir."/1.pdf");
	filePDF($batchDir, $departmentID, $cabinetID, $docID);
}

function filePDF($batchDir, $departmentID, $cabinetID, $docID) {
	global $DEFS;
	$fd = fopen($batchDir . '/INDEX.DAT', 'w+');
	fwrite($fd, "$departmentID $cabinetID $docID");
	fclose($fd);
	$destDir = $DEFS['DATA_DIR'].'/Scan/'.basename($batchDir);
	if(mkdir($destDir)){
		error_log("made dir");
	}else{
		error_log("didn't make dir");
	}
	$lockFile = $destDir.'/.lock';
	touch($lockFile);
	error_log("batchDircopy: $batchDir");
	error_log("destDirCopy: $destDir");
	copyDir($batchDir, $destDir);
	unlink($lockFile);
//wjt	delDir($batchDir);
}

function getRealBarcode($barcodeNum, &$department, &$cabinet, $db_doc) {
	$barcodeID = (int) $barcodeNum;
	if($barcodeID == 0) {
		return 0;
	}
	$barcodeInfo = getTableInfo($db_doc, 'barcode_reconciliation',
		array(), array('id' => $barcodeID), 'queryRow');

	if($barcodeInfo) {
		$barcodeLookup = array (
			'id'            => $barcodeID,
			'department'    => $barcodeInfo['department']
		);
		$department = $barcodeInfo['department'];
		$cabinet = $barcodeInfo['cab'];
		$username = $barcodeInfo['username'];
		$realBarcode = $barcodeInfo['barcode_info'];
	} else {
		$department = getTableInfo($db_doc, 'barcode_lookup',
			array ('department'), array ('id' => $barcodeID), 'queryOne');

		if($department) {
			$db_dept = getDbObject ($department);
			$barcodeInfo = getTableInfo($db_dept, 'barcode_history',
				array (), array ('barcode_rec_id' => $barcodeID), 'queryRow');
			$cabinet = $barcodeInfo['cab'];
			$username = $barcodeInfo['username'];
			$realBarcode = $barcodeInfo['barcode_info'];
		} else {
			$realBarcode = '';
		}
	}
	return $realBarcode;
}

function assignWorkflow_pdfupload($db_doc, $db_dept, $department, $cabinet, $docID, $workflowID, $userName) {
	$wfDocID = addToWorkflow($db_dept, $workflowID, $docID, 0,
		$cabinet, $userName);
	$cabDispName = getTableInfo($db_dept, 'departments', array('departmentname'),
		array('real_name' => $cabinet), 'queryOne');
	$stateNodeObj = new stateNode($db_dept, $department,
		$userName, $wfDocID, $workflowID, $cabinet, $cabDispName, $docID, $db_doc);
	$stateNodeObj->notify();
}

?>
