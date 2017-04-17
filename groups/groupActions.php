<?php
// $Id: groupActions.php 14194 2011-01-04 15:21:54Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../groups/groups.php';

function getListForGroups($db_object,$user,$key,$value) {
	$list = array();
	if( $key == 'real_groupname' ) {
		$list = getGroupAccess($db_object,'departments.real_name',$key,$value);
		if( sizeof($list) != sizeof($user->cabArr) ) {
			foreach($user->cabArr AS $key => $value) {
				if( !array_key_exists($key,$list) ) {
					$list[$key] = 'none';
				}
			}
		} 
	} elseif( $key == 'username' ) {
		$grpObj = new groups($db_object);
		$groupList = $grpObj->getMembers($value);
		$userList = getTableInfo($db_object,'access',array('username'),array(),'queryCol');
		foreach( $userList AS $names ) {
			if( in_array($names, $groupList) ) {
				$list[$names] = 'grant';
			} else {
				$list[$names] = 'deny';
			}
		}
	} elseif( $key == 'group' ) {
		$userList = getGroupsForUser($db_object,$value);
		$groupList = getRealGroupNames($db_object);
		foreach( array_keys($groupList) AS $real) {
			if( in_array($real, $userList) ) {
				$list[$real] = 'grant';
			} else {
				$list[$real] = 'deny';
			}
		}
	} else { 
		$list = getGroupAccess($db_object,'groups.real_groupname',$key,$value);
		$groupList = getRealGroupNames($db_object);
		if( sizeof($list) != sizeof($groupList) ) {
			foreach($groupList AS $key => $value) {
				if( !array_key_exists($key,$list) ) {
					$list[$key] = 'none';
				}
			}
		} 
	}
	uksort($list,'strnatcasecmp');
	return $list;
}

function getGroupAccessList($list,$arbArr,$header) {
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$doc = domxml_new_doc ('1.0');
		$root = $doc->create_element ('ROOT');
		$root = $doc->append_child ($root);

		foreach ($header as $key => $value) {
			$header = $doc->create_element ('HEADER');
			$root->append_child ($header);
			$text = $doc->create_text_node ($value);
			$header->append_child ($text);
			if (!is_numeric ($key)) {
				$header->set_attribute ('type', $key);
			}
		}

		foreach ($list as $name => $rights) {
			if (empty ($arbArr) or !empty ($arbArr[$name])) {
				//create and append element
				$entry = $doc->create_element ('ENTRY');
				$root->append_child ($entry);
				//create the text node and append
				$text = $doc->create_text_node ($rights);
				$entry->append_child ($text);
				$entry->set_attribute ('name', $name);
				if ($arbArr) {
					$entry->set_attribute ('arb', $arbArr[$name]);
				} else {
					$entry->set_attribute ('arb', $name);
				}
			}
		}
		$xmlStr = $doc->dump_mem (true);
	} else {
		$doc = new DOMDocument ();
		$root = $doc->createElement ('ROOT');
		$doc->appendChild ($root);

		foreach ($header as $key => $value) {
			$header = $doc->createElement ('HEADER');
			$root->appendChild ($header);
			$header->appendChild ($doc->createTextNode ($value));
			if (!is_numeric ($key)) {
				$header->setAttribute ('type', $key);
			}
		}

		foreach ($list as $name => $rights) {
			if (empty ($arbArr) or !empty ($arbArr[$name])) {
				//create and append element
				$entry = $doc->createElement ('ENTRY');
				$root->appendChild ($entry);
				//create the text node and append
				$entry->appendChild ($doc->createTextNode ($rights));
				$entry->setAttribute ('name', $name);
				if ($arbArr) {
					$entry->setAttribute ('arb', $arbArr[$name]);
				} else {
					$entry->setAttribute ('arb', $name);
				}
			}
		}
		$xmlStr = $doc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr;
}

