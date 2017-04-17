<?php

function filterDepartments($tempTable,$depAdminList,$db_dept) {
	$uArr = array('disable' => 0);
	foreach($depAdminList AS $dept) {
		$wArr = array('department' => $dept);
		updateTableInfo($db_dept,$tempTable,$uArr,$wArr);
	}
}

function filterGreaterUsers( $username, &$db_doc, &$depAdminList ) {
	$whereArr = array ('db_list_id=list_id');
	if ($username != 'admin') {
		$whereArr[] = "priv!='D'";
	}
	$allUsers = getTableInfo ($db_doc, array('users', 'db_list'),
		array ('username', 'db_name'), 
		$whereArr, 'queryAll');
	$userList = array ();
	
	foreach ($allUsers as $row) {
		if (in_array ($row['db_name'], $depAdminList)) {
			$userList[] = $row['username'];
		}
	}
	$userList[] = $username;
	$userList = array_unique($userList);
	sort($userList);
	return $userList;
}
?>
