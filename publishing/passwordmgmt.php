<?php
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ( '../classuser.inc');

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ){
        //varables whose content may need to be translated
        $docTitle           = $trans['Change User Permissions'];
        $missingField       = $trans['Missing Field'];
        $badCharinPassword  = $trans['Invalid Character in Password'];
        $noMatchPasswords   = $trans['No Match Password'];
        $password_for_user  = $trans['Password for User'];
        $has_been_set       = $trans['Has Been Set'];     
        $managePasswords    = $trans['Manage User Passwords'];          
        $selectuser         = $trans['Select User'];      
        $newPass            = $trans['New Password'];
        $confirmPass        = $trans['Confirm Password']; 
        $submit             = $trans['Submit'];        

	$user->setSecurity();
    $db_object = $user->getDbObject();
	$db_doc = getDbObject('docutron');

	$uid = isset ($_GET['u']) ? $_GET['u'] : '';
	$usernameTest = isset ($_GET['username']) ? $_GET['username'] : '';
	$admin = isset ($_GET['admin']) ? $_GET['admin'] : '';
	$message = isset ($_GET['message']) ? $_GET['message'] : '';
	$confirm = isset ($_GET['confirm']) ? $_GET['confirm'] : '';
	
	$sArr = array('id','email');
	$wArr = array();
	if(!isSet($DEFS['PORTAL_MDEPS']) || $DEFS['PORTAL_MDEPS'] != 1) {
		$wArr['department'] = $user->db_name;
	}
	$oArr = array('email' => 'ASC');
	$usrArr = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'getAssoc',$oArr);
   echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
   <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
   <title>$docTitle</title>
<script type="text/javascript">
function focusPW() {
	var pwBox = document.getElementById('pwBox1');
	if(pwBox) {
		pwBox.focus();
	}
}
</script>
</head>
   <body class="centered" onload="focusPW()">
ENERGIE;
//if admin has submitted user password changes, error check and behave accordingly
	$error = 0;
   if(isset($_POST['submit'])) {
      if(!$_POST['passwd']||!$_POST['passwd_again']) {   //one of the fields was blank
         $error=1;
         echo<<<ENERGIE
<script type="text/javascript">
    document.onload = parent.mainFrame.window.location = "passwordmgmt.php?message=$missingField&u=$uid";
</script>
ENERGIE;
      }

      //check for use of escape charater in the password
      $invalidPool = "\\ ";
      $test = $_POST['passwd'];
      $length = strlen($test);
      for($t = 0; $t < $length; $t++) {
        $status = strrpos($invalidPool, $test[$t]);
        if($status !== false) {
            $error=1;
            echo<<<ENERGIE
<script type="text/javascript">
    document.onload = parent.mainFrame.window.location = "passwordmgmt.php?message=$badCharinPassword&u=$uid";
</script>
ENERGIE;
        }
      }
      //check if passwords do not match
      if(strcmp($_POST['passwd'],$_POST['passwd_again'])!=0) {
         $error=1;
         echo<<<ENERGIE
<script type="text/javascript">
    document.onload = parent.mainFrame.window.location = "passwordmgmt.php?message=$noMatchPasswords&u=$uid";
</script>
ENERGIE;
      }
      
      //get the user's name from the database based upon the uid passed
	$q = getTableInfo($db_doc,'publish_user',array(),array('id'=>(int)$uid));
      if (PEAR::isError($q)) {
  	echo "$qry<br>";
	die($q->getMessage());
      }

      $user_info=$q->fetchRow();
      $user_to_change=$user_info['email'];

      //process the new password for storage
      $_POST['passwd'] = strip_tags($_POST['passwd']);
      $_POST['passwd'] = md5($_POST['passwd']);
	
      if (!get_magic_quotes_gpc()) {
         $_POST['passwd'] = addslashes($_POST['passwd']);
      }
      //make changes to the database if an error message hasn't been reported yet
		$uArr = array('password' => $_POST['passwd']);
		$wArr = array('id' => $uid);
		updateTableInfo($db_doc,'publish_user',$uArr,$wArr);

      $db_object=$user->getDbObject();
      $user->audit("publishing password changed","User password changed for $user_to_change");
      
echo<<<ENERGIE
<script type="text/javascript">
	document.onload = parent.mainFrame.window.location = "passwordmgmt.php?confirm=$password_for_user $user_to_change $has_been_set"; 
</script>
ENERGIE;
   } else {   //admin has not submitted user password changes
   echo<<<ENERGIE
   <div class="mainDiv">
   <div class="mainTitle">
	<span>$managePasswords</span>
</div>
ENERGIE;
   echo<<<ENERGIE
   <form style='padding:0px' id="getUser" method="POST" action="passwordmgmt.php">
   <table class="inputTable">
   <tr>
   <td class="label"><label for="userSel">$selectuser</label></td>
   <td><select id="userSel" name="users" onchange="location=document.getElementById('getUser').users[document.getElementById('getUser').users.selectedIndex].value;">
ENERGIE;
   if(!$uid||strcmp($uid,"default")==0)   //load the "select" option if select or nothing has been select
      echo "<option selected=\"selected\">$selectuser</option>";

	foreach($usrArr AS $id => $email) {
     	if(strcmp($uid, $id)==0) 
         	echo "<option selected=\"selected\" value=\"passwordmgmt.php?u=$id\">$email</option>\n";
      	else 
        	echo "<option value=\"passwordmgmt.php?u=$id\">$email</option>\n";
	}
	echo "</select></td></tr></table></form>\n";
	if( $confirm != null ) {
		echo "<div class=\"error\">$confirm</div>\n";
	}

   if($uid!=NULL&&strcmp($uid,"default")!=0) {  //a user has been selected from the drop down list
echo<<<ENERGIE
<form action="passwordmgmt.php?u=$uid" method="post">
<table class="inputTable">
	<tr>
	   <td class="label">
		<label for="pwBox1">$newPass</label>
	   </td>
	   <td>
	      <input id="pwBox1" type="password" name="passwd" maxlength="50">
	   </td>
	</tr>
	<tr>
	   <td class="label">
		<label for="pwBox2">$confirmPass</label>
	   </td>
	   <td>
	      <input id="pwBox2" type="password" name="passwd_again" maxlength="50">
	   </td>
	</tr>
</table>
<div><input type="submit" name="submit" value="$submit" /></div>
ENERGIE;
   }
   	if( $message != NULL ) {
		echo "<div style=\"margin-left: auto; margin-right: auto\" class=\"error\">$message</div>\n";
   	}
	echo "</form>\n";
   echo"</div></body><html>";
	setSessionUser($user);
  }
} else {
	logUserOut();
}
?>
