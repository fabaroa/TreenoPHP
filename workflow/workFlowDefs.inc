<?php
//This class defines methods for creating a workflow. Included are methods to
//create nodes and create the entire workflow given an array of nodes.
require_once '../lib/utility.php';
error_reporting(E_ALL);

class workFlowDefs {
	var $db;
	var $states;
	var $parentID;
	var $defsName;
	var $owner;
	var $newDB;
	var $user;

	//Constructor
	function workFlowDefs($defsName = NULL, $db_object = NULL) {
		if($defsName) {
			$result = getWFDefsInfo($db_object, $defsName);
			$this->db = $db_object;
			$this->newDB = false;
			$this->states = $result[0];
			$this->parentID = $result[1];
			$this->owner = $result[2];
			$this->defsName = $defsName;
		} else {
			$this->states = 0;
			$this->db = NULL;
			$this->newDB = true;
			$this->parentID = 0;
			$this->defsName = '';
			$this->owner = '';
		}
	}

	function needNewDB() {
		return $this->newDB;
	}

	function setDbObj($db_object) {
		$this->db = $db_object;
		$this->newDB = false;
	}

	function setUser($user) {
		$this->user = $user;
	}

	//This creates an anonymous & blank node and returns the nodeID.
	function createNode($stateNum, $type = "") {
		$nodeID = addWFNode($this->db, $stateNum, $type);
		if(!$nodeID) {
			return false;
		}
		$parentID = $this->parentID;

		$queryArr = array();
		$queryArr['state'] = (int)$stateNum;
		$queryArr['node_id'] = (int) $nodeID;
		$queryArr['defs_name'] =  $this->defsName;
		$queryArr['owner'] = $this->owner;
		if($this->parentID) {
			$queryArr['parent_id'] = (int)$this->parentID;
		}

		//Get reject node's ID
		$myPrev = 0;
		if($type == 'STATE') {
			if($stateNum == 1) {
				$myPrev = 0;
			} else {
				$myPrev = $this->getLastNodeInState($stateNum - 1);
			}
		} else {
			$myPrev = $this->getLastNodeInState($stateNum);
		}
		$queryArr['prev'] = (int)$myPrev;
		//Get next node's ID
		$myNext = 0;
		if($stateNum < $this->states) {
			$myNext = $this->getStateNode($stateNum + 1);
		}
		$queryArr['next'] = (int)$myNext;
		if(!addNodetoWFDefs($this->db, $queryArr)) {
			return false;
		}

		if(!$myNext) {
			updateTableInfo($this->db,'wf_defs',array('next'=>'id'),array('node_id'=>(int)$nodeID));
		}
		if(!$myPrev) {
			updateTableInfo($this->db,'wf_defs',array('prev'=>'id'),array('node_id'=>(int)$nodeID));
		}

		
		if($type != 'STATE') {
			$nodeName = addWFNodeName($this->db, $nodeID, $parentID, $this->states);
			if(!$nodeName) {
				return false;
			}
		} else {
			$nodeName = "STATE$stateNum";
		}
		$aArr = array(
			'Definition Name'	=> $this->defsName,
			'Owner'				=> $this->owner,
			'State Number'		=> $stateNum,
			'NodeID'			=> $nodeID,
			'Node Name'			=> $nodeName,
			'Reject Node'		=> $myPrev,
			'Accept Node'		=> $myNext
		);
		if($this->parentID) {
			$aArr['Parent ID'] = $this->parentID;
		}
		$aArr['Node Type'] = ($type) ? $type : 'SIGNATURE';
		$auditArr = array();
		foreach($aArr as $key => $value) {
			$auditArr[] = "$key: $value";
		}
		$this->user->audit('Workflow Node Created', implode(', ', $auditArr)); 
		return $nodeID;
	}
	
	//Silly helper function
	function createStateNode() {
		$newState = ++$this->states;
		$nodeID = $this->createNode($newState, 'STATE');
		if(!$nodeID) {
			return false;
		}
		if($newState == 1) {
			if(!updateTableInfo($this->db,'wf_defs',array('parent_id'=>'id'),array('id'=>(int)$nodeID))) {
				return false;
			}
			$this->parentID = $nodeID;
		}
		return $nodeID;
	}

	function createRegNode($stateNum) {
		return $this->createNode($stateNum);
	}

	function getStates() {
		return $this->states;
	}

	function getStateLines() {
		$result = getWFStateLines($this->db, $this->parentID);
		if(!$result) {
			return false;
		}
		return $result;
	}

	function setDefsName($name) {
		$this->defsName = $name;
	}

