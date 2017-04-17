<?php
include_once '../lib/email.php';
include_once '../lib/settings.php';

function xmlLoadPublishingData($enArr,$user,$db_doc,$db_dept) {
	$_SESSION['publishSearch'] = "";
	$searchList = getPublishedSearches($user->db_name);
	$userList = getPublishedUsers($user->db_name);

	$gblStt = new Gblstt($user->db_name,$db_doc);
	$maxExpire = 0;
	if($gblStt->get('pub_max_expire')) {
		$maxExpire = $gblStt->get('pub_max_expire');
	}

	$defExpire = 0;
	if($gblStt->get('pub_def_expire')) {
		$defExpire = $gblStt->get('pub_def_expire');
	}
	$usrSett = new Usrsettings($user->username,$user->db_name);
	$forceExpire = 0;

	if(($sett = $usrSett->get('publishingExpire')) != "") {
		$forceExpire = $sett;
	} elseif(($sett = $gblStt->get('publishingExpire')) != "") {
		$forceExpire = $sett;
	}

	$forceDefExpire = 0;
	if(($sett = $gblStt->get('publishingDefaultExp')) != "") {
		$forceDefExpire = $sett;
	} elseif(($sett = $gblStt->get('publishingDefaultExp')) != "") {
		$forceDefExpire = $sett;
	}

	$xmlObj = new xml();
	$xmlObj->setRootAttribute("maxExpire",$maxExpire);
	$xmlObj->setRootAttribute("defExpire",$defExpire);
	$xmlObj->setRootAttribute("forceExpire",$forceExpire);
	$xmlObj->setRootAttribute("forceDefExpire",$forceDefExpire);
	$xmlObj->createKeyAndValue('FUNCTION','setPublishingData(XML)');
	foreach($searchList AS $id => $name) {
		$xmlObj->createKeyAndValue("SEARCH",$name,array('id' => $id));
	}

	foreach($userList AS $id => $name) {
		$xmlObj->createKeyAndValue("USER",$name,array('id' => $id));
	}
	$xmlObj->setHeader();
}

function getPublishedSearches($dep) {
	$db_doc = getDbObject('docutron');
	$tArr = array('publish_search','publish_search_list');
	$sArr = array('publish_search.id','name');
	$wArr = array(	'publish_search.ps_list_id=publish_search_list.ps_list_id',
					"publish_search_list.department='$dep'");
	$list = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc');

	uasort($list,"strnatcasecmp");
	return $list;
}

function getPublishedUsers($dep) {
	global $DEFS;

	$db_doc = getDbObject('docutron');
	$sArr = array('id','email');

	$wArr = array('department' => $dep);
	if(isSet($DEFS['PORTAL_MDEPS']) && $DEFS['PORTAL_MDEPS'] == 1) {
		$wArr = array();
	}
	$list = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'getAssoc');

	uasort($list,"strnatcasecmp");
	return $list;
}

function xmlAddPublishName($enArr,$user,$db_doc,$db_dept) {
	$pubObj = new publishSearch($user->username);			
	$pubObj->setPublishName($enArr['publish_name']);
	$_SESSION['publishSearch'] = $pubObj;
	$user->audit("created published search","published search name: ".$pubObj->getPubName());

	$xmlObj = new xml();
	$val = ($pubObj->id) ? $pubObj->id : 0;
	$xmlObj->createKeyAndValue("FUNCTION","verifyPubName($val,'$pubObj->message')");
	$xmlObj->setHeader();
}

function xmlEditPublishName($enArr,$user,$db_doc,$db_dept) {
	$pubObj = $_SESSION['publishSearch'];
	$pubObj->db = $db_doc;
	$old = $pubObj->getPubName();
	$pubObj->setPublishName($enArr['publish_name']);
	$_SESSION['publishSearch'] = $pubObj;
	$mess = "edited published search name: from: $old to:".$pubObj->getPubName();
	$user->audit("edited published search",$mess);

	$xmlObj = new xml();
	$val = ($pubObj->id) ? $pubObj->id : 0;
	$xmlObj->createKeyAndValue("FUNCTION","verifyPubName($val,'$pubObj->message')");
	$xmlObj->setHeader();
}

