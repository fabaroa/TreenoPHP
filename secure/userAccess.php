<?php
// $Id: userAccess.php 14234 2011-01-04 16:37:48Z acavedon $

/*-----------------------------------------
 * userAccess.php
 * This page is accessed by:
 *  -choosing the "Change Permissions" item
 *  on the "User Functions" menu in settings
 *  -upon creating a new user (after giving
 *  a name and password, you are sent here)
 *---------------------------------------*/
require_once '../check_login.php';

if ($logged_in and $user->username and $user->isDepAdmin()) {
	//variables whose contents may have to be translated 
	$noCabsMessage = $trans['noCabsMessage'];
	$tableTitle = $trans['Change User Permissions'];
	$roCabsMessage = $trans['roCabinetsMessage'];
	$selectUser = $trans['Select User'];
	$cabLabel = $trans['Cabinet'];
	$r_w = $trans['read_write'];
	$r_o = $trans['read_only'];
	$none = $trans['no_permissions'];
	$submit = $trans['Submit'];
	$adminLabel = $trans['Admin'];

	$user->setSecurity();
	$db_object = $user->getDbObject();
	
	if (isset ($_GET['u'])) {
		$uid = $_GET['u'];
	} else {
		$uid = '';
	}
	
	if (isset ($_GET['username'])) {
		$usernameTest = $_GET['username'];
		$uid = getTableInfo($db_object,'access',array('uid'),array('username'=>$usernameTest),'queryOne');
	} else {
		$usernameTest = '';
	}
	
	if (isset ($_GET['admin'])) {
		$admin = $_GET['admin'];
	} else {
		$admin = '';
	}
	
	if (isset ($_GET['mess'])) {
		$message = $_GET['mess'];
	} else {
		$message = '';
	}
	
	if (isset ($_GET['guest']) && $_GET['guest']==1) {
		$guest = $_GET['guest'];
	} else {
		$guest = '';
		$users = getTableInfo($db_object,'access', array(), array (), 'query', array
				('username' => 'ASC'));
		$userArr = array ();
		$uidArr = array ();
		while ($result = $users->fetchRow()) {
			$userArr[] = $result['username'];
			$uidArr[$result['username']] = $result['uid'];
		}
	}

	if ($uid) {
		$accessInfo = getTableInfo($db_object,'access',array(),array('uid'=>(int)$uid));
		$access = $accessInfo->fetchRow();
		$rights = unserialize(base64_decode($access['access']));	
	}
	
	echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<title>$tableTitle</title>
<script type="text/javascript">
function selectRights() {
	var checkList = document.getElementsByTagName('input');
        for(var i=0;i<checkList.length;i++) {
                if(checkList[i].type == 'radio' && checkList[i].value == this.id) {
                        checkList[i].checked = true;
                }
        }
}

function headerMOver(sender) {
	var currObj = document.getElementById('rwmsg');
	while (currObj.hasChildNodes()) {
		currObj.removeChild(currObj.firstChild);
	}
	var txtNode;
	if (sender == 'rw')
		txtNode = document.createTextNode('give all read/write permissions');
	else if (sender == 'ro')
		txtNode = document.createTextNode('give all read only permissions');
	else
		txtNode = document.createTextNode('give all no permissions');
	 
	currObj.appendChild(txtNode);
}

function headerMOut() {
	var currObj = document.getElementById('rwmsg');
	while (currObj.hasChildNodes()) {
		currObj.removeChild(currObj.firstChild);
	}
	currObj.appendChild(document.createTextNode("\u00A0"));
}

function mOver() {
	this.style.cursor = 'pointer';
	this.style.backgroundColor = '#888888';
	headerMOver(this.id);
}

function mOut(type) {
	this.style.backgroundColor = '#ffffff';
	headerMOut(this.id);
}

function userSelChange() {
	var getUsers = document.getElementById('getUser').users;
	window.location = getUsers[getUsers.selectedIndex].value;
}
function registerEvents() {
	var currRow = document.getElementById('rw');
	if (currRow) {
		currRow.onmouseover = mOver;
		currRow.onmouseout = mOut;
		currRow.onclick = selectRights;
	}
	currRow = document.getElementById('ro');
	if (currRow) {
		currRow.onmouseover = mOver;
		currRow.onmouseout = mOut;
		currRow.onclick = selectRights;
	}
	currRow = document.getElementById('none');
	if (currRow) {
		currRow.onmouseover = mOver;
		currRow.onmouseout = mOut;
		currRow.onclick = selectRights;
	}
}
</script>
<style type="text/css">
div.error2 {
	font-weight: bold;
}
div.error {
	margin-left: auto;
	margin-right: auto;
}
</style>
</head>
<body class="centered" onload="registerEvents()">
HTML;
	if ($user->noCabinets()) {
		echo '<div class="error error2">';
		//if there are no cabinets currently in the database:
		echo $roCabsMessage;
		echo '</div>';
	} else {
		echo<<<HTML
<div class="mainDiv">
<div class="mainTitle">
<span>$tableTitle</span>
</div>
HTML;
		if (!$guest) {
			echo<<<HTML
<form id="getUser" method="post" action="userAccess.php">
<table class="inputTable">
<tr>
<td class="label">
	<label for="usersSel">$selectUser</label>
</td>
<td>
<select id="usersSel" name="users" onchange="userSelChange()">
HTML;
			if (!$uid) {
				echo '<option selected="selected" value="default">'.$selectUser.'</option>';
			}

			foreach ($userArr as $uname) {
				$id = $uidArr[$uname];
				if ($user->greaterThanUser($uname) and $user->username != $uname ) {//and !$user->isUserDepAdmin($uname, $user->db_name)) {
					if ($uid == $id) {
						echo '<option selected="selected" value="userAccess.php?u='.$id.'">'.$uname.'</option>';
					} else {
						echo '<option value="userAccess.php?u='.$id.'">'.$uname.'</option>';
					}
				}
			}
			echo<<<HTML
</select>
</td>
</tr>
</table>
</form>
HTML;
		}
		//print confirmation message if updated a user
		if ($message) {
			echo '<div class="error">'.$message.'</div>';
		}

		if ($usernameTest) {
			echo '<form id="changePermissions" method="post" ' .
					'action="updatePermissions.php?username='.$usernameTest .
					'&amp;guest='.$guest.'">';
		} else
			echo '<form id="changePermissions" method="post" ' .
					'action="updatePermissions.php?u='.$uid.'">';
		

		if ($uid or $usernameTest) {
			echo<<<HTML
 <div class="inputForm">
  <table>
   <tr>
    <th>$cabLabel</th>
    <th id="rw">$r_w</th>
    <th id="ro">$r_o</th>
    <th id="none">$none</td>
   </tr>
HTML;

			foreach($user->cabArr as $cabname => $dispname) {
				if (isset ($rights[$cabname])) {
					$cabRights = $rights[$cabname];
				} else {
					$cabRights = '';
				}
				$status1 = '';
				$status2 = '';
				$status3 = '';
				if ($cabRights == 'rw') {
					$status1 = 'checked="checked"';
				} elseif ($cabRights == 'ro') {
					$status2 = 'checked="checked"';
				} else {
					$status3 = 'checked="checked"';
				}
				echo<<<HTML
   <tr>
    <td>$dispname</td>
    <td><input type="radio" value="rw" $status1 name="$cabname" /></td>
    <td><input type="radio" value="ro" $status2 name="$cabname" /></td>
    <td><input type="radio" value="none" $status3 name="$cabname" /></td>
   </tr>
HTML;
				$status1 = '';
				$status2 = '';
				$status3 = '';
			}
			echo<<<HTML
  </table>
 </div>
 <div>
  <div style="float: right">
   <input type="submit" name="Update" value="Save" />
  </div>
  <div style="text-align: center">
   <span id="rwmsg">&nbsp;</span>
  </div>
 </form>
</div>
HTML;
		}
		echo '</div>';
	}
	echo '</body></html>';
	setSessionUser($user);

} else {
	logUserOut();
}
?>
