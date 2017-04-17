<?php 
include_once 'publishUser.php';
include_once 'publishSearch.php';
include_once '../lib/email.php';
include_once '../lib/settings.php';

function xmlGetCabinetFields($enArr,$user,$db_doc,$db_dept) {
	$fields = getCabinetFields($enArr['cabinet'],$db_dept);
	usort($fields,"strnatcasecmp");

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","fillCabinetIndices(XML)");
	foreach($fields AS $index) {
		$xmlObj->createKeyAndValue("INDEX",str_replace("_"," ",$index),array('name'=>$index));
	}
	$xmlObj->setHeader();
}

function getCabinetFields($cabinet,$db_dept) {
	$fields = getCabinetInfo($db_dept,$cabinet);
	usort($fields,"strnatcasecmp");

	return $fields;
}

function xmlGetWorkflowsAndCabinets($enArr,$user,$db_doc,$db_dept) {
	if($enArr['type'] == "workflow") {
		$wfList = getWorkflows($db_dept);
	}
	$cabList = getCabinetList($user);
	$userList = getUserList($db_dept);

	$xmlObj = new xml("ENTRY");
	$xmlObj->setRootAttribute("type",$enArr['type']);
	$xmlObj->createKeyAndValue("FUNCTION","fillWorkflowsAndCabinets(XML)");
	if($enArr['type'] == "workflow") {
		foreach($wfList AS $id => $wf) {
			$xmlObj->createKeyAndValue("WORKFLOW",str_replace("_"," ",$wf),array('id' => $id));
		}
	}

	foreach($cabList AS $real => $arb) {
		$xmlObj->createKeyAndValue("CABINET",$arb,array('name' => $real));
	}
		
	foreach($userList AS $name) {
		$xmlObj->createKeyAndValue("USER",$name);
	}
	$xmlObj->setHeader();
}

function getWorkflows($db_dept) {
	$sArr = array('defs_name','MIN(id)');
	$oArr = array('defs_name' => 'ASC');
	$gArr = array('defs_name');
	$wfList = getTableInfo($db_dept,'wf_defs',$sArr,array(),'getAssoc',$oArr,0,0,$gArr);
	$db_dept->disconnect();

	$wfArr = array();
	foreach($wfList AS $name => $id) {
		$wfArr[$id] = $name;
	}

	return $wfArr;
}

function getCabinetList($user) {
	return $user->cabArr;
}

function getUserList($db_dept) {
	$sArr = array('username');
	$userList = getTableInfo($db_dept,'access',$sArr,array(),'queryCol');

	return $userList;
}

function xmlPublishSearch($enArr,$user,$db_doc,$db_dept) {
	if($enArr['requestType'] == "add") {
		unset($enArr['requestType']);
		$mess = addPublishSearch($enArr,$user);
	} else {
		unset($enArr['requestType']);
		updatePublishSearch($enArr,$db_doc);
		$mess = 'Publish Search Updated Successfully';
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$mess);
	$xmlObj->setHeader();
}

function addPublishSearch($enArr,$user) {
	$owner = (isSet($enArr['owner'])) ? $enArr['owner'] : $user->username;
	$pubObj = new publishSearch($owner);			
	if($pubObj->setPublishName($enArr['name'],$enArr['enabled'])) {
		$wf_def_id = (isSet($enArr['wf_def_id'])) ? $enArr['wf_def_id'] : 0;
		$field = (isSet($enArr['field'])) ? $enArr['field'] : "";
		$term = (isSet($enArr['term'])) ? $enArr['term'] : "" ;
 		$pubObj->addItemToList($user->db_name,$enArr['cab'],0,0,$field,$term,$wf_def_id,$enArr['type']);
	}
	return $pubObj->message;
}

function updatePublishSearch($enArr,$db_doc) {
	$db_doc = getDbObject('docutron');
	$uArr = array('enabled' => (int)$enArr['enabled'],
					'owner'	=> $enArr['owner']);
	$wArr = array('id' => (int)$enArr['pubID']);
	updateTableInfo($db_doc,'publish_search',$uArr,$wArr);

	unset($enArr['enabled']);
	unset($enArr['pubID']);
	unset($enArr['owner']);
	
	$sArr = array('ps_list_id');
	$ps_list_id = getTableInfo($db_doc,'publish_search',$sArr,$wArr,'queryOne');

	$enArr['wf_def_id'] = (int)$enArr['wf_def_id'];
	$wArr = array('ps_list_id' => (int)$ps_list_id);
	updateTableInfo($db_doc,'publish_search_list',$enArr,$wArr,0,0,1);
}

