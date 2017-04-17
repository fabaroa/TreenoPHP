<?php
include_once '../lib/notes.php';

function getBookmarks($enArr,$user,$db_doc,$db_dept) {
	$userStt = new Usrsettings($user->username, $user->db_name);
	$allBookmarks = $userStt->get('bookmarks');
	$allBookmarks = unserialize(base64_decode($allBookmarks));

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setBookmarks(XML)");
	foreach($allBookmarks as $bookNum => $bookmark) {
		$attArr = array('id' => $bookNum);
		$parentEl = $xmlObj->createKeyAndValue("BOOKMARK",$bookNum,$attArr);
		foreach($bookmark as $key => $value) {
			if(is_array($value)) {
				foreach($value as $k => $v) {
					$attArr = array('key' => $k);
					$xmlObj->createKeyAndValue("FIELD",$v,$attArr,$parentEl);
				}
			} else {
				$attArr = array('key' => $key);
				$xmlObj->createKeyAndValue("SEARCH",$value,$attArr,$parentEl);
			}
		}
	}
	$xmlObj->setHeader();
}

function getFields($enArr,$user,$db_doc,$db_dept) {
	$gblStt = new GblStt($user->db_name, $db_doc);
	$cab = $enArr['cabinet'];

	$wArr = array(	'real_name' => $cab, 
			'deleted' => 0 );
	$cabInfo = getTableInfo($db_dept,'departments',array(),$wArr,'queryRow');
	if($cabInfo) {
		$xmlObj = new xml();
		$xmlObj->createKeyAndValue("FUNCTION","setFields(XML)");

		$cabFields = getCabinetInfo($db_dept,$cab);

		$sArr = array('field_name', 'required', 'regex', 'display');
		$wArr = array('cabinet_id' => $cabInfo['departmentid']);
		$fieldInfo = getTableInfo($db_dept,'field_format',$sArr,$wArr,'getAssoc');
		foreach($cabFields as $field) {
			$parentEl = $xmlObj->createKeyAndValue("FIELD",$field);

			$required = 0;
			$regex = "";
			$display = "";
			if(isSet($fieldInfo[$field])) {
				$required = $fieldInfo[$field]['required'];
				$regex = $fieldInfo[$field]['regex'];
				$display = $fieldInfo[$field]['display'];
			}
			$xmlObj->createKeyAndValue("REQUIRED", $required,array(),$parentEl);
			$xmlObj->createKeyAndValue("REGEX", $regex,array(),$parentEl);
			$xmlObj->createKeyAndValue("DISPLAY", $display,array(),$parentEl);
		}
		$xmlObj->createKeyAndValue("SECURITY",$user->checkSecurity($cab));
		$xmlObj->createKeyAndValue("CABINET",$cab);

		if ($gblStt->get('indexing_' . $cab)) {
			$isAuto = '1';
		} else {
			$isAuto = '0';
		}

		$xmlObj->createKeyAndValue('AUTO_COMPLETE', $isAuto);

		$sArr = array('id','k','value');
		$wArr = array(	"department='$user->db_name'",
				"k " . LIKE . " 'dt,$user->db_name,{$cabInfo['departmentid']},%'");
		$dataTypeInfo = getTableInfo($db_doc,'settings',$sArr,$wArr,'queryAll');
		foreach($dataTypeInfo AS $id => $dInfo) {
			$key = str_replace( "dt,$user->db_name,".$cabInfo['departmentid'].",", "", $dInfo['k'] );
			if(in_array($key,$cabFields)) {
				$dtArr = explode(",,,",$dInfo['value']);
				foreach($dtArr AS $dtVal) {
					$attArr = array('key' => $key);
					$xmlObj->createKeyAndValue("DTYPE",$dtVal,$attArr);
				}
			} else {
				deleteTableInfo($db_doc,'settings',array('id' => (int)$id));
			}
		}	
		$xmlObj->setHeader();
	} else {
		removeBookmark($cab,$user);
	}
}

function removeBookmark($cab,$user) {
	$bookNum = $_GET['bm'];
	$UserStt = new Usrsettings($user->username, $user->db_name);
	$bookmarks = unserialize(base64_decode($UserStt->get('bookmarks')));

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","removeBookmark(XML)");
	$mess = "This Cabinet Has Been Deleted.  The Bookmark Will Now Be Removed.";
	$xmlObj->createKeyAndValue("MESSAGE",$mess);
	foreach($bookmarks AS $id => $info) {
		if(isSet($info['cabinet']) && $info['cabinet'] == $cab) {
			$xmlObj->createKeyAndValue("BOOKMARK",$id);
			unset($bookmarks[$id]);
		}
	}
	$enc = base64_encode(serialize($bookmarks));
	$UserStt->set('bookmarks', $enc);

	$xmlObj->setHeader();
}
?>
