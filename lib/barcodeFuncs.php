<?php
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';
include_once '../lib/odbc.php';
include_once '../documents/documents.php';
include_once '../lib/cabinets.php';
include_once '../barcode/barcodeLib.php';

function getAutoCompleteCabinets($user,$db_dept,$db_doc) {
	$gblStt = new GblStt($user->db_name, $db_doc);
	$jsonArr = array();

	$sArr = array('real_name','departmentname');
	$wArr = array('deleted' => 0);
	$cabList = getTableInfo($db_dept,'departments',$sArr,$wArr,'getAssoc');
	foreach($cabList AS $cab => $name) {
		if($gblStt->get('indexing_'.$cab) && $user->checkSecurity($cab) > 0) {
			$jsonArr[] = array('real' => $cab, 'arb' => $name);
		}
	}
	echo json_encode($jsonArr);
}

function getAutoCompleteField($enArr,$user,$db_dept,$db_doc) {
	$gblStt = new GblStt($user->db_name, $db_doc);
	$jsonArr = array();

	$indexArr = getCabinetInfo($db_dept,$enArr['cab']);
	$jsonArr['field'] = array('real' => $indexArr[0],'arb' => str_replace("_"," ",$indexArr[0]));

	if($user->checkSetting('documentView', $enArr['cab'])) {
		$docArr = getDocumentTypes(array('cab' => $enArr['cab']),$user,$db_doc,$db_dept); 	

		$dArr = array();
		foreach($docArr AS $real => $arb) {
			$dArr[] = array('real' => $real, 'arb' => $arb);
		}
		$jsonArr['documents'] = $dArr;
	} else {
		$tabs = $gblStt->get($enArr['cab'].'_tabs');
		$tArr = array('Main');
		if($tabs) {
			$tabArr = explode(',', $tabs);
			usort($tabArr,"strnatcasecmp");
			$sTabs = array_merge($tArr,$tabArr);
		} else {
			$sTabs = $tArr;
		}
		$jsonArr['tabs'] = $sTabs;
	}
	echo json_encode($jsonArr);
}

function getDocFields($enArr,$user,$db_dept,$db_doc) {
	$jsonArr = array();

	$sArr = array('id');
	$wArr = array('document_table_name'	=> $enArr['document_table_name']);
	$docTypeID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$sArr = array('real_field_name','arb_field_name');
	$wArr = array('document_table_name' => $enArr['document_table_name']);
	$oArr = array('ordering' => 'ASC');
	$docInfo = getTableInfo($db_dept,'document_field_defs_list',$sArr,$wArr,'getAssoc',$oArr);
	foreach($docInfo AS $real => $arb) {
		$docFieldArr = array('real' => $real, 'arb' => $arb);

		$defsList = array();
		$sArr = array('definition');
		$wArr = array(	'document_type_id' => (int)$docTypeID,
						'document_type_field' => $real);
		$defsList = getTableInfo($db_dept,'definition_types',$sArr,$wArr,'queryCol');
		if(count($defsList)) {
			$docFieldArr['defs'] = $defsList;
		}
		$jsonArr[] = $docFieldArr;
	}
	echo json_encode($jsonArr);
}

