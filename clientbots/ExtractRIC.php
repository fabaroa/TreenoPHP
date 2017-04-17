<?php
//chdir('/var/www/html/tools');
if(isset($_SESSION) and isset($_SESSION['DEFS'])) {
	$DEFS = $_SESSION['DEFS'];
} else {
	$incDefs = '';
	$incDefs = 'C:/Treeno/config/DMSRIC.DEFS';
	if($incDefs) {
		$lines = file($incDefs);
		foreach($lines as $line) {
			if($line{0} != '#') {
				if( strpos($line, "LOGO") !== false ) {
					$t = substr( $line, 0, strpos($line, "="));
					$t = trim($t);
					$value = substr( $line, strpos($line, "=") + 1);
					$value = trim($value);
					$DEFS[$t] = eval("return ".$value);
				}
				else
			  {
					if(substr(PHP_VERSION, 0, 1) >= 5 and substr(PHP_VERSION, 2, 3) >= 3)
					{
						$t = preg_split('%=%', trim($line));//preg functions allow any non-alphanumeric character as regex delimiters
					}
					else
					{
						$t = explode('=', trim($line),2);
					}
					
					if (isset ($t[1])) {
						$DEFS[trim($t[0])] = trim($t[1]);
					}
				}
			}
		}
		if(isset($DEFS['USE_SECURE_PASSWORDS']) && ($DEFS['USE_SECURE_PASSWORDS'] == '1'))
		{
			$DEFS['DB_PASS'] = base64_decode($DEFS['DB_PASS']);
		}
		else{
			$DEFS['USE_SECURE_PASSWORDS'] = '0';
		}
		$_SESSION['DEFS'] = $DEFS;
	}
}
include '../db/db_common.php';
include '../lib/settings.php';
include '../lib/webServices.php';
global $DEFS;
echo "start<br>\n";
// set this to the directory where files will be copied
$home="E:\\Treeno_Extracted";
// set this to the directory where files will be zipped
//$zipToPath = "/docs/client_files279/personalInbox/ewing/";
$department='client_files';
// set what cabinet to be extracted
$db_dept = getDbObject($department);
$cabinets=$db_dept->queryAll("select real_name from departments");
$db_dept->disconnect();
print_r($cabinets);
$limit=" limit 2";
$limit="";
$px=0;
// set department & cabinets to extract
// create array
if( !is_dir( $home ) )
//create directory with 777 perms
mkdir( $home, 0777 );
//	$department = $row['real_department'];
if( !is_dir( $home."/".$department ) )
//create directory with 777 perms
mkdir( $home."/".$department, 0777 );
echo $department."\n";
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
$fp = fopen( $home."/".$department."/manual_".$department.'_Files.txt', 'a' );
$doc_ids=array();

