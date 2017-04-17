<?php 
// $Id: documentActions.php 14186 2011-01-04 15:11:53Z acavedon $

include_once '../lib/mime.php';
include_once '../lib/xmlObj.php';
include_once '../lib/PDF.php';
include_once '../settings/settings.php';
include_once '../lib/audit.php';
include_once '../lib/redaction.php';
include_once '../centera/centera.php';

function createPDF($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;

	$location = getFolderPath($enArr,$db_dept);
	$fileIDArr = getFileIDs($enArr);

	$sArr = array('id','doc_id','subfolder','filename','parent_filename', 'ca_hash', 'file_size','ordering');
	$whereArr = array('id IN('.implode(",",$fileIDArr).')');
	$oArr = array('ordering' => 'ASC');
	$fileInfo = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$whereArr,'getAssoc',$oArr);

	$PDF = array();
	$tmpDir = $user->getUniqueDirectory($user->userTempDir);
	$invalidExt = false;
	$jpegs = false;
	$pdfs = false;
	$copyList = array ();
	$fileArr = array();
	foreach($fileIDArr AS $fileID ) {
		$info = $fileInfo[$fileID];
		$destPath = $tmpDir.$info['subfolder']."/".$info['parent_filename'];
		$currPath = $location."/".$info['subfolder']."/".$info['filename']; 
		$ext = getExtension($info['filename']); 
		if(check_enable('redaction',$user->db_name)) {
			if(checkRedaction($db_dept,$db_doc,$enArr['cabinet'],$fileID,$user->db_name,$DEFS['DATA_DIR']) != '') {
				if($user->checkSetting('viewNonRedact',$enArr['cabinet']) && $enArr['nonRedact']) {
					$currPath .= '.adminRedacted';
				}
			}
		}
		
		// figure out what type of file(s) we are talking about here
		if(strtolower($ext) == "jpg" || strtolower($ext) == "jpeg") {
			$jpegs = true;
		} elseif(strtolower($ext) == "pdf") {
			$pdfs = true;
		} elseif(strtolower($ext) != "tif" && strtolower($ext) != "tiff") {
			$invalidExt = true;	
			break;
		}
		$PDF[] = array('hash' => $info['ca_hash'],
			'size' => $info['file_size'], 'name' => $currPath,
			'dest_name' => $destPath);

		if (!is_dir(dirname($destPath))) {
			mkdir(dirname($destPath));
		}
		$copyList[$currPath] = $destPath;

		$fileArr[] = $info['parent_filename'];
	}
	$doc_id = $info['doc_id'];
	$subfolder = $info['subfolder'];

	$xmlObj = new xml();
	if($invalidExt) {
		$mess = "Only tif, pdf and jpeg files can be converted to PDFs";
		$xmlObj->createKeyAndValue('MESSAGE',$mess);
	} else {
		if(check_enable( 'centera', $user->db_name )) {
			multiCentGet($DEFS['CENT_HOST'], $PDF, $user,$enArr['cabinet']);
		}
		foreach($copyList as $currPath => $destPath) {
			copy($currPath,$destPath);
		}
		$fNameInfo = array ();
		foreach($PDF as $info) {
			$fNameInfo[] = $info['dest_name'];
		}
		$isBig = isBigPDF($fNameInfo);
		if($jpegs || $isBig) {
			if($jpegs) {
				$mess = "The PDF you requested has Jpegs which take longer to ".
						"convert, and so the PDF will be created in the ".
						"background.  When it is done being processed, it ".
						"can be found in your personal inbox.";
			} elseif($isBig) {
				$mess = "Since the PDF you requested is longer than forty pages, ".
						"it will be created in the background. When it is done ".
						"being processed, it can be found in your personal inbox.";
			}

			$filename = $tmpDir.$user->getRandString();
			while (file_exists($filename)) {
				$filename = $tmpDir.$user->getRandString();
			}

			$fd = fopen($filename, 'w+');
			fwrite($fd, implode("\n", $fNameInfo));
			fclose($fd);

			$myDB = $user->db_name;
			$uname = $user->username;
			$myArgs = array (
					escapeshellarg ($filename),
					escapeshellarg ($uname),
					escapeshellarg ($tmpDir),
					escapeshellarg ($myDB)
					);
			if (substr (PHP_OS, 0, 3) == 'WIN') {
				$cmd = $DEFS['BGRUN_EXE'] . ' ' . escapeshellarg($DEFS['PHP_EXE']) . ' ' .
					$DEFS['DOC_DIR'].'/bots/createPDF.php ' . 
					implode(' ', $myArgs) . ' > NUL 2>&1';
			} else {
				$cmd = escapeshellarg($DEFS['PHP_EXE']) . ' ' .
					$DEFS['DOC_DIR'].'/bots/createPDF.php ' . 
					implode(' ', $myArgs) . ' > /dev/null 2>&1 &';
			}
			shell_exec ($cmd);
			$xmlObj->createKeyAndValue('MESSAGE',$mess);
		} elseif($pdfs) {
			// create one PDF from a group of selected pdf files
			$ret = createPDFFromPDFs($fNameInfo, $tmpDir, $user->username);
			// check for error condition
			if ($ret != 0) {
				$mess = "PDFtk failure - check that tool is installed correctly.";
				$xmlObj->createKeyAndValue('MESSAGE',$mess);
			}
			$pdfFileName = $user->username."-files.pdf";
			$xmlObj->createKeyAndValue('DOWNLOAD',$pdfFileName);
		} else {
			createPDFFromTiffs($fNameInfo, $tmpDir, $user->username);
			$pdfFileName = $user->username."-files.pdf";
			$xmlObj->createKeyAndValue('DOWNLOAD',$pdfFileName);
		}

		$sArr = array('document_id','document_table_name');
		$wArr = array('doc_id' => (int)$doc_id,
					'subfolder' => $subfolder,
					'filename' => 'IS NULL');
		$documentInfo = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'queryRow');
		$docTable = $documentInfo['document_table_name'];
		$documentID = $documentInfo['document_id'];

		$sArr = array('id','document_type_name');
		$wArr = array('document_table_name' => $docTable);
		$dInfo = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryRow');
		$docName = $dInfo['document_type_name'];
		$docTableID = $dInfo['id'];
			
		$sArr = array('document_field_value');
		$wArr = array('document_defs_list_id' => $docTableID,
						'document_id' => (int)$documentID);
		$docInfo = getTableInfo($db_dept,'document_field_value_list',$sArr,$wArr,'queryCol');

		$folderInfo = getCabIndexArr($doc_id,$enArr['cabinet'],$db_dept);	
		$auditStr = "Cabinet: ".$user->cabArr[$enArr['cabinet']]." Folder: ".implode(" ",$folderInfo);
		$auditStr .= " Document: ".$docName." ".implode(" ",$docInfo);
		$auditStr .= " (".implode(" ",$fileArr).") has been converted to PDF";
		$user->audit('PDF created',$auditStr,$db_dept);
	}
	$xmlObj->setHeader();
}

