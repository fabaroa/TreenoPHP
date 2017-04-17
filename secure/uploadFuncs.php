<?php
include_once '../lib/settings.php';
include_once '../lib/random.php';
include_once '../lib/mime.php';
include_once '../lib/xmlObj.php';
include_once '../lib/quota.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/inbox.php';
include_once '../centera/centera.php';
include_once '../modules/modules.php';

function setUploadPath($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;

	$dest = "";
	if(isSet($enArr['type'])) {
		$_SESSION['uploadPath'] = $DEFS['DATA_DIR']."/".$user->db_name;
		if($enArr['type']) {
			$_SESSION['uploadPath'] .= "/personalInbox/".$enArr['username'];
			$dest = $enArr['username']." Personal Inbox";
		} else {
			$_SESSION['uploadPath'] .= "/inbox";
			$dest = "Public Inbox";
		}

		if($enArr['folder']) {
			$_SESSION['uploadPath'] .= "/".$enArr['folder'];
			$dest .= " Folder: ".$enArr['folder'];
		}
		$_SESSION['uploadType'] = 'inbox';
	} else {
		$sArr = array('location');
		$wArr = array('doc_id' => (int)$enArr['doc_id']);
		$folderLoc = getTableInfo($db_dept,$enArr['cabinet'],$sArr,$wArr,'queryOne');	

		$_SESSION['uploadPath'] = $DEFS['DATA_DIR']."/".str_replace(" ","/",$folderLoc);
		$uploadInfo = array('cabinet'	=> $enArr['cabinet'],
							'doc_id'	=> $enArr['doc_id'] );

		$fArr = getCabIndexArr($enArr['doc_id'],$enArr['cabinet'],$db_dept);
		$dest = " Folder: ".implode(" ",$fArr);
		if(isSet($enArr['tab_id'])) {
			if($enArr['tab_id'] != -1) {
				$sArr = array('subfolder');
				$wArr = array('id' => (int)$enArr['tab_id']);
				$tab_name = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'queryOne');	

				$_SESSION['uploadPath'] .= "/".$tab_name;
				$uploadInfo['tab'] = $tab_name;
			}
			$uploadInfo['tab_id'] = $enArr['tab_id'];
		}
		if(isSet($enArr['temp_table'])) {
			$uploadInfo['temp_table'] = $enArr['temp_table'];
		}
			
		if(isSet($uploadInfo)) {
			$_SESSION['uploadInfo'] = $uploadInfo;
		}

		$_SESSION['uploadType'] = 'folder';
	}
	$_SESSION['uploadURL'] = $_SERVER['HTTP_REFERER'];

	if(!isSet($_SESSION['uploadTmpDest'])) {
		$tmpPath = $DEFS['TMP_DIR']."/docutron";
		if(!is_dir($tmpPath)) {
			mkdir($tmpPath,0777);
		}
		
		$tmpPath .= "/".$user->username;
		if(!is_dir($tmpPath)) {
			mkdir($tmpPath,0777);
		}
		$_SESSION['uploadTmpDest'] = getUniqueDirectory($tmpPath);  
	}

	$_SESSION['uploadDestination'] = $dest;

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","openUploadPage()");
	$xmlObj->setHeader();
}

function xmlGetUploadInfo($enArr,$user,$db_doc,$db_dept) {
	$optArr = getUploadInfo($db_dept,$user);
	$fileArr = getExistingUploads();

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setDestination(XML)");
	$xmlObj->createKeyAndValue("DEST",$_SESSION['uploadDestination']);
	foreach($optArr AS $opt) {
		$xmlObj->createKeyAndValue("OPTION",$opt);
	}

	foreach($fileArr AS $file) {
		$xmlObj->createKeyAndValue("FILE",$file);
	}
	$xmlObj->setHeader();	
}

function getExistingUploads() {
	$fileArr = array();
	$dh = opendir($_SESSION['uploadTmpDest']);
	while( false !== ($file = readdir($dh))) {
		if( is_file($_SESSION['uploadTmpDest'].'/'.$file) ) {
			$fileArr[] = $file;
		}
	}
	return $fileArr;
}

function getUploadInfo($db_dept,$user) {
	if(isSet($_SESSION['uploadInfo'])) {
		$info = $_SESSION['uploadInfo'];
	}
	$type = $_SESSION['uploadType'];

	$optArr = array();
	if($type == "folder") {
		if(isSet($info['tab_id'])) {
			$sArr = array('document_table_name');
			$wArr = array('id' => (int)$info['tab_id']);
			$docTable = getTableInfo($db_dept,$info['cabinet']."_files",$sArr,$wArr,'queryOne');	

			$sArr = array('document_type_name');
			$wArr = array('document_table_name' => $docTable);
			$optArr[] = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');
		} else {
			$sArr = array('subfolder');
			$wArr = array(	'doc_id' => (int)$info['doc_id'],
							'filename'	=> 'IS NULL',
							'deleted'	=> 0);
			$oArr = array('subfolder' => 'ASC');
			$tabArr = getTableInfo($db_dept,$info['cabinet']."_files",$sArr,$wArr,'queryCol',$oArr);	

			$tArr = getNoShowTabs($info['cabinet'],$info['doc_id'],$user->db_name);
			$optArr = array("Main");
			foreach($tabArr AS $sfold) {
				if(!in_array($sfold,$tArr)) {
					$optArr[] = $sfold;
				}
			}
		}
	}
	return $optArr;
}

