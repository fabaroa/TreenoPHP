<?php
// $Id: settingsFuncs.php 14985 2013-04-24 12:14:59Z cz $

//This file is used for XMLHTTP functions for the setting panes. These
//functions should probably not be used outside of this file.
require_once '../workflow/workFlowDefs.inc';
require_once '../check_login.php';
require_once '../settings/settings.php';
require_once '../lib/odbc.php';
require_once '../lib/tabFuncs.php';
require_once '../lib/indexing2.php';
require_once '../groups/groups.php';
require_once '../lib/settingsList.inc.php';
require_once '../lib/ldap.php';
require_once '../lib/allSettings.php';
require_once '../lib/passwordSettingsFuncs.php';

if($logged_in and $user->username) {
	switch($_GET['func']) {
		case 'setFrameWidth' :
			setFrameWidth($_GET['v1'],$user->db_name,$db_doc);
			break;
		case 'setTabOrdering':
			setTabOrdering($user->db_name, $_GET['v1'], $db_doc);
			break;
		case 'getDeptInfo':
			$db_obj = getDbObject ($_GET['v1']);
			xmlGetDeptInfo($db_obj, $db_doc, $user->username, $_GET['v1']);
			$db_obj->disconnect ();
			break;
		case 'searchAutoComplete':
			xmlSearchAutoComplete($db_object, $_GET['v1'], $_GET['v2'], $_GET['v3'], $_GET['v4'], $db_doc, $user->db_name);
			break;
		case 'getFileIDs':
			xmlGetFileIDs($_GET['v1'], $_GET['v2'], $_GET['v3'], $user->db_name, $db_object, $db_doc);
			break;
		case 'emptyTabs':
			xmlEmptyTabs($user, $db_doc);
			break;
		case 'dateFuncs':
			xmlDateFuncs($user, $db_doc);
			break;
		case 'setOrdering':
			xmlSetOrdering($user, $db_object);
			break;
		case 'changeWFName':
			xmlChangeWFName($_GET['v1'], $_GET['v2'], $db_object);
			break;
		case 'getSettingList':
			xmlGetSettingList($user->db_name);
			break;
		case 'getSettingsByCab':
			xmlGetSettingsByCab($user->db_name, $db_object, $_GET['v1'], $_GET['v2'], $_GET['v3'], $db_doc);
			break;
		case 'getSettingsBySetting':
			xmlGetSettingsBySetting($user->db_name, $_GET['v1'], $_GET['v2'], $_GET['v3'], $db_doc, $db_object);
			break;
		case 'getGlobalSettings':
			xmlGetGlobalSettings($user->db_name, $db_object, $_GET['v1'], $_GET['v2'], $db_doc);
			break;
		case 'setSettings':
			xmlSetSettings($user, $db_doc, $db_object);
			break;
		case 'getUsrCabSettings':
			xmlGetUsrCabSettings($user, $_GET['v1']);
			break;
		case 'submitUsrSett':
			xmlSubmitUsrSett($user, $_GET['v1']);
			break;
		case 'submitSysSett':
			xmlSubmitSysSett($user, $db_doc);
			break;
		case 'getODBCConnList':
			xmlGetODBCConnList($user,$db_doc);
			break;
		case 'testODBCConn':
			xmlTestODBCConn();
			break;
		case 'submitConn':
			xmlSubmitConn($user,$db_doc);
			break;
		case 'getODBCConnInfo':
			xmlGetODBCConnInfo($_GET['v1'], $db_doc);
			break;
		case 'getAllNonCabinetSettings':
			xmlGetAllNonCabinetSettings($user->db_name);
			break;
		case 'getNonCabinetSettings':
			xmlGetNonCabinetSettings($_GET['v1'], $_GET['v2'], $user->db_name,
				$user, $db_doc, $db_object);
			break;
		case 'setNonCabinetSettings':
			xmlSetNonCabinetSettings($user, $db_doc);
			break;
		case 'getLDAPUserList':
			xmlGetLDAPUserList($user, $_GET['v1'], $db_doc);
			break;
		case 'importLDAPUsers':
			xmlImportLDAPUsers($user, $db_object, $db_doc);
			break;
		case 'getDefaultPages':
			xmlGetDefaultPages ($user, $db_doc);
			break;
		case 'setDefaultPage':
			xmlSetDefaultPage ($user, $db_doc);
			break;
		case 'setFileFormat':
			setFileFormat($user,$db_doc,$_GET['type']);
			break;
		case 'getFileFormat':
			getFileFormat($user,$db_doc);
			break;
		case 'getEncryptedPassword':
			getEncryptedPassword($_GET['v1']);
			break;			
		case 'getPasswordSettingsList':
			$xmlStr = xmlGetPasswordSettingsList($db_doc, $user->db_name);
			header('Content-type:text/xml');
			echo $xmlStr;
			break;	
		case 'updatePasswordSettings':
			xmlUpdatePasswordSettings($db_doc, $user->db_name);
			break;
		case 'displayPasswordSettings':
			xmlDisplayPasswordSettings($db_doc, $user->db_name);	
		default:
			break;
	}
}

function setFrameWidth($width,$dep,$db_doc) {
	$gblStt = new GblStt($dep, $db_doc);
	$gblStt->set('frame_width', $width);
}

/**
 * Sets the global tab ordering to ascending, descending, or no sorting at all.
 * @param	string $dbName	database name for files
 * @param	string $value	choice of ASC, DESC, NONE
 * @return	void
 */
function setTabOrdering($dbName, $value, $db_doc) {
	$gblStt = new GblStt($dbName, $db_doc);
	$tabOrdering = $gblStt->get('tab_ordering');
	if ($value == 'NONE' and $tabOrdering) {
		$gblStt->removeKey('tab_ordering');
	}
	if ($value != 'NONE') {
		$gblStt->set('tab_ordering', $value);
	}
}

/**
 * outputs an XML stream of department information.
 * @return	void
 */
