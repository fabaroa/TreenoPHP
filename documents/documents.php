<?php
include_once '../db/db_common.php';
include_once 'documents.inc.php';
include_once '../lib/audit.php';
include_once '../search/documentSearch.php';
include_once '../lib/webServices.php';
include_once '../lib/licenseFuncs.php';

function xmlUpdateDocumentFields($enArr,$user, $db_doc, $db_object) {
	updateDocumentFields ($enArr, $db_doc, $db_object, false);
}

function updateDocumentFields($enArr,$db_doc,$db_object, $useRealNames = false) {
	$sArr = array('document_table_name');
	$whereArr = array('id' => $enArr['subfolderID']);
	$docTableName = getTableInfo($db_object,$enArr['cabinet'].'_files',$sArr,$whereArr,'queryOne');

	$newDoc = new document($docTableName,$enArr['cabinet'],$enArr['doc_id'],
						$enArr['subfolderID'],$db_object);
	for($i=0;$i<$enArr['field_count'];$i++) {
		$name = $enArr['key'.$i];
		$value = $enArr[$name];
		$newDoc->setFieldForDocument($name,$value,$useRealNames);
	}

	if(isset ($enArr['subfolder']) and isset ($enArr['new_subfolder']) and $enArr['subfolder'] != $enArr['new_subfolder']) {
		$newDoc->setSubfolderForDocument($enArr['new_subfolder']);
		$newDoc->unsetDocumentForCabinetFiles();
		$newDoc->setDocumentForCabinetFiles($enArr['new_subfolder']);
	}
}

function deleteDocument($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	$recBin = true;
	$gblSettings = new GblStt($user->db_name, $db_doc);
	if($gblSettings->get('deleteRecyclebin') === "0") {
		$recBin = false;
	}

	$userSettings = new Usrsettings($user->username,$user->db_name);
	if($userSettings->get('deleteRecyclebin') === "0") {
		$recBin = false;
	}

	$sArr = array('subfolder','document_table_name','document_id');	
	$wArr = array('id' => (int)$enArr['subfolderID']);
	$docInfo = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'queryRow');
	$subfolder = $docInfo['subfolder'];
	
	$wArr = array('document_table_name' => $docInfo['document_table_name']);
	$documentID = getTableInfo($db_dept,'document_type_defs',array('id'),$wArr,'queryOne');
	
	$sArr = array('document_field_value');
	$wArr = array(	'document_defs_list_id' => (int)$documentID,
					'document_id'			=> (int)$docInfo['document_id']);
	$docFieldArr = getTableInfo($db_dept,'document_field_value_list',$sArr,$wArr,'queryCol');
	
	$folderAuditStr = getFolderAuditStr($db_dept,$enArr['cabinet'],$enArr['doc_id']);
	$auditMsg = "Cabinet: ".$enArr['cabinet'].", Folder: ".$folderAuditStr;
	$auditMsg .= ", Tab: ".$subfolder.", Document: (".implode(",",$docFieldArr).")";
	if($recBin) {	
		$uArr = array(	'deleted'	=> 1,
						'display'	=> 0);
		$wArr = array('id' => (int)$enArr['subfolderID']);
		updateTableInfo($db_dept,$enArr['cabinet'].'_files',$uArr,$wArr);

		if ($docInfo['document_table_name']) {
			$wArr = array(	'cab_name' 	=> $enArr['cabinet'],
							'file_id'	=> (int)$enArr['subfolderID']);
			$uArr = array ('deleted' => 1);
			updateTableInfo($db_dept,$docInfo['document_table_name'],$uArr,$wArr);
		}
		$user->audit("document marked for deletion",$auditMsg);
	} else {
		$quota = 0;
		$sArr = array('location');
		$wArr = array('doc_id' => (int)$enArr['doc_id']);
		$loc = getTableInfo($db_dept,$enArr['cabinet'],$sArr,$wArr,'queryOne');
		$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);

		$location .= "/".$subfolder;

		$sArr = array('id','filename','file_size');	
		$wArr = array(	'doc_id'	=> (int)$enArr['doc_id'],
						'subfolder'	=> $subfolder,
						'filename'	=> 'IS NOT NULL' );
		$fileArr = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'getAssoc');
		foreach($fileArr AS $id => $fileInfo) {
			if(file_exists($location."/".$fileInfo['filename'])) {
				if(unlink($location."/".$fileInfo['filename'])) {
					$quota += $fileInfo['file_size'];
					deleteTableInfo($db_dept,$enArr['cabinet'].'_files',array('id' => (int)$id));
				}
			} else {
				deleteTableInfo($db_dept,$enArr['cabinet'].'_files',array('id' => (int)$id));
			}
		}

		if(is_dir($location)) {
			if(rmdir($location)) {
				$quota += 4096;
				deleteTableInfo($db_dept,$enArr['cabinet'].'_files',array('id' => (int)$enArr['subfolderID']));
			}
		}

		$wArr = array('id' => (int)$docInfo['document_id']);
		deleteTableInfo($db_dept,$docInfo['document_table_name'],$wArr);

		$wArr = array(	'document_defs_list_id' => (int)$documentID,
						'document_id'			=> (int)$docInfo['document_id']);
		$docFieldArr = deleteTableInfo($db_dept,'document_field_value_list',$wArr);

		$user->audit("document permanently deleted",$auditMsg);
		if($quota) {
			$uArr = array('quota_used' => 'quota_used-'.(int)$quota);
			$wArr = array('real_department' => $user->db_name);
			updateTableInfo($db_doc,'licenses',$uArr,$wArr,1);
		}
	}

	$wArr = array("cab='".$enArr['cabinet']."'",
			'doc_id='.(int)$enArr['doc_id'], 
			'file_id='.(int)$enArr['subfolderID'],
			"status!='COMPLETED'");
	$id = getTableInfo($db_dept,'wf_documents',array('id'),$wArr,'queryOne');
	$wArr = array('id' => (int)$id);
	deleteTableInfo($db_dept,'wf_documents',$wArr);

	$wArr = array('department'=>$user->db_name);
	$wArr['wf_document_id'] = (int)$id;
	deleteTableInfo($db_doc,'wf_todo',$wArr);
}

function getDocumentFieldsAndValue($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('document_id','document_table_name','subfolder');
	$whereArr = array('id' => (int)$enArr['subfolderID'] );
	$cabInfo = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$whereArr,'queryRow');

	$documentID = $cabInfo['document_id'];
	$docName = $cabInfo['document_table_name'];
	//$subfolder = $cabInfo['subfolder'];

	$sArr = array('id');
	$wArr = array('document_table_name'	=> $docName);
	$docTypeID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$tableArr = array('document_field_defs_list','document_field_value_list');
	$sArr = array('real_field_name','arb_field_name','document_field_value');
	$whereArr = array(	'document_field_defs_list_id=document_field_defs_list.id',
						'document_id='.(int)$documentID,
						"document_table_name='".$docName."'" );
	$oArr = array('ordering' => 'ASC');
	$docInfo = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'getAssoc');

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION","editDocumentTypeFields(XML)");
	foreach($docInfo AS $real => $info) {
		$parentEl = $xmlObj->createKeyAndValue("FIELD",$info['document_field_value'],array('name'=>$info['arb_field_name']));

		$defsList = array();
		$sArr = array('definition');
		$wArr = array(	'document_type_id' => (int)$docTypeID,
						'document_type_field' => $real);
		$defsList = getTableInfo($db_dept,'definition_types',$sArr,$wArr,'queryCol');
		foreach($defsList AS $def) {
			$xmlObj->createKeyAndValue("DEFINITION",$def,array(),$parentEl);
		}
	}
	$xmlObj->setHeader();
}

function xmlGetFolderDocuments($enArr, $user,$db_doc,$db_dept) {
	$docFields = array();
	$sortDir = null;

	if(isSet($enArr['filter'])) {
		$sArr = array('id','arb_field_name');
		$wArr = array('document_table_name' => $enArr['filter'] );
		$oArr = array('arb_field_name' => 'ASC');
		$docFields = getTableInfo($db_dept,'document_field_defs_list',$sArr,$wArr,'getAssoc',$oArr);
	}

	if(isSet($enArr['sortDir'])) {
		$sortDir = $enArr['sortDir'];
	}
	$docSett = getDocumentSettings($enArr,$user,$db_doc);
	$tempTable = getFolderDocuments2($enArr, $user,$db_doc,$db_dept);
	getFolderDocumentsXML($enArr['cabinet'],$enArr['doc_id'],$docSett,$tempTable,$user,$db_dept,$docFields,$sortDir);
}

