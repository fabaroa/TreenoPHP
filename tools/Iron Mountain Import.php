<?php
//VARIABLES THAT NEED TO BE CHANGED ARE BELOW. MAKE SURE THIS SCRIPT IS RUN FROM THE 'C:\Treeno\treeno\bots' DIRECTORY
//$cabinetID: ID from the 'departments' table for the cabinet that you're uploading to.
//$path: This needs to be set to the directory the files are sitting in.
//$deptID: This needs to be set to their client_files# for example $deptID = 1 would be for 'client_files1'. 0 = client_files.
//$documentName: This needs to be updated in the Switch for the classification code. You need to grab the value from the clients 'document_type_defs' table.
//$pathTo: This needs to be set to the path of the temp directory you create to hold the files that won't be imported.
//$csv: This needs to be set to the path that the CSV file sits in. 
//chdir("C:\Treeno\treeno\bots");
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
$department='client_files';
$cabinetID=4;
$userName="admin";
$path="C:\Data";
//$path="C:\im";
$toPath="C:\Treeno\data\Scan";
$passkey=0;

//moveFiles($path,$toPath,$department,$passKey,$cabinetID,$userName);
//echo "\r\n Import Complete";



function moveFiles($path,$toPath,$department,$passKey,$cabinetID,$userName) {
	$db_doc = getDbObject ('docutron');
	$db_dept = getDbObject ($department);
	$subfolderName= array("Documentation","Customer Credit","Credit");
	if(!is_dir($toPath)) {
		if(!mkdir($toPath,0777)) {
			error_log( $toPath );
		}
	}
}
	
function stringrpl($x,$r,$str) 
{ 
	$out = ""; 
	$temp = substr($str,$x); 
	$out = substr_replace($str,"$r",$x); 
	$out .= $temp; 
	return $out; 
}

function checkFile($toPath,$file) 
{
	$fpath = $toPath."/".$file;
	if((is_file($fpath))) {
		$ct = 1;
		$p = $fpath."-".$ct;
		while(is_file($p)) {
			$ct++;
			$p = $fpath."-".$ct;
		}
		$fpath = $p;
	}
	return $fpath;
} 


//CSV LOOP	

$db_dept = getDbObject ($department);
$db_doc = getDbObject ('docutron');
//
//$csv =fopen("C:\\Treeno\\temp\\IronMountain.csv", "r");


//while (($data = fgetcsv($csv, 8000, "|")) !==FALSE)//
//{
//	$num = count($data);
//	$row++;
//	//for($x=0;$x< $num;$x++){echo $data[$x] . "\n";}
//	$indiceArr = array("contract_number"=>$data[0]);
//	list($resultID, $numResults) = searchCabinet($department, (int)$cabinetID, $indiceArr, $userName);
//	if ($numResults==0)
//	{
//		$indiceArr = array("contract_number"=>$data[0],"ccan"=>$data[1],"customer_name"=>$data[2]);
//		$docID = createCabinetFolder($department, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept);
//	}
	//echo("\n"."Record already exists, skipping folder creation."."\n");
	
//}

echo $path;
	//FILE LOOP
	$hd = opendir($path);

	while(false !== ($file = readdir($hd))) {
	echo $path."/".$file;

		if(is_file($path."/".$file)) {
			if($file != "ERROR.txt" && $file != "INDEX.DAT") {

				$path_parts = pathinfo($file);
				$valueARR = explode ( "_" , $path_parts['basename']);
				print_r("\n".'Path Parts:'.$path_parts);
				$id_number = $valueARR[0];
				$classificationCode = trim(strtoupper($valueARR[1]));
				$garbage = $valueArr[3];
				if(strlen($id_number)!== 13)
				{
					$pathTo="C:\DataTemp";
					//echo("\n".$path."\\".$file." IS INVALID");
					$invFile = $path."\\".$file;
					$invFileDest = $pathTo."\\".$file;
					rename($invFile, $invFileDest);
					continue;
				}
				$id_number = stringrpl(3,"-",$id_number);
				$id_number = stringrpl(11,"-",$id_number);
				echo("\n".$id_number."\n");
				//drop file extension from index values
				//$removeExt = explode (".",$classificationCode);
				//$ext = array_pop($removeExt);
				//$docType = implode(".",$removeExt);
				//$classificationCode = $docType;
				//create a folder
				$docID = 0;
				$indiceArr = array("Contract_Number"=>$id_number);

				list($resultID, $numResults) = searchCabinet($department, (int)$cabinetID, $indiceArr, $userName);

				if ($numResults==0){
					$docID = createCabinetFolder($department, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept);
				} else {
					$results = getResultSet($department, $cabinetID, $resultID, 0, 1,"admin");
										foreach($results as $docID => $myResult) {
					}
				}
				$results=$db_dept->query("drop table ".$resultID);
				echo ('docID:'.$docID."\n");
				echo ('Subfolder:'.$classificationCode);
				//create a fileid for doctype=1
				if ($docID){
					$tab=$classificationCode;
					//$tabFileList = getTableInfo($db_dept, 'Iron_Mountain_files',
					$tabFileList = getTableInfo($db_dept, 'im_test_files', 
						array('id', 'filename', 'parent_filename','file_size'), 
						array(	'display' => (int)1, 
							'deleted' => (int)0, 
							'doc_id' => (int)$docID, 
							'filename' => 'IS NULL', 
							'subfolder' => $classificationCode),
						    'queryAll',
						array('ordering' => 'ASC')
					);
					//echo ("\n".'did I find a file_id'.$tabFileList."\n");
			    if (count($tabFileList)==0) {
				//get document_table_name based on document type
					switch($classificationCode)
					{
						case 'DOCUMENTATION':
						$documentName = 'document2';
						//$documentName = 'document5';
						echo("\n".'Doc Type:'.$classificationCode);
						break;
						case 'CUSTOMERCREDIT':
						$documentName = 'document3';
						//$documentName = 'document6';
						echo("\n".'Doc Type:'.$classificationCode);
						break;
						case 'CREDIT':
						$documentName = 'document4';
						//$documentName = 'document7';
						echo("\n".'Doc Type:'.$classificationCode);
						break;
						default:
						echo("\n".'Error with Doc Type');
					}
						$indices=array();
						$file_id = createDocumentInfo($department,$cabinetID,$docID,$documentName,$indices,$userName, $db_doc);
			    } else {
			    	$file_id = $tabFileList[0]['id'];
			    }
					if ($file_id) {
						//echo "back from CreateCabinetFolder\n";
						$deptID=0;
						$toUniquePath=getUniqueDirectory($toPath);
						$fp = fopen($toUniquePath.'/INDEX.DAT', 'w');
						fwrite($fp, $deptID.' '.$cabinetID.' '.$docID.' '.$file_id);
						fclose($fp);
		
						$fpath = checkFile($toUniquePath,$file);
						//echo "to:".$fpath."\n";
						rename($path."/".$file,$fpath);
					}
				}
			} else {
				unlink($path."/".$file);
			}
		} else {
			if($file != "." && $file != "..") {
			
				moveFiles($path."/".$file,$toPath,$department,$passKey,$cabinetID,$userName);	
				rmdir($path."/".$file);
				
				if(file_exists("$toPath/$file")) {
					//uncomment this line if attachments include barcodes.
					//copy("$toPath/$file", "{$DEFS['DATA_DIR']}/Scan");
				}
			}
		}			
	}


?>