function addFolder($enArr,$user,$db_dept,$db_doc) {
	$jsonArr = array();
	$gblStt = new GblStt($user->db_name, $db_doc);

	$cab = $enArr['cab'];
	$sField = $enArr['fieldName'];
	$sValue = $enArr['fieldValue'];
	unset($enArr['cab']);
	unset($enArr['fieldName']);
	unset($enArr['fieldValue']);
	if(isSet($enArr['doc_name'])) {
		$docName = $enArr['doc_name'];
		unset($enArr['doc_name']);
	} else if(isSet($enArr['subfolder'])) {
		$subfolder = $enArr['subfolder'];
		unset($enArr['subfolder']);
	}

	$sArr = array();
	$wArr = array($sField => $sValue, 
				'deleted' => 0);
	$row = getTableInfo($db_dept,$cab,$sArr,$wArr,'queryRow');
	if(!count($row)) {
		if($gblStt->get('indexing_'.$cab) == 'odbc_auto_complete') {
			$sArr = array('connect_id');
			$wArr = array('cabinet_name' => $cab);
			$connID = getTableInfo($db_dept,'odbc_auto_complete',$sArr,$wArr,'queryOne');
			$db_odbc = getODBCDbObject($connID,$db_doc);
		
			$row = getODBCRow($db_odbc,$sValue,$cab,$db_dept,'',$user->db_name,$gblStt);
		} else {
			$autoCab = $gblStt->get('indexing_'.$cab);
			$sArr = array();
			$wArr = array($sField => $sValue);
			$row = getTableInfo($db_dept,$autoCab,$sArr,$wArr,'queryRow');		
		}

		if(!$row) {
			$row[$sField] = $sValue;
		}
		$tempTable = "";
		$doc_id = createFolderInCabinet($db_dept,$gblStt,$db_doc,$user->username,
					$user->db_name,$cab,array_values($row),array_keys($row),$tempTable);
	} else {
		$doc_id = $row['doc_id'];
		unset($row['doc_id']);
		unset($row['location']);
		unset($row['deleted']);
	}
	$jsonArr['cab'] = array('real' => $cab, 'arb' => $user->cabArr[$cab]);
	$jsonArr['folderInfo'] = implode(" ",array_values($row));

	if(isSet($docName)) {
		$j = 0;
		$docDesc = array();
		$docInfo = array('document_table_name' => $docName,
						'field_count' => count($enArr),
						'cabinet' => $cab,
						'doc_id' => $doc_id);
		foreach($enArr AS $name => $value) {
			$docInfo['key'.$j] = $name;
			$docInfo['field'.$j] = $value;
			$docDesc[] = $value;
			$j++;
		}
		$subfolder = addDocumentToCabinet($docInfo,$user,$db_doc,$db_dept);
		$jsonArr['documentInfo'] = $docInfo['document_type_name'].": ".implode(" ",$docDesc);
	} else if(isSet($subfolder)) {
		$jsonArr['documentInfo'] = $subfolder;
	}

	$bcArr = array('cab' => $cab,
                'doc_id' => $doc_id);
    if($subfolder != 'Main') {
        $bcArr['subfolder'] = $subfolder;
    } else {
        $bcArr['subfolder'] = '';
    }
    $_SESSION['barcodeArr'][] = $bcArr;
	echo json_encode($jsonArr);
}

function getDocList($user,$db_dept,$db_doc) {
	$cab = "Contracts";	
	$dArr = array();

	$docArr = getDocumentTypes(array('cab' => $cab),$user,$db_doc,$db_dept); 
	foreach($docArr AS $real => $arb) {
		$dArr[] = array('real' => $real, 'arb' => $arb);
	}
	$jsonArr['documents'] = $dArr;
	
	echo json_encode($jsonArr);
}

function createFolders($enArr,$user,$db_dept,$db_doc) {
	global $DEFS;
	$cab = "Contracts";	
	$gblStt = new GblStt($user->db_name, $db_doc);

	$fieldNames = getCabinetInfo($db_dept,$cab);
	$fpath = $DEFS['TMP_DIR']."/".$enArr['filename'];
	if(is_file($fpath)) {
		$hd = fopen($fpath,"r");	
		if($hd) {
			while(!feof($hd)) {
				$ln = fgets($hd);
				if(trim($ln)) {
					$bcInfo = explode("\t",trim($ln));
					
					$docDesc = $bcInfo[count($bcInfo)-1];
					unset($bcInfo[count($bcInfo)-1]);

					$sArr = array('doc_id');
					$wArr = array($fieldNames[0] => $bcInfo[0],
								'deleted' => 0);
					$doc_id = getTableInfo($db_dept,$cab,$sArr,$wArr,'queryOne');
					if(!$doc_id) {
						$tempTable = "";
						$doc_id = createFolderInCabinet($db_dept,$gblStt,$db_doc,$user->username,
									$user->db_name,$cab,$bcInfo,$fieldNames,$tempTable);
					}
					$docInfo = array('cabinet' => $cab,
									'doc_id' => $doc_id,
									'document_table_name' => $enArr['docname'],
									'field_count' => 1,
									'key0' => 'f1',
									'field0' => $docDesc);
					$subfolder = addDocumentToCabinet($docInfo,$user,$db_doc,$db_dept);

					$bcArr = array('cab' => $cab,
								'doc_id' => $doc_id,
								'subfolder' => $subfolder);
					$_SESSION['barcodeArr'][] = $bcArr;

					$jsonArr['bcInfo'][] = array('folderInfo' => implode(" ",array_values($bcInfo)),
											'documentInfo' => $docInfo['document_type_name'].": ".$docDesc);
				}
			}
			fclose($hd);
			echo json_encode($jsonArr);
		}
	}
}
?>
