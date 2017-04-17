<?php  
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
/*
below you will put in the department cabinet and csv information
*/
$dept='client_files14';
$DeptID = "14";
$toScan = "C:\\treeno\\data\\Scan\\";
$userName="TreenoSupport357";
$cabinetID=4;
$cab='Johnson_Electric_WF_Chart';
$key='invoice'; //unique value for each record
//get file or files to update
$filename="C:\\test.csv";
//********************************************
$handle = fopen("$filename", "r");
$i=1;
$db_doc = getDbObject ('docutron');
$db_dept = getDbObject ($dept);
while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)
{
	if ($i) {
		print_r($data);
		$indiceArr['region'] = $data[0];
		$indiceArr['title'] = $data[1];
		$indiceArr['username'] = $data[2];
		/*
		$query = "select doc_id from ".$cab." where deleted=0 and ".$key." = '".$indiceArr[$key]."';";
		print_r($indiceArr);
		$results = $db_dept->queryAll($query);
		if (count($results)>0) {
			$doc_id=$results[0]['doc_id'];
			echo ("Update\n");
			updateCabinetFolder($db_dept, $cabinetID, $doc_id, $indiceArr, $userName);
		} else {
			*/
			echo ("New Folder\n");
			$doc_id = createCabinetFolder($dept, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept);
		//}
		/****************************************
		//Used for importing documents
		$tmpUniqueDir = getUniqueDirectory($toScan);
		if (!file_exists ($tmpUniqueDir)) {
			mkdir($tmpUniqueDir);
		}
		$barcodeString = "$DeptID $cabinetID $doc_id";
		dbgOut("copyCabLog", "\tBarcode for dest folder ($barcodeString)\n");
		$index = fopen($tmpUniqueDir.'/INDEX.DAT', 'w');
		//echo "DBG: $index file getting barcode ($barcodeString)\n";
		fwrite($index, $barcodeString);
		fclose($index);
		$cmd = "move /Y \"".$tobefiled."\" \"".$tmpUniqueDir."\\\"";
		shell_exec($cmd);
		******************************************/
	}
	++$i;
}
fclose($handle);
rename($filename,$filename.".done");
echo "\ndone\n";
function modDate($old_date)
{
	$old_date_timestamp = strtotime($old_date); 
	$new_date = date('Y-m-d', $old_date_timestamp); 
	echo $old_date." vs. ".$new_date."\n";   
	return $new_date;
}
?>