<?php
require_once 'treenoServices.php';
$treenoService = new treenoServices;
$userName="admin";
$password="No4-scot";
$deptDisplayName="ADP";
$cabDisplayName="ADPCL";
$userName="fabaroa";
$password="Treeno-1";
$deptDisplayName="Demo";
$cabDisplayName="Fred";
$folderID=1;
echo "login results:";
$passKey=$treenoService->login($userName,$treenoService->md5String($password));
echo $passKey."<br>";
/*
echo "***department results:";
$results=$treenoService->getDepartmentList($passKey);
html_show_array($results);
echo "<p>***docType results:";
$results=$treenoService->getCompleteDocTypeList($passKey,"Demo");
html_show_array($results);
echo "</p><p>***docType filter results:";
$results=$treenoService->getCompleteDocTypeList($passKey,"Demo","Fred");
html_show_array($results);
echo "<br>***cabinet results:";
$results=$treenoService->getCabinetAccessList($passKey,"Demo");
html_show_array($results);
echo "</p><p>***docType filter results:";
$results=$treenoService->getDocTypeDetails($passKey,$deptDisplayName,  $cabDisplayName, $folderID);
html_show_array($results);
$cabDisplayName="Fred";
echo "</p><p>***docType filter results:";
$results=$treenoService->isDocumentType($passKey,$deptDisplayName);
echo $results."<br>";
$results=$treenoService->isDocumentType($passKey,$deptDisplayName,$cabDisplayName);
echo $results."<br>";
$cabDisplayName="Fred Tab View";
echo "</p><p>****getSaveTabs results:";
$results=$treenoService->getSavedTabs($passKey,$deptDisplayName,$cabDisplayName);
html_show_array($results);
echo "</p><p>***getTabList results:";
$results=$treenoService->getTabList($passKey,$deptDisplayName,$cabDisplayName,$folderID);
html_show_array($results);
echo "</p><p>***getCabinetDataTypeDefs results:";
$results=$treenoService->getCabinetDataTypeDefs($passKey,$deptDisplayName,$cabDisplayName);
html_show_array($results);
echo "</p><p>***searchTopLevel results:";
$results=$treenoService->searchTopLevel($passKey,$deptDisplayName,"fred");
html_show_array($results);

echo "</p><p>***searchCabinet Indicies results:";
$searchArr=array();
$searchArr['index8']="fred";
//$searchArr['index1']="fred";
$results=$treenoService->searchCabinetIndicies($passKey,$deptDisplayName,$cabDisplayName,$searchArr);
html_show_array($results);
echo "</p><p>***searchCabinetDetails:";
$tempTable=NULL;
$subfolder=NULL;
$date=NULL;
$date2=false;
$who=NULL;
$context='fred';
$contextbool=false;
$notes=NULL;
$filename=NULL;
$results=$treenoService->searchCabinetDetails($passKey,$deptDisplayName,$cabDisplayName,$tempTable, $subfolder,$date,$date2,$who,$context,$contextbool,$notes,$filename);
html_show_array($results);
/*
echo "</p><p>*** folderResults:";
$tempTable=$results['tempTable'];
$numberToFetch=$results['resultCount'];
echo ($tempTable." count:".$numberToFetch."<br>");
$results=$treenoService->getFolderResults($passKey,$deptDisplayName,$cabDisplayName,$tempTable,0,$numberToFetch);
html_show_array($results);

echo "</p><p>*** fileResults:";
$tempTable=$results['tempTable'];
$numberToFetch=$results['resultCount'];
echo ($tempTable." count:".$numberToFetch."<br>");
$results=$treenoService->getFileResults($passKey,$deptDisplayName,$cabDisplayName,$tempTable,0,$numberToFetch);
html_show_array($results);
/*
echo "</p><p>*** tabfileResults:";
$subfolder="Agreement_of_Participation_1";
$results=$treenoService->getTabFileResults($passKey,$deptDisplayName,$cabDisplayName,$subfolder,$tempTable,0,$numberToFetch);
html_show_array($results);
/*
$searchArr=array();
$searchArr['description']="1";
//$searchArr['Date']="2007";
$docTypeDisplayName="Account Education";
foreach ($results['tempTable'] as $index=>$row) {
	echo "</p><p>***searchDocumentTypes results2: ".$index."<br>";
	$tempTable=$row;
	$cabDisplayName=$index;
	$results=$treenoService->searchDocumentTypes($passKey,$deptDisplayName,$cabDisplayName,$docTypeDisplayName,$searchArr,$tempTable);
	//html_show_array($results);
	html_show_array($results);
}
$searchArr=array();
$searchArr['Screening Date']="1";
//$searchArr['Date']="2007";
$docTypeDisplayName="45 day screening";
$cabDisplayName="";

echo "</p><p>***searchDocumentInFolder results all cabs<br>";
$doc_id=2;
$results=$treenoService->searchDocumentInFolder($passKey,$deptDisplayName,$cabDisplayName,$docTypeDisplayName,$searchArr,$doc_id);
//html_show_array($results);
html_show_array($results);
$startIndex=0;
$tempTable=$results['tempTable'];
$numberToFetch=$results['resultCount'];
echo "</p><p>***getDocumentTypes $tempTable,$startIndex,$numberToFetch<br>";
$results=$treenoService->getDocumentResults($passKey,$deptDisplayName,$cabDisplayName,$tempTable,$startIndex,$numberToFetch);
html_show_array($results);

echo "</p><p>***searchDocumentTypes results all cabs<br>";
$results=$treenoService->searchDocumentTypes($passKey,$deptDisplayName,$cabDisplayName,$docTypeDisplayName,$searchArr);
//html_show_array($results);
html_show_array($results);
$startIndex=0;
$tempTable=$results['tempTable'];
$numberToFetch=$results['resultCount'];
echo "</p><p>***getDocumentTypes $tempTable,$startIndex,$numberToFetch<br>";
$results=$treenoService->getDocumentResults($passKey,$deptDisplayName,$cabDisplayName,$tempTable,$startIndex,$numberToFetch);
html_show_array($results);
$fileID=124;
echo "</p><p>***getVersionList <br>";
$results=$treenoService->getVersionList($passKey,$deptDisplayName,$cabDisplayName,$fileID);
html_show_array($results);
echo "</p><p>***getas Zip <br>";
$results=$treenoService->getAsZip($passKey,$deptDisplayName,$cabDisplayName,$fileIDArray,$fileName);
echo $results."<br>";
$fileIDArray=array();
$fileName="fred";
foreach ($results as $index=>$row){
	$fileIDArray[]=$index;
	echo $index."*<br>";
}
echo "</p><p>***getas PDF <br>";
$results=$treenoService->getAsPDF($passKey,$deptDisplayName,$cabDisplayName,$fileIDArray);
echo $results."<br>";
*/
$fileID=124;
$encFileData='';
echo 'checkOutFile<br>';
$results=$treenoService->checkOutFile($passKey,$deptDisplayName,$cabDisplayName,$fileID, $encFileData);
html_show_array($results);
echo $encFileData."<br>";
echo "**<br>done";
?>