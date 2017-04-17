<?php
// $Id: editGroups.php 14193 2011-01-04 15:20:38Z acavedon $

require_once '../check_login.php';
require_once '../groups/groups.php';

if($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin() ) {
	if (isset ($_GET['display'])) {
		$display = $_GET['display'];
	} else {
		$display = '';
	}
	$editGroup = '';
	$username = '';
	if($display) {
		$list = getTableInfo($db_object, 'access' ,array('username'), array(),
				'queryCol', array ('username' => 'ASC'));
		$oldList = array ();
		foreach ($list as $name) {
			if ($user->greaterThanUser($name)) {
				$oldList[] = $name;
			}
		}
		$list = $oldList;
		$selectLabel = "Select User";
		$URL = '../groups/groupActions.php?editGroups=1';
		$type = 'uid';
		if( isSet($_GET['username']) ) {
			$username = $_GET['username'];
			$guest = $_GET['guest'];
			$admin = $_GET['admin'];
			$redirect = "../secure/userAccess.php?username=$username&admin=$admin&guest=$guest";
		} else {
			$redirect = "";
            }
	} else {
		$redirect = "";
		$list = getRealGroupNames($db_object);
		$selectLabel = "Select Group";
		$URL = '../groups/groupActions.php?editUsers=1';
		$type = 'group_id';
		$editGroup = "editGroupName";
        }
echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Edit Group</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="groups.js"></script>
  <script type="text/javascript">
	var postURL = '../groups/groupActions.php?editGroupPriv=1&type=$type';
	var redirect = '$redirect';
	var editGroupName = '$editGroup';
  </script>
 </head>
ENERGIE;
	if( $username ) {
		echo "<body class=\"centered\" onload=\"makeSelect('$URL');\">\n";
	} else {
		echo "<body class=\"centered\">\n";
	}
echo<<<ENERGIE
<div class="mainDiv">
<div class="mainTitle"><span>Edit Group</span></div>
<div style="padding-top:5px;padding-bottom:5px">$selectLabel
 <select id="permissionSelect" onchange="makeSelect('$URL')">
  <option value="default">$selectLabel</option>\n
ENERGIE;
	foreach($list AS $key => $value ) {
		if($display) {
			if( $value == $username ) {
				echo "<option selected value='$value'>$value</option>\n";	
			} else {
				echo "<option value='$value'>$value</option>\n";	
			}
		} else {
			echo "<option value='$key'>$value</option>\n";	
			}
        }
echo<<<ENERGIE
 </select>
	</div>
<div id="editGroupPermissions"></div>
	</div>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {//we want to log them out
	logUserOut();
}
?>