function createZIP($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;

	$location = getFolderPath($enArr,$db_dept);
	$fileIDArr = getFileIDs($enArr);

	$sArr = array('id','subfolder','filename','parent_filename', 'ca_hash', 'file_size');
	$whereArr = array('id IN('.implode(",",$fileIDArr).')');
	$fileInfo = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$whereArr,'queryAll');
	if( isset( $enArr['password'] ) ){
		$password = $enArr['password'];
	}
	$tmpDir = $user->getUniqueDirectory($user->userTempDir);
	chdir($tmpDir);
	$tabArr = array();
	$copyList = array ();
	$caList = array ();
	foreach($fileInfo as $info) {
		if(!in_array($info['subfolder'],$tabArr)) {
			$tab = $info['subfolder'];
			$tabArr[] = array($tab);
		}
		$destPath = $tmpDir.$info['subfolder']."/".$info['parent_filename'];
		$currPath = $location."/".$info['subfolder']."/".$info['filename']; 
		//NOT CENTERA SAFE
		if(check_enable('redaction',$user->db_name)) {
			$fileID = $info['id'];
			if(checkRedaction($db_dept,$db_doc,$enArr['cabinet'],$fileID,$user->db_name,$DEFS['DATA_DIR']) != '') {
				if($user->checkSetting('viewNonRedact',$enArr['cabinet']) && $enArr['nonRedact']) {
					$currPath .= '.adminRedacted';
				}
			}
		}
		
		if (!is_dir(dirname($destPath))) {
			mkdir(dirname($destPath));
		}
		$caList[] = array('hash' => $info['ca_hash'],
			'size' => $info['file_size'], 'name' => $currPath);

		$copyList[$currPath] = $destPath;
	}

	if(sizeof($tabArr) == 1) {
		chdir($tmpDir.'/'.$tab);
	}

	$zipFileName = $user->username."-files.zip";
	if (file_exists($user->userTempDir.'/'.$zipFileName)) {
		unlink($user->userTempDir.'/'.$zipFileName);
	}
	if(check_enable('centera', $user->db_name)) {
		multiCentGet($DEFS['CENT_HOST'], $caList, $user, $enArr['cabinet']);
	}
	foreach($copyList as $currPath => $destPath) {
		copy($currPath,$destPath);
	}
	if( $password!='' ){
		$cmd = $DEFS['ZIP_EXE'] .' -P '.$password. ' -r ' . escapeshellarg ($user->userTempDir . '/' . $zipFileName) . ' .';
	}else{
		$cmd = $DEFS['ZIP_EXE'] . ' -r ' . escapeshellarg ($user->userTempDir . '/' . $zipFileName) . ' .';
	}
	shell_exec($cmd);
	//$user->audit("Zip created", $auditMess." has been zipped");
	
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue('DOWNLOAD',$zipFileName);
	$xmlObj->setHeader();
}

