<?php
// $Id: allFiles.php 14110 2010-09-29 15:37:08Z acavedon $

include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../lib/mime.php';
include_once '../lib/PDF.php';
include_once '../lib/fileFuncs.php';
include_once 'energiefuncs.php';
include_once '../lib/settings.php';
include_once '../modules/modules.php';
include_once '../lib/redaction.php';
include_once '../lib/audit.php';
include_once '../centera/centera.php';

echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>allFiles</title>
</head>
<body>

ENERGIE;
if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$uname = $user->username;

	$NoFiles = $trans['No Files Have Been Checked'];
	$onlyTifs = $trans['Only tif files can be converted to PDFs'];

	$cab = $_GET['cab'];
	$doc_id = $_GET['doc_id'];
	$tab = $_GET['tab'];
	$check = $_POST['check'];
	$type = $_GET['type'];
	$index = $_GET['index'];
	if (isset ($_GET['referer']) and $_GET['referer']) {
		$referer = $_GET['referer'];
	}
	
	if (isset ($_GET['table']) and $_GET['table']) {
		$tempTable = $_GET['table'];
	}

	if (!$check) {
		echo<<<ENERGIE
<script type="text/javascript">
parent.sideFrame.alertBox('$NoFiles');
</script>

ENERGIE;

		setSessionUser($user);
		die();
	}

	$tmpDir = $user->getUniqueDirectory($user->userTempDir);
	$loc = getTableInfo($db_object,$cab,array('location'),array('doc_id'=>(int)$doc_id), 'queryOne');	
	$loc = str_replace(" ", "/", $loc);
	$relativePath = "{$DEFS['DATA_DIR']}/$loc/";

	$j = 0;
	$i = 1;
	$isNotTif  = 1;
	$isPDF     = 0;
	$isJpeg    = 0;
	$auditMess = "";
	$copyList  = array ();

	$del = array ();
	while ($j < sizeof($check)) {
		$myID = $check[$j];
		$row = getNewestVersion($cab, $doc_id, $myID, $db_object);
		if($row) {
			$fInfo = getTableInfo($db_object, $cab.'_files', array(), array('id' => $row['id']));
			$row = $fInfo->fetchRow();
		} else {
			$fInfo = getTableInfo($db_object, $cab.'_files', array(), array('id' => $myID));
			$row = $fInfo->fetchRow();		
		}
		if ($row) {
			$myTab = $row['subfolder'];
			$del[$j] = $row;
			$myFName = $row['filename'];
			$ext = strtolower(getExtension($myFName));
			$destFName = $row['parent_filename'];
			if(check_enable('redaction', $user->db_name)) {
				$fileID = $row['id'];
				//NOT CENTERA SAFE
				if(checkRedaction($db_object, $db_doc, $cab, $fileID, $user->db_name, $DEFS['DATA_DIR']) != '') {
					if($user->checkSetting('viewNonRedact', $cab) and $_GET['expNonRedact'] == 1) {
						$myFName .= '.adminRedacted';
					}
				}
			}
			if ($auditMess) {
				$auditMess = $auditMess." ".$myFName;
			} else {
				$auditMess = $myFName;
			}
			if ($myTab) {
				if ($type == "ZIP" or $type == "PDF") {
					$destPath = $tmpDir.$myTab.'/'.$destFName;
					if (!is_dir(dirname($destPath))) {
						mkdir(dirname($destPath));
					}
					$srcPath = $relativePath.$myTab.'/'.$myFName;
					$copyList[$srcPath] = $destPath;
				}

				if (strcmp($ext, "tif") == 0 or strcmp($ext, "tiff") == 0) {
					$PDF[] = array(
						'hash' => $row['ca_hash'], 
						'size' => $row['file_size'], 
						'name' => $srcPath,
						'dest_name' => $destPath);
					$isNotTif = 0;
				}
				elseif ((strcmp($ext, "jpeg") == 0 or strcmp($ext, "jpg") == 0) && $type == "PDF") {
					$PDF[] = array(
						'hash' => $row['ca_hash'],
						'size' => $row['file_size'],
						'name' => $srcPath,
						'dest_name' => $destPath);
					$isNotTif = 0;
					$isJpeg = 1;
				} elseif ((strcasecmp($ext, "pdf") == 0) && ($type == "PDF")) {
					// special case for concatinating PDFs into one PDF
					$PDF[] = array(
						'hash' => $row['ca_hash'],
						'size' => $row['file_size'],
						'name' => $srcPath,
						'dest_name' => $destPath);
					$isNotTif = 0;
					$isJpeg   = 0;
					$isPDF    = 1;
				} else {
					$isNotTif = 1;
					$isJpeg   = 0;
				}
			} else {
				if ($type == "ZIP" or $type == "PDF") {
					$destPath = $tmpDir.$destFName;
					$copyList[$relativePath.$myFName] = $destPath;
					$srcPath = $relativePath.'/'.$myFName;

					if (strcmp($ext, "tif") == 0 or strcmp($ext, "tiff") == 0) {
						$PDF[] = array(
							'hash' => $row['ca_hash'],
							'size' => $row['file_size'],
							'name' => $srcPath,
							'dest_name' => $destPath);
						$isNotTif = 0;
					}
					elseif ((strcmp($ext, "jpeg") == 0 or strcmp($ext, "jpg") == 0) && $type == "PDF") {
						$PDF[] = array(
							'hash' => $row['ca_hash'],
							'size' => $row['file_size'],
							'name' => $srcPath,
							'dest_name' => $destPath);
						$isNotTif = 0;
						$isJpeg = 1;
					} elseif ((strcasecmp($ext, "pdf") == 0) && ($type == "PDF")) {
						// special case for concatinating PDFs into one PDF
						$PDF[] = array(
							'hash' => $row['ca_hash'],
							'size' => $row['file_size'],
							'name' => $srcPath,
							'dest_name' => $destPath);
						$isNotTif = 0;
						$isJpeg   = 0;
						$isPDF    = 1;
					} else {
						$isNotTif = 1;
						$isJpeg   = 0;
					}
				}
			}
			$i ++;
		}
		$j ++;
	}
	if ($type == 'PDF' or $type == 'ZIP') {
		if(check_enable('centera',$user->db_name)) {
			multiCentGet($DEFS['CENT_HOST'], $PDF, $user, $cab);
		}
		foreach($copyList as $srcPath => $destPath) {
			$ret = copy($srcPath, $destPath);
			if ($ret == false) {
				echo<<<ENERGIE
<script type="text/javascript">
	parent.sideFrame.alertBox('Error copying files to $destPath - permissions?');
</script>

ENERGIE;
			}
		}
	}
	if ($type == "ZIP") {
		$name1 = $uname."-files.zip";
		chdir($tmpDir);
		$dir = dir('.');
		$dirCount = 0;
		$fileCount = 0;
		$lastDir = '';
		while ($filename = $dir->read()) {
			if ($filename != '.' and $filename != '..') {
				if (is_file($tmpDir.'/'.$filename)) {
					$fileCount++;
					break;
				} else if (is_dir($tmpDir.'/'.$filename)) {
					$lastDir = $filename;
					$dirCount++;
					if ($dirCount > 1) {
						break;
					}
				}
			}
		}
		if (!$fileCount and $dirCount == 1) {
			chdir($tmpDir.'/'.$lastDir);
		}
		if (file_exists($user->userTempDir.'/'.$name1)) {
			unlink($user->userTempDir.'/'.$name1);
		}
		$cmd = $DEFS['ZIP_EXE'] . ' -r ' .escapeshellarg ($user->userTempDir.'/'.$name1). ' .';
		shell_exec ($cmd);
		$user->audit("Zip created", $auditMess." has been zipped");
		echo<<<ENERGIE
<script type="text/javascript">
  document.onload = parent.topFrame.window.location = "displayExport.php?file=/$name1";
</script>

ENERGIE;
	} elseif ($type == "PDF") {
		$fNameInfo = array ();
		foreach($PDF as $fInfo) {
			$fNameInfo[] = $fInfo['dest_name'];
		}

			// if statement tests for non-tif files selected for conversion to PDF
			if ($isNotTif) {
				echo<<<ENERGIE
<script type="text/javascript">
	parent.sideFrame.alertBox('$onlyTifs');
	//alert('$onlyTifs');
</script>

ENERGIE;

				setSessionUser($user);
				if (file_exists ($tmpDir)) {
					delDir($tmpDir);
				}
				die();
			}
			$folderInfo = getCabIndexArr($doc_id,$cab,$db_object);	
			$auditStr = "Cabinet: ".$user->cabArr[$cab]." Folder: ".implode(" ",$folderInfo);
			$user->audit('PDF created',$auditStr." ($auditMess) has been converted to PDF",$db_object);
			
			if (isBigPDF($fNameInfo) || $isJpeg) {

				if ($isJpeg) {
					echo<<<ENERGIE
<script type="text/javascript">
parent.sideFrame.alertBox('The PDF you requested has Jpegs which take longer to convert, and so the PDF will be created in the background. When it is done being processed, it can be found in your personal inbox.');
</script>
ENERGIE;
				} else {
					echo<<<ENERGIE
<script type="text/javascript">
parent.sideFrame.alertBox('Since the PDF you requested is longer than forty pages, it will be created in the background. When it is done being processed, it can be found in your personal inbox.');
</script>
ENERGIE;
				}

				$filename = $tmpDir.$user->getRandString();
				while (file_exists($filename)) {
					$filename = $tmpDir.$user->getRandString();
				}

				$fd = fopen($filename, 'w+');
				fwrite($fd, implode("\n", $fNameInfo));
				fclose($fd);

				$myDB = $user->db_name;
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
				die();
			} else {
				if ($isPDF) {
					// PDF from PDFs
					$ret = createPDFFromPDFs($fNameInfo, $tmpDir, $user->username);
					// check for error condition
					if ($ret != 0) {
						echo<<<ENERGIE
<script type="text/javascript">
parent.sideFrame.alertBox('PDFtk failure - check that tool is installed correctly.');
</script>			
ENERGIE;
					}
					$pdfFileName = $uname."-files.pdf";
				} else {
					// PDF from TIFFs
	 				createPDFFromTIffs($fNameInfo, $tmpDir, $user->username);
					$pdfFileName = $uname."-files.pdf";
				}
				// display PDf output filename
				echo<<<ENERGIE
<script type="text/javascript">
document.onload = parent.mainFrame.window.location = "displayExport.php?file=/$pdfFileName";
</script>

ENERGIE;
			}
		} else {
			$folderAuditStr = getFolderAuditStr($db_object, $cab, $doc_id);
			$recBin = true;
			$gblSettings = new GblStt($user->db_name, $db_doc);
			if($gblSettings->get('deleteRecyclebin') === "0") {
				$recBin = false;
			}

			$userSettings = new Usrsettings($user->username,$user->db_name);
			if($userSettings->get('deleteRecyclebin') === "0") {
				$recBin = false;
			}

			$audit = "Cabinet: ".$cab.", Folder: ".$folderAuditStr;
			for ($i=0;$i<sizeof($del);$i++) {
				$tmp = $del[$i];
				$tabName = $tmp['subfolder'];
				if(!$tabName) {
					$tabName = "Main";
				}
				$auditMsg = $audit.", Subfolder: ".$tabName.", Filename: ".$tmp['parent_filename'];
				if($recBin) {
					$uArr = array(	'deleted' => 1,
									'display' => 0 );
					$wArr = array('id='.(int)$tmp['id'].' OR parent_id='.(int)$tmp['id']);
					updateTableInfo($db_object,$cab."_files",$uArr,$wArr);
					$user->audit("file marked for deletion", $auditMsg);
				} else {
					$quota = 0;
					$sArr = array('location');
					$wArr = array('doc_id' => (int)$tmp['doc_id']);
					$loc = getTableInfo($db_object,$cab,$sArr,$wArr,'queryOne');
					$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
					
					if(file_exists($location."/".$tmp['subfolder']."/".$tmp['filename'])) {
						if(unlink($location."/".$tmp['subfolder']."/".$tmp['filename'])) {
							$quota += $tmp['file_size'];
							deleteTableInfo($db_object,$cab.'_files',array('id'=>(int)$tmp['id']));
						}
					} else {
						deleteTableInfo($db_object,$cab.'_files',array('id'=>(int)$tmp['id']));
					}
					$user->audit("file permanently deleted", $auditMsg);

					if($quota) {
						$uArr = array('quota_used' => 'quota_used-'.(int)$quota);
						$wArr = array('real_department' => $user->db_name);
						updateTableInfo($db_doc,'licenses',$uArr,$wArr,1);
					}
				}
			}
			$queryStr = "?cab=$cab&doc_id=$doc_id&index=$index";
			if (isset ($referer)) {
				$queryStr .= "&referer=$referer";
			}
			if (isset ($tempTable)) {
				$queryStr .= "&table=$tempTable";
			}
			echo "<script type=\"text/javascript\">\n";
			echo "document.onload = parent.sideFrame.window.location = ";
			echo "\"allthumbs.php$queryStr\";\n";
			echo "</script>\n";
		}
	if (file_exists ($tmpDir)) {
		delDir($tmpDir);
	}
	setSessionUser($user);
} else { //we want to log them out
	echo<<<ENERGIE
<script type="text/javascript">
document.onload = top.window.location = "../logout.php";
</script>

ENERGIE;
}
?>
</body>
</html>
