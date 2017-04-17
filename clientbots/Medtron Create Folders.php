<?php
require_once '../db/db_common.php';
require_once '../lib/webServices.php';

//----edit for correct department----------
$dept='client_files2002';
//-----------------------------------------



$deptID=substr($dept,12);
$cabinetID=2;
$cab = "Permissions";
$userName="admin";
$db_doc = getDbObject ('docutron');
$db_dept = getDbObject ($dept);

//csv file needs to be created from database query ahead of running script
$handle = fopen("MedtronPermissions.txt", "r");

$linecnt=0;


while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)
{
	++$linecnt;
	echo $linecnt."\n";
	print_r($data);
	//this needs to be modified depending on the docstar data and which fields will be indices in the docstar cabinet
	
		$indiceArr=array();
		$indiceArr['group_']=$data[0];
		$indiceArr['username']=$data[1];
		$indiceArr['pack_type']=$data[2];
		$indiceArr['pr_loc']=$data[3];
		$indiceArr['workflow_step']=$data[4];
		
			$doc_id = createCabinetFolder($dept, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept);
		
	
}
fclose($handle);



?>