function xmlGetPublishSearch($enArr,$user,$db_doc,$db_dept) {
	$pubInfo = getPublishSearch($enArr,$db_doc);
	if($pubInfo['type'] == "folder_search") {
		$fields = getCabinetFields($pubInfo['cabinet'],$db_dept);			
	} else {
		if($pubInfo['type'] == "workflow") {
			$wfList = getWorkflows($db_dept);
		}
		$cabList = getCabinetList($user);
	}

	$xmlObj = new xml("ENTRY");
	$xmlObj->setRootAttribute("fill",1);
	if($pubInfo['type'] == "folder_search") {
		$xmlObj->setRootAttribute("type",$pubInfo['type']);
		$xmlObj->setRootAttribute("cabinet",$pubInfo['cabinet']);
		$xmlObj->setRootAttribute("field",$pubInfo['field']);
		$xmlObj->setRootAttribute("term",$pubInfo['term']);
		$xmlObj->setRootAttribute("enabled",$pubInfo['enabled']);
		$xmlObj->createKeyAndValue("FUNCTION","fillCabinetIndices(XML)");
		foreach($fields AS $index) {
			$xmlObj->createKeyAndValue("INDEX",str_replace("_"," ",$index),array('name'=>$index));
		}
	} else {
		$userList = getUserList($db_dept);
		$xmlObj->setRootAttribute("type",$pubInfo['type']);
		$xmlObj->setRootAttribute("workflow",$pubInfo['workflow']);
		$xmlObj->setRootAttribute("cabinet",$pubInfo['cabinet']);
		$xmlObj->setRootAttribute("enabled",$pubInfo['enabled']);
		$xmlObj->setRootAttribute("owner",$pubInfo['owner']);
		$xmlObj->createKeyAndValue("FUNCTION","fillWorkflowsAndCabinets(XML)");

		if($pubInfo['type'] == "workflow") {
			foreach($wfList AS $id => $wf) {
				$xmlObj->createKeyAndValue("WORKFLOW",str_replace("_"," ",$wf),array('id'=>$id));
			}
		}

		foreach($userList AS $name) {
			$xmlObj->createKeyAndValue("WORKFLOW",$name);
		}

		foreach($cabList AS $real => $arb) {
			$xmlObj->createKeyAndValue("CABINET",$arb,array('name'=>$real));
		}
	}
	$xmlObj->setHeader();
}

function getPublishSearch($enArr,$db_doc) {
	$tArr = array('publish_search','publish_search_list');
	$sArr = array('type','cab','field','term','enabled','owner','wf_def_id');
	$wArr = array(	'publish_search.id = '.(int)$enArr['id'],
					'publish_search.ps_list_id=publish_search_list.ps_list_id');
	$pubInfo = getTableInfo($db_doc,$tArr,$sArr,$wArr,'queryRow');

	$pubSearchInfo = array();
	if($pubInfo['type'] == "folder_search") {
		$pubSearchInfo['type'] = $pubInfo['type']; 
		$pubSearchInfo['cabinet'] = $pubInfo['cab']; 
		$pubSearchInfo['field'] = $pubInfo['field']; 
		$pubSearchInfo['term'] = $pubInfo['term']; 
		$pubSearchInfo['enabled'] = $pubInfo['enabled']; 
	} else {
		$pubSearchInfo['type'] = $pubInfo['type']; 
		$pubSearchInfo['workflow'] = $pubInfo['wf_def_id']; 
		$pubSearchInfo['cabinet'] = $pubInfo['cab']; 
		$pubSearchInfo['enabled'] = $pubInfo['enabled']; 
		$pubSearchInfo['owner'] = $pubInfo['owner']; 
	}
	return $pubSearchInfo;
}