function deleteFiles($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	$doc = $root = "";
	$recBin = true;
	$gblSettings = new GblStt($user->db_name, $db_doc);
	if($gblSettings->get('deleteRecyclebin') === "0") {
		$recBin = false;
	}

	$userSettings = new Usrsettings($user->username,$user->db_name);
	if($userSettings->get('deleteRecyclebin') === "0") {
		$recBin = false;
	}

	$fileIDArr = getFileIDs($enArr);	
	//Gets the file info
	//Gets the folder indices
	$folderAuditStr = getFolderAuditStr($db_dept, $enArr['cabinet'], $enArr['doc_id']);
	$aMsg = "Cabinet: ".$enArr['cabinet'].", Folder: ".$folderAuditStr;
	$whereArr = array('id IN('.implode(",",$fileIDArr).') OR parent_id IN('.implode(",",$fileIDArr).')');

	$sArr = array('subfolder','document_id','document_table_name');
	$wArr = array(	'doc_id'	=> (int)$enArr['doc_id'],
					'filename'	=> "IS NULL",
					'deleted'	=> 0,
					'display'	=> 1 );
	$folderDocs = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'getAssoc');

	$sArr = array('id', 'filename', 'file_size', 'subfolder','parent_filename','document_id','document_table_name');
	$fileArr = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$whereArr,'queryAll');
	foreach($fileArr AS $fInfo) {
		if($folderDocs[$fInfo['subfolder']]['document_id']) {
			$sArr = array('id','document_type_name');
			$wArr = array('document_table_name' => $folderDocs[$fInfo['subfolder']]['document_table_name']);
			$docInfo = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryRow');
			$sArr = array('document_field_value');
			$wArr = array(	'document_defs_list_id' => $docInfo['id'],
							'document_id'			=> $folderDocs[$fInfo['subfolder']]['document_id'] );
			$docFieldInfo = getTableInfo($db_dept,'document_field_value_list',$sArr,$wArr,'getCol');
			$auditMsg = $aMsg.", Document: ".$docInfo['document_type_name']." Desc: ".implode(" ",$docFieldInfo); 
			$auditMsg .= ", Filename: ".$fInfo['parent_filename'];
		} else {
			if(!$fInfo['subfolder']) {
				$fInfo['subfolder'] = "Main";
			}
			$auditMsg = $aMsg.", Subfolder: ".$fInfo['subfolder']." , Filename: ".$fInfo['parent_filename'];
		}
		$user->audit("File Deleted", $auditMsg);
	}

	if($recBin) {
		$updateArr = array('deleted' => 1, 'display' => 0);
		updateTableInfo($db_dept,$enArr['cabinet'].'_files',$updateArr,$whereArr);
		$user->audit("file marked for deletion", $auditMsg);
		$mess = "File successfully deleted";
	} else {
		$quota = 0;
		$sArr = array('location');
		$wArr = array('doc_id' => (int)$enArr['doc_id']);
		$loc = getTableInfo($db_dept,$enArr['cabinet'],$sArr,$wArr,'queryOne');
		$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
		
		foreach($fileArr AS $fileInfo) {
			$id = $fileInfo['id'];
			if(file_exists($location."/".$fileInfo['subfolder']."/".$fileInfo['filename'])) {
				if(unlink($location."/".$fileInfo['subfolder']."/".$fileInfo['filename'])) {
					$quota += $fileInfo['file_size'];
					deleteTableInfo($db_dept,$enArr['cabinet'].'_files',array('id' => (int)$id));
				}
			} else {
				deleteTableInfo($db_dept,$enArr['cabinet'].'_files',array('id' => (int)$id));
			}
		}

		if($quota) {
			$uArr = array('quota_used' => 'quota_used-'.(int)$quota);
			$wArr = array('real_department' => $user->db_name);
			updateTableInfo($db_doc,'licenses',$uArr,$wArr,1);
		}
		$mess = "File successfully deleted";
	}

	$xmlObj = new xml();
    $attArr = array('cabinet' => $enArr['cabinet'],
                    'doc_id' => $enArr['doc_id'],
                    'tab_id' => $enArr['tab_id']);
    $xmlObj->createKeyAndValue('RELOAD',$mess,$attArr);
    $xmlObj->setHeader();
}

