<?php
include_once '../db/db_common.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/utility.php';
include_once '../lib/webServices.php';
include_once '../settings/settings.php';
include_once '../lib/settings.php';
include_once '../lib/xmlObj.php';
include_once 'newFolderObj.inc.php';
include_once '../lib/mime.php';
//include_once '../check_login.php';

	function parseDirStruct($folder, $department, $cabinet, $cabinetID, $indicesKeys, $userName, $maxFileSize, $fileExtensions, $generateTemplate,
								$opCabinetID=NULL, $opDocID=NULL)
	{
		//if nothing entered send back empty array
		if( trim($folder)=='' ){
			return array();
		}
		//if .. is contained remove it
		$pos = strpos( $folder,'..' );
		if( $pos !== false ){
			return array();
		}
		global $DEFS;
		$basepath = $DEFS['DATA_DIR']."/".$department."/import";
		$basepathFolder = $basepath."/$folder";
		if( !file_exists( $basepathFolder ) ){
			return array();
		}
		$dirstruct = array();
		getDocutronFolders( $basepathFolder, $dirstruct );

		$importLogPath = $DEFS['TMP_DIR']."/import-$folder"."_".date('Y-m-d_H_i_s').".txt";
		$fd = fopen($importLogPath, "w+");
		logMessage($fd, "Import of directory $folder\n");
		$foldersTemplateArr = array();
		foreach($dirstruct AS $fullPath => $filesArr) {
			$folderObj = new newFolderObj($department, $cabinet, $cabinetID, $fullPath);

			//parse the path into indices
			$partialPath = str_replace($basepath."/", "", $fullPath);
			$dirArr = explode("/", $partialPath);
			$indicesArr = array(0 => "",1 => "", 2 => "", 3 => "");
			for($i = 0; $i < sizeof($dirArr); $i++ ) {
				if($i <= 1 ) {
					$indicesArr[$i] = "".$dirArr[$i];
				} elseif( $indicesArr[2] != "" ) { 
					$indicesArr[2] = $indicesArr[2]."/".$dirArr[$i];
				} else {
					$indicesArr[2] = "".$dirArr[$i];
				}
			}
			$indicesArr[3] = date('Y-m-d');
			$indicesArr[4] = $opDocID;
			$folderObj->indicesArr = $indicesArr;

			$fileObjArr = array();
			$zipArr = array();
			foreach($filesArr AS $filePath) {
				$fileSize = filesize($filePath);
				$filename = basename($filePath);
				$extension = strtolower(getExtension($filename));
				$fileObj = new fileObj($filename, $filePath, $extension);
				$logStr = date('Y-m-d H:i:s')." importing source file: $filePath $fileSize\n";
				logMessage($fd, $logStr);
				if($fileSize >= $maxFileSize AND in_array($extension, $fileExtensions) ) {
					$logStr = date('Y-m-d H:i:s')." $filename is a $extension file that is larger than the threshold and will be compressed\n";
					logMessage($fd, $logStr);
					$zipFilename = str_replace(".", "_", $filename);
					$zipFilename = str_replace(" ", "_", $zipFilename);
					$zipFilename = $DEFS['TMP_DIR']."/".makeUniqueFilename($DEFS['TMP_DIR'], $zipFilename, "zip");
					$zipPath = dirname($filePath);
					chdir($zipPath);
					zipFile( $filename,$zipFilename ); 
					$filesize = 0;
					if( !$generateTemplate ) {
						$filesize = filesize($zipFilename);
					}
					$logStr = date('Y-m-d H:i:s')." zip file ".basename($zipFilename)." generated $filesize\n";
					logMessage($fd, $logStr);
					$fileObj = new fileObj( basename($zipFilename), $zipFilename, "zip" );
					$fileObjArr[] = $fileObj;
				} else {
					$fileObjArr[] = $fileObj;
				}
			}
			$folderObj->fileObjArr = $fileObjArr;
			//Add folder object to the list
			$foldersTemplateArr[] = $folderObj;	
		}
		logMessage($fd, "\n");
		fclose($fd);
		return array($importLogPath, $foldersTemplateArr);
	}

