<?php
//$Id: node.inc.php 14908 2012-09-04 20:21:20Z rweeks $
include_once '../lib/email.php';
include_once '../lib/utility.php';
include_once '../DataObjects/DataObject.inc.php';
class node {
	var $db;
	var $db_doc;
	var $wf_document_id;
	var $wf_def_id;
	var $state_wf_def_id;
	var $fileLink;//link to file
	var $noActionMsg;//prints out this node type needs no action 
	var $header;//prints out node header for the node type
	var $message;//this is the description of the node
	var $messageAlert;//this is the description of the node
	var $auditAction;//stores the audit information
	var $email;//this is a boolean value 0/1. 1 means they want an email notifciation
   	var $subject;//subject of email
	var $body;//body of email
	var $actionURL;
	
	//this is for the link and for creating new node objects when using next and prev.
	var $department;
	var $cab;
	var $doc_id;
	var $fileID;
	var $uname;

	//this is for display purposes
	var $depDisplayName;
	var $cabDisplayName;
	
	function node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDispName, $doc_id, $db_doc, $fileID = NULL) {
		$this->db = $db_object;
		$this->db_doc = $db_doc;
		$this->department = $department;
		$this->cabDisplayName = $cabDispName;
		$this->depDisplayName = $this->getDepName($department);
		$this->wf_document_id = $wf_document_id;
		$this->state_wf_def_id = $state_wf_def_id;
		$this->cab = $cab;
		$this->doc_id = $doc_id;
		$this->fileID = $fileID;
		$this->uname = $uname;

		$this->wf_def_id = $this->getWFDefID();
		$this->email = getWFNodeEmail( $this->db, $wf_document_id );
		//$this->fileLink = getLink( $department, $cab, $doc_id, $fileID );
		$this->fileLink = getLink( $department, $cab, $wf_document_id, $fileID );
		$this->message = getWFNodeMessage( $this->db, $wf_document_id );
		$this->messageAlert = getWFNodeMessage($this->db,$wf_document_id,1);
		$this->actionURL = '../workflow/defaultAction.php';
	}
	
	function getCabName($cab) {
		$cabName = getTableInfo($this->db, 'departments', array('departmentname'), array('real_name'=>$cab),'queryOne');
		return $cabName;
	}

	function getDepName($department) {
		$depName = getTableInfo($this->db_doc, 'licenses', array('arb_department'), array('real_department'=>$department),'queryOne');
		return $depName;
	}

	function getNodeName() {
		$nodeID = getTableInfo($this->db,'wf_documents',array('state_wf_def_id'),array('id'=>(int)$this->wf_document_id),'queryOne');
		$nodeInfo = getWFNodeInfo($this->db, $nodeID);
		return $nodeInfo['node_name'];
	}
	
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
			if( sizeof($userList) == 0 ) {
				$userList[] = $wfOwner;	
			}
		} else{
			$this->deleteFromTodo();
			$userList[] = $wfOwner;	
		}

		//add entry to the wf_todo table in docutron
		$todoArr = array();
		foreach( $userList AS $username ) {
			$this->addTodoItem($username);
		}
		
		//$treenoV4 = true;	// Don't send out email from PHP side for TreenoV4
		//if( !$treenoV4 && $this->email ) {
		if($this->email ) {
			$attachment = "";
			$serverName = "treenosoftware.com";
			$message = $this->message."\n".$this->body;
			//email userlist if applies
			$addressList = $this->generateEmailList($wfOwner, $userList);
			sendMail( $addressList, $this->subject, $message, $attachment, $serverName );
						
			$notes = "user notified that new workflow has entered their todo list";
			$this->addToWFHistory('notified',$notes,$userList);
		}
		else
		{		
			$notes = "New workflow has entered their todo list";
			$this->addToWFHistory('AddedToDoList',$notes,$userList);
		}
	}

	//Generates the email addresses that needs to be notified about the current workflow
	function generateEmailList($wfOwner, $userList) {
		$addressList = array();
		$usersEmail = array();
		$DO_users = DataObject::factory('users', $this->db_doc);
		$DO_users->orderBy('username', 'ASC');
		$DO_users->find();
		while($DO_users->fetch()) { 
			$usersEmail[$DO_users->username] = $DO_users->email;
		}

		if( $this->email == 1 || $this->email == 3 ) {
			foreach( $userList AS $username ) {
				if( array_key_exists( $username, $usersEmail ) ) {
                	//adds address to an array that will be used to email
	                $addressList[] = $usersEmail[$username];
    	        }
			}
		}

		if( ($this->email > 1) && (array_key_exists($wfOwner, $usersEmail)) ) {
			$ownerEmail = $usersEmail[$wfOwner];
			if( !in_array($ownerEmail, $addressList) ) {
                //adds address to an array that will be used to email
                $addressList[] = $ownerEmail;
            }
		}

		return $addressList;
	}

	function reject() {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$notifyUser = '';
			$this->deleteFromTodo(1);
			$this->lockWorkflow();
			if($notes) {
				$this->addToWFHistory('rejected',$notes);
			} else {
				$this->addToWFHistory('rejected');
			}

			$prev = getTableInfo($this->db,'wf_defs',array('prev'),array('id'=>(int)$this->state_wf_def_id),'queryOne');
			//$prev == 0 when rejecting to previous node, not selected reject node
			if( $prev == 0 ) {
				$prev = getNodeFromHistory($this->db, $this->wf_document_id, $this->state_wf_def_id);
			}

			//updates the workflow node to reject
			$nodeType = getWFPrevNodeType( $this->db, $this->wf_document_id );
			$updateArr = array('state_wf_def_id'=>(int)$prev);
			$whereArr = array('id'=>(int)$this->wf_document_id);
			if( $nodeType == "STATE" ) {
				$updateArr['status'] = 'PAUSED';
				updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
			} else {
				updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
				$oldRows = getTableInfo($this->db, 'wf_history',array(), 
					array('wf_document_id' => (int) $this->wf_document_id),
					'queryAll', array('id' => 'DESC'), 2);
				if($oldRows and count($oldRows) == 2) {
					$nodeID = getTableInfo($this->db,'wf_documents',array('state_wf_def_id'),array('id' =>(int)$this->wf_document_id),'queryOne');
					if($nodeID == $oldRows[1]['wf_node_id']) {
						$notifyUser = $oldRows[1]['username'];
					}
				}
			}
			$nodeClass = $nodeType."Node";
			$nodeObj = new $nodeClass( $this->db, $this->department, $this->uname, 
										$this->wf_document_id, $prev, 
										$this->cab, $this->cabDisplayName, $this->doc_id, $this->db_doc, $this->fileID );
			$nodeObj->notify($notifyUser);
			$this->unlockWorkflow();
		}
	}
	
	function accept($needLock = true) {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$this->deleteFromTodo();
			
			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			$ct = getTableInfo($this->db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne');
			
			if ($needLock) {
				$this->lockWorkflow();
			}
			$this->addToWFHistory('accepted');
			$next = getTableInfo($this->db,'wf_defs',array('next'),array('id'=>(int)$this->state_wf_def_id),'queryOne');
			//$next == 0 when accepting to previous node
			if( $next == 0 ) {
				$next = getNodeFromHistory($this->db, $this->wf_document_id, $this->state_wf_def_id);
			}

			//updates the workflow node to next
			//if($next) {
			if ($ct == 0) {
				$nodeType = getWFNodeType( $this->db, $this->wf_document_id );
				if($next == $this->checkCurrentNode() && $nodeType == "STATE") {
					$notes = "Infinite loop detected.  State node points to self";
					$this->addToWFHistory("Logical Error",$notes);

					$updateArr = array('status'=>'COMPLETED');
					$whereArr = array('id'=>(int)$this->wf_document_id);
					updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
					if ($needLock) {
						$this->unlockWorkflow();
					}
				} else {
					$updateArr = array('state_wf_def_id'=>(int)$next);
					$whereArr = array('id'=>(int)$this->wf_document_id);
					updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);

					$nodeType = getWFNodeType( $this->db, $this->wf_document_id );
					if ($needLock) {
						$this->unlockWorkflow();
					}
					$nodeClass = $nodeType."Node";
					//error_log("node.inc.php::accept() - nodeClass: ".$nodeClass);
					$nodeObj = new $nodeClass( $this->db, $this->department, $this->uname, 
											$this->wf_document_id, $next, 
											$this->cab, $this->cabDisplayName, $this->doc_id, $this->db_doc, $this->fileID );
					$nodeObj->notify('', false);
				}
			} else {
				if ($needLock) {
					$this->unlockWorkflow();
				}
			}
		}
	}
	
	///////////////////////////////////////////
	//retreive the users from the group list
	function getGroupList() {
		$groupList = array();
		$groupList = getGroupUsers($this->db, $this->wf_document_id);
		return($groupList);		
	}
	///////////////////////////////////////////
	//retrieve the users from the user list
	function getUserList() {
		$userList = getUsers($this->db, $this->wf_document_id);
		return($userList);
	}

	function reassignNode ($newUser) {
		$this->lockWorkflow ();
		$owner = getTableInfo ($this->db, 'wf_documents',
			array ('owner'),
			array ('id' => (int) $this->wf_document_id), 'queryOne');
		if ($owner == $this->uname) {
			updateTableInfo ($this->db, 'wf_documents',
				array ('owner' => $newUser), 
				array ('id' => (int) $this->wf_document_id));
			$retVal = true;
		} else {
			$retVal = false;
		}
		$this->unlockWorkflow ();
		if ($retVal) {
			$this->deleteFromTodo ();
			$this->addTodoItem($newUser);
			$this->addToWFHistory ('reassign', 
				'Reassigned Workflow Owner to ' . $newUser, 
				array ($this->uname));
			return true;
		} else {
			return false;
		}
	}

	function getUniqueUsers() {
		$userListArr = array();
		$groupListArr = array();
		//returns an array of the users that are selected for that node
		$userListArr = $this->getUserList();
		//return an array of users that belonged to each group selected for that node
		$groupListArr = $this->getGroupList();
		//merges both arrays
		$completeUserList = array_merge($userListArr, $groupListArr);
		for($i = 0; $i < count($completeUserList); $i++) {
			if($completeUserList[$i] == 'Workflow Owner') {
				$completeUserList[$i] = getWFOwner($this->db, $this->wf_document_id);
				break;
			}
		}
		//removes duplicates out of the array
		$completeUserList = array_unique($completeUserList);

		return $completeUserList;
	}

	function getWhichUser( $userList ) {
		$newUserList = array();
		$which_user = getWFWhichUser( $this->db, $this->wf_document_id );	
		if($which_user == 4) {
			$arr = getCurrentWFNodeInfo($this->db, $this->wf_document_id);
			list($wf_node_id, $state) = array_values($arr);

			usort($userList,"strnatcasecmp");
			$sArr = array('username');
			$wArr = array('wf_node_id'	=> (int)$wf_node_id); 
			$oArr = array('id' => 'DESC');
			$uname = getTableInfo($this->db,'wf_history',$sArr,$wArr,'queryOne',$oArr);
	
			$key = array_search($uname,$userList);
			if($key === false || (($key+1) == count($userList))) {
				$newUserList[] = $userList[0];
			} else {
				$newUserList[] = $userList[($key+1)];
			}
		} elseif( $which_user == 3) {
  			$accessRights = getTableInfo($this->db,'access');
			$userArr = array();
			while( $accessInfo = $accessRights->fetchRow() ) {
				$accessArr = unserialize(base64_decode($accessInfo['access'])); 
				if( $accessArr[$this->cab] != "none" ) {
					$userArr[$accessInfo['username']] = $accessArr;
				}
			}
		
			foreach( $userList AS $username ) {
				if( array_key_exists($username,$userArr) ) {
					$newUserList[] = $username;
				}
			}
		} else if( $which_user > 1 ) {
			$randNum = mt_rand(0,sizeof( $userList ) - 1 );
			$newUserList[] = $userList[$randNum];
		} else
			$newUserList = $userList;

		return( $newUserList );
	}

	function addTodoItem($username) {
		$db_doc = getDbObject('docutron');
		$insArr = array (	'department'	=> $this->department,
							'username'		=> $username,
							'wf_document_id'=> (int)$this->wf_document_id,
							'wf_def_id' 	=> (int)$this->wf_def_id,
							'date'			=>	date("Y-m-d G:i:s") );
		$res = $db_doc->extended->autoExecute ('wf_todo', $insArr);
		dbErr($res);
		$db_doc->disconnect();
	}
	
	function deleteFromTodo($reject=0) {
		$which_user = getWFWhichUser( $this->db, $this->wf_document_id );	
		$whereArr = array(	'wf_document_id'	=>(int)$this->wf_document_id,
							'department'		=> $this->department );
		if(!$reject) {
			if( $which_user != "" && $which_user != 1 ) {
				$whereArr['username'] = $this->uname;
			}
		}
		deleteTableInfo($this->db_doc,'wf_todo',$whereArr);
	}
	
	function checkCurrentNode() {
		return getTableInfo($this->db,'wf_documents',array('state_wf_def_id'),array('id'=>(int)$this->wf_document_id),'queryOne');
	}

	function lockWorkflow() {
		$tables = array (	'wf_documents',
							'wf_defs',
							'wf_nodes',
							'wf_value_list',
							'user_list',
							'users_in_group',
							'access',
							'group_list',
							'wf_history',
							'signatures',
							'groups' );
		lockTables($this->db,$tables);
	}

	function unlockWorkFlow() {
		unlockTables($this->db);
	}
	
	function getExtraAction() {
	}
	
	function addToWFHistory($type,$notes='',$unameArr='') {
		if( !empty($_POST['notes']) && $_POST['notes'] != "Enter notes here" && !$notes) {
			$notes = $_POST['notes'];
		}

		if(!$unameArr) {
			$unameArr[] = $this->uname;
		}
		$arr = getCurrentWFNodeInfo($this->db, $this->wf_document_id);
		if(is_array($arr)) {
			list($wf_node_id, $state) = array_values($arr);
			$insertArr = array(	"wf_document_id"	=> (int)$this->wf_document_id,
								"wf_node_id"		=> (int)$wf_node_id,
								"action"			=> $type,
								"username"			=> implode(',',$unameArr),
								"date_time"			=> date('Y-m-d H:i:s'),
								"state"				=> (int)$state,
								"notes"				=> $notes );
			$res = $this->db->extended->autoExecute('wf_history',$insertArr);
			dbErr($res);
		}
	}

	//Test that the given user is allowed to sign off on the workflow
	function isAssignedUser($userName, $department, $db_doc) {
		$isAssignedUser = false;
		$userList = $this->getUniqueUsers();
		if( sizeof($userList) == 0 ) {
			$userList[] = getWFOwner($this->db, $this->wf_document_id);
		} 

		//Add the usernames from the wf_todo table
		$wfTodoUsers = getTableInfo($db_doc, 'wf_todo', 
			array('username'), 
			array('wf_document_id' => (int)$this->wf_document_id,
				'department' => $department),
			'queryCol'
		);

		if( in_array($userName, $userList) ) {
			return true;
		} elseif( in_array($userName, $wfTodoUsers) ) {
			return true;
		} else {
			return false;
		}
	}

	function getWFDefID() {
		$sArr = array('wf_def_id');
		$wArr = array('id' => (int)$this->wf_document_id);
		$wf_def_id = getTableInfo($this->db,'wf_documents',$sArr,$wArr,'queryOne');
		return $wf_def_id;
	}

}
//all nodes created must be included below
include_once 'finalNode.inc.php';
include_once 'stateNode.php';
include_once 'mas500Node.inc.php';
include_once 'outlookNode.inc.php';
include_once 'signode.php';
include_once 'addFileNode.inc.php';
include_once 'valueNode.inc.php';
include_once 'indexingNode.inc.php';
include_once 'customWorkflowNode.php';
include_once 'WORKINGNode.php';
?>
