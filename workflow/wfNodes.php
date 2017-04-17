<?php
require_once '../lib/utility.php';
error_reporting(E_ALL);
class wfNodes {
	var $db;
	function wfNodes($db_object) {
		$this->db = $db_object;
	}
	
	function getGroupsInList($listID) {
		return getTableInfo($this->db,'group_list',array('groupname'),array('list_id'=>(int)$listID),'queryCol');
	}

	function getUsersInList($listID) {
		return getTableInfo($this->db,'user_list',array('username'),array('list_id'=>(int)$listID),'queryCol');
	}
	
	function setList($type, $nodeID, $listID) {
		return updateTableInfo($this->db,'wf_nodes',array($type.'_list_id'=>(int)$listID),array('id'=>(int)$nodeID));
	}

	function deleteValueList($valueID) {
		deleteTableInfo($this->db,'wf_value_list',array('value_list_id'=>(int)$valueID));
		return updateTableInfo($this->db,'wf_nodes',array('value_list_id'=>0),array('value_list_id'=>(int)$valueID));
	}

	function getValueID($nodeID) {
		return getTableInfo($this->db,'wf_nodes',array('value_list_id'),array('id'=>(int)$nodeID),'queryOne');
	}
	
	function setValueID( $nodeID, $valueID ){
		if(!$valueID) {
			$valueID = 0;
		}
		return updateTableInfo($this->db,'wf_nodes',array('value_list_id'=>(int)$valueID),array('id'=>(int)$nodeID));
	}

	function addToList($type, $listID, $name)
	{
		if($type == 'group') {
			return addToWFGroupList($this->db, $listID, $name);
		}
		return addToWFUserList($this->db, $listID, $name);
	}
	
	function removeFromList($type, $listID, $name)
	{
		if($type == 'group') {
			$whereArr = array('list_id'=>(int)$listID,'groupname'=>$name);
			deleteTableInfo($this->db,'group_list',$whereArr);
			return getTableInfo($this->db,'group_list',array('COUNT(*)'),array('list_id'=>(int)$listID),'queryOne');
		}
		$whereArr = array('list_id'=>(int)$listID,'username'=>$name);
		deleteTableInfo($this->db,'user_list',$whereArr);
		return getTableInfo($this->db,'user_list',array('COUNT(*)'),array('list_id'=>(int)$listID),'queryOne');
	}
	///////////////////////////////////////////

	function deleteList($type, $listID) {
		$whereArr = array('list_id'=>(int)$listID);
		if($type == 'group') {
			return deleteTableInfo($this->db,'group_list',$whereArr);
		}
		return deleteTableInfo($this->db,'user_list',$whereArr);
	}
}
error_reporting(E_ALL & ~E_NOTICE);
?>
