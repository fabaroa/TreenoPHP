<?php
include_once 'node.inc.php';
include_once 'signode.php';
include_once '../lib/utility.php';
include_once '../lib/email.php';

class stateNode extends node {

	function stateNode($db_object, $department, $uname, $wf_document_id,
		$state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL) {

		node::node($db_object, $department, $uname, $wf_document_id,$state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);

		$this->noActionMsg = "TODO item complete";
		$this->header = "State Node";
		$this->subject = "This document has reached the following state in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = $this->fileLink;
	}

	//We don't use notifyUser in this class.
	function notify($notifyUser = '', $needLock = true) {
		//get userlist and grouplist
		$userList = array();
		$wfOwner = getWFOwner($this->db, $this->wf_document_id);
		if($notifyUser) {
			$userList[] = $notifyUser;
		} elseif( getWFStatus($this->db, $this->wf_document_id) != "PAUSED" ) {
			$userList = $this->getUniqueUsers();
			//determine list of users to be notified
			$userList = $this->getWhichUser( $userList );	
			if( sizeof($userList) == 0 || $this->email > 1 ) {
				$userList[] = $wfOwner;	
			}
		} else{
//			$this->deleteFromTodo();
			$userList[] = $wfOwner;	
		}

		if( $this->email ) {
			$attachment = "";
			$serverName = "treenosoftware.com";
			$message = $this->message."\n".$this->body;
			//email userlist if applies
			$addressList = $this->generateEmailList($wfOwner, $userList);
			sendMail( $addressList, $this->subject, $message, $attachment, $serverName );
		}
//		node::notify($notifyUser);
		$this->accept($needLock);
	}
}
?>
