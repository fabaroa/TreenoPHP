<?php
class groups
{
	var $members;
	var $db;

	/////////////////////////////////////////////
	function groups($db) {
		$this->members = array();
		$this->db = $db;
	}
	/////////////////////////////////////////////
	function getMembers( $groupName ) {
		$userList = array();
		$userList = getUsersFromGroup($this->db,$groupName);
		return( $userList );
	}
	/////////////////////////////////////////////
	function editGroupMembers( $group, $groupList ) {
		$group_id = getTableInfo($this->db,'groups',array('id'),array('real_groupname'=>$group),'queryOne');	
		$whereArr = array('group_id'=>(int)$group_id);
		deleteTableInfo($this->db,'users_in_group',$whereArr);
		$this->insertUsersInGroup($groupList,$group_id);
	}
	/////////////////////////////////////////////
	function addGroup( $newGroup, $usrArr ) {
		$realGroupName = $this->nextGroupName();
		$id = (int)str_replace( "group", "", $realGroupName );
		$insertArr = array('real_groupname'=>$realGroupName,'arb_groupname'=>$newGroup);
		$res = $this->db->extended->autoExecute('groups',$insertArr);
		dbErr($res);
		$this->insertUsersInGroup($usrArr,$id);
		return $realGroupName;
	}
	/////////////////////////////////////////////
	function nextGroupName() {
		$maxID = getTableInfo($this->db,'groups',array('MAX(id)'),array(),'queryOne');
		if( $maxID == NULL )
			return( 'group1' );
		else {
			$newID = $maxID + 1;
			return( 'group'.$newID );
		}
	}
	/////////////////////////////////////////////
	function removeGroup( $groupName, $department ) {
		$id = getTableInfo($this->db,'groups',array('id'),array('real_groupname'=>$groupName),'queryOne');
		$whereArr = array('group_id'=>(int)$id);
		deleteTableInfo($this->db,'users_in_group',$whereArr);

		$whereArr = array('real_groupname'=>$groupName);
		deleteTableInfo($this->db,'groups',$whereArr);
	}
	/////////////////////////////////////////////
	function editGroupName( $groupName, $newGroupName ) {
		$updateArr = array('arb_groupname'=>$newGroupName);
		$whereArr = array('real_groupname'=>$groupName);
		updateTableInfo($this->db,'groups',$updateArr,$whereArr);
	}
	/////////////////////////////////////////////
	function getGroups() {
		$groupInfo = getRealGroupNames( $this->db);
		return( $groupInfo );	
	}
	/////////////////////////////////////////////
	function checkGroup( $groupName ) {
		$count = getTableInfo($this->db,'groups',array('COUNT(*)'),array('arb_groupname'=>$groupName),'queryOne');
		return( $count );	
	}
	/////////////////////////////////////////////
	function insertUsersInGroup($userList,$group_id) {
		$insertArr = array();
		$uidList = getTableInfo($this->db, 'access', array('username', 'uid'), array(), 'getAssoc');
		foreach($userList AS $username) {
			$insertArr[] = array(
							"group_id"	=>	(int)$group_id,
							"uid"		=>	(int)$uidList[$username]
								);
		}
		foreach($insertArr AS $groupInfo) {
			$this->db->extended->autoExecute("users_in_group",$groupInfo);
		}
	}
}
?>
