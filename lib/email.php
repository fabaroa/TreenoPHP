<?php
include_once '../lib/settings.php';

/*
 * function to send mails to multiple users
 */
function sendMail( $users, $subject, $body, $attachment, $serverName ) {
	if(is_array($users)) {
	$userList = implode( ",", $users );
		mail( $userList, $subject, $body, "From: workflow@$serverName" );
	}
}

function getLink( $department, $cab, $doc_id, $fileID=NULL ) {
	global $DEFS;
	if ($department=="client_files177") {
		$server = "treeno.retirementalliance.com";
	} else {
		$server = $DEFS['HOST'];
	}
	$link = '';
	if (isset($DEFS['LINK_SSL']) && $DEFS['LINK_SSL'] == '1') {
		$link .= 'https://';
	} else {
		$link .= 'http://';
	}
	//$link .= "$server/energie/energie.php?department=$department&cab=$cab";
	//$link .= "&doc_id=$doc_id&fileID=$fileID&link=1&wf=1";
	//$link .= "tr1.treenosoftware.com/home.aspx?d=$department&c=$cab&doc=$doc_id&wf=1";
	$link .= "$server/home.aspx?d=$department&c=$cab&doc=$doc_id&wf=1";	//$DEFS['HOST'] = tr1.treenosoftware.com
	//error_log("wf link: ".$link);
	return( $link );
}

function getLinkExt( $department, $cab, $doc_id, $wf_todo_id=null, $fileID=NULL) {
	global $DEFS;
	if ($department=="client_files177") {
		$server = "treeno.retirementalliance.com";
	} else {
		$server = $DEFS['HOST'];
	}
	$link = '';
	if (isset($DEFS['LINK_SSL']) && $DEFS['LINK_SSL'] == '1') {
		$link .= 'https://';
	} else {
		$link .= 'http://';
	}
	$link .= "$server/login.php?department=$department&cab=$cab";
//	$link .= "&doc_id=$doc_id&fileID=$fileID&link=1&wf=1";
	$link .= "&doc_id=$doc_id&fileID=$fileID&link=1&wf=1&todoID=$wf_todo_id";
	return( $link );
}

function getLinkAbs($id) {
	$link = "/energie/energie.php?todoID=".$id."&link=1&wf=1";
	return( $link );
}

function portalLink() {
	global $DEFS;
	return($DEFS['PORTAL']);
}
?>