function xmlPublishUser($enArr,$user,$db_doc,$db_dept) {
	if($enArr['requestType'] == "add") {
		unset($enArr['requestType']);
		if(addPublishUser($enArr,$user,$db_doc)) {
			$mess = 'Publish User Created Successfully';
		} else {
			$mess = "Publish User already exists";
		}
	} else {
		unset($enArr['requestType']);
		updatePublishUser($enArr,$user->db_name,$db_doc);
		$mess = 'Publish User Updated Successfully';
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$mess);
	$xmlObj->setHeader();
}

function addPublishUser($enArr,$user,$db_doc) {
	$pubUserObj = new publishUser();

	$upload = 0;
	if($enArr['upload']) {
		$upload += 1;
	}

	if($enArr['publish']) { 
		$upload += 2;
	}
	if($pubUserObj->setPublishUser($enArr['email'],$user->db_name,$upload)) {
		$pubSearchIDArr = getPubSearchList($enArr);
		foreach($pubSearchIDArr AS $id) {
			$insertArr = array(	'p_id' 	=> (int)$pubUserObj->id,
								'ps_id'	=> (int)$id);
			$res = $db_doc->extended->autoExecute('publish_user_list',$insertArr);
			dbErr($res);
		}
		return true;
	}
	return false;
}

function getPubSearchList(&$enArr) {
	$ct = 1;
	$pubSearchIDArr = array();
	while(isSet($enArr['pubSearch'.$ct])) {
		$pubSearchIDArr[] = $enArr['pubSearch'.$ct];
		unset($enArr['pubSearch'.$ct]);
		$ct++;
	}
	return $pubSearchIDArr;
}

function getNextListID($db_doc,$idArr) {
	if(count($idArr)) {
		$sArr = array('MAX(list_id)+1');
		$list_id = getTableInfo($db_doc,'user_search_list',$sArr,array(),'queryOne');
		if(!$list_id) {
			$list_id = 1;
		}
	} else {
		$list_id = 0;
	}
	return $list_id;
}

function updatePublishUser($enArr,$dep,$db_doc) {
	global $DEFS;

	$idArr = array();
	$ct = 1;
	while(isSet($enArr['pubSearch'.$ct])) {
		$idArr[] = $enArr['pubSearch'.$ct];
		$ct++;
	}

	if(!isSet($DEFS['PORTAL_MDEPS']) || $DEFS['PORTAL_MDEPS'] != 1) {
		$wArr = array('p_id' => (int)$enArr['pubUserID']);
		deleteTableInfo($db_doc,'publish_user_list',$wArr);
	} else {
		$tArr = array('publish_user_list','publish_search','publish_search_list');
		$sArr = array('publish_user_list.id');
		$wArr = array('p_id='.$enArr['pubUserID'],
					'ps_id=publish_search.id',
					'publish_search.ps_list_id=publish_search_list.ps_list_id',
					"publish_search_list.department='$dep'");
		$idList = getTableInfo($db_doc,$tArr,$sArr,$wArr,'queryCol');
		if(count($idList)) {
			foreach($idList AS $id) {
				$wArr = array('id' => (int)$id);
				deleteTableInfo($db_doc,'publish_user_list',$wArr);
			}
		}
	}

	foreach($idArr AS $id) {
		$insertArr = array(	'p_id' 	=> (int)$enArr['pubUserID'],
							'ps_id'	=> (int)$id);
		$res = $db_doc->extended->autoExecute('publish_user_list',$insertArr);
		dbErr($res);
	}

	$upload = 0;
	if($enArr['upload']) {
		$upload += 1;
	}

	if($enArr['publish']) { 
		$upload += 2;
	}

	$uArr = array('upload' => (int)$upload);
	$wArr = array('id' => (int)$enArr['pubUserID']);
	updateTableInfo($db_doc,'publish_user',$uArr,$wArr);
}

function xmlGetPublishUser($enArr,$user,$db_doc,$db_dept) {
	$pubInfo = getPublishUser($enArr,$user->db_name,$db_doc);

	$xmlObj = new xml("ENTRY");
	$xmlObj->setRootAttribute("fill",1);
	$xmlObj->setRootAttribute("upload",$pubInfo['upload']);
	$xmlObj->setRootAttribute("publish",$pubInfo['publish']);
	$xmlObj->createKeyAndValue("FUNCTION","fillPublishUser(XML)");
	foreach($pubInfo['pubSearchList'] AS $id) {
		$xmlObj->createKeyAndValue("SEARCH",$id);
	}
	$xmlObj->setHeader();
}