function xmlPageFolderDocuments($enArr,$user,$db_doc,$db_dept) {
	$docSett = getDocumentSettings($enArr,$user,$db_doc);
	$start = ($enArr['page'] - 1) *25;
	$sortDir = (isSet($enArr['sortDir'])) ? $enArr['sortDir'] : "";
	getFolderDocumentsXML($enArr['cabinet'],$enArr['doc_id'],$docSett,$enArr['tempTable'],$user,$db_dept,array(),$sortDir,$start);

/*
	$cab = $enArr['cabinet'];
	$tempTable = $enArr['tempTable'];

	$xmlObj = new xml("ENTRY");
	foreach($docSett AS $k => $sett) {
		$xmlObj->setRootAttribute($k,$sett);
	}
	$xmlObj->createKeyAndValue("FUNCTION","setDocumentPage(XML)");

	$sArr = array('document_table_name','document_type_name');
	$docTypeArr = getTableInfo($db_dept,'document_type_defs',$sArr,array(),'getAssoc');

	$start = ($enArr['page'] - 1) *25;
	$sArr = array('document_id','document_table_name','result_id');
	$tArr = array($tempTable,$cab."_files");
	$wArr = array("$tempTable.result_id = ".$cab."_files.id");
	$oArr = array('result_id' => 'DESC');
	$docArr = getTableInfo($db_dept,$tArr,$sArr,$wArr,'getAssoc',$oArr,$start,25);
	foreach($docArr as $id => $name) {
		$attArr = array('name' => $docTypeArr[$name['document_table_name']],'id' => $name['result_id']);
		if($user->checkSetting("showDocumentCreation",$cab)) {
			$sArr = array("date_created");
			$wArr = array("id" => (int)$id);
			$date_created = getTableInfo($db_dept,$name['document_table_name'],$sArr,$wArr,'queryOne');
			$attArr['date_created'] = $date_created;
		}
		$parentEl = $xmlObj->createKeyAndValue("DOCUMENT",NULL,$attArr);

		$tableArr = array('document_field_defs_list','document_field_value_list');
		$sArr = array(	'arb_field_name',
						'document_field_value' );
		$whereArr = array(	'document_field_defs_list_id=document_field_defs_list.id',
							'document_id='.(int)$id,
							"document_table_name='".$name['document_table_name']."'" );
		$oArr = array('ordering' => 'ASC');
		$docInfo = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'getAssoc',$oArr);
		foreach($docInfo AS $key => $value) {
			$xmlObj->createKeyAndValue("FIELD",$value,array('name'=>$key),$parentEl);
		}
	}
	$xmlObj->setHeader();
*/
}

function getFolderDocumentsXML($cab,$doc_id,$docSett,$tempTable,$user,$db_dept,$docFields=array(),$sortDir=null,$start=-1) {
	global $DEFS;

	$docList = array();
	$xmlObj = new xml("ENTRY");
	foreach($docSett AS $k => $sett) {
		$xmlObj->setRootAttribute($k,$sett);
	}

	$ocr = 0;
	if(check_enable("ocr",$user->db_name)) {
		$ocr = 1;	
	}

	$sArr = array('COUNT(result_id)');
	$docCt = getTableInfo($db_dept,$tempTable,$sArr,array(),'queryOne');
	$xmlObj->setRootAttribute('document_count',$docCt);
	$xmlObj->setRootAttribute('tempTable',$tempTable);
	$xmlObj->setRootAttribute('ocr',$ocr);

	if($start < 0) {
		$xmlObj->createKeyAndValue("FUNCTION","setFolderDocuments(XML)");
		$start = 0;
	} else {
		$xmlObj->createKeyAndValue("FUNCTION","setDocumentPage(XML)");
	}

	if(count($docFields)) {
		foreach($docFields AS $fid => $fname) {
			$xmlObj->createKeyAndValue("DOC_FIELD",$fname,array('fid' => $fid));
		}
	}

	$sArr = array('document_table_name','document_type_name');
	$docTypeArr = getTableInfo($db_dept,'document_type_defs',$sArr,array(),'getAssoc');

	$sArr = array('document_id','document_table_name','result_id','subfolder','table_id');
	$tArr = array($tempTable,$cab."_files");
	$wArr = array("$tempTable.result_id = ".$cab."_files.id");
	if($sortDir) {
		$sArr[] = "field_value";
		$oArr = array('field_value' => $sortDir);
	} else {
		$oArr = array('table_id' => 'ASC');
	}
	$docArr = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryAll',$oArr,$start,25);
	foreach($docArr as $info) {
		if($info['document_table_name']) {
			$attArr = array('name' => $docTypeArr[$info['document_table_name']],'id' => $info['result_id']);
			$docList[$info['document_table_name']] = $docTypeArr[$info['document_table_name']];
		} else {
			if($info['subfolder']) {
				$attArr = array('name' => 'DEFAULT','id' => $info['result_id']);
			} else {
				$attArr = array('name' => 'DEFAULT','id' => -1);
			}
			$docList['DEFAULT'] = 'DEFAULT';
		}
		if($user->checkSetting("showDocumentCreation",$cab) && $info['document_table_name']) {
			$sArr = array("date_created");
			$wArr = array("id" => (int)$info['document_id']);
			$date_created = getTableInfo($db_dept,$info['document_table_name'],$sArr,$wArr,'queryOne');
			$attArr['date_created'] = $date_created;
		}
		$parentEl = $xmlObj->createKeyAndValue("DOCUMENT",NULL,$attArr);

		if($info['document_table_name']) {
			$tableArr = array('document_field_defs_list','document_field_value_list');
			$sArr = array(	'arb_field_name',
							'document_field_value' );
			$whereArr = array(	'document_field_defs_list_id=document_field_defs_list.id',
								'document_id='.(int)$info['document_id'],
								"document_table_name='".$info['document_table_name']."'" );
			$oArr = array('ordering' => 'ASC');
			$docInfo = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'getAssoc',$oArr);
			foreach($docInfo AS $key => $value) {
				$xmlObj->createKeyAndValue("FIELD",$value,array('name'=>$key),$parentEl);
			}
		} else {
			if($info['subfolder']) {
				$xmlObj->createKeyAndValue("FIELD",str_replace("_"," ",$info['subfolder']),array('name'=>'subfolder'),$parentEl);
			} else {
				$xmlObj->createKeyAndValue("FIELD",'main',array('name'=>'subfolder'),$parentEl);
			}
		}
	}

	$sArr = array('DISTINCT(document_table_name)');
	$wArr = array(	'doc_id='.(int)$doc_id,
					'document_id != 0',
					'deleted = 0');
	$docList = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'queryCol');
	$dArr = array();
	foreach($docList AS $d) {
		$dArr[$d] = $docTypeArr[$d];
	}
	uasort($dArr,"strnatcasecmp");

	$sArr = array('COUNT(id)');
    $wArr = array('doc_id' => (int)$doc_id,
                'document_id' => 0,
                'filename' => 'IS NULL',
                'deleted' => 0 );
    $ct = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'queryOne');
    if($ct) {
        $dArr['DEFAULT'] = 'DEFAULT';
    } else {
        $sArr = array('COUNT(id)');
        $wArr = array('doc_id' => (int)$doc_id,
                    'subfolder' => 'IS NULL',
                    'deleted' => 0 );
        $mct = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'queryOne');
        if($mct) {
            $dArr['DEFAULT'] = 'DEFAULT';
        }
    }
	
	if(isSet($DEFS['DOC_TYPE_EXCLUSION'])) {
		foreach(explode(",",$DEFS['DOC_TYPE_EXCLUSION']) AS $name) {
			$tbl = array_search($name,$dArr);
			if($tbl) {
				$xmlObj->createKeyAndValue("DOCLIST","All Docs Except ".$name,array('table' => 'NOT-'.$tbl));
			}
		}
	}

	foreach($dArr AS $tbl => $doc) {
		$xmlObj->createKeyAndValue("DOCLIST",$doc,array('table' => $tbl));
	}
	$xmlObj->setHeader();
}

function getDocumentSettings($enArr,$user,$db_doc) {
	$add = false;
	$cab = $enArr['cabinet'];
	if($user->checkSecurity($cab) == 2 && $user->checkSetting('addDocument',$enArr['cabinet']) && isValidLicense($db_doc)) {
		$add = true;
	}
	$sett['add'] = $add;

	$edit = false;
	if($user->checkSecurity($cab) == 2 && $user->checkSetting('editDocument',$enArr['cabinet']) && isValidLicense($db_doc)) {
		$edit = true;
	}
	$sett['edit'] = $edit;

	$delete = false;
	if($user->checkSecurity($cab) == 2 && $user->checkSetting('deleteDocuments',$enArr['cabinet']) && isValidLicense($db_doc)) {
		$delete = true;
	}
	$sett['delete'] = $delete;

	return $sett;	
}