function createGroupPermissions ($db_object, $type, $user) {
	$listArr = array();
	$accessArr = array ();
	$xmlStr = file_get_contents('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_open_mem ($xmlStr);
		$root = $domDoc->get_elements_by_tagname ('ROOT');
		$name = $root[0]->get_attribute ('name');
		foreach ($domDoc->get_elements_by_tagname('PERM') as $listname) {
			$accessArr[$listname->get_attribute('name')] =
				$listname->get_content ();
		}
	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
		$name = $domDoc->documentElement->getAttribute ('name');
		$domArr = $domDoc->getElementsByTagName ('PERM');
		for($i = 0; $i < $domArr->length; $i++) {
			$listname = $domArr->item ($i);
			$accessArr[$listname->getAttribute('name')] =
				$listname->nodeValue;
		}
	}
	if( $type == 'cabID') {
		$id = getTableInfo ($db_object, 'departments', array('departmentid'),
				array('real_name' => $name), 'queryOne');
		$idArr = getGroupIDList ($db_object);
	} else {
		$id = getTableInfo($db_object, 'groups', array('id'),
				array('real_groupname' => $name), 'queryOne');
		$idArr = getCabinetIDList($db_object); 
	}
	$query = "DELETE FROM group_access WHERE $type=$id";
	$check = $db_object->query($query);
	dbErr($check);
	
	$auditArr = array();
	$groupList = getTableInfo($db_object, 'groups', array('real_groupname',
				'arb_groupname'), array(), 'getAssoc');
	
	foreach ($accessArr AS $realName => $access) {
		if ($type == 'cabID') {
			$listArr[] = array ( 
						'group_id'	=> (int) $idArr[$realName],
						'cabID'		=> (int) $id,
						'access'	=> $access
							 );
			$auditArr[] = "group: '{$groupList[$realName]}' and cabinet: " .
					"'{$user->cabArr[$name]}' and access: $access";
			
		} else {
			$listArr[] = array ( 
						'group_id'	=> (int) $id,
						'cabID'		=> (int) $idArr[$realName],
						'access'	=> $access
							 );
			$auditArr[] = "group: '{$groupList[$name]}' and cabinet: " .
					"'{$user->cabArr[$realName]}' and access: $access";

		}
	}	
	if ($auditArr) {
		$user->audit('group permissions changed', implode(', ', $auditArr));
	}
	
	foreach ($listArr AS $groupInfo) {
		$db_object->extended->autoExecute ('group_access', $groupInfo);
	}
}

function editGroupAccess ($db_object, $type, $user) {
	$listArr = array ();
	$info = array ();
	$task = '';
	$newName = '';
	$permList = array ();
	$xmlStr = file_get_contents ('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_open_mem ($xmlStr);
		$root = $domDoc->get_elements_by_tagname ('ROOT');
		$name = $root[0]->get_attribute ('name');
		if (count ($domDoc->get_elements_by_tagname ('DELETE'))) {
			$task = 'delete';
		} else {
			$edit = $domDoc->get_elements_by_tagname ('EDIT');
			if (count ($edit)) {
				$task = 'edit';
				$newName = $edit[0]->get_content ();
			}
			$permInfo = $domDoc->get_elements_by_tagname ('PERM');
			foreach ($permInfo as $perm) {
				$permList[$perm->get_attribute ('name')] = $perm->get_content();
			}
		}
	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
		$name = $domDoc->documentElement;
		$name = $name->getAttribute ('name');
		$del = $domDoc->getElementsByTagName('DELETE');
		if ($del->length) {
			$task = 'delete';
		} else {
			$edit = $domDoc->getElementsByTagName('EDIT');
			if ($edit->length) {
				$task = 'edit';
				$newName = $edit->item(0);
				if ($newName) {
					$newName = $newName->firstChild;
					$newName = $newName->nodeValue;
				} else {
					$newName = '';
				}
			}
			$domArr = $domDoc->getElementsByTagName ('PERM');
			for ($i = 0; $i < $domArr->length; $i++) {
				$perm = $domArr->item ($i);
				$permTerm = $perm->firstChild;
				$permList[$perm->getAttribute ('name')] = 
					$permTerm->nodeValue;
			}
		}
	}
	$query = 'SELECT real_groupname, arb_groupname FROM groups';
	$groupList = $db_object->extended->getAssoc ($query);
	dbErr ($groupList);
	if ($type == 'uid') {
		$id = getTableInfo ($db_object, 'access', array('uid'), array('username'
					=> $name), 'queryOne');
		$idArr = getGroupIDList ($db_object);
	} else {
		$id = getTableInfo ($db_object, 'groups', array('id'),
				array('real_groupname' => $name), 'queryOne');
		$idArr = getTableInfo ($db_object, 'access', array('username', 'uid'),
				array(), 'getAssoc');
		$arbGroupName = $groupList[$name];
	}
	$query = "DELETE FROM users_in_group WHERE $type=$id";
	$check = $db_object->query ($query);
	dbErr ($check);

	if ($task == 'delete') {
		$id = getTableInfo ($db_object, 'groups', array('id'),
				array('real_groupname' => $name), 'queryOne');
		$whereArr = array ('group_id' => (int)$id);
		deleteTableInfo ($db_object, 'users_in_group', $whereArr);

		$whereArr = array ('real_groupname' => $name);
		deleteTableInfo ($db_object, 'groups', $whereArr);

		$whereArr = array ('authorized_group' => $name);
		deleteTableInfo ($db_object, 'group_tab', $whereArr);

		$user->audit ('Group Deleted', "Group: $arbGroupName");
	} else {
		if ($task == 'edit') {
			if ($newName != $groupList[$name]) {
				$updateArr = array ('arb_groupname' => $newName);
				$whereArr = array ('real_groupname' => $name);
				updateTableInfo ($db_object, 'groups', $updateArr, $whereArr);
				$user->audit ('Group name changed', "old: $arbGroupName new: " .
						"$newName");
			}
		}
		foreach ($permList as $realName => $perm) {
			if ($perm == 'grant') {
				if ($type == 'uid') {
					//list of groups for the user
					$listArr[] = array('group_id'	=> (int) $idArr[$realName],
									   'uid'		=> (int) $id
										);
					$info[] = $groupList[$realName].':granted';
				} else {
					//list of users for the group
					$listArr[] = array('group_id'	=> (int) $id,
									   'uid'		=> (int) $idArr[$realName]
									   );
					$info[] = $realName.':granted';
				}
			} else {
				if ($type == 'uid') {
					$info[] = $groupList[$realName].':denied';
				} else {
					$info[] = $realName.':denied';
				}
			}
		}	
		
		foreach($listArr AS $groupInfo) {
			$db = $db_object->extended->autoExecute ('users_in_group', $groupInfo);
			dbErr($db);
		}
		$user->audit ('Group permissions changed', 'Group Permissions: {' .
				implode (',', $info) . '}');
	}
}

