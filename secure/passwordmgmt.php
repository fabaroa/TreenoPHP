<?php
// $Id: passwordmgmt.php 14634 2012-01-09 14:17:19Z cz $

include_once '../db/db_common.php';
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/settingsFuncs.php';
include_once '../lib/passwordSettingsFuncs.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ){
        //varables whose content may need to be translated
        $docTitle           = $trans['Change User Permissions'];
        $missingField       = $trans['Missing Field'];
        $badCharinPassword  = $trans['Invalid Character in Password'];
        $noMatchPasswords   = $trans['No Match Password'];
        $password_for_user  = $trans['Password for User'];
        $has_been_set       = $trans['Has Been Set'];     
        $managePasswords    = $trans['Manage Passwords'];          
        $selectuser         = $trans['Select User'];      
        $newPass            = $trans['New Password'];
        $confirmPass        = $trans['Confirm Password']; 
        //$submit             = $trans['Submit'];
        $invalidPassword	= '';        

	$user->setSecurity();
        $db_object = $user->getDbObject();

	$uid = isset ($_GET['u']) ? $_GET['u'] : '';
	$usernameTest = isset ($_GET['username']) ? $_GET['username'] : '';
	$admin = isset ($_GET['admin']) ? $_GET['admin'] : '';
	$message = isset ($_GET['message']) ? $_GET['message'] : '';
	$confirm = isset ($_GET['confirm']) ? $_GET['confirm'] : '';
	
	$user->getUserSortInfo( $usrArr, $uidArr, 'uid');