function getFolderDocumentsByFullText($enArr,$db_dept,$docTempTbl) {
	$sArr = array('document_table_name','document_type_name');
	$wArr = array();
	$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc');

	$sArr = array('DISTINCT(subfolder)');
	$wArr = array('doc_id = '.$enArr['doc_id'],
				'deleted = 0',
				'filename IS NOT NULL');
	if($enArr['filter'] != "All" && $enArr['filter'] != "DEFAULT") {
		$wArr[] = "subfolder LIKE '".$docArr[$enArr['filter']]."%'";	
	}
	$wArr[] = "ocr_context LIKE '%".$enArr['search']."%'";
	$docList = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'queryCol');

	$sArr = array('subfolder','document_table_name','id');
	$wArr = array('doc_id' => $enArr['doc_id'],
				'deleted' => 0,
				'filename' => 'IS NULL');
	$tabArr = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'getAssoc');
	
	$sArr = array('id');
	$wArr = array(	'doc_id'		=> (int)$enArr['doc_id'],
					'subfolder'		=> 'IS NULL' );
	$id = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'queryOne');
	$tabArr["main"] = $id;	

	foreach($docList AS $tab) {
		if(!$tab) {
			$tab = "main";
		}

		if(isSet($tabArr[$tab])) {
			$insArr = array('result_id' => $tabArr[$tab]['id']);
		}
		$res = $db_dept->extended->autoExecute($docTempTbl,$insArr);
		dbErr($res);
	}
}

function getFolderDocuments2($enArr,$user,$db_doc,$db_dept) {
	$docArr = array();
	$docList = array();
	$retArr = array ();
	
	$tArr = array('document_type_defs','document_permissions','group_list');
	$sArr = array('document_table_name','groupname');
	$wArr = array('permissions_id != 0',
				  'permissions_id=permission_id',
				  'group_list_id=list_id');
	$permArr = getTableInfo($db_dept,$tArr,$sArr,$wArr,'getAssoc',array(),0,0,array(),true);
	$noSeeArr = array ();
	foreach($permArr AS $k => $groupArr) {
		$check = false;
		foreach($groupArr AS $g) {
			if(in_array($g,$user->groups)) {
				$check = true;
				break;
			}
		}
		if(!$check) {
			$noSeeArr[] = $k;
		}
	}

	$docTempTbl = "";
	$docSeachTempTbl = "";
	
	$sArr = array('id');
	$docTempTbl = createTemporaryTable($db_dept);
	if(isSet($enArr['tab_id']) && $enArr['tab_id'] > 0) {
			$insArr = array('result_id' => $enArr['tab_id']);
			$res = $db_dept->extended->autoExecute($docTempTbl,$insArr);
			dbErr($res);
	} else {
		if(isSet($enArr['fullTextSearch'])) {
			getFolderDocumentsByFullText($enArr,$db_dept,$docTempTbl); 
		} else {
			if($enArr['filter'] != 'DEFAULT') {
				$wArr = array(	'doc_id='.(int)$enArr['doc_id'],
								'document_id != 0',
								'deleted = 0');
				if($enArr['filter'] != 'All' && $enArr['filter'] != 'DEFAULT') {
					if(substr($enArr['filter'],0,4) == "NOT-") {
						$wArr[] = "document_table_name != '".substr($enArr['filter'],4)."'";
					} else {
						$wArr[] = "document_table_name = '".$enArr['filter']."'";
					}
				}
				$oArr = array('id' => 'DESC');
				insertFromSelect($db_dept,$docTempTbl,array('result_id'),$enArr['cabinet']."_files",$sArr,$wArr,$oArr);
			}
		}
	}

	$wArr = array();
	if($enArr['filter'] != 'All') {
		$wArr['document_table_name'] = $enArr['filter'];
	} 
	$sArr = array('document_table_name','id');
	$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc');

	$sArr = array('result_id','document_id','document_table_name');
	$tArr = array($docTempTbl,$enArr['cabinet']."_files");
	$wArr = array("$docTempTbl.result_id = ".$enArr['cabinet']."_files.id");
	$docList = getTableInfo($db_dept,$tArr,$sArr,$wArr,'getAssoc');

	$fArr = array (
		'table_id '.AUTOINC,
		'PRIMARY KEY (table_id)',
		'result_id INT DEFAULT 0',
		'field_value VARCHAR(255) NULL',
	);
	$docSearchTempTbl = createDynamicTempTable($db_dept,$fArr);
	foreach($docList AS $id => $dInfo) {
		if(($user->restore == 1 && $user->file_id == $id) || !in_array ($dInfo['document_table_name'], $noSeeArr)) {
			if(!isSet($enArr['fullTextSearch'])) {
				if($enArr['filter'] != 'DEFAULT' && isSet($enArr['search'])) {
					$fs = $enArr['search'];
					$fs = str_replace("\\","\\\\",$fs);
					$fs = str_replace("%","\%",$fs);
					$fs = str_replace("_","\_",$fs);

					if($dInfo['document_table_name']) {
						$sArr = array('document_field_defs_list_id','document_field_value'); 
						$whereArr = array(	'document_id='.(int)$dInfo['document_id'],
											"document_defs_list_id=".(int)$docArr[$dInfo['document_table_name']],
											"document_field_value ".LIKE." '%".$db_dept->escape($fs)."%'");
						$ct = getTableInfo($db_dept,'document_field_value_list',$sArr,$whereArr,'getAssoc');
						if($ct) {
							$insArr = array('result_id' => (int)$id);
							if(isSet($enArr['sortBy'])) {
								$insArr['field_value'] = $ct[$enArr['sortBy']];
							}
							$db_dept->extended->autoExecute($docSearchTempTbl,$insArr);
						}
					}
				} else if($enArr['filter'] != 'DEFAULT' && isSet($enArr['sortBy'])) {
					$sArr = array('document_field_value'); 
					$whereArr = array(	'document_id='.(int)$dInfo['document_id'],
										"document_defs_list_id=".(int)$docArr[$dInfo['document_table_name']],
										"document_field_defs_list_id=".(int)$enArr['sortBy']);
					$fv = getTableInfo($db_dept,'document_field_value_list',$sArr,$whereArr,'queryOne');
					$insArr = array('result_id' => (int)$id);
					if($fv) {
						$insArr['field_value'] = $fv;
					}
					$db_dept->extended->autoExecute($docSearchTempTbl,$insArr);
				}
			} else {
				if($enArr['filter'] != 'DEFAULT' && isSet($enArr['sortBy'])) {
					$sArr = array('document_field_value'); 
					$whereArr = array(	'document_id='.(int)$dInfo['document_id'],
										"document_defs_list_id=".(int)$docArr[$dInfo['document_table_name']],
										"document_field_defs_list_id=".(int)$enArr['sortBy']);
					$fv = getTableInfo($db_dept,'document_field_value_list',$sArr,$whereArr,'queryOne');
					$insArr = array('result_id' => (int)$id);
					if($fv) {
						$insArr['field_value'] = $fv;
					}
					$db_dept->extended->autoExecute($docSearchTempTbl,$insArr);
				}				
			}
		} else {
			if(!isSet($enArr['search'])) {
				$wArr = array('result_id' => (int)$id);
				deleteTableInfo($db_dept,$docTempTbl,$wArr);
			}
		}
	}

	if($enArr['filter'] != 'DEFAULT' && ((isSet($enArr['search']) && !isSet($enArr['fullTextSearch'])) || isSet($enArr['sortBy']))) {
		return $docSearchTempTbl;
	}

	if(!isSet($enArr['tab_id']) || $enArr['tab_id'] < 0) {
		if(!isSet($enArr['fullTextSearch']) && !isSet($enArr['search']) && ($enArr['filter'] == 'All' || $enArr['filter'] == 'DEFAULT')) {
			$sArr = array('id');
			$wArr = array(	'doc_id='.(int)$enArr['doc_id'],
						'filename IS NULL',
						'document_id = 0',
						'deleted = 0');
			$oArr = array('id' => 'ASC');
			insertFromSelect($db_dept,$docTempTbl,array('result_id'),$enArr['cabinet']."_files",$sArr,$wArr,$oArr);

			$sArr = array('min(id)');
			$wArr = array(	'doc_id'		=> (int)$enArr['doc_id'],
							'subfolder'		=> 'IS NULL' );
			$id = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'queryOne');
			if($id) {
				$insArr = array('result_id' => $id);
				$res = $db_dept->extended->autoExecute($docTempTbl,$insArr);
				dbErr($res);
			}
		}
	}
	return $docTempTbl;
}