function xmlCreatePublishUser($enArr,$user,$db_doc,$db_dept) {
	$pubUserObj = new publishUser();	
	$pubUserObj->setPublishUser($enArr['publish_user'],$user->db_name);
	
	$pubObj = $_SESSION['publishSearch'];
	$pubObj->db = $db_doc;
	$pubObj->addToUserList($pubUserObj->id,$pubUserObj->email);
	$_SESSION['publishSearch'] = $pubObj;
	$mess = "Publish User has been created";
	$auditMess = "created published user: ".$pubUserObj->email;
	$user->audit("created published user",$auditMess);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","addNewUser($pubUserObj->id,'".$pubUserObj->email."')");
	$xmlObj->setHeader();
}

function xmlAddPublishUser($enArr,$user,$db_doc,$db_dept) {
	$pubObj = $_SESSION['publishSearch'];
	$pubObj->db = $db_doc;
	$pubObj->addToUserList($enArr['publish_id'],$enArr['publish_user']);
	$_SESSION['publishSearch'] = $pubObj;
	$auditMess = "selected published user for published search: ".$pubObj->getPubName();
	$user->audit("selected published user",$auditMess);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","printMessage('$pubObj->message')");
	$xmlObj->setHeader();
}

function xmlRemovePublishUser($enArr,$user,$db_doc,$db_dept) {
	$pubObj = $_SESSION['publishSearch'];
	$pubObj->db = $db_doc;
	$uname = $pubObj->userList[$enArr['publish_id']];
	$pubObj->deleteFromUserList($enArr['publish_id']);
	$_SESSION['publishSearch'] = $pubObj;
	$auditMess = "removed published user: ".$uname." from published search: ".$pubObj->getPubName();
	$user->audit("unselected published user",$auditMess);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","printMessage('$pubObj->message')");
	$xmlObj->setHeader();
}

function xmlGetSandbox($enArr,$user,$db_doc,$db_dept) {
	$pubObj = new publishSearch($user->username,$enArr['publish_search_id']);			
	$_SESSION['publishSearch'] = $pubObj;
	$auditMess = "loaded published search: ".$pubObj->getPubName();
	$user->audit("loaded published search",$auditMess);
	
	$xmlObj = new xml();
	$xmlObj->setRootAttribute("expire",$pubObj->getExpireTime());
	$xmlObj->createKeyAndValue("FUNCTION","setSandbox(XML)");
	foreach($pubObj->userList AS $id => $name) {
		$xmlObj->createKeyAndValue("USER",$name,array('id' => $id));
	}
	
	$attArr = array();
	$dependArr = $pubObj->getDependArr();
	foreach($pubObj->itemList AS $dep => $cabList) {
		$attArr['dep'] = $dep;
		foreach($cabList AS $cab => $folderList) {
			$attArr['cab'] = $cab;
			$attArr['cabname'] = $user->cabArr[$cab];
			foreach($folderList AS $doc_id => $docList) {
				$attArr['doc_id'] = $doc_id;
				if(in_array("$dep-$cab-$doc_id",$dependArr)) {
					$attArr['independent'] = 1; 
				} else {
					$attArr['independent'] = 0; 
				}
				$xmlObj->createKeyAndValue("ITEM",$docList['folder'],$attArr);
				unset($docList['folder']);
				foreach($docList AS $file_id => $doc_desc) {
					$xmlObj->createKeyAndValue("ITEM",$doc_desc,array('file_id' => $file_id));
				}
			}
		}
	}
	$xmlObj->setHeader();
}

