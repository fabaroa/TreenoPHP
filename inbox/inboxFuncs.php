<?php
include_once '../lib/settings.php';
include_once '../lib/cabinets.php';
include_once '../lib/xmlObj.php';
include_once '../lib/webServices.php';
include_once '../lib/indexing.inc.php';
include_once '../lib/tabFuncs.php';

function xmlGetInboxBatches($enArr,$user,$db_doc,$db_dept) {
	$batchList = getInboxBatches($user->db_name,$user->username);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setInboxBatches(XML)");
	foreach($batchList AS $batch) {
		$xmlObj->createKeyAndValue("BATCH",$batch);
	}
	$xmlObj->setHeader();
}

function getInboxBatches($dep,$uname) {
	global $DEFS;
	$inboxPath = $DEFS['DATA_DIR']."/$dep/personalInbox/$uname";

	$bList = array();
	if(is_dir($inboxPath)) {
		$dh = opendir ($inboxPath);
		$myEntry = readdir ($dh);
		while (false !== $myEntry) {
			if (is_dir($inboxPath.'/'.$myEntry) && $myEntry != "." && $myEntry != "..") {
				$notEmpty = false;
				$fArr = scandir($inboxPath."/".$myEntry);
				foreach($fArr AS $f) {
					if(!is_dir($inboxPath."/".$myEntry."/".$f)) {
						$notEmpty = true;
						$mTime = filemtime($inboxPath."/".$myEntry."/".$f);
						break;
					}
				}

				if($notEmpty) {
					$bList[$myEntry] = $mTime;
				} else {
					rmdir($inboxPath."/".$myEntry);
				}
			}
			$myEntry = readdir ($dh);
		}
		closedir ($dh);
	}
	uasort($bList,"strnatcasecmp");

	return array_keys($bList);
}

function xmlGetBatchFiles($enArr,$user,$db_doc,$db_dept) {
	$fileList = getBatchFiles($user->db_name, $user->username,$enArr['path']);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setBatchFiles(XML)");
	foreach($fileList as $file) {
		$xmlObj->createKeyAndValue("FILE", $file);
	}
	$xmlObj->setHeader();
}

function getBatchFiles($dep,$uname,$batch) {
	global $DEFS;
	$inboxPath = $DEFS['DATA_DIR']."/$dep/personalInbox/$uname/";
	$inboxPath .= $batch;

	$fList = array();
	if(is_dir($inboxPath)) {
		$dh = opendir ($inboxPath);
		$myEntry = readdir ($dh);
		while (false !== $myEntry) {
			if (is_file($inboxPath.'/'.$myEntry)) {
				$fList[] = $myEntry;
			}
			$myEntry = readdir ($dh);
		}
		closedir ($dh);
	}
	usort($fList,"strnatcasecmp");

	return $fList;

}