function getFolderDocuments($enArr,$user,$db_doc,$db_dept) {
	$docArr = array();
	$docList = array();
	$retArr = array ();
	
	$tArr = array('document_type_defs','document_permissions','group_list');
	$sArr = array('document_table_name','groupname');
	$wArr = array('permissions_id != 0',
				  'permissions_id=permission_id',
				  'group_list_id=list_id');
	$permArr = getTableInfo($db_dept,$tArr,$sArr,$wArr,'getAssoc',array(),0,0,array(),true);
	$noSeeArr = array ();
	foreach($permArr AS $k => $groupArr) {
		$check = false;
		foreach($groupArr AS $g) {
			if(in_array($g,$user->groups)) {
				$check = true;
				break;
			}
		}
		if(!$check) {
			$noSeeArr[] = $k;
		}
	}

	$sArr = array('document_table_name','document_id','subfolder','id');
	//$whereArr = array(	'doc_id='.(int)$enArr['doc_id'],
	//					'document_id != 0');
	//$docList = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$whereArr,'getAssoc');
	if(!array_key_exists('tab_id',$enArr)) 	{
		$wArr = array(	'doc_id='.(int)$enArr['doc_id'],
						'document_id != 0',
						'deleted = 0');
		if($enArr['filter'] != 'All' && $enArr['filter'] != 'DEFAULT') {
			$wArr[] = "document_table_name = '".$enArr['filter']."'";
		}

		$oArr = array('id' => 'DESC');
		$docList = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'queryAll',$oArr);
	} else {
		if($enArr['tab_id'] > 0) {
			$wArr = array(	'id' => (int)$enArr['tab_id'] );
			$oArr = array('id' => 'DESC');
			$docList = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'queryAll',$oArr);
		} else if($enArr['tab_id'] == -2) {
			$wArr = array(	'doc_id = '.(int)$enArr['doc_id'],
							'filename IS NULL');
			$oArr = array('id' => 'DESC');
			$docList = getTableInfo($db_dept,$enArr['cabinet']."_files",$sArr,$wArr,'queryAll',$oArr);
		} else {
			$retArr[] = array (
				'name' 				=> 'DEFAULT',
				'table'				=> 'DEFAULT',
				'subfolder_name' 	=> 'main',
				'id' 				=> -1,
				'documents' 		=> array('subfolder' => 'main')
			);
			return $retArr;
		}
	}

	if($enArr['filter'] != 'DEFAULT') {
		$wArr = array();
		if($enArr['filter'] != 'All') {
			$wArr['document_table_name'] = $enArr['filter'];
		} 
		$sArr = array('document_table_name','document_type_name','id');
		$oArr = array('document_type_name' => 'ASC');
		$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);

		foreach($docList AS $doc) {
			$docTable = $doc['document_table_name'];
			if(!in_array ($docTable, $noSeeArr)) {
				if(isSet($enArr['search'])) {
					$fs = $enArr['search'];
					$fs = str_replace("\\","\\\\",$fs);
					$fs = str_replace("%","\%",$fs);
					$fs = str_replace("_","\_",$fs);

					$tableArr = array('document_field_value_list');
					$sArr = array('COUNT(id)'); 
					$whereArr = array(	'document_id='.(int)$doc['document_id'],
										"document_defs_list_id=".(int)$docArr[$docTable]['id'],
										"document_field_value ".LIKE." '%".$db_dept->escape($fs)."%'");
					$ct = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'queryOne');
					if(!$ct) {
						continue;
					}
				}
			
				$docInfo = array();
				$tableArr = array('document_field_defs_list','document_field_value_list');
				$sArr = array(	'arb_field_name',
								'document_field_value' );
				$whereArr = array(	'document_field_defs_list_id=document_field_defs_list.id',
									'document_id='.(int)$doc['document_id'],
									"document_table_name='".$docTable."'" );
				$oArr = array('ordering' => 'ASC');
				$docInfo = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'getAssoc',$oArr);

				$doc_name = (isSet($docArr[$docTable]['document_type_name'])) ? $docArr[$docTable]['document_type_name'] : "";
				$dArr = array(	'name'			=> $doc_name,
								'table'			=> $docTable,
								'subfolder_name'=> $doc['subfolder'],
								'id'			=> $doc['id'],
								'documents'		=> $docInfo );

				if($user->checkSetting("showDocumentCreation",$enArr['cabinet'])) {
					if($doc['document_id']) {
						$sArr = array("date_created");
						$wArr = array("id" => (int)$doc['document_id']);
						$date_created = getTableInfo($db_dept,$docTable,$sArr,$wArr,'queryOne');

						$dArr['date_created'] = $date_created;
					}
				}
				$retArr[] = $dArr;
			}		
		}
	}
	
	if((!isSet($enArr['search']) && $enArr['filter'] == 'All') || $enArr['filter'] == 'DEFAULT') {
		$sArr = array('id','subfolder');
		$wArr = array(	'doc_id'		=> (int)$enArr['doc_id'],
						'filename'		=> 'IS NULL',
						'document_id'	=> 0,
						'deleted'		=> 0);
		$gDocArr = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'getAssoc');
		
		$sArr = array('COUNT(id)');
		$wArr = array(	'doc_id'		=> (int)$enArr['doc_id'],
						'subfolder'		=> 'IS NULL' );
		$ct = getTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$wArr,'queryOne');

		if($ct > 0) {
			$retArr[] = array (
				'name' => 'DEFAULT',
				'table' => 'DEFAULT',
				'subfolder_name' => 'main',
				'id' => -1,
				'documents' => array('subfolder' => 'main')
			);
		}

		$tabArr = getNoShowTabs($enArr['cabinet'],$enArr['doc_id'],$user->db_name);
		foreach($gDocArr AS $id => $sfold) {
			if(!in_array($sfold,$tabArr)) {
				$retArr[] = array (
					'name' => 'DEFAULT',
					'table' => 'DEFAULT',
					'subfolder_name' => $sfold,
					'id' => $id,
					'documents' => array('subfolder' => $sfold)
				);
			}
		}
	}
	return $retArr;
}

function xmlAddDocumentToCabinet($enArr,$user,$db_doc,$db_object) {
	$docSett = getDocumentSettings ($enArr, $user,$db_doc);
	addDocumentToCabinet($enArr,$user,$db_doc,$db_object);
	
	$xmlObj = new xml("ENTRY");
	foreach($docSett AS $k => $sett) {
		$xmlObj->setRootAttribute($k,$sett);
	}
	$xmlObj->setRootAttribute('doc_type',$enArr['document_type_name']);
	$xmlObj->setRootAttribute('doc_table',$enArr['document_table_name']);
	$xmlObj->setRootAttribute('folderID',$enArr['subfolderID']);
	$xmlObj->setRootAttribute('prepend',$enArr['prepend']);
	if($user->checkSetting("showDocumentCreation",$enArr['cabinet'])) {
		$xmlObj->setRootAttribute('date_created',$enArr['date_created']);
	}
	$xmlObj->createKeyAndValue("FUNCTION","addFolderDocument(XML)");

	for($i=0;$i<$enArr['field_count'];$i++) {
		$xmlObj->createKeyAndValue("FIELD",$enArr['field'.$i],array('name'=>$enArr['fieldArr'][$i]));
	}
	$xmlObj->setHeader();
}

function xmlInboxAddDocumentToCabinet($enArr,$user,$db_doc,$db_object) {
	$tabName = addDocumentToCabinet($enArr,$user,$db_doc,$db_object);
	
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_new_doc("1.0");
		$entry = $domDoc->create_element("ENTRY");
		$domDoc->append_child($entry);

		$entry->append_child($domDoc->create_text_node($tabName));
		$xmlStr = $domDoc->dump_mem (false);
	} else {
		$domDoc = new DOMDocument ();
		$entry = $domDoc->createElement("ENTRY");
		$domDoc->appendChild($entry);

		$entry->appendChild($domDoc->createTextNode($tabName));
		$xmlStr = $domDoc->saveXML ();
	}
	
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlMoveFilesAddDocumentToCabinet($enArr,$user,$db_doc,$db_dept) {
	$tab = addDocumentToCabinet($enArr,$user,$db_doc,$db_dept);
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("TAB",$tab);
	$xmlObj->setHeader();
}

