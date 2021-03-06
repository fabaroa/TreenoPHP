<?php
include_once '../lib/utility.php';
include_once '../lib/settings.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/indexing2.php';
include_once '../lib/indexing.inc.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
include_once '../lib/tabFuncs.php';
include_once '../secure/tabChecks.php';
include_once '../documents/documents.php';
include_once '../check_login.php';

function xmlSetIndexingSession($enArr,$user,&$db_doc,$db_dept) {
	$cab = $enArr['cabinet'];
	$mess = "";
	if($user->checkSecurity($cab) == 2) {
		setIndexingSession($cab);
	} else {
		$mess = "User doesn't have Read/Write Permissions";
	}

	$xmlObj = new xml();	
	$xmlObj->createKeyAndValue('FUNCTION','loadCabinet()');
	if($mess) {
		$xmlObj->createKeyAndValue('MESSAGE',$mess);
	}
	$xmlObj->setHeader();
}

function setIndexingSession($cab) {
	if(!isset ($_SESSION['indexing']) or !is_array($_SESSION['indexing'])) {
		$indArr = array();
	} else {
		$indArr = $_SESSION['indexing'];
	}

	$indArr['cabinet'] = $cab;
	$_SESSION['indexing'] = $indArr;
}

function xmlGetIndexingType($enArr,$user,&$db_doc,$db_dept) {
	$cab = $_SESSION['indexing']['cabinet'];
	$fieldArr = getCabinetInfo($db_dept,$cab); 
	$indSettArr = getIndexingSettings($cab,$fieldArr,$user,$db_doc);
	$wfDefs = getIndexingWorkflow($user);
	$indexInfo = getIndexingInfo($cab,$user);
	$count = getTableInfo($db_dept, $cab."_indexing_table", array("count(*)"), array(), "queryOne");
	$count -= 1;

	if($user->checkSetting('documentView',$cab)) {
		$enArr['cab'] = $cab;
		$docArr = getDocumentTypes($enArr,$user,$db_doc,$db_dept);	
		$docView = 1;
	} else {
		$tabArr = getSavedTabs($cab,$user->db_name,$db_doc);
		usort($tabArr,"strnatcasecmp");
		$docView = 0;
	}

	$userSett = new Usrsettings ($user->username,$user->db_name,$db_doc);
	$fp = $userSett->get('indexingQuickView');

	$quickView = 1;
	if($fp && $fp == 0) {
		$quickView = 0;
	}
		
	$xmlObj = new xml();	
	$xmlObj->createKeyAndValue('FUNCTION','loadIndexingType(XML)');
	if($indexInfo) {
		$xmlObj->setRootAttribute('count', $count);
		$xmlObj->setRootAttribute('docView', $docView);
		$xmlObj->setRootAttribute('quickView', $quickView);
		$xmlObj->setRootAttribute('date_indexed',date('Y')."-".date('m')."-".date('d'));	
		foreach($indSettArr AS $k => $v) {
			$xmlObj->setRootAttribute($k,$v);	
		}
		$xmlObj->setRootAttribute("pages",count($_SESSION['indexFileArray']));	
		
		$pathArr = explode("/",$_SESSION['indexFileArray'][0]);
		$file = implode("/",array_slice($pathArr,7));
		$xmlObj->setRootAttribute("viewing",$cab."/".$file);	

		foreach($wfDefs AS $wf) {
			$xmlObj->createKeyAndValue('WORKFLOW',$wf);
		}

		if($docView) {
			if($docArr) {
				foreach($docArr AS $k => $doc) {
					$xmlObj->createKeyAndValue('DOCUMENT',$doc,array('name' => $k));
				}
			}
		} else {
			$xmlObj->createKeyAndValue('TAB','Main');
			if($tabArr) {
				foreach($tabArr AS $tab) {
					$xmlObj->createKeyAndValue('TAB',$tab);
				}
			}
		}

//		if($autoComp) {
//			$value = str_replace("_"," ",$fieldArr[0]);
//			$attArr = array('name' => $fieldArr[0]);
//			$xmlObj->createKeyAndValue('FIELD',$value,$attArr);
//		} else {
			for($i=0;$i<count($fieldArr);$i++) {
				$value = str_replace("_"," ",$fieldArr[$i]);
				$attArr = array('name' => $fieldArr[$i]);
				$xmlObj->createKeyAndValue('FIELD',$value,$attArr);
			}
//		}
	}
	$xmlObj->setHeader();
}