//get the doctype and head tag	
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
<script type="text/javascript" src="../lib/settings.js"></script>
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
      else {
      	$pass = $_POST['passwd'];
        //validate the passwords//
	    if(!checkXMLPasswordSettings($pass, $user->db_name))
	    { 
		      //get the user's name from the database based upon the uid passed
		      
			$q = getTableInfo($db_object,'access',array(),array('uid'=>(int)$uid));
		      if (PEAR::isError($q)) {

		  	echo "$qry<br>";
			die($q->getMessage());
		      }
		      	  	
		
		      $user_info=$q->fetchRow();
		      $user_to_change=$user_info['username'];
	    		//check if the dept requires frequent password change 
				if (getdbType() == 'mssql') {
					// mssql
					$sArr = array('min(cast(value as varchar(30)))');
				} else {
					// mysql & pgsql
					$sArr = array('min(value)');
				}
		      	$forcePassword = getTableInfo($db_doc, 'settings', $sArr,array("k='forcePassword' AND department='".$user->db_name."'"), 'queryOne');
		      	$user_settings = new Usrsettings($user_to_change, $user->db_name);
		      	if($forcePassword > 0)
		     	{	
				$sql = "SELECT date_add(CURDATE(), INTERVAL ".$forcePassword." DAY)";
				$next_update = $db_doc->queryOne($sql);
				$user_settings->set('next_password_update', $next_update);
				}		
		      
		      //process the new password for storage
		      $_POST['passwd'] = strip_tags($_POST['passwd']);
		      $_POST['passwd'] = md5($_POST['passwd']);
			
		      if (!get_magic_quotes_gpc()) {
		         $_POST['passwd'] = addslashes($_POST['passwd']);
		      }
		      //make changes to the database if an error message hasn't been reported yet
		      if($error == NULL){
		      		$db_object2 = getDbObject('docutron');
		      		$DO_userOrig = DataObject::factory('users', $db_object2);
		      		$DO_userOrig->get('username', $user_to_change);
		      		$DO_userChange = DataObject::factory('users', $db_object2, $DO_userOrig);
		      		$DO_userChange->password = $_POST['passwd'];
		      		$DO_userChange->update($DO_userOrig);
		      }
		
		      $db_object=$user->getDbObject();
		      $user->audit("password changed","User password changed for $user_to_change");
echo<<<ENERGIE
<script type="text/javascript">
	document.onload = parent.mainFrame.window.location = "passwordmgmt.php?confirm=$password_for_user $user_to_change $has_been_set"; 
</script>
ENERGIE;
		      
	    }else{
echo "
<script type=\"text/javascript\">
		document.onload = parent.mainFrame.window.location = \"passwordmgmt.php?message=$invalidPassword&u=$uid\";
</script>
"; 
		}
      }
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

   for($i=0;$i<sizeof($usrArr);$i++) {   
		$uname = $usrArr[$i];
		$id = $uidArr[$uname];
      	if( $user->greaterThanUser( $uname ) )  {
	      	if(strcmp($uid, $id)==0) 
 	         	echo "<option selected=\"selected\" value=\"passwordmgmt.php?u=$id\">$uname</option>\n";
	      	else 
	        	echo "<option value=\"passwordmgmt.php?u=$id\">$uname</option>\n";
		}
   }
	echo "</select></td></tr></table></form>\n";
	if( $confirm != null ) {
		echo "<div class=\"error\">$confirm</div>\n";
	}

   if($uid!=NULL&&strcmp($uid,"default")!=0) {  //a user has been selected from the drop down list
      //get the user's name from the database based upon the uid passed
	$q = getTableInfo($db_object,'access',array(),array('uid'=>(int)$uid));
      if(PEAR::isError($q)) {
         echo "$qry<br>\n";
	 die($q->getMessage());
      }
      $user_info=$q->fetchRow();
      $user_to_change=$user_info['username'];
      
      //check if the user wants to change his own password, or reset another user's
      if(strcmp($user->username,$user_to_change)==0) {   //load the normal change password page
echo<<<ENERGIE
<script type="text/javascript">
    document.onload = parent.mainFrame.window.location = "changePassword.php";
</script>
ENERGIE;
      } else {// show the password reset table
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
ENERGIE;
   	if( $message != NULL ) {
		echo "<div style=\"margin-left: auto; margin-right: auto\" class=\"error\">$message</div>\n";
   	}
echo<<<ENERGIE
<div style="display: block; height: 24px;" id="submitDiv">
<div style="float:left; padding-left: 5px; line-height: 18px;">
	<a href="#" onclick="ShowRestrictions(); return false;" title="Click here to view password validation restrcitions">
		View Password Rules&raquo;</a>
</div>		
<div style="float: right;"><input type="submit" name="submit" value="Save" /></div>
<div class="passwordSettings" id="passwordSettings"></div>
</div>
ENERGIE;
      }
   }
	echo "</form>\n";
   echo"</div></body><html>";
	setSessionUser($user);
  }
} else {
	logUserOut();
}

    function checkXMLPasswordSettings($pass, $dept)
    {
    	global $invalidPassword;
		$xmlDoc = new DOMDocument ();
		$error = false;
		//if an xml string has been passed, use that to create the array
		//otherwise, get the array from the settings table.
		$xmlString = getPasswordSettingsXmlString($dept);
		$xmlDoc->loadXML($xmlString);
		    $xmlArray = $xmlDoc->getElementsByTagName('Setting');
		
		foreach($xmlArray as $setting)
		{
			// if the passworRestriction is turned off then return no error, 
			// otherwise, read through everyone of the restrictions for one that
			// is set (continue).
			$id = $setting->getAttribute('id');		
			$value = $setting->getAttribute('value');
			switch($id)
			{
				case 'passwordRestriction':
					if($value == 0) $error = false;
					break 2;
				case 'minLength':
				//check that the minimum safe password length
					if(strlen($pass) < $value)
					{
						$invalidPassword = ("Passwords must be at least ".$value." characters in length");
						$error = true;
					}
					continue;
				case 'alpha_character':
				//check that passwords must include at least one letter	
					if($value > 0 && (!preg_match("/[A-za-z]+/", $pass)))
					{
						$invalidPassword = ("Passwords must include at least one alpha character");
						$error = true;
					}
					continue;
				case 'numeric_character':
				//check that passwords must have at least one number
					if(value > 0 && (!preg_match("/[0-9]+/", $pass)))
					{
						$invalidPassword = ("Passwords must include at least one numeric character");
						$error = true;
					}
					continue;	
				case 'special_character':
				//check that passwords need a special character
					if($value > 0 && (!preg_match("/\W+/", $pass)))
					{
						$invalidPassword = ("Passwords must include at least one special character");
						$error = true;
					}
					continue;																				
			}			
		}
		return $error;		      
    }

?>