function addDocumentToCabinet(&$enArr,$user,$db_doc,$db_dept) {
	$auditArr = array();
	$auditArr[] = "Cabinet: ".$enArr['cabinet'];

	$sArr = array('id','document_type_name');
	$whereArr = array('document_table_name'	=> $enArr['document_table_name']);
	$typeDefsID = getTableInfo($db_dept,'document_type_defs',$sArr,$whereArr,'queryRow');	
	$enArr['document_type_name'] = $typeDefsID['document_type_name'];

	lockTables($db_dept,array($enArr['document_table_name'],$enArr['cabinet'].'_files',$enArr['cabinet']));	
	if(!empty($enArr['subfolderID'])) {
		$subfolderID = $enArr['subfolderID'];
		$enArr['prepend'] = 0;
	} else {
		$tabName = "";
		$tab = $enArr['document_type_name'];
		$subfolderID = createTabForDocument($db_dept,$user->db_name,$enArr['cabinet'],$enArr['doc_id'],$tab,$tabName, $db_doc);
		$enArr['subfolderID'] = $subfolderID;
		$enArr['prepend'] = 1;
	}

	$folderArr = getCabIndexArr($enArr['doc_id'],$enArr['cabinet'],$db_dept);
	$auditArr[] = "Folder: ".implode(" ",$folderArr);

	$date = date('Y-m-d G:i:s');
	$enArr['date_created'] = $date;
	$insertArr = array(	"cab_name"		=> $enArr['cabinet'],
						"doc_id"		=> (int)$enArr['doc_id'],
						"file_id"		=> (int)$subfolderID,
						"date_created"	=> $date,
						"date_modified"	=> $date,
						"created_by"	=> $user->username );
	$res = $db_dept->extended->autoExecute($enArr['document_table_name'],$insertArr);
	dbErr($res);
	$documentID = getTableInfo($db_dept,$enArr['document_table_name'],array('MAX(id)'),array(),'queryOne');	
	unlockTables($db_dept);

	$sArr = array(	'document_id'			=> (int)$documentID,
					'document_table_name'	=> $enArr['document_table_name']);
	$whereArr = array('id' => (int)$subfolderID);
	updateTableInfo($db_dept,$enArr['cabinet'].'_files',$sArr,$whereArr);

	$sArr = array('real_field_name','id','arb_field_name');
	$whereArr = array('document_table_name' => $enArr['document_table_name']);
	$fieldArr = getTableInfo($db_dept,'document_field_defs_list',$sArr,$whereArr,'getAssoc');

	$insertArr = array(	"document_defs_list_id"	=> (int)$typeDefsID['id'],
						"document_id"			=> (int)$documentID);
	$fArr = array();
	$docArr = array();
	for($i=0;$i<$enArr['field_count'];$i++) {
		$insertArr['document_field_defs_list_id'] = (int)$fieldArr[$enArr['key'.$i]]['id'];
		$insertArr['document_field_value'] = $enArr['field'.$i];
		$res = $db_dept->extended->autoExecute('document_field_value_list',$insertArr);
		dbErr ($res);

		$fArr[] = $fieldArr[$enArr['key'.$i]]['arb_field_name'];
		$docArr[] = $enArr['field'.$i];
	}
	$auditArr[] = "Document: ".implode(" ",$docArr);
	$enArr['fieldArr'] = $fArr;
	$user->audit("Document Created",implode(" ",$auditArr),$db_dept);
	return $tabName;
}

function getDocumentFields($enArr,$user,$db_doc,$db_dept) {
	$docName = $enArr['document_table_name'];

	$sArr = array('real_field_name','arb_field_name');
	$whereArr = array('document_table_name'	=> $docName);
	$orderArr = array('ordering' => 'ASC');
	$docFieldArr = getTableInfo($db_dept,'document_field_defs_list',$sArr,$whereArr,'getAssoc',$orderArr);

	$sArr = array('id');
	$wArr = array('document_table_name'	=> $docName);
	$docTypeID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$xmlObj = new xml("ENTRY");
	if(isSet($enArr['noCallBackFunction'])) {
		$xmlObj->createKeyAndValue("FUNCTION","setDocument(XML)");
	} else {
		$xmlObj->createKeyAndValue("FUNCTION","fillDocumentTypeFields(XML)");
	}

	$regList = array();
	$sArr = array('field_name', 'required', 'regex', 'display', 'is_date');
	$wArr = array(	'document_table_name' => $docName);
	$regList = getTableInfo($db_dept,'field_format',$sArr,$wArr,'getAssoc');

	foreach($docFieldArr AS $real => $arb) {
		$parentEl = $xmlObj->createKeyAndValue("FIELD",$arb,array('name'=>$real));

		$defsList = array();
		$sArr = array('definition');
		$wArr = array(	'document_type_id' => (int)$docTypeID,
						'document_type_field' => $real);
		$defsList = getTableInfo($db_dept,'definition_types',$sArr,$wArr,'queryCol');
		foreach($defsList AS $def) {
			$xmlObj->createKeyAndValue("DEFINITION",$def,array(),$parentEl);
		}

		if(isSet($regList[$real])) {
			$xmlObj->createKeyAndValue("REQUIRED",$regList[$real]['required'],array(),$parentEl);
			$xmlObj->createKeyAndValue("REGEX",$regList[$real]['regex'],array(),$parentEl);
			$xmlObj->createKeyAndValue("DISPLAY",$regList[$real]['display'],array(),$parentEl);
			$xmlObj->createKeyAndValue("ISDATE",$regList[$real]['is_date'],array(),$parentEl);
		}
	}
	$xmlObj->setHeader();
}

function xmlGetDocumentTypes($enArr, $user,$db_doc,$db_object) {
	$docArr = getDocumentTypes($enArr, $user,$db_doc,$db_object);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION","fillDocumentTypeDropDown(XML)");
	foreach($docArr AS $real => $arb) {
		$xmlObj->createKeyAndValue("DOCUMENT",$arb,array('name'=>$real));
	}
	$xmlObj->setHeader();
}

function getDocumentTypes($enArr,$user,$db_doc,$db_dept) {
	$sArr = array(	'document_table_name','document_type_name','id');
	$wArr = array('enable' => 1);
	$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc');

	$tArr = array('document_type_defs','document_permissions','group_list');
	$sArr = array('document_table_name','groupname');
	$wArr = array('permissions_id != 0',
				  'permissions_id=permission_id',
				  'group_list_id=list_id');
	$permArr = getTableInfo($db_dept,$tArr,$sArr,$wArr,'getAssoc',array(),0,0,array(),true);
	$newPermArr = $permArr;
	$outArr = array ();
	foreach($newPermArr AS $k => $groupArr) {
		$check = false;
		foreach($groupArr AS $g) {
			if(in_array($g,$user->groups)) {
				$check = true;
				break;
			}
		}
		if($check) {
			$outArr[$k] = $groupArr;
		}
	}
	$newPermArr = $outArr;

	$filterArr = array();
	if(isSet($enArr['cab'])) {
		$type = "filter";
		if(isSet($enArr['type'])) {
			$type = $enArr['type'];
		}
		$filterArr = getDocumentFilters($enArr['cab'],$type,$db_dept);
	}
	$newDocArr = array();
	foreach($docArr AS $k => $info) {
		if((in_array($info['id'],$filterArr)) || (count($filterArr) == 0)) {
			if((is_array($newPermArr) && array_key_exists($k,$newPermArr)) || 
			   (is_array($permArr) && !array_key_exists($k,$permArr)) ) {
				$newDocArr[$k] = $info['document_type_name'];
			}
		}
	}
	
	uasort($newDocArr,'strnatcasecmp');
	return $newDocArr;
}

function addDocType($enArr, &$docTable, $db_doc, $db_dept) {
	if(trim($enArr['document_type_name'])) {
		$whereArr = array('document_type_name'=>$enArr['document_type_name']);
		$checkDocType = getTableInfo($db_dept,'document_type_defs',array('COUNT(id)'),$whereArr,'queryOne');
		if(!$checkDocType) {
			if(array_key_exists('document_table_name',$enArr)) {
				$whereArr = array('document_table_name'=>$enArr['document_table_name']);	
				$updateArr = array('document_type_name'=>$enArr['document_type_name']);
				updateTableInfo($db_dept,'document_type_defs',$updateArr,$whereArr);

				$myMess = 'Document type updated successfully';
			} else {
				$total = getTableInfo($db_dept,'document_type_defs',array('COUNT(id)'),array(),'queryOne');
				$docTable = ($total) ? 'document'.($total+1) : 'document1';

				$enArr['document_table_name'] = $docTable; 	
				$enArr['enable'] = 0; 	
				$enArr['permissions_id'] = 0; 	
				$res = $db_dept->extended->autoExecute('document_type_defs',$enArr);
				dbErr($res);
				createDocument($db_dept,$enArr['document_table_name']);
				
				$myMess = 'Document type created successfully';
			}
		} else {
			$myMess = 'Document type already exists';
			$funcStr = '';
		}
	} else {
		$myMess = 'Document type is empty';
		$funcStr = '';
	}
	return $myMess;
}

function xmlAddDocType($enArr, $user, $db_doc, $db_dept) {
	$docTable = '';
	$funcStr = '';
	$myMess = addDocType($enArr, $docTable, $db_doc, $db_dept);
	if($docTable) {
		$funcStr = 'setDocumentInfo("'.$docTable.'","'.$enArr['document_type_name'].'")';
	}

	$xmlObj = new xml("ENTRY");
	if($funcStr) {
		$xmlObj->createKeyAndValue("FUNCTION",$funcStr);
	}
	$xmlObj->createKeyAndValue("MESSAGE",$myMess);
	$xmlObj->setHeader();
}