function xmlGetInboxDocumentTypes($enArr,$user,$db_doc,$db_dept) {
	if($user->checkSetting('documentView',$enArr['cab'])) {
		$documentTypes = getInboxDocumentTypes($enArr['cab'],$user,$db_dept);
	} else {
		$tabs = getSavedTabs($enArr['cab'],$user->db_name,$db_doc);
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setDocumentTypes(XML)");
	if(isSet($documentTypes)) {
		foreach($documentTypes AS $name => $type) {
			$xmlObj->createKeyAndValue("DOCUMENT_TYPE", $type, array('name'=>$name));
		}
	} else {
		foreach($tabs AS $myTab) {
			$xmlObj->createKeyAndValue("TAB", str_replace("_"," ",$myTab), array('name'=>$myTab));
		}
	}
	$xmlObj->setHeader();
}

function getInboxDocumentTypes($cab,$user,$db_dept) {
	$sArr = array('document_table_name','document_type_name','id');
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
    if($cab) {
        $filterArr = getInboxDocumentFilters($cab,$db_dept);
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

function getInboxDocumentFilters($cab,$db_dept) {
    $tArr = array('document_settings','document_settings_list');
    $sArr = array('document_id');
    $wArr = array(  "cab='".$cab."'",
                    "k='filter'",
                    "document_settings.list_id=document_settings_list.list_id");
    $filterList = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryCol');

    return $filterList;
}

function xmlGetDocumentFields($enArr,$user,$db_doc,$db_dept) {
	$docInfo = getInboxDocumentFields($enArr['docname'],$db_dept);

    $xmlObj = new xml();
    $xmlObj->createKeyAndValue("FUNCTION","setDocumentFields(XML)");
    foreach($docInfo AS $real => $info) {
		$arb = $info['name'];
        $parentEl = $xmlObj->createKeyAndValue("FIELD",$arb,array('name'=>$real));

		if(isSet($info['datatypes'])) {
        	foreach($info['datatypes'] AS $def) {
        	    $xmlObj->createKeyAndValue("DEFINITION",$def,array(),$parentEl);
        	}
		}
    }
    $xmlObj->setHeader();
}

function getInboxDocumentFields($docName,$db_dept) {
	$sArr = array('real_field_name','arb_field_name');
    $whereArr = array('document_table_name' => $docName);
    $orderArr = array('ordering' => 'ASC');
    $docFieldArr = getTableInfo($db_dept,'document_field_defs_list',$sArr,$whereArr,'getAssoc',$orderArr);

    $sArr = array('id');
    $wArr = array('document_table_name' => $docName);
    $docTypeID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$docInfo = array();
    foreach($docFieldArr AS $real => $arb) {
		$docInfo[$real]['name'] = $arb;
		$defsList = array();
		$sArr = array('definition');
		$wArr = array(  'document_type_id' => (int)$docTypeID,
						'document_type_field' => $real);
		$defsList = getTableInfo($db_dept,'definition_types',$sArr,$wArr,'queryCol');
		if(count($defsList)) {
			$docInfo[$real]['datatypes'] = $defsList;
		}
	}
	return $docInfo;
}

function xmlSubmitBatch($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	$inboxPath = $DEFS['DATA_DIR']."/$user->db_name/personalInbox/$user->username";
	$fileArr = array ();
	$i = 1;
	while (isset ($enArr['file' . $i])) {
		$myFile = $enArr['file' . $i];
		if (is_dir ($inboxPath . '/' . $myFile)) {
			$tmpFiles = glob ($inboxPath . '/' . $myFile . '/*');
			foreach ($tmpFiles as $myTmpFile) {
				$fileArr[] = $myTmpFile;
			}
		} else {
			$fileArr[] = $inboxPath . '/' . $myFile;
		}
		$i++;
	}

	if(isSet($DEFS['UPLOAD_TMP'])) {
		$tmpPath = $DEFS['UPLOAD_TMP'];
	} else {
		$tmpPath = $DEFS['TMP_DIR']."/docutron/".$user->username;
	}
	
	$tmpDir = $user->getUniqueDirectory ($tmpPath);
	foreach ($fileArr as $myFile) {
		if (file_exists ($myFile)) {
			$tmpName = basename ($myFile);
			while (file_exists ($tmpDir . '/' . $tmpName)) {
				$i = 0;
				$tmpLoc = strrpos ($myFile, '.');
				if ($tmpLoc !== false) {
					$myPref = substr ($myFile, 0, $tmpLoc);
					$mySuff = substr ($myFile, $tmpLoc);
				} else {
					$myPref = $myFile;
					$mySuff = '';
				}
				$tmpName = $myPref . '-' . $i . $mySuff;
				$i++;
			}
			rename ($myFile, $tmpDir . '/' . $tmpName);
		}
	}
	$cabinet = $enArr['cabinet'];
	$fields = array ();
	$cabIndices = getCabinetInfo($db_dept, $cabinet);
	foreach ($cabIndices as $myIndex) {
		$fields[$myIndex] = $enArr[$myIndex];
	}
	$queryArr = $fields;
	$queryArr['deleted'] = 0;
	$docID = getTableInfo ($db_dept, $cabinet, array ('doc_id'),
		$queryArr, 'queryOne');
	if (!$docID) {
		$gblStt = new GblStt ($user->db_name, $db_doc);
		$tempTable = '';
		$docID = createFolderInCabinet ($db_dept, $gblStt, $db_doc,
			$user->username, $user->db_name, $cabinet,
			array_values ($fields), array_keys ($fields),
			$tempTable);
	}

	$cabinetID = getTableInfo ($db_dept, 'departments',
		array('departmentid'),
		array ('real_name' => $cabinet), 'queryOne');
	if(isSet($enArr['document'])) {
		$docCols = getTableInfo ($db_dept, 'document_field_defs_list', 
			array ('real_field_name'), 
			array('document_table_name' => $enArr['document']), 'queryCol');
		$docIndices = array ();
		foreach ($docCols as $myCol) {
			$docIndices[$myCol] = $enArr[$myCol];
		}
		$tabID = createDocumentInfo($user->db_name, $cabinetID, $docID,
			$enArr['document'], $docIndices, $user->username,
			$db_doc);
	} else {
		$tabID = "";
		if(isSet($enArr['subfolder']) && $enArr['subfolder'] != "main") {
			$sArr = array('id');
			$wArr = array(	'doc_id'	=> (int)$docID,
							'subfolder'	=> $enArr['subfolder'] );
			$tabID = getTableInfo($db_dept,$cabinet.'_files',$sArr,$wArr,'queryOne');
		}
	}
	$deptID = str_replace ('client_files', '', $user->db_name);
	if (!$deptID) {
		$deptID = '0';
	}

	$indexDat = $deptID . ' ' . $cabinetID . ' ' . $docID;
	if($tabID) {
		$indexDat .= ' ' . $tabID;
	}
	file_put_contents($tmpDir . '/INDEX.DAT', $indexDat);
	$myDir = Indexing::makeUnique ($DEFS['DATA_DIR'] . '/Scan/' . basename($tmpDir));

	rename ($tmpDir, $myDir);

	$xmlObj = new xml();
    $xmlObj->createKeyAndValue("FUNCTION","resetBatchList()");
	$xmlObj->setHeader();

}
?>