foreach($cabinets as $cabinetRes){
	$cabinet = $cabinetRes["real_name"];
	echo $cabinet."\n";
	if( !is_dir( $home."/".$department."/".$cabinet ) )
		//create directory with 777 perms
		mkdir( $home."/".$department."/".$cabinet, 0777 );
	$db_dept = getDbObject($department);
	$cabinetIndices = getTableColumnInfo ($db_dept, $cabinet);
	$db_dept->disconnect();
	if ($cabinet=="DocStar")
	{
		continue;
		$doc_idQ="select doc_id from ".$cabinet." where deleted=0 and doc_id >= 120124";
	}
	else
	{
		$doc_idQ="select doc_id from ".$cabinet." where deleted=0 and doc_id >= 66153 order by doc_id";
	}
		
	$db_dept = getDbObject($department);
	$doc_ids=$db_dept->queryAll($doc_idQ);
	$db_dept->disconnect();
	
	foreach ($doc_ids as $doc_idTmp)	{
		$doc_id = $doc_idTmp['doc_id'];
		echo $doc_id."\n";
		$queryA="select Distinct ".$cabinet.".* from ".$cabinet.",".$cabinet."_files where ".$cabinet.".doc_id='".$doc_id."' and ".$cabinet."_files.display=1 and ".$cabinet."_files.deleted=0 and ".$cabinet."_files.doc_id=".$cabinet.".doc_id and ".$cabinet.".deleted=0".$limit;
		echo $queryA."\n";
		$db_dept = getDbObject($department);
		$folders=$db_dept->queryAll($queryA);
		foreach ($folders as $folder) {
			echo "Now working on ".$folder['doc_id']."*\n";
			$queryB="select * from ".$cabinet."_files where display=1 and deleted=0 and doc_id=".$folder['doc_id']." order by id,subfolder";
			//echo $queryB."\n";
			$fileNames=$db_dept->queryAll($queryB);
			$folderMetaData="";
			foreach($cabinetIndices as $indexValue){
				if ($indexValue != "doc_id" && $indexValue != 'location' && $indexValue != 'deleted') $folderMetaData=$folderMetaData.$folder[$indexValue]."__";
			}
			$nonFileChar = array(" ", "'", "$", "&", "<", ">", ":", "I", '"', "/","\\","|","?","*",".","(",")",";");
			$folderMetaData = str_replace($nonFileChar, "_", $folderMetaData);
			if (strlen($folderMetaData)>180) $folderMetaData=substr($folderMetaData, 0, 180);  

			if( !is_dir( $home."/".$department."/".$cabinet."/".$folderMetaData ) )
			//create directory with 777 perms
			mkdir( $home."/".$department."/".$cabinet."/".$folderMetaData, 0777 );
			foreach($fileNames as $fileName){
				if( !is_dir( $home."/".$department."/".$cabinet."/".$folderMetaData."/".$fileName['subfolder'] ) )
				//create directory with 777 perms
				mkdir( $home."/".$department."/".$cabinet."/".$folderMetaData."/".$fileName['subfolder'], 0777 );
				$message="";
				if ($fileName['filename'] == NULL){
					//is there a document type? get name and meta data write to file
					if ($fileName['document_table_name']) {
						$documentTypeMetadata=getDetailedDocumentIndexList($db_dept, $fileName['document_id'], $fileName['document_table_name']);
						$indexContents="";
						foreach ($documentTypeMetadata as $temp) {
							$indexContents=$indexContents.$temp['arb_field_name']."=".$temp['document_field_value']."\n";
						}
						$message = "doctype:".$indexContents;
					}
				} else {
					if ($fileName['subfolder']) {
						$dest=$home."/".$department."/".$cabinet."/".$folderMetaData."/".$fileName['subfolder']."/";
					} else {
						$dest=$home."/".$department."/".$cabinet."/".$folderMetaData."/";
					}
					$path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/".$folder['location']);
					if ($fileName['subfolder']) {
						$source=$path."/".$fileName['subfolder']."/";
					} else {
						$source=$path."/";
					}
					if (!copy($source.$fileName['filename'],$dest.$fileName['filename'])) {
						if (is_file($source.$fileName['filename']))
						{
							$message = "problem: ".$folder['doc_id']." copy /Y ".$source.$fileName['filename']." ".$dest.$fileName['filename']."\n";
							++$px;
							if ($px>=10)
							{
								error_log("over 10 ".$message);
								die();
							}
						}
						else
						{
							$message = "problem:".$folder['doc_id']." ".$source." does not exist\n";
						}
					} else {
						$temp=str_replace("/","\t",$dest);
						$temp=str_replace("__","\t",$temp);
						$message = $folder['doc_id']."\t".$temp."\t".$fileName['filename']."\t".$fileName['date_created']."\n";
						if (isset($indexContents))
						{
							$fp2=fopen($dest."INDEX.info","w");
							fwrite($fp2,$indexContents);
							fclose($fp2);
						}
					}
				}
				fwrite($fp,$message);
			}
		}
		$db_dept->disconnect();
	}
}
//
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
$totalhours = $totaltime/3600;
$string='This ran in ' .duration($totaltime). ' .';
fwrite($fp,$string);
fclose($fp);
echo $string;
echo "\nend\n";
function duration($secs)
{
	$vals = array('w' => (int) ($secs / 86400 / 7),
	'd' => $secs / 86400 % 7,
	'h' => $secs / 3600 % 24,
	'm' => $secs / 60 % 60,
	's' => $secs % 60);

	$ret = array();

	$added = false;
	foreach ($vals as $k => $v) {
		if ($v > 0 || $added) {
			$added = true;
			$ret[] = $v . $k;
		}
	}

	return join(' ', $ret);
}
?>