	function setOwner($uname) {
		$this->owner = $uname;
		if($this->parentID) {
			$aArr = array(
				'Definition Name'	=> $this->defsName,
				'Owner'				=> $uname
			);
			$auditArr = array();
			foreach($aArr as $key => $value) {
				$auditArr[] = "$key: $value";
			}
			$this->user->audit('Workflow Owner Changed', implode(', ', $auditArr)); 
			updateTableInfo($this->db,'wf_defs',array('owner'=>$uname),array('parent_id'=>(int)$this->parentID));
		}
	}

	function getOwner() {
		return $this->owner;
	}

	function getDefsLine($id) {
		return getTableInfo($this->db,'wf_defs',array(),array('id'=>(int)$id),'queryRow');
	}

	function getDefsName() {
		return $this->defsName;
	}

	function getLastNodeInState($stateNum) {
		return getTableInfo($this->db,'wf_defs',array('MAX(id)'),array('state'=>(int)$stateNum,'parent_id'=>(int)$this->parentID),'queryOne');
	}
	
	function getStateNode($stateNum) {
		return getTableInfo($this->db,'wf_defs',array('MIN(id)'),array('state'=>(int)$stateNum,'parent_id'=>(int)$this->parentID),'queryOne');
	}

	function getDefsList() {
		return getTableInfo($this->db,'wf_defs',array('DISTINCT(defs_name)'),array('isDWF <> 1', 'parent_id = id'),'queryCol',array('defs_name'=>'ASC'));
	}
/*	LEAVE THIS HERE AND COMMENTED, WE MAY WANT THIS LATER.
	function deleteWorkflow($defsName) {
		$nodeIDs = getTableInfo ($this->db, 'wf_defs',
			array ('node_id'), array ('defs_name' => $defsName),
			'queryCol');
		deleteTableInfo ($this->db, 'wf_defs',
			array (defs_name' => $defsName));

		$query = 'DELETE FROM wf_nodes WHERE id = ?';
		$p = $this->db->prepare($query);
		foreach($nodeIDs as $myID) {
			$result = $p->execute($myID);
			if(PEAR::isError($result)) {
				return false;
			}
		}
		return true;
	}
*/
	function deleteNode($nodeID) {
		deleteTableInfo($this->db,'wf_defs',array('node_id'=>(int)$nodeID));
		deleteTableInfo($this->db,'wf_nodes',array('id'=>(int)$nodeID));
	}

	function getNodeInfo($nodeID) {
		return getWFNodeInfo($this->db, $nodeID);
	}

	function setNodeName($nodeID, $nodeName) {
		return updateTableInfo($this->db,'wf_nodes',array('node_name'=>$nodeName),array('id'=>(int)$nodeID));
	}

	function setPrev($nodeID, $newPrev) {
		return updateTableInfo($this->db,'wf_defs',array('prev'=>(int)$newPrev),array('node_id'=>(int)$nodeID));
	}

	function setNext($nodeID, $newNext) {
		return updateTableInfo($this->db,'wf_defs',array('next'=>(int)$newNext),array('node_id'=>(int)$nodeID));
	}

	function setNodeType($nodeID, $newType) {
		return updateTableInfo($this->db,'wf_nodes',array('node_type'=>$newType),array('id'=>(int)$nodeID));
	}
	
	function setNodeMessage( $nodeID, $description ){
		return updateTableInfo($this->db,'wf_nodes',array('message'=>$description),array('id'=>(int)$nodeID));
	}
	function setNotesRequired( $nodeID, $value ){
		$dept = $this->db->database_name;
	  $db_doc = getDbObject( "docutron" );
		$queryDoc = "select * from settings where k='wfNotesRequired".$nodeID."' and department='".$dept."'";
		$required = $db_doc->queryRow($queryDoc);
		if (isset($required["value"])) 
		{
			$queryDoc = "update settings set value='".$value."' where k='wfNotesRequired".$nodeID."' and department='".$dept."'";
		}
		else
		{
			$queryDoc = "insert into settings (k,value,department) values ('wfNotesRequired".$nodeID."','".$value."','".$dept."')";
		}
		$required = $db_doc->queryRow($queryDoc);
		return;
	}

	function getWhichUser($nodeID){
		return getTableInfo($this->db,'wf_nodes',array('which_user'),array('id'=>(int)$nodeID),'queryOne');
	}
	
	function setWhichUser($nodeID, $which_user){
		return updateTableInfo($this->db,'wf_nodes',array('which_user'=>$which_user),array('id'=>(int)$nodeID));
	}
	
	function setNodeEmail($nodeID, $email){
		return updateTableInfo($this->db,'wf_nodes',array('email'=>$email),array('id'=>(int)$nodeID));
	}
	
	function setMessageAlert($nodeID,$message_alert) {
		$uArr = array('message_alert' => (int)$message_alert);
		$wArr = array('id' => (int)$nodeID);
		return updateTableInfo($this->db,'wf_nodes',$uArr,$wArr);
	}

	function close() {
		$this->newDB = true;
	}
}
error_reporting(E_ALL & ~E_NOTICE);
?>