function xmlCheckSubfolder($enArr,$user,$db_doc,$db_dept) {
	$cab = $_SESSION['indexing']['cabinet'];
	$tab = strip_tags($enArr['subfolder']);
	$tab = $user->parseStr($tab);
	$status = tabCheck($tab,$user);

	$dupTab = false;
	$tabArr = getSavedTabs($cab,$user->db_name,$db_doc);
	foreach($tabArr AS $t) {
		if(strtolower($tab) == strtolower($t)) {
			$dupTab = true;	
			break;
		}
	}

	$xmlObj = new xml();	
	$xmlObj->createKeyAndValue('FUNCTION','addSubFolder(XML)');
	if($status !== false) {
		$xmlObj->createKeyAndValue('SUCCESS','0');
		$xmlObj->createKeyAndValue('MESSAGE',$status);
	} else if($dupTab) {
		$xmlObj->createKeyAndValue('SUCCESS','0');
		$xmlObj->createKeyAndValue('MESSAGE','Duplicate Tab');
	} else {
		$xmlObj->createKeyAndValue('SUCCESS','1');
		$xmlObj->createKeyAndValue('TAB',$tab);
	}
	$xmlObj->setHeader();
}

function getIndexingSettings($cab,$fieldArr,$user,&$db_doc) {
	$indSettArr = array();
	$gblStt = new Gblstt($user->db_name, $db_doc);
	$autoCompTbl = $gblStt->get ('indexing_' . $cab);
	if($autoCompTbl == "auto_complete_".$cab or
		$autoCompTbl == 'odbc_auto_complete' or
		$autoCompTbl == 'sagitta_ws_auto_complete') {

		$indSettArr['auto_complete'] = 1;
	} else {
		$indSettArr['auto_complete'] = 0;
	}
	$indSettArr['scroll'] = $gblStt->get('scroll');
	$indSettArr['date_functions'] = ($gblStt->get('date_functions')) ? 1 : 0;

	$db_dept = $user->getDbObject();
	$sArr = array('departmentid');
	$wArr = array('real_name' => $cab);
	$cabID = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne');
	foreach($fieldArr AS $k => $v) {
		if($typeDefs = $gblStt->get("dt,$user->db_name,$cabID,$v")) {
			$indSettArr[$v] = $typeDefs;
		}		
	}

	return $indSettArr;
}

function getIndexingInfo($cab,$user) {
	global $DEFS;
	$db_dept = $user->getDbObject();

	lockTables($db_dept,array($cab."_indexing_table"));
	$sArr = array('id','path','folder');
	$wArr = array('flag=0','finished<total');
	$oArr = array('id' => 'ASC');
	$indArr = getTableInfo($db_dept,$cab."_indexing_table",$sArr,$wArr,'getAssoc',$oArr);
	foreach($indArr AS $id => $info) {
		$path=explode(" ",$info['path'],4);
		$temp=$path[0]."/".$path[1]."/".$path[2]."/".$path[3];
		$curPath = $DEFS['DATA_DIR']."/".$temp;
		if(is_dir($curPath)) {
			$uArr = array('flag' => 1);	
			$wArr = array('id' => (int)$id);
			updateTableInfo($db_dept,$cab."_indexing_table",$uArr,$wArr);

			$filesArray = array();
			$filesArray = getAllFilesFromIndexingFolder($curPath);
			$_SESSION['indexFileArray'] = $filesArray;
			$_SESSION['indexing']['id'] = $id;
			unlockTables($db_dept);
			return true;
		} else {
			$wArr = array('id' => (int)$id);
			deleteTableInfo($db_dept,$cab."_indexing_table",$wArr);
		}
	}
	unlockTables($db_dept);
	return false;
}

function getIndexingWorkflow($user) {
	$wf_defs = array();
	if(check_enable('workflow', $user->db_name)) {
		$db_dept = $user->getDbObject();
		$sArr = array('DISTINCT(defs_name)');
		$oArr = array('defs_name' => 'ASC');
		$wf_defs =  getTableInfo($db_dept,'wf_defs',$sArr,array(),'queryCol',$oArr);
	}
	return $wf_defs;
}

function getAllFilesFromIndexingFolder($path) {
	$dh = opendir($path);
	$filesArray = array();
	while($file = readdir($dh)) {
		if(is_file($path.'/'.$file)) {
			if($file != "INDEX.DAT") {
				$filesArray[] = $path.'/'.$file;
			}
		}
		if(is_dir($path.'/'.$file) and $file != '.' and $file != '..') {
			$subDh = opendir($path.'/'.$file);
			while($subFile = readdir($subDh)) {
				if(is_file($path.'/'.$file.'/'.$subFile)) {
					if($subFile != "INDEX.DAT") {
						$filesArray[] = $path.'/'.$file.'/'.$subFile;
					}
				}
			}
			closedir($subDh);
		}
	}
	closedir($dh);
	usort($filesArray, 'strnatcasecmp');
	return $filesArray;
}

function xmlDeleteIndexingBatch($enArr,$user,&$db_doc,$db_dept) {
	deleteIndexingBatch($user);
	xmlGetIndexingType($enArr,$user,$db_doc, $db_dept);
}

function deleteIndexingBatch($user) {
	$db_dept = $user->getDbObject();
	$indexArr = $_SESSION['indexing'];

	$path = $_SESSION['indexFileArray'][0];
	$batch = dirname($path);
	if(delDir($batch)) {
		$wArr = array('id' => (int)$indexArr['id']);
		deleteTableInfo($db_dept,$indexArr['cabinet']."_indexing_table",$wArr);
	}
}

