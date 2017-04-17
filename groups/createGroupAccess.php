<?php
// $Id: createGroupAccess.php 14191 2011-01-04 15:17:42Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin()) {
	if(!empty($_GET['group'])) {
		$group = $_GET['group'];
	} else {
		$group = '';
	}
	if(!empty($_GET['display'])) {
		$display = $_GET['display'];
	} else {
		$display = '';
	}
	$tableTitle			= 'Group Cabinet Permissions'; 
	$list = array();
	$arb = array();
	if( $display ) {
		$selectLabel		= 'Choose Group';
		$arb = getRealGroupNames($db_object);
		uasort($arb,'strnatcasecmp');
		$name = 'Cabinet';
		$type = 'group_id';
		$URL = '../groups/groupActions.php?groupPerm=1';
	} else {
		$selectLabel		= $trans['Choose Cabinet'];
		$user->setSecurity();
		$arb = $user->cabArr;
		$name = 'Group';
		$type = 'cabID';
		$URL = '../groups/groupActions.php?cabPerm=1';
	}

echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="groups.js"></script>
<script type="text/javascript">
	var postURL = '../groups/groupActions.php?createGroup=1&type=$type';
	var redirect = '';
	var editGroupName = '';
</script>
<title>$tableTitle</title>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle"><span>$tableTitle</span></div>
<div style="padding-top:5px;padding-bottom:5px">$selectLabel
 <select id="permissionSelect" onchange="makeSelect('$URL')">
ENERGIE;
	if(!($display == 1 and $group)) {
		echo '<option value="default">'.$selectLabel.'</option>'."\n";
	}

	foreach( $arb AS $key => $value ) {
		if($display == 1 and $group == $key) {
			echo '<option value="'.$key.'" selected>'.$value.'</option>';
			 echo "<script type=\"text/javascript\">makeSelect('$URL')</script>";
		} else {
			echo "<option value='$key'>$value</option>\n";
		}
	}
	echo '</select>';
echo<<<ENERGIE
</div>
<div id="editGroupPermissions"></div>
</div>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
