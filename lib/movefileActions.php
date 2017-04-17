<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/inbox.php';
include_once '../lib/settings.php';
include_once '../movefiles/moveFiles2.php';
include_once '../settings/settings.php';
include_once '../search/search.php';
include_once '../lib/searchLib.php';
include_once '../centera/centera.php';
include_once '../lib/odbcFuncs.php';
include_once '../lib/xmlObj.php';
include_once '../settings/settings.php';
include_once '../documents/documents.php';

function folderList( $cab, $db_object, $user, $value,$deleted=0, $db_doc ) {
	$doc_id = 0;
	$ct = 0;
	if($value) {
		$v = splitOnQuote($db_object,$value,true);
		$temp_table = searchTable($db_object,$cab,true,$v);

		$sArr = array('COUNT(result_id)');
		$ct = getTableInfo($db_object,$temp_table,$sArr,array(),'queryOne');
	} else {
		$search = new search();
		$temp_table = $search->getSearch($cab,array(),$db_object);
	}

	$myMess = '';
	$indices = array ();
	$folderArr = array ();
	if($value && !$ct) {
		if(check_enable('searchResODBC', $user->db_name) && $user->checkSecurity($cab) == 2) {
			$whereArr = array("cabinet_name='$cab'", "location=''");
			$conn_id = getTableInfo($db_object,'odbc_auto_complete',array('connect_id'),$whereArr,'queryOne');
			if($conn_id) {
				$db_odbc = getOdbcObject($conn_id, $db_doc);
				if($db_odbc) {
					$whereArr = array('cabinet_name'=>$cab,'level'=>1,'previous_value'=>1);
					$type = getTableInfo($db_object,'odbc_mapping',array('quoted'),$whereArr,'queryOne');

					$check = true;
					if(!$type && !is_numeric($value)) {
						$check = false;
					}
					
					if($check) {
						$gblStt = new GblStt ($user->db_name, $db_doc);
						$row = getODBCRow($db_odbc,$value,$cab,$db_object, '', $user->db_name,$gblStt);
						if(PEAR::isError($row)) {
							$user->audit('Bad ODBC Query',$row->getMessage());
							$row = array();

							$myMess = 'Bad ODBC Query';
						} else {
							if($row) {
								$row['deleted'] = 0;
								$doc_id = getTableInfo($db_object,$cab,array('doc_id'),$row,'queryOne');
								if(!$doc_id) {
									$temp_table = '';
									$doc_id = createFolderInCabinet($db_object, $gblStt, $db_doc, 
										$user->username,$user->db_name,$cab,
										array_values($row),array_keys($row),$temp_table);
								}
								$indices = array_keys($row);
								$folderArr[$doc_id] = array_values($row);
							}
						}
					}
				} else {
					$myMess = 'Error searching external database';
					$user->audit('ODBC Fatal Error','Cannot Establish ODBC Connection');
				}
			}
		}
	}

	if(!$doc_id and $temp_table) {
		$indices = getCabinetInfo($db_object,$cab);
		$folderArr = getTableInfo($db_object, array($cab, $temp_table), array('doc_id',implode(",",$indices)),
						array("$cab.doc_id = $temp_table.result_id", 'deleted = 0'), 'getAssoc',array('doc_id' => 'DESC'), 25);
	}

	$docView = ($user->checkSetting('documentView',$cab)) ? 1 : 0;	

	$xmlObj = new xml('CABINET');
	$xmlObj->setRootAttribute("docView",$docView);
	if($myMess) {
		$xmlObj->createKeyAndValue("MESSAGE",$myMess);
	}

	foreach ($indices as $ind) {
		$xmlObj->createKeyAndValue("INDICE",$ind);
	}

	foreach ($folderArr as $doc_id => $fields) {
		//create and append element
		$parentEl = $xmlObj->createKeyAndValue("FOLDER",NULL,array('doc_id'=>$doc_id));
		if( is_array($fields) ) {
			foreach ($fields as $f) {
				$xmlObj->createKeyAndValue("FIELD",$f,array(),$parentEl);
			}
		} else {
			$xmlObj->createKeyAndValue("FIELD",$fields,array(),$parentEl);
		}
	}
	$xmlObj->setHeader();
}