function xmlGetIndexingFilename($enArr,$user,&$db_doc,$db_dept) {
	$fname = getIndexingFilename($enArr['page']);

	$xmlObj = new xml();	
	$xmlObj->createKeyAndValue('FUNCTION','setIndexingFilename(XML)');
	$xmlObj->createKeyAndValue('NAME',$fname);
	$xmlObj->setHeader();
}

function getIndexingFilename($page) {
	$pathArr = explode("/",$_SESSION['indexFileArray'][$page-1]);
	$file = implode("/",array_slice($pathArr,5));

	return $file;
}

function xmlIndexBatch($enArr,$user,&$db_doc,$db_dept) {
	indexBatch($enArr,$user,$db_doc);
	xmlGetIndexingType($enArr,$user,$db_doc,$db_dept);
}

function indexBatch($enArr,$user, &$db_doc) {
	global $DEFS;
	$db_dept = $user->getDbObject();

	$tab = null;
	if(isSet($enArr['tab'])) {
		if($enArr['tab'] != 'Main') {
			$tab = $enArr['tab'];
		}
		unset($enArr['tab']);
	}

	$docType = "";
	$docArr = array();
	if(isSet($enArr['docType'])) {
		$docType = $enArr['docType'];
		unset($enArr['docType']);
		$i = 0;
		while(isSet($enArr['docKey'.$i])) {
			$docArr[$enArr['docKey'.$i]] = $enArr['docVal'.$i];
			unset($enArr['docKey'.$i]);
			unset($enArr['docVal'.$i]);
			$i++;
		}
	}

	$indexArr = $_SESSION['indexing'];	
	$cab = $indexArr['cabinet']; 
	if(isSet($enArr['workflow'])) {
		$workflow = $enArr['workflow'];
		unset($enArr['workflow']);
	} else {
		$workflow = null;
	}
	$folderIndices = $enArr;
	
	$batchLoc = $DEFS['DATA_DIR']."/";
	$sArr = array('path');	
	$wArr = array('id' => (int)$indexArr['id']);

	$loc = getTableInfo($db_dept,$cab.'_indexing_table',$sArr,$wArr,'queryOne');	
	$path=explode(" ",$loc,4);
	if (isset($path[3]))
	{
		$temp=$path[0]."/".$path[1]."/".$path[2]."/".$path[3];
		$batchLoc .= $temp;
		
		$user->audit( "Indexing", "Indexed Folder" );
		$gblStt = new GblStt ($user->db_name, $db_doc);
		Indexing::index($db_dept, $db_doc, $folderIndices, $cab, $user->username, 
						$user->db_name, $DEFS, $batchLoc, $gblStt, $workflow,$tab,$docType,$docArr,$user);

		deleteTableInfo($db_dept,$cab.'_indexing_table',$wArr);
		unset ($_SESSION['indexFileArray']);
	}
	else
	{
		$message="***The Result for select path from ".$cab."_indexing_table where id=".$indexArr['id']." is ".$loc;
		error_log($message);
		mail("fabaroa@treenosoftware.com","TR1 Indexing Bad Path",$message);
	}
}

function xmlGetAutoComplete($enArr,$user,&$db_doc,$db_dept) {
	$rowInfo = getAutoComplete2($enArr,$user,$db_doc, $db_dept);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue('FUNCTION','setAutoComplete(XML)');
	foreach($rowInfo AS $k => $v) {
		$xmlObj->createKeyAndValue('FIELD',$v,array('name' => $k));
	}
	$xmlObj->setHeader();
}

function getAutoComplete2($enArr,$user,&$db_doc,$db_dept) {
	$gblStt = new GblStt ($user->db_name, $db_doc);
	$cab = $_SESSION['indexing']['cabinet'];
	$fieldArr = getCabinetInfo($db_dept,$cab); 
	$acTable = $gblStt->get ('indexing_' . $cab);
	$rowInfo = searchAutoComplete ($db_dept, $acTable, $fieldArr[0],
		$enArr['value'], $cab, $db_doc, '', $user->db_name, $gblStt);
	$newRowInfo = array ();
	foreach ($fieldArr as $myField) {
		if (isset ($rowInfo[$myField])) {
			$newRowInfo[$myField] = $rowInfo[$myField];
		} else {
			$newRowInfo[$myField] = '';
		}
	}

	return $newRowInfo;
}

function xmlSetFirstPage($enArr,$user,$db_doc,$db_dept) {
	$uSett = $_SESSION[$user->db_name.'-'.$user->username.'-Usrsettings'];
	$uSett['settings']['indexingQuickView'] = $enArr['quickView'];

	$userSett = new Usrsettings ($user->username,$user->db_name,$db_doc);
	$userSett->set('indexingQuickView',$enArr['quickView']);

	$_SESSION[$user->db_name.'-'.$user->username.'-Usrsettings'] = $uSett;

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue('FUNCTION','loadPage(1)');
	$xmlObj->setHeader();
}
?>
