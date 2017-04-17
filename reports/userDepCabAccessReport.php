<?php
/*-----------------------------------------
 * userAccess.php
 * This page is accessed by:
 *  -choosing the "Change Permissions" item
 *  on the "User Functions" menu in settings
 *  -upon creating a new user (after giving
 *  a name and password, you are sent here)
 *---------------------------------------*/
include_once "../lib/mime.php";
include_once '../classuser.inc';
include_once '../groups/groups.php';
require_once '../check_login.php';

$db_doc = getDbObject( 'docutron' );
$select="select username,db_name,arb_department,real_department from users,db_list,licenses where db_list_id=list_id and db_name=real_department order by username,arb_department";
$results = $db_doc->queryAll($select);
	if(!file_exists("{$DEFS['TMP_DIR']}/userDepCabAccess")) {
		mkdir("{$DEFS['TMP_DIR']}/userDepCabAccess");
		allowWebWrite ("{$DEFS['TMP_DIR']}/userDepCabAccess", $DEFS, 0777);
	} else {
		if (file_exists("{$DEFS['TMP_DIR']}/userDepCabAccess/userDepCabAccess.csv"))
		unlink("{$DEFS['TMP_DIR']}/userDepCabAccess/userDepCabAccess.csv");
	}
	$fp=fopen("{$DEFS['TMP_DIR']}/userDepCabAccess/userDepCabAccess.csv","w");
fwrite($fp,"User,Dept,Cabinet,Group,Access\n");

foreach ($results as $result) {
	$db_dept = getDbObject( $result['db_name'] );
	$query = "SELECT arb_groupname FROM groups,users_in_group,access WHERE access.username='".$result['username']."' AND access.uid=users_in_group.uid AND groups.id=users_in_group.group_id ORDER BY arb_groupname ASC";
	$rows = $db_dept->queryAll($query);
	foreach ($rows as $row) {
		//get cabinets and show access by group
		$query = "SELECT departmentname,real_groupname,group_access.access,arb_groupname FROM group_access,groups,departments,users_in_group,access WHERE groups.arb_groupname='".$row['arb_groupname']."' and departments.deleted=0 and groups.id=group_access.group_id AND DepartmentID=cabID AND groups.id=users_in_group.group_id AND users_in_group.uid=access.uid AND group_access.access!='none' AND access.username='".$result['username']."' ORDER BY arb_groupname ASC";
		$results = $db_dept->queryAll($query);
		foreach ($results as $rescab) {
			fwrite($fp,$result['username'].",".$result['arb_department'].",".$rescab['departmentname'].",".$row['arb_groupname'].",".$rescab['access']."\n");
		}
	}
	$query = "SELECT access FROM access WHERE username='".$result['username']."' ";
	$access = $db_dept->queryAll($query);
	$rights = unserialize(base64_decode($access[0]['access']));
	$query = 'select real_name,departmentname from departments where deleted=0 ORDER BY departmentname ASC';
	$cabinets = $db_dept->queryAll($query);
	foreach($cabinets as $cabinet) {
		$cabname = $cabinet['real_name'];
		$dispname=$cabinet['departmentname'];
		if (isset ($rights[$cabname])) {
			$cabRights = $rights[$cabname];
		} else {
			$cabRights = '';
		}

		if ($cabRights == 'rw'|| $cabRights == 'ro') {
			fwrite($fp,$result['username'].",".$result['arb_department'].",".$dispname.",,".$cabRights."\n");
		} else {
		}
	}
	$db_dept->disconnect();
}
fclose($fp);
downloadFile("{$DEFS['TMP_DIR']}/userDepCabAccess","userDepCabAccess.csv", true, true);
/*
Example Report - User Access to Cabinets and Departments
User	Dept	Cabinet	Group 	Access
sbaril01	Alliance	Activty	Agency	rw
sbaril01	Alliance	Carrier	Agency	rw
sbaril01	Alliance	Policy	Agency	ro
sbaril01	Home office	Acctg Vendor Invoice	Agency	rw
sbaril01	Home office	Ops Compliance	Agency	ro
				
OR				
				
Dept	User	Cabinet	Group	Access
Alliance	sbaril01	Activity	Agency	rw
Alliance	sbaril01	Carrier	Agency	rw
Alliance	sbaril01	Policy	Agency	ro
Home Office	sbaril01	Acctg Vendor Invoice	Agency	rw
Home Office	sbaril01	Ops Compliance	Agency	ro
*/
?>