function xmlGetDeptInfo($db, $db_doc, $username,$dep) {
	$defsList =  getTableInfo($db,'wf_defs',array('DISTINCT(defs_name)'),array(),'queryCol',array('defs_name'=>'ASC'));
	$cabsInfo = getTableInfo($db, 'departments', array(), array('deleted' => 0));
	$cabList = array ();
	while ($row = $cabsInfo->fetchRow()) {
		$cabList[$row['real_name']] = $row['departmentname'];
	}
	//gets the usernames
	$oArr = array('username' => 'ASC');
	$usernames = getTableInfo($db,'access',array('username'),array(),'queryCol',$oArr);
	foreach($usernames AS $uname) {
		$userArr[] = "username='$uname'";
	}

	$gblStt = new GblStt($dep,$db_doc);
	$disableSecurity = $gblStt->get('wfSecurity');

	$tmpUser = new user();
	$tmpUser->username = $username;
	$tmpUser->fillUser(NULL,$dep);
	foreach($tmpUser->access AS $cab => $rights) {
		if($disableSecurity) {
			continue;
		} else if($rights != "rw") {
			unset($cabList[$cab]);
		}
	}

	$sArr = array('id','username');
	$wArr = array('('.implode(' OR ',$userArr).')');
	$userList = getTableInfo($db_doc,'users',$sArr,$wArr,'getAssoc');
	uasort($userList,"strnatcasecmp");
 
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc('1.0');
 		$root = $xmlDoc->create_element('depts');
 		$xmlDoc->append_child($root);
 		$defs = $xmlDoc->create_element('defs');
 		$root->append_child($defs);
 		foreach ($defsList as $myDef) {
 			$def = $xmlDoc->create_element('def');
 			$def->set_attribute('name', $myDef);
 			$defs->append_child($def);
 		}
 		$cabs = $xmlDoc->create_element('cabs');
 		$root->append_child($cabs);
 		foreach ($cabList as $real => $arb) {
 			$cab = $xmlDoc->create_element('cab');
 			$cab->set_attribute('real', $real);
 			$cab->set_attribute('arb', $arb);
 			$cabs->append_child($cab);
 		}
		$users = $xmlDoc->create_element('users');
		$root->append_child($users);
		foreach($userList as $id => $name) {
			$uname = $xmlDoc->create_element('user');
			$uname->set_attribute('id', $id);
			$uname->set_attribute('name', $name);
			$users->append_child($uname);
		}
 		$xmlStr = $xmlDoc->dump_mem (false);
 	} else {
 		$xmlDoc = new DOMDocument ();
 		$root = $xmlDoc->createElement('depts');
 		$xmlDoc->appendChild ($root);
 		$defs = $xmlDoc->createElement('defs');
 		$root->appendChild ($defs);
 		foreach ($defsList as $myDef) {
 			$def = $xmlDoc->createElement('def');
 			$def->setAttribute('name', $myDef);
 			$defs->appendChild ($def);
 		}
 		$cabs = $xmlDoc->createElement('cabs');
 		$root->appendChild ($cabs);
 		foreach ($cabList as $real => $arb) {
 			$cab = $xmlDoc->createElement('cab');
 			$cab->setAttribute('real', $real);
 			$cab->setAttribute('arb', $arb);
 			$cabs->appendChild ($cab);
 		}
		$users = $xmlDoc->createElement('users');
		$root->appendChild($users);
		foreach($userList as $id => $name) {
			$uname = $xmlDoc->createElement('user');
			$uname->setAttribute('id', $id);
			$uname->setAttribute('name', $name);
			$users->appendChild($uname);
		}
 		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlSearchAutoComplete($db, $searchTerm, $searchField, $cabinet, $autoCompleteTable, $db_doc, $department) {
  	$indices = getCabinetInfo($db, $cabinet);
	$gblStt = new GblStt ($department, $db_doc);
	$row = searchAutoComplete($db, $autoCompleteTable, $searchField, $searchTerm, $cabinet, $db_doc, '', $department, $gblStt);
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc('1.0');
 		$root = $xmlDoc->create_element('search');
 		$xmlDoc->append_child($root);
 		foreach ($row as $myField => $value) {
 			$res = $xmlDoc->create_element('res');
 			$res->set_attribute('field', $myField);
 			$res->set_attribute('value', $value);
 			$root->append_child($res);
 		}
 		$xmlStr = $xmlDoc->dump_mem (false);
 	} else {
 		$xmlDoc = new DOMDocument ();
 		$root = $xmlDoc->createElement('search');
 		$xmlDoc->appendChild($root);
 		foreach ($row as $myField => $value) {
 			$res = $xmlDoc->createElement('res');
 			$res->setAttribute('field', $myField);
 			$res->setAttribute('value', $value);
 			$root->appendChild($res);
 		}
 		$xmlStr = $xmlDoc->saveXML ();
 	}
  	header('Content-type: text/xml');
 	echo $xmlStr;
}

function xmlGetFileIDs($cabinet, $docID, $file_id, $db_name, $db, $db_doc) {
	if($file_id == -1) {
		$file_id = NULL;
	}
	$notShow = array();
	//This is to show tabs based on the access list in group_access in the
	//department database.
	$groups = new groups($db);
	$notShow = getNoShowTabs($cabinet, $docID, $db_name);
	$settings = new GblStt($db_name, $db_doc);
	if($file_id) {
		$whereArr = array('id' => (int)$file_id);
	} else {
		$whereArr = array('filename'=>'IS NULL','doc_id'=>(int)$docID,'display'=>1,'deleted'=>0);
	}
	$orderArr = array('subfolder'=>$settings->get('tab_ordering'));
	$tabs = getTableInfo($db,$cabinet."_files",array('DISTINCT(subfolder)'),$whereArr,'query',$orderArr);
	//gets all unique tabs that have files in them
	$allTabs = array();
	if(!$file_id) {
		$allTabs = array('main');
	}
	while($tabList = $tabs->fetchRow()) {
		$tmp = $tabList['subfolder'];	
		if($tmp and !in_array($tmp, $notShow)) {
			$allTabs[] = $tmp;
		}
	}

	$subfolder = "";
	if($file_id) {
		$subfolder = $allTabs[1]; 	
	}
	$fileArr = queryAllFilesInFolder($db, $cabinet, $docID,$subfolder);
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc('1.0');
 		$root = $xmlDoc->create_element('files');
 		$xmlDoc->append_child($root);
 		foreach($allTabs as $myTab) {
 			if(isset($fileArr[$myTab])) {
 				foreach($fileArr[$myTab] as $myFile) {
 					$file = $xmlDoc->create_element('file');
 					$file->set_attribute('fileID', $myFile['id']);
 					$root->append_child($file);
 				}
  			}
  		}
 		$xmlStr = $xmlDoc->dump_mem (false);
 	} else {
 		$xmlDoc = new DOMDocument ();
 		$root = $xmlDoc->createElement('files');
 		$xmlDoc->appendChild($root);
 		foreach($allTabs as $myTab) {
 			if(isset($fileArr[$myTab])) {
 				foreach($fileArr[$myTab] as $myFile) {
 					$file = $xmlDoc->createElement('file');
 					$file->setAttribute('fileID', $myFile['id']);
 					$root->appendChild($file);
 				}
 			}
 		}
 		$xmlStr = $xmlDoc->saveXML ();
 	}

  	header('Content-type: text/xml');
 	echo $xmlStr; 
}

function xmlEmptyTabs($user, $db_doc) {
  	$xmlStr = file_get_contents('php://input');
  	$settings = new GblStt($user->db_name, $db_doc);
 	$cabinets = array ();
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_open_mem($xmlStr);
 		$cabTags = $xmlDoc->get_elements_by_tagname('cabinet');
 		foreach($cabTags as $myCab) {
 			$cabName = $myCab->get_attribute('name');
 			$action = $myCab->get_attribute('action');
 			$cabinets[] = array ('name' => $cabName, 'action' => $action);
 		}
 	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
 		$cabTags = $xmlDoc->getElementsByTagName('cabinet');
		for ($i = 0; $i < $cabTags->length; $i++) {
			$myCab = $cabTags->item($i);
 			$cabName = $myCab->getAttribute('name');
 			$action = $myCab->getAttribute('action');
 			$cabinets[] = array ('name' => $cabName, 'action' => $action);
 		}
 	}
 	foreach ($cabinets as $cabinetInfo) {
 		if($cabinetInfo['action'] == 'show') {
  			$value = 0;
  		} else {
  			$value = 1;
  		}
 		$settings->set('tab_hiding_'.$cabinetInfo['name'], $value,
 				$user->db_name);
  	}
  	echo "Tab Hiding Settings Updated";
}