function xmlAddDocumentField($enArr,$user,$db_doc,$db_dept) {
	$myMsg = '';
	$fieldName = '';

	$indexVal = str_replace(array(" ", "-"), "_", trim($enArr['field_name']));
	if( $user->invalidDocTypeIndexNames(strtolower($indexVal))) {
		$myMsg = "Invalid Index Name -> $indexVal";
		$fieldName = '';
	}
	else if(trim($enArr['field_name'])) {
		$myMsg = addDocumentField($enArr, $fieldName, $db_doc, $db_dept);
	} else {
		$myMsg = 'Index is Empty';
		$fieldName = '';
	}

	$xmlObj = new xml("ENTRY");
	if($fieldName) {
		$myFunc = 'addIndexElement("'.$fieldName.'")'; 
		$xmlObj->createKeyAndValue("FUNCTION",$myFunc);
	}
	$xmlObj->createKeyAndValue("MESSAGE",$myMsg);
	$xmlObj->setHeader();
}

function addDocumentField($enArr, &$fieldName, $db_doc, $db_dept) {
	$colArr = array();	
	$docName = $enArr['document_table_name'];		
	$whereArr = array('document_table_name' => $docName);
	$colArr = getTableInfo($db_dept,'document_field_defs_list',array('arb_field_name'),$whereArr,'queryCol');
	$maxFNum = getTableInfo($db_dept,'document_field_defs_list',array('real_field_name'),$whereArr,'queryOne',array('real_field_name' => 'DESC')); 
	$maxFNum++;
	if(!in_array($enArr['field_name'],$colArr)) {
		lockTables($db_dept,array('document_field_defs_list'));
		
		$insertArr = array(	"document_table_name"	=> $docName,
							"real_field_name"		=> ((sizeof($colArr)) ? $maxFNum : "f1"),
							"arb_field_name"		=> $enArr['field_name'],
							"ordering"				=> (int)(ltrim($maxFNum,"f")));
		$res = $db_dept->extended->autoExecute('document_field_defs_list',$insertArr);
		dbErr($res);
		$fieldID = getTableInfo($db_dept,'document_field_defs_list',array('MAX(id)'),array(),'queryOne');
		unlockTables($db_dept);

		$sArr = array('id');
		$wArr = array('document_table_name' => $docName);
		$tableID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');
		
		$documentIDArr = getTableInfo($db_dept,$docName,$sArr,array(),'queryCol');
		$insertArr = array(	'document_defs_list_id'			=> (int)$tableID,
							'document_field_defs_list_id'	=> (int)$fieldID,
							'document_field_value'			=> "");
		foreach($documentIDArr AS $docID) {
			$insertArr['document_id'] = (int)$docID;
			$res = $db_dept->extended->autoExecute('document_field_value_list',$insertArr);
			dbErr($res);
		}

		$uArr = array('enable' => 1);
		$wArr = array('document_table_name' => $docName);
		updateTableInfo($db_dept,'document_type_defs',$uArr,$wArr);

		$myMsg = 'Index added successfully';
		$fieldName = $enArr['field_name'];
	} else {
		$myMsg = 'Duplicate Index';
		$myFunc = '';
	}
	return $myMsg;
}

function addCompleteDocumentType($docName, $indices, $db_doc, $db_dept) {
	$docTable = '';
	$mess = addDocType(array('document_type_name' => $docName),
		$docTable, $db_doc, $db_dept);
	if($docTable) {
		foreach($indices as $myIndex) {
			$fieldName = '';
			addDocumentField(array('document_table_name' => $docTable, 'field_name' => $myIndex), $fieldName,
				$db_doc, $db_dept);
		}
		return $docTable;
	} else {
		return false;
	}
}

function getDocumentInfo($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('document_type_name','document_table_name');
	$whereArr = array("document_table_name" => $enArr['document_table_name']);
	$docInfo = getTableInfo($db_dept,'document_type_defs',$sArr,$whereArr,'queryRow');
	$funcStr = 'setDocumentInfo("'.$docInfo['document_table_name'].'","'.$docInfo['document_type_name'].'")';

	$selArr = array('arb_field_name');
	$docInfo = getTableInfo($db_dept,'document_field_defs_list',$selArr,$whereArr,'queryCol',array('ordering'=>'ASC'));

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION",$funcStr);
	foreach($docInfo AS $col) {
		$xmlObj->createKeyAndValue("INDICE",$col);
	}
	$xmlObj->setHeader();
}

function renameDocumentField($enArr,$user,$db_doc,$db_dept) {
	//updates the field definition for that document
	$updateArr = array( 'arb_field_name'		=> $enArr['new_field_name']);
	$whereArr = array(	'document_table_name'	=> $enArr['document_table_name'],
						'arb_field_name' 		=> $enArr['arb_field_name'] );
	updateTableInfo($db_dept,'document_field_defs_list',$updateArr,$whereArr);

	$myMsg = 'Index successfully renamed';

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE",$myMsg);
	$xmlObj->setHeader();
}

function deleteDocumentField($enArr,$user,$db_doc,$db_dept) {
	$tableArr = array('document_type_defs','document_field_defs_list');
	$sArr = array('document_type_defs.id AS type_id','document_field_defs_list.id AS field_id','real_field_name');
	$whereArr = array(	"document_type_defs.document_table_name=document_field_defs_list.document_table_name",
						"document_type_defs.document_table_name='{$enArr['document_table_name']}'",
						"arb_field_name='{$enArr['arb_field_name']}'" );	
	$docInfo = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'queryRow');	

	//deletes the field definition for that document
	$whereArr = array(	'document_table_name'	=> $enArr['document_table_name'],
						'arb_field_name' 		=> $enArr['arb_field_name'] );
	deleteTableInfo($db_dept,'document_field_defs_list',$whereArr);

	//deletes all the corresponding values that are associated with that field
	$whereArr = array(	'document_defs_list_id'			=> (int)$docInfo['type_id'],
						'document_field_defs_list_id'	=> (int)$docInfo['field_id'] );
	deleteTableInfo($db_dept,'document_field_value_list',$whereArr);

	$wArr = array('document_type_id' => (int)$docInfo['type_id'],
				'document_type_field' => $docInfo['real_field_name']);
	deleteTableInfo($db_dept,'definition_types',$wArr);

	$sArr = array('COUNT(id)');
	$wArr = array('document_table_name' => $enArr['document_table_name']);
    $ct = getTableInfo($db_dept,'document_field_defs_list',$sArr,$wArr,'queryOne');
	if(!$ct) {
		$uArr = array('enable' => 0);
		updateTableInfo($db_dept,'document_type_defs',$uArr,$wArr);
	}

	$myMsg = 'Index successfully removed';

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE",$myMsg);
	$xmlObj->setHeader();
}

function reorderDocumentFields($enArr,$user,$db_doc,$db_dept) {
	for($i=0;$i<$enArr['field_count'];$i++) {
		$updateArr = array(	'ordering'	=> (int)($i+1));
		$whereArr = array(	'document_table_name'	=> $enArr['document_table_name'],
							'arb_field_name'		=> $enArr['f'.$i] );

		updateTableInfo($db_dept,'document_field_defs_list',$updateArr,$whereArr);
	}

	$myMsg = 'Indices successfully ordered';

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE",$myMsg);
	$xmlObj->setHeader();
}

function disableDocumentType($enArr,$user,$db_doc,$db_dept) {
	$updateArr = array(	'enable'				=> (int)$enArr['disable'] );
	$whereArr = array(	'document_table_name'	=> $enArr['document_table_name'] );
	updateTableInfo($db_dept,'document_type_defs',$updateArr,$whereArr);
	$xmlObj = new xml("ENTRY");
	$xmlObj->setHeader();
}

function deleteDocumentType($enArr,$user,$db_doc,$db_dept) {
	//$updateArr = array(	'enable'				=> (int)$enArr['disable'] );
	//$whereArr = array(	'document_table_name'	=> $enArr['document_table_name'] );
	//updateTableInfo($db_dept,'document_type_defs',$updateArr,$whereArr);

	$xmlObj = new xml("ENTRY");
	$xmlObj->setHeader();
}

function getDocumentDisableInfo($enArr,$user,$db_doc,$db_dept) {
	$whereArr = array(	'document_table_name'	=> $enArr['document_table_name'] );
	$disable = getTableInfo($db_dept,'document_type_defs',array('enable'),$whereArr,'queryOne');
	if($disable) {
		$funcStr = 'setSelectedChkBox("rdEnableDoc")';
	} else {
		$funcStr = 'setSelectedChkBox("rdDisableDoc")';
	}

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION",$funcStr);
	$xmlObj->setHeader();
}

