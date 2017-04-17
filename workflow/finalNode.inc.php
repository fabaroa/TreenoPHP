<?php
include_once 'node.inc.php';

class finalNode extends node
{
	function finalNode($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL) {
	    node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->subject = "This document has completed workflow in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Workflow Name: ".getWFDefsName($this->db,$wf_document_id)."\n\n";
    	$this->body .= "username\t\tstatus\t\tdate-time\t\tcomments\n";
		$this->body .= "---------------------------------------------\n";
    	$this->body .= getWorkflowReport( $db_object, $wf_document_id );
    	$this->body .= "\nDocument Link:\n".$this->fileLink;
	}
												
	function notify ($notifyUser = '', $needLock = true) {
		if( $this->email ) {
			//get userlist and grouplist
			$userList = array();
			if ($notifyUser) {
				$userList[] = $userList;
			} else {
				$userList = $this->getUniqueUsers();
				//determine list of users to be notified
				$userList = $this->getWhichUser( $userList );
				if( sizeof($userList) == 0 || $this->email > 1 ) {
					$userList[] = getWFOwner($this->db, $this->wf_document_id);	
				}
			}

			//email userlist if applies
			$usersEmail = array();
			$DO_users = DataObject::factory('users', $this->db_doc);
			$DO_users->orderBy('username', 'ASC');
			$DO_users->find();
			while($DO_users->fetch()) { 
				$usersEmail[$DO_users->username] = $DO_users->email;
			}

			foreach( $userList AS $username ) {
				if( array_key_exists( $username, $usersEmail ) ) {
					//adds address to an array that will be used to email
					$addressList[] = $usersEmail[$username];
				}
			}

			$attachment = "";
			$serverName = "treenosoftware.com";
			sendMail( $addressList, $this->subject, $this->body, $attachment, $serverName );
		}				
		$notes = "user(s) notified that workflow has been completed";
		list($wf_node_id, $state) = array_values(getCurrentWFNodeInfo($this->db, $this->wf_document_id));
		$insertArr = array(
				"wf_document_id"	=> (int)$this->wf_document_id,
				"wf_node_id"		=> (int)$wf_node_id,
				"action"		=> 'notified',
				"username"		=> $this->uname,
				"date_time"		=> date('Y-m-d H:i:s'),
				"state"			=> (int)$state,
				"notes"			=> $notes	
					);
		$this->db->extended->autoExecute('wf_history',$insertArr);
		$updateArr = array('status'=>'COMPLETED');
		$whereArr = array('id'=>(int)$this->wf_document_id);
		updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
	}
}

?>