function tabList ($cab, $doc_id, $db_object, $deleted = 0) {
 	$whereArr = array ('filename' => 'IS NULL', 'doc_id' => (int) $doc_id,
 			'display' => 1, 'deleted' => 0);
 	$tabList = getTableInfo ($db_object, $cab.'_files',
 			array('DISTINCT(subfolder)'), $whereArr, 'queryCol');
 	$tabList = array_merge (array ('main'), $tabList);
  
	$xmlObj = new xml('FOLDER');
 	foreach ($tabList as $myTab) {
		$xmlObj->createKeyAndValue('TAB',$myTab);
	}
	$xmlObj->setHeader();
}

function documentList($cab,$doc_id,$user, $db_dept) {
	$sArr = array('document_table_name','id','document_type_name');
	$docTypeArr = getTableInfo($db_dept,'document_type_defs',$sArr,array(),'getAssoc');
	
	$sArr = array('document_id','document_table_name','subfolder');
	$wArr = array(	'doc_id='.(int)$doc_id,
					'deleted=0',
					'document_id != 0');
	$docList = getTableInfo($db_dept,$cab.'_files',$sArr,$wArr,'queryAll');
	$allFieldValues = array ();
	foreach($docList as $info) {
		$id = $info['document_id'];
		$name = $info['document_table_name'];
		$type = $docTypeArr[$name]['document_type_name'];
		$tab = $info['subfolder'];
		$sArr = array('document_field_value');			
		$wArr = array(	'document_defs_list_id' => (int)$docTypeArr[$name]['id'],
						'document_id' => (int)$id);
		$fieldValueArr = getTableInfo($db_dept,'document_field_value_list',$sArr,$wArr,'queryCol');
		$allFieldValues[] = array ('tab' => $tab, 'type' => $type, 'fieldValues' => $fieldValueArr);
	}

	$enArr = array('cab' => $cab);
	$db_doc = getDbObject('docturon');
	$docTypeArr = getDocumentTypes($enArr,$user,$db_doc,$db_dept);

	$xmlObj = new xml('FOLDER');
	foreach ($allFieldValues as $fieldValueInfo) {
		$attArr = array('name' => $fieldValueInfo['tab']);
		$docStr = $fieldValueInfo['type'].": ".implode(" ",$fieldValueInfo['fieldValues']);
		$xmlObj->createKeyAndValue("DOCUMENT",$docStr,$attArr);
	}

	foreach($docTypeArr AS $name => $type) {
		$xmlObj->createKeyAndValue("DOCTYPE",$type,array('name' => $name));
	}
	$xmlObj->setHeader();
}