function xmlDateFuncs($user, $db_doc) {
  	$xmlStr = file_get_contents('php://input');
 	$depts = array ();
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_open_mem($xmlStr);
 		$deptTags = $xmlDoc->get_elements_by_tagname('dept');
 		foreach($deptTags as $myDept) {
 			$deptName = $myDept->get_attribute('name');
 			$action = $myDept->get_attribute('action');
 			$depts[] = array ('name' => $deptName,
 					'action' => $action);
 		}
 	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
 		$deptTags = $xmlDoc->getElementsByTagName('dept');
		for ($i = 0; $i < $deptTags->length; $i++) {
			$myDept = $deptTags->item ($i);
 			$deptName = $myDept->getAttribute('name');
 			$action = $myDept->getAttribute('action');
 			$depts[] = array ('name' => $deptName,
 					'action' => $action);
 		}
 	}
 	foreach ($depts as $deptInfo) {
 		$settings = new GblStt($deptInfo['name'], $db_doc);
 		if($deptInfo['action'] == 'date') {
  			$value = 1;
  		} else {
  			$value = 0;
  		}
 		$settings->set('date_functions', $value, $deptInfo['name']);
  	}
  	echo "Date Functions Settings Updated";
}

function xmlSetOrdering($user, $db) {
  	$xmlStr = file_get_contents('php://input');
  	$orderingArr = array ();
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_open_mem($xmlStr);
 		$tmpArr = $xmlDoc->get_elements_by_tagname('cabinet');
 		$cabinet = $tmpArr[0]->get_attribute('name');
 		$tmpArr = $xmlDoc->get_elements_by_tagname('docID');
 		$docID = $tmpArr[0]->get_attribute('id');
 		$tabArr = $xmlDoc->get_elements_by_tagname('tab');
 		foreach($tabArr as $myTab) {
 			$tabName = $myTab->get_attribute('name');
 			$orderingArr[$tabName] = array ();
 			$fileArr = $myTab->get_elements_by_tagname('file');
 			foreach($fileArr as $myFile) {
 				$fileID = $myFile->get_attribute('fileID');
 				$orderingNum = $myFile->get_attribute('order');
 				$orderingArr[$tabName][$orderingNum] = $fileID;
 			}
 		}
 	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);	
 		$tmpArr = $xmlDoc->getElementsByTagName('cabinet');
 		$cabinet = $tmpArr->item(0);
		$cabinet = $cabinet->getAttribute('name');
 		$tmpArr = $xmlDoc->getElementsByTagName('docID');
 		$docID = $tmpArr->item(0);
		$docID = $docID->getAttribute('id');
 		$tabArr = $xmlDoc->getElementsByTagName('tab');
		for ($i = 0; $i < $tabArr->length; $i++) {
			$myTab = $tabArr->item($i);
 			$tabName = $myTab->getAttribute('name');
 			$orderingArr[$tabName] = array ();
 			$fileArr = $myTab->getElementsByTagName('file');
			for($j = 0; $j < $fileArr->length; $j++) {
				$myFile = $fileArr->item ($j);
 				$fileID = $myFile->getAttribute('fileID');
 				$orderingNum = $myFile->getAttribute('order');
 				$orderingArr[$tabName][$orderingNum] = $fileID;
 			}
 		}
  	}
	lockTables($db, array($cabinet.'_files'));
	$query = 'UPDATE '.$cabinet.'_files SET ordering = ? WHERE id = ?';
	$p = $db->prepare($query);
	dbErr($p);
	foreach($orderingArr as $myTab) {
		ksort($myTab);
		$myTab = array_values($myTab);
		foreach($myTab as $myNewOrder => $fileID) {
			$res = $p->execute(array((int) $myNewOrder, (int) $fileID));
			dbErr($res);
			$parentID = getTableInfo($db, $cabinet.'_files', array('parent_id'), array('id' => $fileID), 'queryOne');
            $fileIDArr = getTableInfo($db, $cabinet.'_files', array('id'), array("parent_id=$parentID", "parent_id!=0"), 'queryCol');
			foreach($fileIDArr AS $parentFileID) {
				updateTableInfo($db, $cabinet.'_files', array('ordering' => (int)$myNewOrder), array('id' => $parentFileID));
			}
		}
	}
	unlockTables($db);
	$user->audit('ordering changed', "ordering in cabinet, folder: $docID " .
			"changed");
}

function xmlChangeWFName($newName, $origName, $db) {
	updateTableInfo ($db, 'wf_defs', array ('defs_name' => $newName),
		array ('defs_name' => $origName));
	$wfDefs = $_SESSION['wfDefs'];
	$wfDefs->setDefsName($newName);
	$_SESSION['wfDefs'] = $wfDefs;
}