function xmlGetPageResults($enArr,$user,$db_doc,$db_object) {
	$docObj = "";
	$sortDir = "";
	if(isSet($enArr['sortField'])) {
		sortDocFields($db_object,$enArr['sortField']);	
		$sortDir = $enArr['sortDir'];	
	}
	$res = getPageResults($enArr['page'],$enArr['total'],$user,$db_object,$docObj,$sortDir);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue('FUNCTION','loadPage(XML)');	
	foreach($res AS $id => $info) {
		$attArr = array('document_id' => $id,
						'cab' => $info[0]['cab_name'],
						'arb_cab' => $user->cabArr[$info[0]['cab_name']],
						'doc_id' => $info[0]['doc_id'],
						'file_id' => $info[0]['file_id']);
		$parentEl = $xmlObj->createKeyAndValue('DOCUMENT',NULL,$attArr);	
		foreach($docObj->fields AS $name => $fInfo) {
			foreach($info AS $val) {
				if($fInfo['id'] == $val['field_id']) {
					$fVal = "";
					if($val['field_value']) {
						$fVal = $val['field_value'];
					}
					$xmlObj->createKeyAndValue('FIELD',$fVal,array('field_id'=>$val['field_id']),$parentEl);
					break;
				}
			}
		}
	}
	$xmlObj->setHeader();
} 

function sortDocFields($db_dept,$sField) {
	$docName = $_SESSION['documentInfo']['docType'];
	$docObj = new documentSearch($db_dept,$docName);
	$docObj->tempTable = $_SESSION['documentInfo']['tempTable'];

	$sArr = array('id');
	$wArr = array('document_table_name' => $docName,
			'arb_field_name' => $sField );
	$fieldID = getTableInfo($db_dept,'document_field_defs_list',$sArr,$wArr,'queryOne');

	$queryArr = array (
        'table_id '.AUTOINC,
        'PRIMARY KEY (table_id)',
        'result_id INT DEFAULT 0',
        'doc_val VARCHAR(255) NULL',
    );
	$tempTable = createDynamicTempTable($db_dept,$queryArr);

	$insCol = array("result_id","doc_val");	
	$sArr	= array("$docObj->tempTable.result_id","document_field_value");
	$tableArr = array($docObj->tempTable,"document_field_value_list");
	$wArr		= array("document_defs_list_id=$docObj->docTypeID", 
						"document_field_defs_list_id=$fieldID",
						"$docObj->tempTable.result_id=document_id");
	insertFromSelect($db_dept,$tempTable,$insCol,$tableArr,$sArr,$wArr);

	$_SESSION['documentInfo']['tempTable'] = $tempTable;
}

function getPageResults($page,$total,$user,$db_dept,&$docObj,$sDir=NULL) {
	$docObj = new documentSearch($db_dept,$_SESSION['documentInfo']['docType']);
	$docObj->tempTable = $_SESSION['documentInfo']['tempTable'];
	$res = $docObj->getResults($page,$total,$sDir);

	return $res;
}

function xmlUpdateDocumentInfo($enArr,$user,$db_doc,$db_object) {
	$ct = 1;
	$fieldArr = array();
	while(array_key_exists("id".$ct,$enArr)) {
		$fieldID = $enArr['id'.$ct];
		$fieldArr[$fieldID] = $enArr['field-'.$fieldID]; 
		$ct++;
	}
	updateDocumentInfo($enArr['docType'],$enArr['documentID'],$fieldArr,$user,$db_object);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE","Document Fields Updated Successfully");
	$xmlObj->setHeader();
}

function updateDocumentInfo($docName,$documentID,$fieldArr,$user,$db_dept) {
	$wArr = array('document_table_name' => $docName);
	$docNameID = getTableInfo($db_dept,'document_type_defs',array('id'),$wArr,'queryOne');
	$wArr = array(	'document_defs_list_id' => (int)$docNameID,
					'document_id' 			=> (int)$documentID);
	foreach($fieldArr AS $id => $val)  {
		$uArr = array('document_field_value' => $val);
		$wArr['document_field_defs_list_id'] = (int)$id;
		updateTableInfo($db_dept,'document_field_value_list',$uArr,$wArr);
	}
}

function xmlDeleteDocuments($enArr,$user,$db_doc,$db_object) {
	$ct = 1;
	$idArr = array();
	while(array_key_exists("doc".$ct,$enArr)) {
		$idArr[] = $enArr['doc'.$ct]; 
		$ct++;
	}
	deleteDocuments($enArr['docType'],$idArr,$user,$db_object);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE","Document(s) deleted successfully");
	$xmlObj->setHeader();
}

function deleteDocuments($docName,$idArr,$user,$db_dept) {
	foreach($idArr AS $id) {
		$wArr = array('id' => (int)$id);
		$uArr = array('deleted' => 1);
		updateTableInfo($db_dept,$docName,$uArr,$wArr);	

		$sArr = array('cab_name','file_id');
		$docInfo = getTableInfo($db_dept,$docName,$sArr,$wArr,'queryRow');

		$wArr = array('id' => (int)$docInfo['file_id']);
		$uArr = array('deleted' => 1, 'display' => 0);
		updateTableInfo($db_dept,$docInfo['cab_name'].'_files',$uArr,$wArr);
	}
}

function xmlGetDocumentFilters($enArr,$user,$db_doc,$db_dept) {
	$type = "filter";
	if(isSet($enArr['type'])) {
		$type = $enArr['type'];
	}
	$filterList = getDocumentFilters($enArr['cab'],$type,$db_dept);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION","fillDocumentFilter(XML)");
	//error_log("FILTERS: " . print_r($filterList, true));
	if(is_array($filterList)) {
		foreach($filterList AS $id) {
			$xmlObj->createKeyAndValue("FILTER",$id);
		}
	}
	$xmlObj->setHeader();
}

function getDocumentFilters($cab,$type,$db_dept) {
	$tArr = array('document_settings','document_settings_list');
	$sArr = array('document_id');
	$wArr = array(	"cab='".$cab."'",
					"k='$type'",
					"document_settings.list_id=document_settings_list.list_id");
	$filterList = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryCol');

	return $filterList;
}

function xmlAddDocumentFilter($enArr,$user,$db_doc,$db_object) {
	$listArr = getDocumentFilterList($enArr);
	addDocumentFilter($enArr['cab'],$enArr['type'],$listArr,$db_object);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE","Filter Successfully Created");
	$xmlObj->setHeader();
}

function getDocumentFilterList($enArr) {
	$listArr = array();
	$ct = 1;
	while(array_key_exists('filter-'.$ct,$enArr)) {
		$listArr[] = $enArr['filter-'.$ct];
		$ct++;
	}

	return $listArr;
}

function addDocumentFilter($cab,$type,$listArr,$db_dept) {
	$k = "filter";
	if($type) {
		$k = "$type";
	}
	$sArr = array('id','list_id');
	$wArr = array(	'cab'	=> $cab,
					'k'		=> $k);
	$filterInfo = getTableInfo($db_dept,'document_settings',$sArr,$wArr,'queryRow');
	$list_id = $filterInfo['list_id'];
	if($list_id) {
		$wArr = array('list_id' => (int)$list_id);
		deleteTableInfo($db_dept,'document_settings_list',$wArr);
	} elseif($filterInfo['id']) {
		$sArr = array('MAX(list_id)+1');
		$list_id = getTableInfo($db_dept,'document_settings_list',$sArr,array(),'queryOne');
		if(!$list_id) {
			$list_id = 1;
		}

		$uArr = array('list_id' => (int)$list_id);
		$wArr = array('id' => (int)$filterInfo['id']);
		updateTableInfo($db_dept,'document_settings',$uArr,$wArr);
	} else {
		$sArr = array('MAX(list_id)+1');
		$list_id = getTableInfo($db_dept,'document_settings_list',$sArr,array(),'queryOne');
		if(!$list_id) {
			$list_id = 1;
		}

		$insertArr = array(	'list_id'	=> (int)$list_id,
							'cab'		=> $cab,
							'k'			=> $k); 
		$res = $db_dept->extended->autoExecute('document_settings',$insertArr);
		dbErr($res);
	}

	$insertArr = array('list_id' => (int)$list_id);
	foreach($listArr AS $id) {
		$insertArr['document_id'] = (int)$id;
		$res = $db_dept->extended->autoExecute('document_settings_list',$insertArr);
		dbErr($res);
	}
}

