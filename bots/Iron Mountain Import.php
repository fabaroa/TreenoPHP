<?php
chdir("C:\Treeno\treeno\bots");
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
$department='client_files7';
$cabinetID=20;
$userName="dannym";
$path="C:\Treeno\data\IronMountain\Test";
$toPath="C:\Treeno\data\Scan";
$passkey=0;
moveFiles($path,$toPath,$department,$passKey,$cabinetID,$userName);
echo "\r\n Import Complete";



function moveFiles($path,$toPath,$department,$passKey,$cabinetID,$userName) {
	$db_doc = getDbObject ('docutron');
	$db_dept = getDbObject ($department);
	$subfolderName= array("Documentation","Customer Credit");
	if(!is_dir($toPath)) {
		if(!mkdir($toPath,0777)) {
			error_log( $toPath );
		}
	}

	$hd = opendir($path);
	while(false !== ($file = readdir($hd))) {
		if(is_file($path."/".$file)) {
			if($file != "ERROR.txt" && $file != "INDEX.DAT") {

				$path_parts = pathinfo($file);
				$valueARR = explode ( "_" , $path_parts['basename']);
print_r("\n".'Path Parts:'.$path_parts);
				$id_number = $valueARR[0];
				$classificationCode = trim(strtoupper($valueARR[1]));
				$garbage = $valueArr[3];
				//drop file extension from index values
				//$removeExt = explode (".",$classificationCode);
				//$ext = array_pop($removeExt);
				//$docType = implode(".",$removeExt);
				//$classificationCode = $docType;
				//create a folder
				$docID = 0;
				$indiceArr = array("ID"=>$id_number);

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
					$tabFileList = getTableInfo($db_dept, 'Iron_Mountain_files', 
						array('id', 'filename', 'parent_filename','file_size'), 
						array(	'display' => (int)1, 
							'deleted' => (int)0, 
							'doc_id' => (int)$docID, 
							'filename' => 'IS NULL', 
							'subfolder' => $classificationCode),
						    'queryAll',
						array('ordering' => 'ASC')
					);
					echo ("\n".'did I find a file_id'.$tabFileList."\n");
			    if (count($tabFileList)==0) {
				//get document_table_name based on document type
					switch($classificationCode)
					{
						case 'DOCUMENTATION':
						$documentName = 'document14';
						echo("\n".'Doc Type:'.$classificationCode);
						break;
						case 'CUSTOMERCREDIT':
						$documentName = 'document15';
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
						echo "back from CreateCabinetFolder\n";
						$deptID=7;
						$toUniquePath=getUniqueDirectory($toPath);
						$fp = fopen($toUniquePath.'/INDEX.DAT', 'w');
						fwrite($fp, $deptID.' '.$cabinetID.' '.$docID.' '.$file_id);
						fclose($fp);
		
						$fpath = checkFile($toUniquePath,$file);
						echo "to:".$fpath."\n";
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
}
function checkFile($toPath,$file) {
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

?>