function xmlGetSettingList($dbName) {
	$allSettings = settingsList::queryAllSettings($dbName);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('settingList');
		$xmlDoc->append_child($root);
		foreach($allSettings as $real => $disp) {
			$el = $xmlDoc->create_element('setting');
			$el->set_attribute('name', $real);
			$el->set_attribute('disp_name', $disp);
			$root->append_child($el);
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('settingList');
		$xmlDoc->appendChild($root);
		foreach($allSettings as $real => $disp) {
			$el = $xmlDoc->createElement('setting');
			$el->setAttribute('name', $real);
			$el->setAttribute('disp_name', $disp);
			$root->appendChild($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlGetSettingsByCab($db_name, $db_dept, $cabinet, $username = '', $groupname =
		'', $db) {
	if($username) {
		$settingsList = new settingsList($db, $db_name, $db_dept, 'user', $username);
		$type = 'User';
		$typeName = $username;
	} elseif($groupname) {
		$settingsList = new settingsList($db, $db_name, $db_dept, 'group', $groupname);
		$type = 'Group';
		$typeName = $groupname;
	} else {
		$settingsList = new settingsList($db, $db_name, $db_dept);
		$type = 'System';
		$typeName = '';
	}
	$cabSettings = $settingsList->getCabinetSettings($cabinet);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('cabinetSettings');
		$xmlDoc->append_child($root);
		$el = $xmlDoc->create_element('type');
		$el->set_attribute('name', $type);
		if($typeName) {
			$el->set_attribute('value', $typeName);
		}
		$root->append_child($el);
		$el = $xmlDoc->create_element('cabinet');
		$el->set_attribute('name', $cabinet);
		$root->append_child($el);
		foreach($cabSettings as $eachSett) {
			$el = $xmlDoc->create_element('setting');
			$el->set_attribute('name', $eachSett['name']);
			$el->set_attribute('disp_name', $eachSett['disp_name']);
			$el->set_attribute('enabled', $eachSett['enabled']);
			$root->append_child($el);
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('cabinetSettings');
		$xmlDoc->appendChild($root);
		$el = $xmlDoc->createElement('type');
		$el->setAttribute('name', $type);
		if($typeName) {
			$el->setAttribute('value', $typeName);
		}
		$root->appendChild($el);
		$el = $xmlDoc->createElement('cabinet');
		$el->setAttribute('name', $cabinet);
		$root->appendChild($el);
		foreach($cabSettings as $eachSett) {
			$el = $xmlDoc->createElement('setting');
			$el->setAttribute('name', $eachSett['name']);
			$el->setAttribute('disp_name', $eachSett['disp_name']);
			$el->setAttribute('enabled', $eachSett['enabled']);
			$root->appendChild($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlGetSettingsBySetting($db_name, $setting, $username = '', $groupname
		= '', $db, $db_dept) {
	if($username) {
		$settingsList = new settingsList($db, $db_name, $db_dept, 'user', $username);
		$type = 'User';
		$typeName = $username;
	} elseif($groupname) {
		$settingsList = new settingsList($db, $db_name, $db_dept, 'group', $groupname);
		$type = 'Group';
		$typeName = $groupname;
	} else {
		$settingsList = new settingsList($db, $db_name, $db_dept);
		$type = 'System';
		$typeName = '';
	}
	$mySettings = $settingsList->getSettingCabinets($setting);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('settingsByCab');
		$xmlDoc->append_child($root);
		$el = $xmlDoc->create_element('type');
		$el->set_attribute('name', $type);
		if($typeName) {
			$el->set_attribute('value', $typeName);
		}
		$root->append_child($el);
		$el = $xmlDoc->create_element('setting');
		$el->set_attribute('name', $setting);
		$root->append_child($el);
		foreach($mySettings as $eachSett) {
			$el = $xmlDoc->create_element('cabinet');
			$el->set_attribute('real_name', $eachSett['real_name']);
			$el->set_attribute('arb_name', $eachSett['arb_name']);
			$el->set_attribute('enabled', $eachSett['enabled']);
			$root->append_child($el);
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('settingsByCab');
		$xmlDoc->appendChild($root);
		$el = $xmlDoc->createElement('type');
		$el->setAttribute('name', $type);
		if($typeName) {
			$el->setAttribute('value', $typeName);
		}
		$root->appendChild($el);
		$el = $xmlDoc->createElement('setting');
		$el->setAttribute('name', $setting);
		$root->appendChild($el);
		foreach($mySettings as $eachSett) {
			$el = $xmlDoc->createElement('cabinet');
			$el->setAttribute('real_name', $eachSett['real_name']);
			$el->setAttribute('arb_name', $eachSett['arb_name']);
			$el->setAttribute('enabled', $eachSett['enabled']);
			$root->appendChild($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlGetGlobalSettings($db_name, $db_dept, $username = '', $groupname = '', $db) {
	if($username) {
		$settingsList = new settingsList($db, $db_name, $db_dept, 'user', $username);
		$type = 'User';
		$typeName = $username;
	} elseif($groupname) {
		$settingsList = new settingsList($db, $db_name, $db_dept, 'group', $groupname);
		$type = 'Group';
		$typeName = $groupname;
	} else {
		$settingsList = new settingsList($db, $db_name, $db_dept);
		$type = 'System';
	}
	$mySettings = $settingsList->getGlobalSettings();
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('globalSettings');
		$xmlDoc->append_child($root);
		$el = $xmlDoc->create_element('type');
		$el->set_attribute('name', $type);
		if($typeName) {
			$el->set_attribute('value', $typeName);
		}
		$root->append_child($el);
		foreach($mySettings as $eachSett) {
			$el = $xmlDoc->create_element('setting');
			$el->set_attribute('name', $eachSett['name']);
			$el->set_attribute('disp_name', $eachSett['disp_name']);
			$el->set_attribute('state', $eachSett['state']);
			$el->set_attribute('mixed', $eachSett['mixed']);
			$root->append_child($el);
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('globalSettings');
		$xmlDoc->appendChild($root);
		$el = $xmlDoc->createElement('type');
		$el->setAttribute('name', $type);
		if($typeName) {
			$el->setAttribute('value', $typeName);
		}
		$root->appendChild($el);
		foreach($mySettings as $eachSett) {
			$el = $xmlDoc->createElement('setting');
			$el->setAttribute('name', $eachSett['name']);
			$el->setAttribute('disp_name', $eachSett['disp_name']);
			$el->setAttribute('state', $eachSett['state']);
			$el->setAttribute('mixed', $eachSett['mixed']);
			$root->appendChild($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlSetSettings($user, $db, $db_dept) {
	$settings = array ();
	$xmlStr = file_get_contents ('php://input');
	$xmlDoc = new DOMDocument ();
	$xmlDoc->loadXML ($xmlStr);
	$tmpArr = $xmlDoc->getElementsByTagName('type');
	$type = $tmpArr->item(0);
	$type = $type->getAttribute('name');
	$typeVal = $tmpArr->item(0);
	$typeVal = $typeVal->getAttribute ('value');
	$settArr = $xmlDoc->getElementsByTagName('setting');
	if($settArr->length > 0) {
		$forCabinet = $settArr->item(0);
		$forCabinet = $forCabinet->getAttribute ('cabinet');
		for ($i = 0; $i < $settArr->length; $i++) {
			$mySetting = $settArr->item($i);
			$settings[] = array ('name' => $mySetting->getAttribute ('name'), 
					'state' => $mySetting->getAttribute ('state'), 'cabinet' =>
					$mySetting->getAttribute ('cabinet'));
		}
	}

	if($type == 'Users') {
		$username = $typeVal; 
		$settingsList = new settingsList($db, $user->db_name, $db_dept, 'user', $username);
	} elseif($type == 'Groups') {
		$groupname = $typeVal; 
		$settingsList = new settingsList($db, $user->db_name, $db_dept, 'group', $groupname);
	} else {
		$settingsList = new settingsList($db, $user->db_name, $db_dept);
	}
	if(!$forCabinet) {
		$cabInfo = getTableInfo($db_dept, 'departments', array(), array('deleted' =>
					0));
		$cabArr = array ();
		while($row = $cabInfo->fetchRow()) {
			$cabArr[] = $row['real_name'];
		}
		foreach($settings as $mySetting) {
			$setting = (string) $mySetting['name'];
			$state = (string) $mySetting['state'];
			foreach($cabArr as $cabinet) {
				$settingsList->markInherited($cabinet, $setting);
			}
			if($state == 'enabled') {
				$settingsList->markEnabled('0', $setting);
			} elseif($state == 'disabled') {
				$settingsList->markDisabled('0', $setting);
			} else {
				$settingsList->markInherited('0', $setting);
			}
		}
	} else {
		foreach($settings as $mySetting) {
			$setting = (string) $mySetting['name'];
			$cabinet = (string) $mySetting['cabinet'];
			$state = (string) $mySetting['state'];
			if($state == 'enabled') {
				$settingsList->markEnabled($cabinet, $setting);
			} elseif($state == 'disabled') {
				$settingsList->markDisabled($cabinet, $setting);
			} else {
				$settingsList->markInherited($cabinet, $setting);
			}
		}
	}
	$settingsList->commitChanges();
	$user->setSecurity(true);
	echo 'Settings Successfully Updated';
}


function xmlSetNonCabinetSettings($user, $db) {
	$settings = array ();
	$xmlStr = file_get_contents('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_mem($xmlStr);
		$tmpArr = $xmlDoc->get_elements_by_tagname('type');
		$type = $tmpArr[0]->get_attribute('name');
		$setting = $tmpArr[0]->get_attribute('setting');
		$settingArr = $xmlDoc->get_elements_by_tagname('setting');
		foreach ($settingArr as $mySetting) {
			$settings[] = array ('name' => $mySetting->get_attribute ('name'),
					'state' => $mySetting->get_attribute ('state'));
		}
	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
		$tmpArr = $xmlDoc->getElementsByTagName('type');
		$type = $tmpArr->item(0);
		$type = $type->getAttribute('name');
		$setting = $tmpArr->item(0);
		$setting = $setting->getAttribute('setting');
		$settingArr = $xmlDoc->getElementsByTagName('setting');
		for ($i = 0; $i < $settingArr->length; $i++) {
			$mySetting = $settingArr->item($i);
			$settings[] = array ('name' => $mySetting->getAttribute ('name'),
					'state' => $mySetting->getAttribute ('state'));
		}
	}

	foreach($settings as $mySetting) {
		$name = (string) $mySetting['name'];
		if($type == 'Users') {
			$sett = new Usrsettings($name, $user->db_name, $db);
		} elseif($type == 'Groups') {
			$sett = new groupSettings($name, $user->db_name);
		} else {
			$sett = new Gblstt($user->db_name, $db);
		}

 		if($mySetting['state'] == 'enabled') {
			$sett->set($setting, 1, $user->db_name);
		} elseif($mySetting['state'] == 'disabled') {
			$sett->set($setting, 0, $user->db_name);
		} else {
			$sett->removeKey($setting);
		}
	}
	$user->setSecurity(true);
	echo 'Settings Successfully Updated';
}

function xmlGetUsrCabSettings($user, $username) {
	$setting = 'deleteCabinets';
	$settingTxt = 'Delete Cabinets';
	$usrStt = new Usrsettings($username, $user->db_name);
	$val = $usrStt->get($setting);
	if($val == '1') {
		$retVal = 'enabled';
	} elseif($val == '0') {
		$retVal = 'disabled';
	} else {
		$retVal = 'inherited';
	}
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('settings');
		$xmlDoc->append_child($root);
		$mySetting = $xmlDoc->create_element('setting');
		$mySetting->set_attribute('text', $settingTxt);
		$mySetting->set_attribute('name', $setting);
		$mySetting->append_child($xmlDoc->create_text_node($retVal));
		$root->append_child($mySetting);
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement('settings');
		$xmlDoc->appendChild($root);
		$mySetting = $xmlDoc->createElement('setting');
		$mySetting->setAttribute('text', $settingTxt);
		$mySetting->setAttribute('name', $setting);
		$mySetting->appendChild($xmlDoc->createTextNode($retVal));
		$root->appendChild($mySetting);
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlSubmitUsrSett($user, $username) {
	$usrSett = new Usrsettings($username, $user->db_name);
	$settings = array ();
	$xmlStr = file_get_contents ('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_mem($xmlStr);
		$sett = $xmlDoc->get_elements_by_tagname('setting');
		foreach($sett as $mySett) {
			$settings[$mySett->get_attribute ('name')] = 
				$mySett->get_content ();
		}
	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
		$sett = $xmlDoc->getElementsByTagName('setting');
		for($i = 0; $i < $sett->length; $i++) {
			$mySett = $sett->item ($i);
			$settings[$mySett->getAttribute ('name')] = 
				$mySett->nodeValue;
		}
	}
	foreach ($settings as $name => $state) {
		if($state == 'enabled') {
			$usrSett->set($name, '1');
		} elseif($state == 'disabled') {
			$usrSett->set($name, '0');
		} else {
			$usrSett->removeKey($name);
		}
	}
	echo "Settings Successfully Updated";
}

function xmlSubmitSysSett($user, $db_doc) {
	$gblSett = new GblStt($user->db_name, $db_doc);
	$settings = array ();
	$xmlStr = file_get_contents ('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_mem($xmlStr);
		$sett = $xmlDoc->get_elements_by_tagname('setting');
		foreach($sett as $mySett) {
			$settings[$mySett->get_attribute ('name')] = 
				$mySett->get_content ();
		}
	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
		$sett = $xmlDoc->getElementsByTagName('setting');
		for ($i = 0; $i < $sett->length; $i++) {
			$mySett = $sett->item($i);
			$settings[$mySett->getAttribute ('name')] = 
				$mySett->nodeValue;
		}
	}
	foreach ($settings as $name => $state) {
		if($state == 'enabled') {
			$gblSett->set($name, '1');
		} elseif($state == 'disabled') {
			$gblSett->set($name, '0');
		} else {
			$gblSett->removeKey($name);
		}
	}
	echo "Settings Successfully Updated";
}

function xmlGetODBCConnList($user,$db) {
	$arr = getTableInfo($db, 'odbc_connect', array('id', 'connect_name'), array('department' => $user->db_name), 'getAssoc', 
		array ('connect_name' => 'ASC'));
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element('root');
		$xmlDoc->append_child($root);
		$el = '';
		foreach($arr as $myID => $myConn) {
			$el = $xmlDoc->create_element('connect');
			$el->append_child($xmlDoc->create_text_node($myConn));
			$el->set_attribute('id', $myID);
			$root->append_child($el);
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement('root');
		$xmlDoc->appendChild($root);
		$el = '';
		foreach($arr as $myID => $myConn) {
			$el = $xmlDoc->createElement('connect');
			$el->appendChild($xmlDoc->createTextNode($myConn));
			$el->setAttribute('id', $myID);
			$root->appendChild($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlTestODBCConn() {
	$xmlStr = file_get_contents('php://input');
	$db_dsn = array (
		'protocol'	=> 'tcp',
		'dbparam'	=> 'DSN'
	);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_mem($xmlStr);
		$connArr = $xmlDoc->get_elements_by_tagname('connect');
		foreach($connArr as $myConn) {
			switch($myConn->get_attribute('key')) {
				case 'host':
					$db_dsn['hostspec'] = $myConn->get_attribute('value');
					break;
				case 'dsn':
					$db_dsn['database'] = $myConn->get_attribute('value');
					break;
				case 'username':
					$db_dsn['username'] = $myConn->get_attribute('value');
					break;
				case 'password':
					$db_dsn['password'] = $myConn->get_attribute('value');
					break;
				case 'type':
					$db_dsn['phptype'] = $myConn->get_attribute('value');
					break;
			}
		}
	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
		$connArr = $xmlDoc->getElementsByTagName('connect');
		for ($i = 0; $i < $connArr->length; $i++) {
			$myConn = $connArr->item($i);
			$value = $myConn->getAttribute ('value');
			switch($myConn->getAttribute('key')) {
				case 'host':
					$db_dsn['hostspec'] = $value; 
					break;
				case 'dsn':
					$db_dsn['database'] = $value; 
					break;
				case 'username':
					$db_dsn['username'] = $value; 
					break;
				case 'password':
					$db_dsn['password'] = $value; 
					break;
				case 'type':
					$db_dsn['phptype'] = $value; 
					break;
			}
		}
	}
	$db = MDB2::connect($db_dsn, array('portability' => MDB2_PORTABILITY_ALL));
	if(PEAR::isError($db)) {
		echo 'Database Connection Failed!';
	} else {
		echo 'Database Connection Succeeded!';
		$db->disconnect();
	}
}

function xmlSubmitConn($user,$db_doc) {
	$queryArr = array ();
	$xmlStr = file_get_contents('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$isPhp4 = true;
		$xmlDoc = domxml_open_mem($xmlStr);
		$tmp = $xmlDoc->get_elements_by_tagname('update_id');
		$updateID = $tmp[0]->get_content();
		
		$connArr = $xmlDoc->get_elements_by_tagname('connect');
		foreach($connArr as $myConn) {
			$queryArr[$myConn->get_attribute('key')] = $myConn->get_attribute('value');
		}
	} else {
		$isPhp4 = false;
		$xmlDoc = new DOMDocument ();
		$xmlDoc->loadXML ($xmlStr);
		$tmp = $xmlDoc->getElementsByTagName('update_id');
		$updateID = $tmp->item(0);
		$updateID = $updateID->nodeValue;
		
		$connArr = $xmlDoc->getElementsByTagName('connect');
		for ($i = 0; $i < $connArr->length; $i++) {
			$myConn = $connArr->item ($i);
			$queryArr[$myConn->getAttribute('key')] = $myConn->getAttribute('value');
		}
	}
	if($updateID) {
		$oldName = getTableInfo($db_doc, 'odbc_connect', array('connect_name'), array('id' => (int)$updateID), 'queryOne');
	} else {
		$oldName = '';
	}

	lockTables($db_doc, array('odbc_connect'));
	$good = 0;	
	$connectNames = getTableInfo($db_doc, 'odbc_connect', array('connect_name'), array('department' => $user->db_name), 'queryCol');
	if($oldName != $queryArr['connect_name'] and in_array($queryArr['connect_name'], $connectNames)) {
		$good = 0;
		$msg = 'Duplicate Connection Name!';
	} elseif($updateID) {
		$whereArr = array ('id' => (int)$updateID);
		$connRow = getTableInfo($db_doc, 'odbc_connect', array(), $whereArr, 'queryRow');
		$updateArr = array ();
		foreach($queryArr as $key => $value) {
			if($connRow[$key] != $value) {
				$updateArr[$key] = $value; 
			}
		}
		if(!$updateArr) {
 			$good = 0;
 			$msg = 'Nothing to Update!';
		} else {
			updateTableInfo($db_doc, 'odbc_connect', $updateArr, $whereArr);
 			$good = 1;
 			$msg = 'Connection Successfully Updated';
		}
	} else {
		$queryArr['department'] = $user->db_name;
		$res = $db_doc->extended->autoExecute('odbc_connect', $queryArr);
		dbErr($res);
 		$good = 1;
 		$msg = 'Connection Successfully Added';
	}
	unlockTables($db_doc);
 	if ($isPhp4) {
 		$retXML = domxml_new_doc('1.0');
 		$retEl = $retXML->create_element('return');
 		$retXML->append_child($retEl);
 		$retEl->set_attribute ('val', $good);
 		$retEl->set_attribute ('text', $msg);
 		$xmlStr = $retXML->dump_mem(false);
 	} else {
 		$retXML = new DOMDocument (); 
 		$retEl = $retXML->createElement('return');
 		$retXML->appendChild($retEl);
 		$retEl->setAttribute ('val', $good);
 		$retEl->setAttribute ('text', $msg);
 		$xmlStr = $retXML->saveXML ();
 	}
	header('Content-type: text/xml');
	echo $xmlStr;
}

function xmlGetODBCConnInfo($id, $db_doc) {
	global $DEFS;
 	$connRow = getTableInfo($db_doc, 'odbc_connect', array(), array('id' =>
 				(int) $id), 'queryRow');
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc('1.0');
 		$root = $xmlDoc->create_element('root');
 		$xmlDoc->append_child($root);
  
 		$el = $xmlDoc->create_element('connect');
 		$el->set_attribute('key', 'connect_name');
 		$el->set_attribute('value', $connRow['connect_name']);
 		$root->append_child($el);
  
 		$el = $xmlDoc->create_element('connect');
 		$el->set_attribute('key', 'host');
 		$el->set_attribute('value', $connRow['host']);
 		$root->append_child($el);
  
 		$el = $xmlDoc->create_element('connect');
 		$el->set_attribute('key', 'dsn');
 		$el->set_attribute('value', $connRow['dsn']);
 		$root->append_child($el);
  
 		$el = $xmlDoc->create_element('connect');
 		$el->set_attribute('key', 'username');
 		$el->set_attribute('value', $connRow['username']);
 		$root->append_child($el);
	  	if($DEFS['USE_SECURE_PASSWORDS'] == '1') 
	  	{		
	  		$el = $xmlDoc->create_element('connect');
	 		$el->set_attribute('key', 'password');
	 		$el->set_attribute('value', base64_decode($connRow['password']));
	 		//error_log("Password: ".base64_decode($connRow['password'])."\n");
	 		$root->append_child($el);		 		
	  	}else{			
	 		$el = $xmlDoc->create_element('connect');
	 		$el->set_attribute('key', 'password');
	 		$el->set_attribute('value', $connRow['password']);
	 		$root->append_child($el); 
	  	}	
 		$el = $xmlDoc->create_element('connect');
 		$el->set_attribute('key', 'type');
 		$el->set_attribute('value', $connRow['type']);
 		$root->append_child($el);
 		$xmlStr = $xmlDoc->dump_mem (false);
 	} else {
 		$xmlDoc = new DOMDocument ();
 		$root = $xmlDoc->createElement('root');
 		$xmlDoc->appendChild($root);
 
 		$el = $xmlDoc->createElement('connect');
 		$el->setAttribute('key', 'connect_name');
 		$el->setAttribute('value', $connRow['connect_name']);
 		$root->appendChild($el);
 
 		$el = $xmlDoc->createElement('connect');
 		$el->setAttribute('key', 'host');
 		$el->setAttribute('value', $connRow['host']);
 		$root->appendChild($el);
 
 		$el = $xmlDoc->createElement('connect');
 		$el->setAttribute('key', 'dsn');
 		$el->setAttribute('value', $connRow['dsn']);
 		$root->appendChild($el);
 
 		$el = $xmlDoc->createElement('connect');
 		$el->setAttribute('key', 'username');
 		$el->setAttribute('value', $connRow['username']);
 		$root->appendChild($el);
	 	if($DEFS['USE_SECURE_PASSWORDS'] == '1') 
	 	{  		
	 		$el = $xmlDoc->createElement('connect');
	 		$el->setAttribute('key', 'password');
	 		$el->setAttribute('value', base64_decode($connRow['password']));
	 		//error_log("Password: ".base64_decode($connRow['password'])."\n");
	 		$root->appendChild($el);	 				
	 	}else{		
	 		$el = $xmlDoc->createElement('connect');
	 		$el->setAttribute('key', 'password');
	 		$el->setAttribute('value', $connRow['password']);
	 		$root->appendChild($el);	
	  	}	
 		$el = $xmlDoc->createElement('connect');
 		$el->setAttribute('key', 'type');
 		$el->setAttribute('value', $connRow['type']);
 		$root->appendChild($el);
 		$xmlStr = $xmlDoc->saveXML ();
 	}
  	header('Content-type: text/xml');
 	echo $xmlStr;
}

function xmlGetAllNonCabinetSettings($db_name) {
	$settings = array (
		'deletePersonalInbox'	=> 'Delete From Personal Inbox',
		'inboxAccess'           => 'Allow Access to All Users in Personal Inbox',
		'pcDocCart'           => 'Paper Clipped Document Cart',
		'printBarcodePrompt'    => 'Print Barcode Prompt',
		'disallowFileInMain'    => 'No File In Main Tab Allowed'
	);
	//'deleteCabinets'		=> 'Delete Cabinets',
	//'imgCompression'		=> 'Use LZW Compression',
	//'inboxDelOnePage'	=> 'Delete Single Page In Inbox ',
	
	if(!check_enable('lite', $db_name)) {
		$settings['inboxGroupAccess']       = 'Allow Access to Personal Inbox For All Users in Group';
		$settings['allowReassignTodo']      = 'Allow Reassigning of To Do Items';
		$settings['wfGroupAccess']          = 'Allow Reassigning To Do Items For All Users in Group';
		$settings['allowViewTodo']      	= 'Allow Viewing of To Do Items';
		$settings['wfGroupViewing']          = 'Allow Viewing To Do Items For All Users in Group';
		$settings['allowSelfAudit']         = 'Allow Self Audit';
		//$settings['displayQuota']           = 'Display Quota';
		//$settings['displayDepartmentName']  = 'Display Department Name';
		$settings['inboxWorkflow']          = 'Enable/Disable Inbox Workflow';
		//$settings['deleteRecyclebin']       = 'Enable/Disable Recycle Bin';
		$settings['publishingExpire']       = 'Force Published Searches to Expire';
		$settings['publishingDefaultExp']   = 'Force Publishing Default Expire Time';
		$settings['versioningReportAccess'] = 'Allow Access to Versioning Report';
		$settings['deletePublicInbox']      = 'Delete From Public Inbox';
		$settings['wfSecurity']             = 'Allow Access to All Cabinets For Printing Workflow Barcodes';
		$settings['WF_Once']             = 'Do not show WF button after a workflow has been completed';
		if(check_enable('global_search',$db_name)) {
			$settings['globalSearch']           = 'Global Search';
		}
	}
	asort($settings);
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc('1.0');
 		$root = $xmlDoc->create_element('root');
 		$xmlDoc->append_child($root);
 		foreach($settings as $real => $fake) {
 			$el = $xmlDoc->create_element('setting');
 			$el->set_attribute('settingName', $real);
 			$el->set_attribute('text', $fake);
 			$root->append_child($el);	
 		}
 		$xmlStr = $xmlDoc->dump_mem (false);
 	} else {
 		$xmlDoc = new DOMDocument ();
 		$root = $xmlDoc->createElement('root');
 		$xmlDoc->appendChild($root);
 		foreach($settings as $real => $fake) {
 			$el = $xmlDoc->createElement('setting');
 			$el->setAttribute('settingName', $real);
 			$el->setAttribute('text', $fake);
 			$root->appendChild($el);	
 		}
 		$xmlStr = $xmlDoc->saveXML ();
 	}
 	header('Content-type: text/xml');
 	echo $xmlStr; 
}

function xmlGetNonCabinetSettings($type, $mySetting, $db_name, $user, $db_doc, $db) {
	$settings = array ();
	if($type == 'System') {
		$setting = new GblStt($db_name, $db_doc);
		$enabled = getCurrSettingVal($setting, $mySetting);
		if($enabled == 2) {
			$enabled = 0;
		}
 		$settings[] = array ('enabled' => $enabled, 'name' => 'System');
	} elseif($type == 'Users') {
		$allList = getTableInfo($db, 'access', array('username'), array(),
			'queryCol', array('username' => 'ASC'));
		foreach($allList as $myUser) {
			if($user->greaterThanUser($myUser)) {
				$setting = new Usrsettings($myUser, $db_name, $db_doc);
				$enabled = getCurrSettingVal($setting, $mySetting);
				$settings[] = array ('enabled' => $enabled, 'name' => $myUser);
			}
		}
	} elseif($type == 'Groups') {
		$allList = getTableInfo($db, 'groups', array('real_groupname',
					'arb_groupname'), array(), 'getAssoc');
		foreach($allList as $realName => $dispName) {
			$setting = new groupSettings($realName, $db_name);
			$enabled = getCurrSettingVal($setting, $mySetting);
			$settings[] = array ('enabled' => $enabled, 'real_name' =>
					$realName, 'name' => $dispName);
		}
	}

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('root');
		$xmlDoc->append_child($root);
		foreach ($settings as $mySetting) {
			$el = $xmlDoc->create_element ('setting');
			foreach ($mySetting as $attrib => $val) {
				$el->set_attribute ($attrib, $val);
			}
			$root->append_child ($el);
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('root');
		$xmlDoc->appendChild($root);
		foreach ($settings as $mySetting) {
			$el = $xmlDoc->createElement ('setting');
			foreach ($mySetting as $attrib => $val) {
				$el->setAttribute ($attrib, $val);
			}
			$root->appendChild ($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr;
}

function getCurrSettingVal($settingObj, $setting) {
	$val = $settingObj->get($setting);
	if($val === '0') {
		return 0;
	} elseif($val === '1') {
		return 1;
	} else {
		return 2;
	}
}

function xmlGetLDAPUserList($user, $connID, $db) {
	if($user->isSuperUser ()) {
		$userList = getLDAPUserListByID ($connID);
		$allUsers = getTableInfo ($db, 'users', array ('username'), array (),
			'queryCol');
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element ('user_list');
		$xmlDoc->append_child ($root);
		foreach ($userList as $userName) {
			$el = $xmlDoc->create_element ('user');
			$el->append_child ($xmlDoc->create_text_node ($userName));
			if (in_array ($userName, $allUsers)) {
				$existing = 1;
			} else {
				$existing = 0;
			}
			$el->set_attribute ('existing', $existing);
			$root->append_child ($el);
		}
		header ('Content-type: text/xml');
		echo $xmlDoc->dump_mem (false);
	}
}

function xmlImportLDAPUsers($user, $db, $db_dept) {
	if ($user->isSuperUser ()) {
		$cabinetList = getTableInfo ($db_dept, 'departments',
			array ('real_name'), array ('deleted' => 0), 'queryCol');
		$rights = array ();
		foreach ($cabinetList as $myCab) {
			$rights[$myCab] = 'none';
		}
		$encRights = base64_encode (serialize ($rights));
		$allUsers = getTableInfo ($db, 'users', array ('username'), array (),
			'queryCol');
		$xmlDoc = domxml_open_mem (file_get_contents ('php://input'));
		$root = $xmlDoc->document_element ();
		$ldapID = $root->get_attribute ('id');
		foreach ($xmlDoc->get_elements_by_tagname ('user') as $myUser) {
			$uName = $myUser->get_content ();
			if (!in_array ($uName, $allUsers)) {
				$DO_user = DataObject::factory ('users', $db);
				$DO_user->username = $uName;
				$DO_user->password = '';
				$DO_user->ldap_id = $ldapID;
				$DO_user->regdate = date('Y-m-d G:i:s');
				$DO_user->last_login = 'Never';
				$DO_user->insertUser ($user->db_name);
				$db_dept->extended->autoExecute ('access', array ('username' => $uName,
					'access' => $encRights));
			}
		}
		header ('Content-type: text/xml');
		echo '<root>1</root>';
	}
}

function xmlGetDefaultPages ($user, $db_doc) {
	if ($user->isDepAdmin ()) {
		$gblStt = new GblStt ($user->db_name, $db_doc);
		$defPage = $gblStt->get ('defaultPage');
		$pgInfo = getDefaultPageInfo ();
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$xmlDoc = domxml_new_doc ('1.0');
			$root = $xmlDoc->create_element ('root');
			$xmlDoc->append_child ($root);
			foreach ($pgInfo as $pgName => $info) {
				$inf = $xmlDoc->create_element ('page');
				$inf->set_attribute ('name', $pgName);
				$inf->set_attribute ('disp', $info['dispStr']);
				if ($defPage == $pgName) {
					$inf->set_attribute ('current', '1');
				} else {
					$inf->set_attribute ('current', '0');
				}
				$root->append_child ($inf);
			}
			$xmlStr = $xmlDoc->dump_mem (true);
		} else {
			$xmlDoc = new DOMDocument ();
			$root = $xmlDoc->createElement ('root');
			$xmlDoc->appendChild ($root);
			foreach ($pgInfo as $pgName => $info) {
				$inf = $xmlDoc->createElement ('page');
				$inf->setAttribute ('name', $pgName);
				$inf->setAttribute ('disp', $info['dispStr']);
				if ($defPage == $pgName) {
					$inf->setAttribute ('current', '1');
				} else {
					$inf->setAttribute ('current', '0');
				}
				$root->appendChild ($inf);
			}
			$xmlStr = $xmlDoc->saveXML ();
		}
		header ('Content-type: text/xml');
		echo $xmlStr;
	}
}

function xmlSetDefaultPage ($user, $db_doc) {
	if ($user->isDepAdmin ()) {
		$xmlStr = file_get_contents ('php://input');
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$xmlDoc = domxml_open_mem ($xmlStr);
			$defEls = $xmlDoc->get_elements_by_tagname ('default');
			$defName = $defEls[0]->get_attribute ('name');
		} else {
			$xmlDoc = new DOMDocument ();
			$xmlDoc->loadXML ($xmlStr);
			$defEls = $xmlDoc->getElementsByTagName ('default');
			$defName = $defEls->item(0);
			$defName = $defName->getAttribute ('name');
		}
		$gblStt = new GblStt ($user->db_name, $db_doc);
		$gblStt->set ('defaultPage', $defName);
		header ('Content-type: text/xml');
		echo '<root>Default Settings Page Successfully Set</root>';
	}
}

function setFileFormat($user,$db_doc,$type) {
	$gblStt = new GblStt ($user->db_name, $db_doc);
	if($type) {
		$gblStt->set ('fileFormat', $type);
	} else {
		$gblStt->removeKey('fileFormat');
	}

	$jsonStr = array('mess' => "Settings Updated");
	echo json_encode($jsonStr);
}

function getFileFormat($user,$db_doc) {
	$lite = (check_enable("lite",$user->db_name)) ? 1 : 0; 

	$type = "";
	$gblStt = new GblStt ($user->db_name, $db_doc);
	$type = $gblStt->get ('fileFormat');
	if(!$type && $lite == "1") {
		$type = "pdf";
	}

	$jsonStr = array('lite' => $lite);
	if($type) {
		$jsonStr['type'] = $type;
	}
	echo json_encode($jsonStr);
}

function getEncryptedPassword($plainText)
{
	echo base64_encode($plainText);
}

function xmlGetPasswordSettingsList($db, $dept)
{
	$keys = array("passwordRestriction", "requireChange", "minLength", "alpha_character", "numeric_character", "special_character", "forcePassword");
	$settings = getTableInfo($db, 'settings', array('*'), array('department'=>$dept), 'queryAll');
	$settingsArr = array();
	foreach($settings as $setting)
	{
		$settingsArr[$setting['k']] = $setting['value'];
	}
	$xmlDoc = new DOMDocument ();
	$root = $xmlDoc->createElement('root');
	$xmlDoc->appendChild($root);
	$el = '';
	
	if(in_array("passwordRestriction", array_keys($settingsArr)))
	{
		for($i = 0; $i < count($keys); $i++)
		{
			$el = $xmlDoc->createElement('Setting');
 			$el->setAttribute('id', $keys[$i]);
 			$el->setAttribute('value', $settingsArr[$keys[$i]]);
 			$root->appendChild($el);
		}	
	}
	$xmlStr = $xmlDoc->saveXML ();
	//header('Content-type: text/xml');
	//echo $xmlStr;
	return $xmlStr;
	
}

function xmlUpdatePasswordSettings($db, $dept)
{
	//update the settings and get back the message
	$message = updatePasswordSettings(file_get_contents ('php://input'), $dept);
	//create the response text
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('root');
		$xmlDoc->append_child($root);
		
		$el = $xmlDoc->create_element ('return');
		$el->set_attribute ('text', $message);

		$root->append_child ($el);
		
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('root');
		$xmlDoc->appendChild($root);

		$el = $xmlDoc->createElement ('return');
		$el->setAttribute ('text', $message);
		
		$root->appendChild ($el);
		
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr;	
}

function xmlDisplayPasswordSettings($db, $dept)
{
	//call the display password settings function from the password
	//settings function file.
	$restrictions = displayPasswordSettingsArray($dept);
	$xmlDoc = new DOMDocument ();
	$root = $xmlDoc->createElement('root');
	$xmlDoc->appendChild($root);
	$el = '';

	for($i = 0; $i < count($restrictions); $i++)
	{
		$el = $xmlDoc->createElement('Restrictions');
 		$el->setAttribute('value', $restrictions[$i]);
 		$root->appendChild($el);
	}	

	$xmlStr = $xmlDoc->saveXML ();
	header('Content-type: text/xml');
	//echo $xmlStr;
	echo $xmlStr;
}
?>