function xmlAddPublishingItem($enArr,$user,$db_doc,$db_dept) {
	if($enArr['new'] == "1") {
		$pubObj = new publishSearch($user->username);			
		$ct = 1;
		$pubName = $user->username." ".$ct;
		while(!$pubObj->setPublishName($pubName)) {
			$ct++;
			$pubName = $user->username." ".$ct;
		}
	} else {
		$pubObj = $_SESSION['publishSearch'];
		$pubObj->db = $db_doc;
	}

	if($enArr['file_id']) {
		$dispArr = $pubObj->addItemToList($user->db_name,$enArr['cabinet'],$enArr['doc_id'],$enArr['file_id']);
	} else {
		$dispArr = $pubObj->addItemToList($user->db_name,$enArr['cabinet'],$enArr['doc_id']);
	}

	$itemDesc = array(	"Cabinet:".$user->cabArr[$enArr['cabinet']],
						"Folder: ".$dispArr['folder']);
	if($enArr['file_id']) {
		$itemDesc[] = "Document: ".$dispArr['document'];	
	}
	
	$_SESSION['publishSearch'] = $pubObj;
	$auditMess = "added published item:{".implode(" ",$itemDesc)."} to ".$pubObj->getPubName();
	$user->audit("added published item",$auditMess);
	
	$xmlObj = new xml();
	if($enArr['new'] == "1") {
		$xmlObj->setRootAttribute('id', $pubObj->id);
		$xmlObj->setRootAttribute('name', $pubObj->name);
	}
	$xmlObj->setRootAttribute('message', $pubObj->message);

	$xmlObj->createKeyAndValue("FUNCTION","displayItem(XML)");
	$attArr = array('dep'		=> $dispArr['dep'],
					'cab'		=> $dispArr['cab'],
					'cabname'	=> $user->cabArr[$dispArr['cab']],
					'doc_id'	=> $dispArr['doc_id']);
	if(in_array($dispArr['dep']."-".$dispArr['cab']."-".$dispArr['doc_id'],$pubObj->getDependArr())) {
		$attArr['independent'] = 1; 
	} else {
		$attArr['independent'] = 0; 
	}
	$xmlObj->createKeyAndValue("ITEM",$dispArr['folder'],$attArr);

	if(isSet($dispArr['file_id'])) {
		$xmlObj->createKeyAndValue("ITEM",$dispArr['document'],array('file_id' => $dispArr['file_id']));
	}
	$xmlObj->setHeader();
}

function xmlRemovePublishedItem($enArr,$user,$db_doc,$db_dept) {
	$pubObj = $_SESSION['publishSearch'];
	$pubObj->db = $db_doc;
	
	$folderArr = $pubObj->itemList[$enArr['department']][$enArr['cabinet']][$enArr['doc_id']];
	$itemDesc = array(	"Cabinet: ".$user->cabArr[$enArr['cabinet']],
						"Folder: ".$folderArr['folder']);
	if($enArr['file_id']) {
		$itemDesc[] = "Document: ".$folderArr[$enArr['file_id']]['document'];	
	}

	$pubObj->deleteItemFromList($enArr['department'],$enArr['cabinet'],$enArr['doc_id'],$enArr['file_id']);
	$_SESSION['publishSearch'] = $pubObj;

	$auditMess = "removed published item:{".implode(" ",$itemDesc)."} to ".$pubObj->getPubName();
	$user->audit("removed published item",$auditMess);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","printMessage('$pubObj->message')");
	$xmlObj->setHeader();
}

function xmlSetPublishedExpireTime($enArr,$user,$db_doc,$db_dept) {
	$pubObj = $_SESSION['publishSearch'];
	$pubObj->db = $db_doc;
	$pubObj->setExpireTime($enArr['expire']);
	$_SESSION['publishSearch'] = $pubObj;
	$auditMess = "set published expire time to ".$enArr['expire']." for published search: ".$pubObj->getPubName();
	$user->audit("set published expire time",$auditMess);
	
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","printMessage('$pubObj->message')");
	$xmlObj->setHeader();
}

function xmlGetPublishingExpiration($enArr,$user,$db_doc,$db_dept) {
	$gblStt = new Gblstt($user->db_name,$db_doc);
	$maxExpire = 0;
	if($gblStt->get('pub_max_expire')) {
		$maxExpire = $gblStt->get('pub_max_expire');
	}

	$defExpire = 0;
	if($gblStt->get('pub_def_expire')) {
		$defExpire = $gblStt->get('pub_def_expire');
	}
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setPublishingExpiration(XML)");
	$xmlObj->createKeyAndValue("DEFAULT",$defExpire);
	$xmlObj->createKeyAndValue("MAX",$maxExpire);
	$xmlObj->setHeader();
}

