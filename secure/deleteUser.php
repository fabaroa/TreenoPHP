<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/padding.php';
include_once '../db/db_engine.php';

if($logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin()) {
    //variables that may be have to be translated
    $docTitle         = $trans['Delete a User'];
    $selectuser       = $trans['Select User']; 
    $delete           = $trans['Delete'];        
    $_user            = $trans['User'];
    $deleted          = $trans['Deleted']; 
    $noSecurityAccess = $trans['No Security Access'];
    $db_object = $user->getDbObject();
	$uid = isset ($_GET['user']) ? $_GET['user'] : '';
	$userID = isset ($_GET['userID']) ? $_GET['userID'] : '';
	$message = isset ($_GET['mess']) ? $_GET['mess'] : '';

echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
 <title>$docTitle</title>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle">
<span>$docTitle</span>
</div>
ENERGIE;
if($uid != 1 && $userID != 1){
if($userID == NULL){
echo<<<ENERGIE
<form id="getUser" method="post" action="deleteUser.php">
<table class="inputTable">
<tr>
<td class="label">
<label for="userSel">$selectuser</label></td>
<td><select name="users" id="userSel" onchange="location=document.getElementById('getUser').users[document.getElementById('getUser').users.selectedIndex].value">
ENERGIE;
	if($uid != NULL) {
  		$selectUser = getTableInfo($db_object,'access',array(),array('uid'=>(int)$uid));
		$row = $selectUser->fetchRow();
		echo "<option value=\"deleteUser.php?user=".$row['uid']."\">".$row['username']."</option>\n";
	} else
		echo "<option>$selectuser</option>\n";
	$user->getUserSortInfo( $userArr, $userIDs, 'uid');
	foreach( $userArr as $usernam ) {
		//if user is "admin" be able to delete other dep admins
		if( $user->isSuperUser() ) {
			if($user->isDepAdmin() && $usernam != $user->getUsername() && $usernam != "admin" ) 
				echo "<option value=\"deleteUser.php?user={$userIDs[$usernam]}\">$usernam</option>\n";
		} else {
			if($user->isDepAdmin()&&$usernam!=$user->getUsername()&&$usernam!="admin"&&!$user->IsUserDepAdmin($usernam))
				echo "<option value=\"deleteUser.php?user={$userIDs[$usernam]}\">$usernam</option>\n";
		}
	}
	echo "</select>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
	
	if($uid != NULL) {
		echo "<form method=\"post\" action=\"deleteUser.php?userID=$uid\">\n";
		echo "<div style=\"margin-right: auto; margin-left: auto; text-align: center\">\n";
		echo "<input type=\"submit\" name=\"Delete\" value=\"$delete\"/></div>\n";
		echo "</form>";
	} elseif(  $message != null ) {
		echo "<div class=\"error\" style=\"margin-right: auto; margin-left: auto; text-align: center\">\n";
		echo "$message</div>\n";
	} else {	/* do nothing */ }
//	echo "</body></html>";
} else{
  		$user1 = getTableInfo($db_object,'access',array(),array('uid'=>(int)$userID));

		$name = $user1->fetchRow();
		$usernameDel = $name['username'];
		$sttArr = new Usrsettings( $usernameDel, $user->db_name  );
		$sttArr->removeKey($usernameDel);
		$whereArr = array('username'=>$usernameDel);
		deleteTableInfo($db_object,'access',$whereArr);
		$db_doc = getDbObject('docutron');
		$updateArr = array('username'=> 'admin');
		$whereArr = array('username'=> $usernameDel, 'department' => $user->db_name);
		updateTableInfo($db_doc,'wf_todo',$updateArr,$whereArr);

		$uid =getTableInfo($db_object,'access',array('uid'),array('username'=>$usernameDel),'queryOne');				
		$whereArr = array('uid'=>(int)$uid);
		deleteTableInfo($db_object,'users_in_group',$whereArr);

		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->get('username', $usernameDel);
	
		if(sizeof($DO_user->departments) == 1) {
			$DO_user->delete();
			$deletedMessage = "User $usernameDel Deleted from System";
		} else {
			$DO_user->deleteDeptFromUser($user->db_name);	
			if($DO_user->defaultDept == $user->db_name) {
				foreach($DO_user->departments AS $dep => $p) {
					if($DO_user->defaultDept != $dep ) {
						$DO_user->changeDepartmentAccess($dep, $DO_user->departments[$dep], 1);
						break;
					}
				}
			}
			$deletedMessage = "User $usernameDel Deleted from Department";
		}
		
		$whereArr = array('username'=>$usernameDel);
		deleteTableInfo($db_object,'user_list',$whereArr);
		deleteTableInfo($db_doc,'user_security',$whereArr);
		deleteTableInfo($db_doc,'user_polls',$whereArr);
		//remove all the deleted user's settings
		$whereArr = array('username'=>$usernameDel,'department'=>$user->db_name);
		deleteTableInfo($db_doc,'user_settings',$whereArr);
		$curInboxPath = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/".$usernameDel;
		$destInboxPath = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/admin/".$usernameDel;
		$inboxList = array();
		if( file_exists( $destInboxPath ) ) {
			$handle = opendir($curInboxPath);
			while (false !== ($file = readdir($handle))) {
            	if($file != "." && $file != "..") {
               		pad( $curInboxPath.$file."/", $user );
               		$inboxList[] = $file;
            	}
    		}
			closedir( $handle );
			
			foreach( $inboxList AS $fname ) {
				$newName = $fname;
				$i=1;
				while( file_exists( $destInboxPath."/".$newName) ) {		
					$newName = $i."-".$fname;
					$i++;
				}
				rename( $curInboxPath."/".$fname, $destInboxPath."/".$newName );
			}
			rmdir( $curInboxPath );
		} else {
			if( is_dir($curInboxPath) ) {
				@rename( $curInboxPath, $destInboxPath );
			}
		}
		$user->audit('user deleted', $deletedMessage);
echo<<<ENERGIE
<script>
	document.onload = parent.mainFrame.window.location = "deleteUser.php?mess=$deletedMessage"; 
</script>
ENERGIE;
	}
echo"</div></body></html>";
} else {
echo<<<ENERGIE
        <html>
         <body bgcolor="#FFFFFF">
            <br>$noSecurityAccess</br>
         </body>
        </html>
ENERGIE;
}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