function getSelectedFiles( $cab, $doc_id, $user, $db_doc, $db_object) {
	global $DEFS;

	$gblStt = new gblStt($user->db_name, $db_doc);
	$destCab    = $_POST['destCab'];//destination cab
	if (isset ($_POST['destDoc_id'])) {
		$destFolder = $_POST['destDoc_id'];//destination doc_id
	} else {
		$destFolder = '';
	}
	if($destCab == "personal") {
		$folderTo = "Personal Inbox";
	} elseif($destCab == "inbox") {
		$folderTo = "Public Inbox";
	} else {
		$folder = getCabIndexArr($destFolder,$destCab,$db_object); 
		$folderTo = "Cabinet: ".$user->cabArr[$destCab]." Folder: ".implode(" ",$folder);
		if( $_POST['destTab'] != "main" ) {
			$destTab = $_POST['destTab'];//destination tab
			$folderTo .= " Tab: ".$destTab;
		} else {
			$destTab = NULL;
		}
	} 
	$moveType   = $_POST['moveType'];//move type cut/copy
	$orderType = getOrderSett($user->db_name,$cab,$db_doc);	
	$lockTableArr = array($cab,$cab."_files","audit");
	if($cab != $destCab && $destCab != "personal" && $destCab != "inbox") {
		$lockTableArr[] = $destCab;
		$lockTableArr[] = $destCab."_files";
	}
	
	$filesTable = createFilesTable( $db_object );
	$lockTableArr[] = $filesTable;
	lockTables($db_object, $lockTableArr);

	$selArr = array();
	$selArr = getAssocSelectedList( $db_object, $cab, $doc_id );
    $folderLoc = getTableInfo($db_object,$cab,array('location'),array('doc_id'=>(int)$doc_id),'queryOne');
    $folderLoc = str_replace( " ", "/", $folderLoc );
    $selectedFilesList = $_POST['check'];
    $selectedFiles = array ();
    foreach( $selectedFilesList AS $selected ) {
        $filename = str_replace( "*", ".", $selected );
		if (isset($selArr[$filename])) {
			$fname = $selArr[$filename];
			$selectedFiles[] = $fname;
		        $selFilesArr[] = array(	"filename"  => $fname,
							"ext"       => substr(strrchr($fname, "."), 1),
							"path"      => $folderLoc."/".$fname );
		}
    }
	
    foreach( $selFilesArr AS $fileArr ) {
        $db_object->extended->autoExecute( $filesTable, $fileArr );
    }
	$destFileList = array();
	//get the destination location
	$destLoc = $DEFS['DATA_DIR']."/";
	if( $destCab == "personal" ) {
		$destLoc = createDestLocForInbox( $user, $destLoc, "personalInbox" );
		createDestFileList( $destLoc, $destFileList );
	} else if( $destCab == "inbox" ) {
		$destLoc = createDestLocForInbox( $user, $destLoc, "inbox" );
		createDestFileList( $destLoc, $destFileList );
	} else {
    	$destfolderLoc = getTableInfo($db_object,$destCab,array('location'),array('doc_id'=>(int)$destFolder),'queryOne');
		$destLoc .= str_replace( " ", "/", $destfolderLoc )."/";
		if( $_POST['destTab'] != "main" ) {
			$destLoc .= $destTab."/";
		} 
		$orderID = getOrderingValue($db_object, $destCab."_files",$destFolder,$destTab,$orderType,sizeof($selectedFiles));
//		$fileID = getTableInfo($db_object,$destCab."_files",array('MAX(id)+1'),array(),'queryOne');
		$whereArr = array('doc_id'=>(int)$destFolder,'filename'=>'IS NOT NULL');	
		if($destTab) {
			$whereArr['subfolder'] = $destTab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$dfList = getTableInfo($db_object,$destCab."_files",array('filename','parent_filename'),$whereArr,'getAssoc');
		$destFileList = array_map("strtolower",array_merge(array_keys($dfList),array_values($dfList)));
	}

	$wholeFileList = array();
	$whereArr = array('doc_id'=>(int)$doc_id,'filename'=>'IS NOT NULL');
	$orderArr = array('subfolder'=>'ASC','ordering'=>'ASC','v_minor'=>'ASC');
	$wholeFileList = getTableInfo($db_object,$cab."_files",array(),$whereArr,'queryAll',$orderArr);
	$fileArr = array();
	$cutArr = array();
	$parent_filename = "";

	$folder = getCabIndexArr($doc_id,$cab,$db_object); 
	$folderFrom = "Cabinet: ".$user->cabArr[$cab]." Folder: ".implode(" ",$folder);
	$oldFileString = '';
	$oldParent = '';
	$currentFileListLocations = array();
	$centeraInfos = array ();
	
	$tempFile = fopen('/tmp/cent_error/', 'a+');
	
	foreach( $wholeFileList AS $wholeList ) {
		$parent_filename	= $wholeList['parent_filename'];
		$subfolder			= $wholeList['subfolder'];
		$fname				= $wholeList['filename'];

		if( $subfolder != NULL ) {
			$fileString = $subfolder."/".$parent_filename;
			$audit[] = $folderFrom." Tab: ".$subfolder." Filename: ".$parent_filename;
		} else {
			$fileString = $parent_filename;
			$audit[] = $folderFrom." Filename: ".$parent_filename;
		}
		$tempFile = fopen('/tmp/cent_error/', 'w+');
		if( in_array( $fileString, $selectedFiles ) ) {
			setCurrentFileLocation( $folderLoc, $subfolder, $fname, $currentFileListLocations );
			if( check_enable('centera',$user->db_name) ){
				$cenTemp = array ('hash' => $wholeList['ca_hash'],
					'size' => $wholeList['file_size'],
					'name' => $folderLoc.'/'.$subfolder.'/'.$fname);
				$centeraInfos[] = $cenTemp;
				fwrite($tempFile, "Time ". date('r') . ": ".print_r($cenTemp, true));	
			}
			$cutArr[] = array((int)$wholeList['id'] );
				
			if( $oldFileString == $fileString  ) {
				$parent_filename = $oldParent;
				$filename = getVersFilename( $fname, $wholeList, $destFileList );
				$orderID--;
			} else {
				//checks to see if the the version number of the parent has been changed
				if( $parent_filename == $fname ) {
					$parent_filename = getNewFilename( $parent_filename, $destFileList );
					$filename = $parent_filename;
				} else {
					$parent_filename = getNewFilename( $parent_filename, $destFileList );
					$filename = getVersFilename( $fname, $wholeList, $destFileList );
				}
//				$parentID = $fileID;
			}
			$destFileListLocations[] = $destLoc.$filename;
			if( $destCab != "personal" && $destCab != "inbox" ) {
				$fileArr[] = array(
					"id"		    => $wholeList['id'], 
					"filename"          => $filename,
					"doc_id"            => (int)$destFolder,
					"subfolder"         => $destTab,
					"ordering"          => (int)$orderID,
					"date_created"      => $wholeList['date_created'],
					"date_to_delete"    => ($wholeList['date_to_delete'] ? $wholeList['date_to_delete'] : NULL),
					"who_indexed"       => $wholeList['who_indexed'],
					"access"            => $wholeList['access'],
					"ocr_context"       => $wholeList['ocr_context'],
					"notes"             => $wholeList['notes'],
					"deleted"           => (int)$wholeList['deleted'],
					"parent_id"         => ($wholeList['parent_id']) ? $wholeList['parent_id']  : $wholeList['id'],
					"v_major"           => (int)$wholeList['v_major'],
					"v_minor"           => (int)$wholeList['v_minor'],
					"parent_filename"   => $parent_filename,
					"who_locked"        => $wholeList['who_locked'],
					"date_locked"       => ($wholeList['date_locked'] ? $wholeList['date_locked'] : NULL),
					"display"	        => (int)$wholeList['display'],
					'redaction_id'		=> (int)$wholeList['redaction_id'],
					'redaction'			=> $wholeList['redaction'],
					"file_size"         => (int)$wholeList['file_size'] );
				$orderID++;
			}

//			$fileID++;
			$oldFileString = $fileString;
			$oldParent = $parent_filename;
		}
    }
	fclose($tempFile);
	if( $destCab == "personal" && $destCab == "inbox" ) {
		if( !is_dir($destLoc) ) {
			mkdir($destLoc);
		}
	}
	if(check_enable('centera',$user->db_name)) {
		multiCentGet($DEFS['CENT_HOST'], $centeraInfos,$user);
	}
	if( $moveType == "cut" ) {
		$query = "DELETE FROM ".$cab."_files WHERE id = ?";
		$preparedQuery = $db_object->prepare( $query );
		$db_object->extended->executeMultiple( $preparedQuery, $cutArr );
		//move the files to there new destinations
		for($i=0;$i<sizeof($currentFileListLocations);$i++) {
			rename( $currentFileListLocations[$i], $destFileListLocations[$i] );
			if(check_enable('centera',$user->db_name)) {
				//DELETE CA_HASH FROM CENTERA - $centeraInfos[$i]['hash']
				if($destCab != 'personal' and $destCab != 'inbox') {
					$newCA = centput($destFileListLocations[$i], $DEFS['CENT_HOST'],$user, $cab);
					$fileArr[$i]['ca_hash'] = $newCA;
				}
			}
			if(file_exists($currentFileListLocations[$i].'.adminRedacted.ca_hash')
				and check_enable('centera',$user->db_name)) {
				
				$caArr = explode(',', file_get_contents($currentFileListLocations[$i].'.adminRedacted.ca_hash'));
				centget($DEFS['CENT_HOST'], $caArr[0], $caArr[1], $currentFileListLocations[$i].'.adminRedacted',$user,$cab);
				rename( $currentFileListLocations[$i].'.adminRedacted',
					$destFileListLocations[$i].'.adminRedacted' );
				//DELETE CA_HASH FROM CENTERA - $centeraInfos[$i]['hash']
				if($destCab != 'personal' and $destCab != 'inbox') {
					$newCA = centput($destFileListLocations[$i].'.adminRedacted', $DEFS['CENT_HOST'],$user, $destCab);
					$fileArr[$i]['ca_hash'] = $newCA;
				}
			} elseif(file_exists($currentFileListLocations[$i].'.adminRedacted')) {
				rename( $currentFileListLocations[$i].'.adminRedacted',
					$destFileListLocations[$i].'.adminRedacted' );
			}
			$user->audit("Files Cut", "FROM: $audit[$i] TO: $folderTo",$db_object);
			$thumbPath = str_replace($user->db_name,$user->db_name."/thumbs",$currentFileListLocations[$i]);
			if(file_exists($thumbPath.".jpeg")) {
				unlink($thumbPath.".jpeg");
		}
		}
	} else {
		//copy files over to new destination
		for($i=0;$i<sizeof($currentFileListLocations);$i++) {
			copy( $currentFileListLocations[$i], $destFileListLocations[$i] );
			if(check_enable('centera',$user->db_name)) {
				if($destCab != 'personal' and $destCab != 'inbox') {
					$newCA = centput($destFileListLocations[$i], $DEFS['CENT_HOST'],$user, $cab);
					$fileArr[$i]['ca_hash'] = $newCA;
				}
			}
			if(file_exists($currentFileListLocations[$i].'.adminRedacted.ca_hash')
				and check_enable('centera',$user->db_name)) {
					$caArr = explode(',', file_get_contents($currentFileListLocations[$i].'.adminRedacted.ca_hash'));
				centget($DEFS['CENT_HOST'], $caArr[0], $caArr[1], $currentFileListLocations[$i].'.adminRedacted',$user,$cab);
				copy( $currentFileListLocations[$i].'.adminRedacted',
					$destFileListLocations[$i].'.adminRedacted' );
				if($destCab != 'personal' and $destCab != 'inbox') {
					$newCA = centput($destFileListLocations[$i].'.adminRedacted', $DEFS['CENT_HOST'],$user, $destCab);
					$fileArr[$i]['ca_hash'] = $newCA;
				}
			} elseif(file_exists($currentFileListLocations[$i].'.adminRedacted')) {
				copy( $currentFileListLocations[$i].'.adminRedacted',
					$destFileListLocations[$i].'.adminRedacted' );
			}
			$user->audit("Files Copied", "FROM: $audit[$i] TO: $folderTo",$db_object);
		}
	}

	if( $destCab != "personal" && $destCab != "inbox" ) {
		$parentIDArr = array();	
		foreach( $fileArr AS $fileInfo ) {
			$lookupID = false;
			$curParentID = $fileInfo['parent_id'];
			//if the file is the original file, and not a versionned copy
			if( $fileInfo['id'] == $curParentID ) {
				$oldFileID = $fileInfo['id'];
				$lookupID = true;
			//check to see if the parentID has changed for the parent file
			} elseif( isSet($parentIDArr[$curParentID]) ) {
				$fileInfo['parent_id'] = $parentIDArr[$curParentID];
			}
			unset ($fileInfo['id']);

			$res=$db_object->extended->autoExecute( $destCab."_files", $fileInfo );
			dbErr ($res);

			if($lookupID) {
				$sArr = array('MAX(id)');
				//get the new fileID of the moved file
				$maxFileID = getTableInfo($db_object,$destCab.'_files',$sArr,array(),'queryOne');
				//set the new parentID/fileID so that later entries with the old parent_id is updated
				$parentIDArr[$oldFileID] = $maxFileID;
				//update the new parentID for the original file and any versionned files of the original
				updateTableInfo($db_object, $destCab.'_files', array('parent_id' => $maxFileID), 
					array("id" => (int)$maxFileID));
			}
		}
	}

	unlockTables($db_object);
}

function getDocInfo($docType,$db_dept) {
	$sArr = array('real_field_name','arb_field_name');
	$whereArr = array('document_table_name'	=> $docType);
	$orderArr = array('ordering' => 'ASC');
	$docFieldArr = getTableInfo($db_dept,'document_field_defs_list',$sArr,$whereArr,'getAssoc',$orderArr);

	$sArr = array('id');
	$wArr = array('document_table_name'	=> $docType);
	$docTypeID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$sArr = array('field_name', 'required', 'regex', 'display');
	$wArr = array(	'document_table_name' => $docType);
	$regList = getTableInfo($db_dept,'field_format',$sArr,$wArr,'getAssoc');

	$xmlObj = new xml('FOLDER');
 	foreach ($docFieldArr as $k => $f) {
		$parentEl = $xmlObj->createKeyAndValue('DOCFIELD',$f,array('name'=>$k));

		$defsList = array();
		$sArr = array('definition');
		$wArr = array(	'document_type_id' => (int)$docTypeID,
						'document_type_field' => $k);
		$defsList = getTableInfo($db_dept,'definition_types',$sArr,$wArr,'queryCol');
		foreach($defsList AS $def) {
			$xmlObj->createKeyAndValue("DEFINITION",$def,array(),$parentEl);
		}

		if(isSet($regList[$k])) {
			$xmlObj->createKeyAndValue("REQUIRED",$regList[$k]['required'],array(),$parentEl);
			$xmlObj->createKeyAndValue("REGEX",$regList[$k]['regex'],array(),$parentEl);
			$xmlObj->createKeyAndValue("DISPLAY",$regList[$k]['display'],array(),$parentEl);
		}
	}
	$xmlObj->setHeader();
}

if( $logged_in == 1 && $user->username ) {
	if( isSet( $_GET['cab'] ) ) {
		$cab = $_GET['cab'];
	}
	if( isSet( $_GET['deleted'] ) ) {
		$deleted = $_GET['deleted'];
	} else {
		$deleted = '';
	}

	if (isset ($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		$action = '';
	}
	if( $action == "folderlist" ) {
		if( isSet($_POST['searchValue'] ) ) {
			$value = $_POST['searchValue'];
		} else {
			$value = '';
		}
		folderList( $cab, $db_object, $user, $value,$deleted, $db_doc );
	} elseif($action == "doclist") {
		documentList($cab,$_GET['doc_id'],$user, $db_object);
	} elseif( $action == "tablist" ) {
		$doc_id = $_GET['doc_id'];
		tabList($cab,$doc_id,$db_object, $user->db_name, $deleted);
	} elseif( $action == 'filelist') {
		$doc_id = $_GET['doc_id'];
		$tab = $_GET['tab'];
		fileList($cab, $doc_id, $tab, $db_object);
	} elseif( $action == "selectedFiles" ) {
		$doc_id = $_GET['doc_id'];
		getSelectedFiles( $cab, $doc_id, $user, $db_doc, $db_object );
	} elseif($action == "getDocInfo") {
		$docType = $_GET['docType'];
		getDocInfo($docType,$db_object);
	}
		
	setSessionUser($user);
} else {
	logUserOut();
}


?>