function logMessage($fd, $message) {
	fwrite($fd, $message);
}

function uploadLogFile($department, $opCabinetID, $opDocID, $filePath, $folder) {
	$db_dept = getDBObject($department);
	$db_doc = getDBObject("docutron");
	$fileID = transferFile("admin", $department, $opCabinetID, $opDocID, 0, basename($filePath), $filePath, $db_doc, $db_dept);
	if($fileID != NULL OR $fileID != '') {
		unlink($filePath);
	}
}

function generateTemplate($department, $cabinet, $cabinetID, $folder, $userName, $maxFileSize, $fileExtensions) {
	$db_dept = getDbObject($department);
	$cabinetIndices = getCabinetInfo($db_dept, $cabinet);
	list($importLogPath, $templateArr) = parseDirStruct($folder, $department, $cabinet, $cabinetID, $cabinetIndices, 
												$userName, $maxFileSize, $fileExtensions, true);

	$xmlObj = new xml('CRAWLER');
	$parentEl = $xmlObj->createKeyAndValue('INDICES');
	foreach($cabinetIndices AS $index) {
		$xmlObj->createKeyAndValue('INDEX',$index,array(),$parentEl);
	}

	foreach($templateArr AS $folderObj) {
		$parentEl = $xmlObj->createKeyAndValue('ENTRY');
		$folderIndices = $folderObj->indicesArr;
		$i = 0;
		$indicesArr = array();
		foreach($folderIndices AS $indexValue) {
			$indicesArr[$cabinetIndices[$i]] = $indexValue;
			$i++;
		}
		$xmlObj->createKeyAndValue('INDEX',NULL,$indicesArr,$parentEl);

		$folderFiles = $folderObj->fileObjArr;
		foreach($folderFiles AS $files) {
			$filename = $files->filename;
			$xmlObj->createKeyAndValue('FILE',NULL,array('filename'=> $filename),$parentEl);
		}
	}
	$retStr = $xmlObj->createDOMString();
	return $retStr;
}

function updateStatus($db_dept, $opCabinet, $opDocID, $status) {
	updateTableInfo($db_dept, $opCabinet, array("status" => $status), array("doc_id" => $opDocID));
}

//Returns the directory name based off the assessment number of operational cabinet
function getDirName($db_dept, $opCabinet, $opDocID) {
	$directory = getTableInfo($db_dept, $opCabinet, array('file_number'), array('doc_id' => $opDocID), 'queryOne');
	return $directory;
}

//Moves the file from the current location to a Docutron folder
//Handles the db too
function moveFile($db_doc, $db_dept, $folderObj, $indicesKeys, $userName, $importLogPath) {
	global $DEFS;
//	allowWebWrite($path, $DEFS);
	$fd = fopen($importLogPath, "a+");
	$docID = createFolder($db_doc, $db_dept, $folderObj->department, $folderObj->cabinet, $userName, $folderObj->indicesArr, $indicesKeys);
	$logStr = date('Y-m-d H:i:s')." created folder ".implode(", ", $folderObj->indicesArr)." with docID: $docID\n";
	logMessage($fd, $logStr);
	foreach($folderObj->fileObjArr AS $fileObj) {
		$filePath = $fileObj->filePath;
		$filename = basename($filePath);
		$fileID = transferFile($userName, $folderObj->department, $folderObj->cabinetID, 
			$docID, 0, $filename, $filePath, $db_doc, $db_dept);

		if($fileID != NULL OR $fileID != "") {
			$logStr = date('Y-m-d H:i:s')." successfully transfered file $filename with fileID: $fileID\n";
			logMessage($fd, $logStr);
		} else {
			$logStr = date('Y-m-d H:i:s')." failed to transfer file $filename\n";
			logMessage($fd, $logStr);
		}
	}
	logMessage($fd, "\n");
	fclose($fd);
}