function reorderFiles($enArr,$user,$db_doc,$db_object) {
	$fileIDArr = getFileIDs($enArr);	

	if($enArr['tab_id'] != '-1') {
		$sArr = array('subfolder');
		$whereArr = array('id' => (int)$enArr['tab_id']);
	}

	for($i=0;$i<sizeof($fileIDArr);$i++) {
		$parentID = getTableInfo ($db_object, $enArr['cabinet'].'_files',
			array ('parent_id'),
			array ('id' => (int) $fileIDArr[$i]), 'queryOne');

		$updateArr = array('ordering' => (int)$i);
		$whereStr = 'id='.$fileIDArr[$i];
		if ($parentID != 0) {
			$whereStr .= ' OR parent_id='.$parentID;
		}
		$whereArr = array($whereStr);

		updateTableInfo($db_object,$enArr['cabinet'].'_files',$updateArr,$whereArr);
	}
}

function getFolderPath($enArr,$db_dept) {
	global $DEFS;
	$sArr = array('location');
	$whereArr = array('doc_id' => (int)$enArr['doc_id']);
	$loc = getTableInfo($db_dept,$enArr['cabinet'],$sArr,$whereArr,'queryOne');
	$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);

	return $location;
}

function getFileIDs($enArr) {
	$i = 1;
	while(isset ($enArr['file'.$i]) and $id = $enArr['file'.$i]) {
		$fileIDArr[] = (int)$id;		
		$i++;
	}

	return $fileIDArr;
}
?>