function getPublishUser($enArr,$dep,$db_doc) {
	global $DEFS;

	$sArr = array('upload');
	$wArr = array('id' => (int)$enArr['id']);
	$pubInfo = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'queryRow');

	if(!isSet($DEFS['PORTAL_MDEPS']) || $DEFS['PORTAL_MDEPS'] != 1) {
		$sArr = array('ps_id');
		$wArr = array('p_id' => (int)$enArr['id']);
		$listArr = getTableInfo($db_doc,'publish_user_list',$sArr,$wArr,'queryCol');
	} else {
		$tArr = array('publish_user_list','publish_search','publish_search_list');
		$sArr = array('publish_user_list.ps_id');
		$wArr = array('p_id='.$enArr['id'],
					'ps_id=publish_search.id',
					'publish_search.ps_list_id=publish_search_list.ps_list_id',
					"publish_search_list.department='$dep'");
		$listArr = getTableInfo($db_doc,$tArr,$sArr,$wArr,'queryCol');
	}

	$pubUserInfo = array();
	$pubUserInfo['upload'] = 0;
	$pubUserInfo['publish'] = 0;
	if($pubInfo['upload'] == 1) {
		$pubUserInfo['upload'] = 1;
		$pubUserInfo['publish'] = 0;
	} else if($pubInfo['upload'] == 2) {
		$pubUserInfo['upload'] = 0;
		$pubUserInfo['publish'] = 1;
	} else if($pubInfo['upload'] == 3) {
		$pubUserInfo['upload'] = 1;
		$pubUserInfo['publish'] = 1;
	}
	$pubUserInfo['pubSearchList'] = $listArr;

	return $pubUserInfo;
}

function getPublishSearchDisplay($user) {
	global $DEFS;

	$db_doc = getDbObject('docutron');
	$tArr = array('publish_search','publish_search_list');
	$sArr = array('publish_search.id','name','type','cab','field','term','wf_def_id');
	$wArr = array(	'publish_search.ps_list_id = publish_search_list.ps_list_id',
					'enabled = 1'); 
	$wArr[] =  "publish_search_list.department = '".$user->db_name."'";

	$oArr = array('name' => 'ASC');
	$pubSearchList = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc',$oArr);
	$db_doc->disconnect();

	$searchList = array();
	foreach($pubSearchList AS $id => $info) {
		$searchList[$id] = array();
		$searchList[$id]['name'] = $info['name'];
		$searchList[$id]['type'] = str_replace("_"," ",$info['type']);
		if($info['type'] == "folder_search") {
			$search = "Cabinet: ".$info['cab'];
			$search .= " Field: ".$info['field']." Term: ".$info['term']; 
		} elseif($info['type'] == "workflow") {
			$search = "Workflow: ".$info['wf_def_id'];
			$search .= " Cabinet: ".$info['cab'];
		} else {
			$search = "Cabinet: ".$info['cab'];
		}
		$search =  str_replace("_"," ",$search); 
		$searchList[$id]['search'] = $search;
	}

	return $searchList;
}

function xmlManagePublishUser($enArr,$user,$db_doc,$db_dept) {
	if($enArr['action'] == "delete") {
		$idArr = getSelectedUsers($enArr);
		deletePublishUser($idArr,$db_doc);
		$mess = "Published Users Deleted Successfully";
	} else if($enArr['action'] == "toggle") {
		togglePublishUser($enArr,$db_doc);
		$mess = "Published Users Suspended/Enabled Successfully";
	} else if($enArr['action'] == "password"){
		resendPassword2PublishUser($enArr,$db_doc);
		$mess = "Published Users Password Resent Successfully";
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$mess);
	$xmlObj->setHeader();
}

function xmlRemovePublishSearch($enArr,$user,$db_doc,$db_dept) {
	$idArr = getSelectedSearches($enArr);
	deletePublishSearch($idArr,$db_doc);
	$mess = "Published Searches Deleted Successfully";

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$mess);
	$xmlObj->setHeader();
}