//creates the folder in the database and on disk
//Returns the location of the folder
function createFolder($db_doc, $db_dept, $department, $cabinet, $userName, $indicesValues, $indicesKeys) {
	global $DEFS;
	$temp_table = '';
	$gblStt = new GblStt($department, $db_doc);

	$docID = (int) createFolderInCabinet($db_dept, $gblStt, $db_doc, $userName, $department, $cabinet, 
		$indicesValues, $indicesKeys, $temp_table);

	if($docID == NULL) {
		return false;
	} else {
		return $docID;
	}
}

function transferFile($username, $department, $cabinetID, $docID, $tabID, $filename, $filePath, $db_doc, $db_dept)
{
    global $DEFS;
    $cab = hasAccess($db_dept,$username,$cabinetID,true,false);
    if($cab !== false) {
		$destTab = NULL;
        if($tabID != 0) {
			$destTab = getTableInfo($db_dept, $cab.'_files', array('subfolder'), 
				array('id' => (int)$tabID), 'queryOne');
        }
        $ordering = getOrderType($department,$cab,$docID,$destTab,$username,1, $db_doc, $db_dept);
        if($ordering === NULL) {
            $ordering = 1;
        }

		$result = getTableInfo($db_dept, $cab, array(), array('doc_id' => $docID));
        if(PEAR::isError($result)) {
            return false;
        }

        $filename = getSafeFilename($db_dept, $cab, $docID, $destTab, $filename);
        $location = str_replace(" ","/",getFolderLocation($db_dept,$cab,$docID));
        $location = $DEFS['DATA_DIR']."/".$location."/".$destTab."/".$filename;
        //Puts the file on disk
 //       rename($filePath, $location);
        copy($filePath, $location);

        //Values for placing query into db
        $res['filename'] = $filename;
        $res['doc_id'] = $docID;
        $res['subfolder'] = $destTab;
        $res['ordering'] = $ordering;
        $res['date_created'] = date('Y-m-d G:i:s');
        $res['who_indexed'] = $username;
        $res['parent_filename'] = $filename;
        $res['file_size'] = filesize($location);

        $result = $db_dept->extended->autoExecute($cab."_files",$res);
        if(PEAR::isError($result)) {
            return false;
        }

        //audit
//      $auditMessage = "$filename created through webservices to Doc ID: $docID"; //need to change later
//      auditMoveFile($username, $department, $auditMessage);
    }
    $fileID = 0;
	$fileID = getTableInfo($db_dept, $cab.'_files', array('MAX(id)'), 
		array('filename' => $filename, 'doc_id' => $docID), 'queryOne');
    return $fileID;
}

//getDocutronFolders returns an array of folders to create
//with an array of files to put in the folder
function getDocutronFolders( $path, &$dirstruct ){
	$dh = opendir( $path );
	$fArr = listDir( $path );
	foreach( $fArr as $str ){
		if( is_file( $path."/$str" ) ){
			$dirstruct[$path][] = $path."/$str";
		} else {
			getDocutronFolders( $path."/$str", $dirstruct );
		}
	}
}

function makeUniqueFilename($dirPath, $filename, $extension) {
	$retFilename = $filename.".".$extension;
	$i = 1;
	$str = sprintf('%05d', $i);
	while( file_exists($dirPath."/".$retFilename) ) {
		$retFilename = $filename.'-'.$str.".".$extension;
		$str = sprintf('%05d', $i);
		$i++;
	}
	return $retFilename;
}
function zipFile( $filepath, $zipfilename ){
	$zip = new ZipArchive();
	$zip->open($zipfilename, ZipArchive::CREATE);
	$zip->addFile($filepath, basename( $filepath ));
	$zip->close();
}
?>
