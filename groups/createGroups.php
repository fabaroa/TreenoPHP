<?php
// $Id: createGroups.php 14192 2011-01-04 15:19:40Z acavedon $

require_once '../check_login.php';
require_once 'groups.php';

if($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin() )
{
	$success = false;
	$userList = getTableInfo($db_object,'access',array(),array(),'query',array('username'=>'ASC'));
	while( $result = $userList->fetchRow() )
		$unamelist[] = $result['username'];

	$mess = '';
	$invalid = false;
	if( isSet( $_POST['group'] )) {
		$groupObj = new groups($db_object);
		$newGroup = trim( $_POST['group'] );

		$pool = $user->characters(4);
		for($i=0;$i<strlen( $newGroup );$i++) {
			$status = strrpos( $pool, $newGroup{$i} );
			if($status === false) {
				$invalid = true;
				break;
			}
		}

		if( $invalid )
			$mess = "Invalid Character";
		elseif( $newGroup == NULL )
			$mess = "Must enter group name";
		elseif( $groupObj->checkGroup( $newGroup ) )
			$mess = "Group Already Exists";
		else {
			$groupList = array();
			for($i=0;$i<sizeof($unamelist);$i++) {
				$uname = $unamelist[$i];
				if( $_POST[$uname] == "yes" )
					$groupList[] = $uname;
			}
			$realGroupName = $groupObj->addGroup( $newGroup, $groupList );
			$auditStr = "Group: $newGroup, Users: ".implode(', ', $groupList);
			$user->audit('Group added', $auditStr);
			$mess = "Group successfully added";
			$success = true;
		}
	}

	if($success) {
		echo<<<HTML
		<script type="text/javascript">
			top.mainFrame.window.location = '../groups/createGroupAccess.php?display=1&group=$realGroupName';
		</script>
HTML;
	} else {
		echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Create New Group</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
  <script type="text/javascript">
  var redirect = '../groups/createGroupAccess.php?display=1&group=';
   function selectRights(type) {
	var checkList = document.getElementsByTagName('input');
        for(var i=0;i<checkList.length;i++) {
                if(checkList[i].type == 'radio' && checkList[i].value == type) {
                        checkList[i].checked = true;
                }
        }
   }
  </script>
 </head>
 <body onload="document.getElementById('groupBox').focus()">
 <div class="mainDiv">
 <div class="mainTitle">
 <span>Create New Group</span>
 </div>
  <form id="addGroup" method="post" action="createGroups.php">
  <table class="inputTable">
    <tr>
     <td class="label">
	 <label for="groupBox">Group Name</label>
	 </td>
	 <td>
	 <input type="text" id="groupBox" name="group"/></td>
    </tr>
</table>
<div class="inputForm">
<table>
<tr>
<th>Username</th>
<th style="cursor: pointer"
	onmouseover="this.style.backgroundColor='#888888'"
	onmouseout="this.style.backgroundColor='#ffffff'"
	onclick="selectRights('yes')"
>Grant Access</th>
<th style="cursor: pointer"
	onmouseover="this.style.backgroundColor='#888888'"
	onmouseout="this.style.backgroundColor='#ffffff'"
	onclick="selectRights('no')"
>Deny Access</th>
</tr>
ENERGIE;
		for($i=0;$i<sizeof($unamelist);$i++)
		{
			$uname = $unamelist[$i];
			echo " <tr>\n";
			echo "  <td>$uname</td>\n";
			echo "  <td><input type=\"radio\" name=\"$uname\" value=\"yes\"/></td>\n";
			echo "  <td><input type=\"radio\" name=\"$uname\" value=\"no\" checked=\"checked\"/></td>\n";
			echo " </tr>\n";
		}
		echo<<<ENERGIE
	</table>
	</div>
    <div>
	<div style="float: right">
	  <input type="submit" name="B1" value="Save"/>
	</div>
     <div class="error">

ENERGIE;
		if($mess) {
			echo $mess;
		} else {
			echo '&nbsp;';
		}

		echo<<<ENERGIE
	</div>
	 </div>
  </form>
  </div>
 </body>
</html>
ENERGIE;

	}
	setSessionUser($user);
}
else
{//we want to log them out
echo<<<ENERGIE
<html>
 <body bgcolor="#FFFFFF">
  <script type="text/javascript">
   document.onload = top.window.location = "../logout.php";
  </script>
 </body>
</html>
ENERGIE;
}
?>