function xmlRemoveFile($enArr,$user,$db_doc,$db_dept) {
	removeFile($enArr['filename']);
}

function removeFile($filename) {
	if(is_file($_SESSION['uploadTmpDest']."/".$filename)) {
		unlink($_SESSION['uploadTmpDest']."/".$filename);
	}
}

function xmlProcessUpload($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;

	$fileArr = getExistingUploads();
	$URL = "";
	foreach($fileArr AS $file) {
		$fpath = $_SESSION['uploadTmpDest']."/".$file;
		if(is_file($fpath)) {
			if($_SESSION['uploadType'] == "folder") {
				$size = filesize($fpath);
				if(checkQuota($db_doc, $size,$user->db_name)) {
					$cab = $_SESSION['uploadInfo']['cabinet'];
					$doc_id = $_SESSION['uploadInfo']['doc_id'];

					$destPath = $_SESSION['uploadPath'];
					if(!isSet($_SESSION['uploadInfo']['tab_id'])) {
						$tab = $enArr[$file];
						$dispTab = str_replace("_"," ",$tab);
						if($tab && strtolower($tab) != "main") {
							$destPath .= "/".$tab;
						}
						$wArr = array('doc_id' => (int)$doc_id,
									'subfolder' => 'IS NULL',
									'filename' => 'IS NOT NULL');
					} else {
						$tab_id = $_SESSION['uploadInfo']['tab_id'];
						$sArr = array('subfolder');
						$wArr = array('id' => $tab_id);
						$tab = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'queryOne');
						$dispTab = str_replace("_"," ",$tab);

						$wArr = array('doc_id' => (int)$doc_id,
									'subfolder' => $tab,
									'filename' => 'IS NOT NULL');
					}
					$sArr = array('parent_filename');
					$fnameArr = getTableInfo($db_dept,$cab.'_files',$sArr,$wArr,'queryCol');

					$filename = $file;
					$ct = 1;
					$noFile = false;
					while(!$noFile) {
						if(!is_file($destPath."/".$filename) && !in_array($filename,$fnameArr)) {
							$noFile = true;
						} else {
							$ext = getExtension($filename);
							$filename = basename($file,".$ext")."-$ct.$ext";  
							$ct++;
						}
					}

					if(rename($fpath,$destPath."/".$filename)) {
						allowWebWrite ("$destPath/$filename", $DEFS);
						$insertArr = array(	'filename'			=> $filename,
											'parent_filename'	=> $filename,
											'doc_id'			=> (int) $doc_id,
											'who_indexed'		=> $user->username,
											'date_created'		=> date('Y-m-d G:i:s'),
											'file_size'			=> (int) $size );
						if( check_enable('centera',$user->db_name) ) {
							$gblStt = new Gblstt($user->db_name,$db_doc);
							if($gblStt->get('centera_'.$cab) == 1) {
								$ca = centput( $destPath."/".$filename, $DEFS['CENT_HOST'], $user, $cab );
								$insertArr['ca_hash']= $ca;
							}
						}
						if($tab && strtolower($tab) != "main") {
							$insertArr['subfolder'] = $tab;
						}
						
						$orderType = getOrderSett($user->db_name, $cab, $db_doc);

						lockTables($db_dept,array($cab.'_files'));
						$ordering = getOrderingValue($db_dept, $cab."_files", $doc_id, $tab, $orderType,1);
						$insertArr['ordering'] = (int)$ordering;
						$res = $db_dept->extended->autoExecute($cab.'_files', $insertArr);
						dbErr($res);
						unlockTables($db_dept);
							
						$folder = getCabIndexArr($doc_id,$cab,$db_dept);
						$folderDisp = implode(" ",$folder);
						$myAudit = "Cabinet: $cab, Folder: $folderDisp Tab: $dispTab, Location: $filename"; 
						$user->audit("file uploaded to cabinet", $myAudit);
						$errorMsg = 'File successfully Uploaded';

						if(isSet($_SESSION['uploadInfo']['tab_id'])) {
							$tab_id = $_SESSION['uploadInfo']['tab_id'];
						} else {
							$tempTable = $_SESSION['uploadInfo']['temp_table'];
						}
					} else {
						$errorMsg = 'An error occured during file upload';
					}
				} else {
					$errorMsg = 'Not Enough Space';
				}
			} else {
				$filename = $file;
				$ct = 1;
				while(is_file($_SESSION['uploadPath']."/".$filename)) {
					$ext = getExtension($file);
					$filename = basename($file,".$ext")."-$ct.$ext";  
					$ct++;
				}
				rename($fpath,$_SESSION['uploadPath']."/".$filename);			
				allowWebWrite($_SESSION['uploadPath']."/".$filename,$DEFS);
				$user->audit("file uploaded to inbox", $_SESSION['uploadDestination']);
			}
		}
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","displayMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE","File(s) successfully uploaded");
	$xmlObj->createKeyAndValue("TYPE",$_SESSION['uploadType']);
	$xmlObj->createKeyAndValue("URL",$_SESSION['uploadURL']);
	$xmlObj->setHeader();
}
?>