function getSelectedUsers($enArr) {
	$ct = 1;
	$idArr = array();
	while(isSet($enArr['userID-'.$ct])) {
		$idArr[] = $enArr['userID-'.$ct];
		$ct++;
	}
	return $idArr;
}

function getSelectedSearches($enArr) {
	$ct = 1;
	$idArr = array();
	while(isSet($enArr['searchID-'.$ct])) {
		$idArr[] = $enArr['searchID-'.$ct];
		$ct++;
	}
	return $idArr;
}

function deletePublishUser($idArr,$db_doc) {
	foreach($idArr AS $id) {
		$wArr = array('id' => (int)$id);
		deleteTableInfo($db_doc,'publish_user',$wArr);
		$wArr = array('p_id' => (int)$id);
		deleteTableInfo($db_doc,'publish_user_list',$wArr);
	}
}

function deletePublishSearch($idArr,$db_doc) {
	foreach($idArr AS $id) {
		$sArr = array('ps_list_id');
		$wArr = array('id' => (int)$id);
		$ps_list_id = getTableInfo($db_doc,'publish_search',$sArr,$wArr,'queryOne');
		if($ps_list_id) {
			$wArr = array('ps_list_id' => (int)$ps_list_id);
			deleteTableInfo($db_doc,'publish_search_list',$wArr);
		}

		$wArr = array('id' => (int)$id);
		deleteTableInfo($db_doc,'publish_search',$wArr);

		$wArr = array('ps_id' => (int)$id);
		deleteTableInfo($db_doc,'publish_user_list',$wArr);
	}
}

function togglePublishUser($enArr,$db_doc) {
	$ct = 1;
	while(isSet($enArr['userID-'.$ct])) {
		$uArr = array('status' => $enArr['userStatus-'.$ct]);
		$wArr = array('id' => (int)$enArr['userID-'.$ct]);
		updateTableInfo($db_doc,'publish_user',$uArr,$wArr);
		$ct++;
	}
}

function resendPassword2PublishUser($enArr,$db_doc) {
	global $DEFS;
	$password = generatePassword();
	$ct = 1;
	while(isSet($enArr['userID-'.$ct])) {
		$id = $enArr['userID-'.$ct];
		$uArr = array(	'password' => md5($password),
						'reset_password' => 1);
		$wArr = array('id' => (int)$id);
		updateTableInfo($db_doc,'publish_user',$uArr,$wArr);
	
		if( isset( $DEFS['CREDENTIAL_EMAIL_SUBJECT2'] ) )
           	{$subject = $DEFS['CREDENTIAL_EMAIL_SUBJECT2'];
		} else {$subject = "Publishing Account Information";
		}
		if( isset( $DEFS['CREDENTIAL_EMAIL_BODY3'] ) )
            {$body = $DEFS['CREDENTIAL_EMAIL_BODY3']."\n---------------------------------------------\n";
	 	} else {
			$body = "";
		}
		$body = "User Name: ".$enArr['email-'.$id]."\n";
		$body .= "Password: $password\n";
		$body .= "The password has been resent\n";
		$body .= "---------------------------------------------\n\n";
		$body .= "Document Link:\n";
		$body .= portalLink();
		if( isset( $DEFS['CREDENTIAL_EMAIL_BODY2'] ) )
            $body .= "\n\n\n". $DEFS['CREDENTIAL_EMAIL_BODY2'];
		if( isset( $DEFS['CREDENTIAL_EMAIL_FOOTER'] ) )
			$body .= "\n\n\n". $DEFS['CREDENTIAL_EMAIL_FOOTER'];
		if( isset( $DEFS['EMAIL_FOOTER'] ) ){
			$body .= "\n\n".$DEFS['EMAIL_FOOTER']."\n\n";
		}
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$res = mail($enArr['email-'.$id],$subject,$body,$headers."From: publishing@{$DEFS['HOST']}");	
		$ct++;
	}
}

function generatePassword() {
	$totalChar = 8; // number of chars in the password
	$salt = "abcdefghijklmnpqrstuvwxyz";
	$salt .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
	$salt .= "123456789"; 
	srand((double)microtime()*1000000); // start the random generator
	$password=""; // set the inital variable
	for ($i=0;$i<$totalChar;$i++) { // loop and create password
		$password .= substr($salt, rand() % strlen($salt), 1);
	}

	return $password;
}
?>