function xmlSetPublishingExpiration($enArr,$user,$db_doc,$db_dept) {
	$gblStt = new Gblstt($user->db_name,$db_doc);
	$gblStt->set('pub_def_expire',$enArr['defExp']);
	$gblStt->set('pub_max_expire',$enArr['maxExp']);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","printMessage('Publishing expiration set successfully')");
	$xmlObj->setHeader();
}

function xmlPublishSandbox($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	$pubObj = $_SESSION['publishSearch'];
	$list_id = $pubObj->getItemID();

	foreach($pubObj->userList AS $id => $name) {
		$subject = "Publishing Account Information";
		if( isset( $DEFS[$user->db_name.'_PUBLISH_EMAIL_SUBJECT'] ) )
           	{$subject = $DEFS[$user->db_name.'_PUBLISH_EMAIL_SUBJECT'];
		} else if( isset( $DEFS['PUBLISH_EMAIL_SUBJECT'] ) )
           	{$subject = $DEFS['PUBLISH_EMAIL_SUBJECT'];
		} else {$subject = "Publishing Account Information";
		}
		if( isset( $DEFS[$user->db_name.'_PUBLISH_EMAIL_BODY1'] ) )
            {$body = $DEFS[$user->db_name.'_PUBLISH_EMAIL_BODY1']."<br>---------------------------------------------<br>";
	 	} else if( isset( $DEFS['PUBLISH_EMAIL_BODY1'] ) )
            {$body = $DEFS['PUBLISH_EMAIL_BODY1']."<br>---------------------------------------------<br>";
	 	} else {
			$body = "";
		}
		$body .= "User Name: ".$name."<br>";
		$body .= "A document has been published<br>";
		$body .= "---------------------------------------------<br><br>";
		$body .= "Document Link:<br><a href='";
		$body .= portalLink()."?autosearch=&list_id=".$list_id."'>".portalLink()."?autosearch=&list_id=".$list_id." </a>";
		if( isset( $DEFS[$user->db_name.'_PUBLISH_EMAIL_BODY2'] ) )
            $body .= "<br><br><br>". $DEFS[$user->db_name.'_PUBLISH_EMAIL_BODY2'];
		if( isset( $DEFS[$user->db_name.'_PUBLISH_EMAIL_FOOTER'] ) )
			$body .= "<br><br><br>". $DEFS[$user->db_name.'_PUBLISH_EMAIL_FOOTER'];
		if( isset( $DEFS[$user->db_name.'_EMAIL_FOOTER'] ) ){
			$body .= "<br><br>".$DEFS[$user->db_name.'_EMAIL_FOOTER']."<br><br>";
		}
		if( isset( $DEFS['PUBLISH_EMAIL_BODY2'] ) )
            $body .= "<br><br><br>". $DEFS['PUBLISH_EMAIL_BODY2'];
		if( isset( $DEFS['PUBLISH_EMAIL_FOOTER'] ) )
			$body .= "<br><br><br>". $DEFS['PUBLISH_EMAIL_FOOTER'];
		if( isset( $DEFS['EMAIL_FOOTER'] ) ){
			$body .= "<br><br>".$DEFS['EMAIL_FOOTER']."<br><br>";
		}
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		if( isset( $DEFS['REPLYUSER'] ) ){
			$temp=getTableInfo($db_doc, 'users', array('email'), array('username'=>$user->username,'password'=>$user->password), 'queryOne');
			if ($temp!="" && $temp!=NULL) {
				$email = "From: ".$temp;
			} else {
				$email = "From: publishing@{$DEFS['HOST']}";
			}
		} else {
			$email = "From: publishing@{$DEFS['HOST']}";
		}
		mail($name,$subject,$body,$headers.$email);	
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","closeSandbox()");
	$xmlObj->setHeader();
}

?>