function xmlGetDeptUserList($db) {
	$accessInfo = getTableInfo ($db, 'access', array (), array (), 'query',
			array ('username' => 'ASC'));

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element ('userList');
		$xmlDoc->append_child ($root);
		while ($row = $accessInfo->fetchRow ()) {
			$el = $xmlDoc->create_element ('user');
			$el->set_attribute ('name', $row['username']);
			$root->append_child ($el);		
		}
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement ('userList');
		$xmlDoc->appendChild ($root);
		while ($row = $accessInfo->fethRow ()) {
			$el = $xmlDoc->createElement ('user');
			$el->setAttribute ('name', $row['username']);
			$root->appendChild ($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function xmlGetGroupList($db) {
	$groupArr = getRealGroupNames($db);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element ('groupList');
		$xmlDoc->append_child ($root);
		foreach ($groupArr as $real => $arb) {
			$el = $xmlDoc->create_element ('group');
			$el->set_attribute ('real_name', $real);
			$el->set_attribute ('arb_name', $arb);
			$root->append_child ($el);
		}
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement ('groupList');
		$xmlDoc->appendChild ($root);
		foreach ($groupArr as $real => $arb) {
			$el = $xmlDoc->createElement ('group');
			$el->setAttribute ('real_name', $real);
			$el->setAttribute ('arb_name', $arb);
			$root->appendChild ($el);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr;
}

if($logged_in==1 && strcmp($user->username,'')!=0 && $user->isAdmin()) {
	$selection = isset($_POST['selName']) ? $_POST['selName'] : '';
	if( isSet($_GET['groupPerm']) ) {
		$list = getListForGroups($db_object,$user,'real_groupname',$selection);
		$arbArr = $user->cabArr; 
		$header = array('0'=>'Cabinet','rw'=>'Read/Write','ro'=>'Read Only','none'=>'None');
		getGroupAccessList($list,$arbArr,$header);
	} elseif( isSet($_GET['cabPerm']) ) {
		$cabID = (int)getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $selection), 'queryOne');
		$list = getListForGroups($db_object,$user,'departmentid',$cabID);
		$arbArr = getRealGroupNames($db_object); 
		$header = array('0'=>'Group','rw'=>'Read/Write','ro'=>'Read Only','none'=>'None');
		getGroupAccessList($list,$arbArr,$header);
	} elseif( isSet($_GET['createGroup']) ) {
		createGroupPermissions($db_object,$_GET['type'], $user);
	} elseif( isSet($_GET['editGroups']) ) {
		$list = getListForGroups($db_object,$user,'group',$selection);	
		$arbArr = getRealGroupNames($db_object); 
		$header = array('0'=>'Group','grant'=>'Grant Access','deny'=>'Deny Access');
		getGroupAccessList($list,$arbArr,$header);
	} elseif( isSet($_GET['editUsers']) ) {
		$list = getListForGroups($db_object,$user,'username',$selection);	
		$header = array('0'=>'Username','grant'=>'Grant Access','deny'=>'Deny Access');
		getGroupAccessList($list,NULL,$header);
	} elseif( isSet($_GET['editGroupPriv']) ) {
		editGroupAccess($db_object,$_GET['type'],$user);
	} elseif(isset($_GET['getDeptUserList'])) {
		xmlGetDeptUserList($db_object);
	} elseif(isset($_GET['getGroupList'])) {
		xmlGetGroupList($db_object);
	}
    setSessionUser($user);
} else {
    logUserOut();	
}
?>
