<?php  
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
$dept='client_files'; //what department?
$cabinetID=8; //need ID from departments table in db client_filesxxx
$docdir="C:/Treeno/testfiles/"; //this is the location where the file directories (CL dir and PL dir) reside
$userName="admin";
$tabID=0;
$db_doc = getDbObject ('docutron');
$db_dept = getDbObject ($dept);
$filename="dummydata.txt";
$indices=array("voucher_number","invoice_number","vendor","amount");
$handle = fopen($filename, "r");


while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)
{
	print_r($data);
	$indiceArr=array();
	$indiceArr[$indices[0]]=$data[0];   //"voucher_number",        
	$indiceArr[$indices[1]]=$data[1];   //"invoice_number",         
	$indiceArr[$indices[2]]=$data[2];     //"vendor",    
	$indiceArr[$indices[3]]=$data[3];     //"amount",      
	$docID=createCabinetFolder($dept, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept);
	$realfile=($docID % 140)+1;
	$encfilename=$docdir.$realfile.".tif";
	$cabfilename=$realfile.".tif";
	echo $cabfilename."\n".$encfilename."\n\n";
	$handle2 = fopen($encfilename,'rb');
	$encodedFile = fread($handle2,filesize($encfilename));
	fclose($handle2);
	$fileID = uploadFileToFolder($userName, $dept, $cabinetID, $docID, $tabID, $cabfilename, $encodedFile, $db_doc, $db_dept);
}
fclose($handle);

?>