function copyDocumentInfo($enArr,$user,$db_doc,$db_dept) {
	$copyDoc = $enArr['document_table_name'];
	$wArr= array('document_table_name' => $enArr['document_table_name']);
	$docInfo = getTableInfo($db_dept,'document_type_defs',array(),$wArr,'queryRow');

	$total = getTableInfo($db_dept,'document_type_defs',array('COUNT(id)'),array(),'queryOne');
	$docTable = ($total) ? 'document'.($total+1) : 'document1';

	$sArr = array('document_type_name');
	$docTypeList = getTableInfo($db_dept,'document_type_defs',$sArr,array(),'queryCol');

	$name = "Copy of ".$docInfo['document_type_name'];	
	$i = 2;
	while(in_array($name,$docTypeList)) {
		$name = "Copy(".$i.") of ".$docInfo['document_type_name'];
		$i++;
	}

	$enArr['document_table_name'] = $docTable; 	
	$enArr['document_type_name'] = $name; 	
	$enArr['enable'] = $docInfo['enable']; 	
	$enArr['permissions_id'] = $docInfo['permissions_id']; 	
	$res = $db_dept->extended->autoExecute('document_type_defs',$enArr);
	dbErr($res);
	createDocument($db_dept,$enArr['document_table_name']);

	$sArr = array('real_field_name','arb_field_name','ordering');
	$wArr = array('document_table_name' => $copyDoc);
	$indexInfo = getTableInfo($db_dept,'document_field_defs_list',$sArr,$wArr,'queryAll');
	foreach($indexInfo AS $info) {
		$info['document_table_name'] = $docTable;
		$res = $db_dept->extended->autoExecute('document_field_defs_list',$info);
		dbErr($res);
	}

	$enArr = array();
	$enArr['document_table_name'] = $docTable;
	getDocumentInfo($enArr,$user,$db_doc,$db_dept);
}
function xmlGetDocumentPermissions($enArr,$user,$db_doc,$db_dept) {
	$permList = getDocumentPermissions($enArr['docType'],$db_dept);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION","fillDocumentPermissions(XML)");
	if(is_array($permList)) {
		foreach($permList AS $name) {
			$xmlObj->createKeyAndValue("PERMISSION",$name);
		}
	}
	$xmlObj->setHeader();
}

function getDocumentPermissions($documentID,$db_dept) {
	$sArr = array('permissions_id');
	$wArr = array("id" => (int)$documentID);
	$permID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$sArr = array('group_list_id');
	$wArr = array('permission_id' => (int)$permID);
	$groupID = getTableInfo($db_dept,'document_permissions',$sArr,$wArr,'queryOne');

	$sArr = array('groupname');
	$wArr = array('list_id' => (int)$groupID);
	$groupList = getTableInfo($db_dept,'group_list',$sArr,$wArr,'queryCol');
	if(!$groupList) {
		$groupList = array();
	} 

	return $groupList;
}
function xmlAddDocumentPermissions($enArr,$user,$db_doc,$db_dept) {
	$listArr = getDocumentPermissionsList($enArr);
	addDocumentPermissions($enArr['docType'],$listArr,$db_dept);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("MESSAGE","Permissions Successfully Created");
	$xmlObj->setHeader();
}
function addDocumentPermissions($documentID,$listArr,$db_dept) {
	$sArr = array('permissions_id');
	$wArr = array(	'id'	=> (int)$documentID);
	$perm_id = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');
	if($perm_id) {
		$sArr = array('group_list_id');
		$wArr = array(	'permission_id'	=> (int)$perm_id);
		$group_list_id = getTableInfo($db_dept,'document_permissions',$sArr,$wArr,'queryOne');

		$wArr = array('list_id' => (int)$group_list_id);
		deleteTableInfo($db_dept,'group_list',$wArr);
	} else {
		lockTables($db_dept,array('document_permissions'));
		$sArr = array('MAX(permission_id)+1');
		$perm_id = getTableInfo($db_dept,'document_permissions',$sArr,array(),'queryOne');
		if(!$perm_id) {
			$perm_id = 1;
		}

		$insertArr = array('permission_id' => $perm_id);
		$res = $db_dept->extended->autoExecute('document_permissions',$insertArr);
		dbErr($res);
		unlockTables($db_dept);

		$uArr = array('permissions_id' => (int)$perm_id);
		$wArr = array('id' => (int)$documentID);
		updateTableInfo($db_dept,'document_type_defs',$uArr,$wArr);
	}

	lockTables($db_dept,array('group_list','document_permissions'));
	$sArr = array('MAX(list_id)+1');
	$group_list_id = getTableInfo($db_dept,'group_list',$sArr,array(),'queryOne');
	if(!$group_list_id) {
		$group_list_id = 1;
	}

	$uArr = array('group_list_id' => (int)$group_list_id);
	$wArr = array('permission_id' => (int)$perm_id);
	updateTableInfo($db_dept,'document_permissions',$uArr,$wArr);

	$insertArr = array('list_id' => (int)$group_list_id);
	foreach($listArr AS $name) {
		$insertArr['groupname'] = $name;
		$res = $db_dept->extended->autoExecute('group_list',$insertArr);
		dbErr($res);
	}
	unlockTables($db_dept);
}
function getDocumentPermissionsList($enArr) {
	$listArr = array();
	$ct = 1;
	while(array_key_exists('perm-'.$ct,$enArr)) {
		$listArr[] = $enArr['perm-'.$ct];
		$ct++;
	}

	return $listArr;
}

function getDocTypeList($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('id','document_table_name','document_type_name');
	$wArr = array('enable' => 1);
	$oArr = array('document_type_name' => 'ASC');
	$docTypeList = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setDocTypeList(XML)");
	foreach($docTypeList AS $id => $info) {
		$attArr = array('id' => $id,
						'name' => $info['document_table_name']);
		$xmlObj->createKeyAndValue("DOCTYPE",$info['document_type_name'],$attArr);
	}
	$xmlObj->setHeader();
}

function getDocTypeFields($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('real_field_name','arb_field_name');
	$wArr = array('document_table_name' => $enArr['docName']);
	$oArr = array('arb_field_name' => 'ASC');
	$docTypeList = getTableInfo($db_dept,'document_field_defs_list',$sArr,$wArr,'getAssoc',$oArr);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setDocTypeFieldList(XML)");
	foreach($docTypeList AS $real => $name) {
		$attArr = array('real_name' => $real);
		$xmlObj->createKeyAndValue("DOCFIELD",$name,$attArr);
	}
	$xmlObj->setHeader();
}

function getDocIndexTypeDefs($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('id','definition');
	$defList = getTableInfo($db_dept,'definition_types',$sArr,$enArr,'getAssoc');
	uasort($defList,"strnatcasecmp");

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setDocTypeDefs(XML)");
	foreach($defList AS $id => $def) {
		$xmlObj->createKeyAndValue("DEFINITION",$def,array('id'=>$id));
	}
	$xmlObj->setHeader();
}

function addNewDocDefinition($enArr,$user,$db_doc,$db_dept) {
	$id = 0;
	$isError = 1;
	$xmlObj = new xml();
	if(!addDocDef($enArr,$id,$db_dept)) {
		$xmlObj->createKeyAndValue("MESSAGE","Definition already exists");
	} else {
		if($id) {
			$xmlObj->createKeyAndValue("MESSAGE","Definition added successfully");
			$isError = 0;
		} else {
			$xmlObj->createKeyAndValue("MESSAGE","An error has occured during processing");
		}
	}

	$xmlObj->createKeyAndValue("FUNCTION","setNewDocIndexValue(XML)");
	if($isError) {
		$xmlObj->createKeyAndValue("ERROR","1");
	}
	$xmlObj->setHeader();
}

function addDocDef($enArr,&$id,$db_dept) {
	$sArr = array('COUNT(id)');
	$ct = getTableInfo($db_dept,'definition_types',$sArr,$enArr,'queryOne');
	if($ct) {
		return false;
	} else {	
		lockTables($db_dept,array('definition_types'));
		$res = $db_dept->extended->autoExecute('definition_types',$enArr);
		if(PEAR::isError($res)) {
		} else {
			$sArr = array('id');
			$id = getTableInfo($db_dept,'definition_types',$sArr,$enArr,'queryOne');
		}
		unlockTables($db_dept);

		return $id;
	}
}

function editDocTypeDefinition($enArr,$user,$db_doc,$db_dept) {
	lockTables($db_dept,array('definition_types'));

	$sArr = array('COUNT(id)');
	$wArr = array(	'document_type_id' => (int)$enArr['document_type_id'],
					'document_type_field' => $enArr['document_type_field'],
					'definition' => $enArr['definition']);
	$ct = getTableInfo($db_dept,'definition_types',$sArr,$wArr,'queryOne');

	$isError = 1;
	$xmlObj = new xml();
	if($ct) {
		$xmlObj->createKeyAndValue("MESSAGE","Definition already exists");
	} else {
		$uArr = array('definition' => $enArr['definition']);
		$wArr = array('id' => (int)$enArr['definition_id']);
		updateTableInfo($db_dept,'definition_types',$uArr,$wArr);

		$xmlObj->createKeyAndValue("MESSAGE","Definition updated successfully");
		$isError = 0;
	}
	unlockTables($db_dept);

	$xmlObj->createKeyAndValue("FUNCTION","setDocIndexValue(XML)");
	if($isError) {
		$xmlObj->createKeyAndValue("ERROR","1");
	}
	$xmlObj->setHeader();
}

function deleteDocTypeDefinition($enArr,$user,$db_doc,$db_dept) {
	lockTables($db_dept,array('definition_types'));
	$wArr = array('id' => (int)$enArr['definition_id']);
	deleteTableInfo($db_dept,'definition_types',$wArr);
	unlockTables($db_dept);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("MESSAGE","Definition removed successfully");
	$xmlObj->createKeyAndValue("FUNCTION","removeDocIndexValue(XML)");
	$xmlObj->setHeader();
}
?>
