<?php
// $Id: changePassword.php 15023 2013-07-30 17:26:06Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/passwordSettingsFuncs.php';

if($logged_in==1 && strcmp($user->username,"")!=0){
       //translated variables
       $chPass        = $trans['Change Password'];
       $missingField  = $trans['Missing Field'];
       $badPass       = $trans['Incorrect Password'];
       $badCharinPass = $trans['Invalid Character in Password'];
       $noMatchPass   = $trans['No Match Password'];         
       $currPass      = $trans['Current Password'];
       $newPass       = $trans['New Password'];
       $confPass      = $trans['Confirm Password']; 
       $hastoLogin    = $trans['Has to Login'];
       //$Submit        = $trans['Submit'];
       $PasswordSame  = 'New Password Cannot Match The Old Password';       
       $error = 0;

echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<title>$chPass</title>
	<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
	function focusPW() {
		var toFocus = document.getElementById('oldpass');
		if(toFocus) {
			toFocus.focus();
		}
	}
</script>
</head>
<body class="centered" onload="focusPW()">
ENERGIE;
if (isset($_POST['submit'])) { // if form has been submitted
	/* check they filled in what they supposed to, 
	passwords matched, username
	isn't already taken, etc. */

	if (!$_POST['oldpass'] | !$_POST['passwd'] | !$_POST['passwd_again']) {
	$error = 1;
echo<<<ENERGIE
<script type="text/javascript">
    parent.mainFrame.window.location = "changePassword.php?message=$missingField";
</script>
ENERGIE;
	}
	if ($_POST['oldpass'] == $_POST['passwd']) {
	$error = 1;
echo<<<ENERGIE
<script type="text/javascript">
    parent.mainFrame.window.location = "changePassword.php?message=$PasswordSame";
</script>
ENERGIE;
	}
	// check if username exists in database.
	$username=$user->username;
	$cur_passwd = md5(stripslashes($_POST['oldpass']));
	$DO_user =& DataObject::factory('users', $db_doc);
	$DO_user->get('username', $username);

	if ($cur_passwd != $DO_user->password) {
		$error = 1;
echo<<<ENERGIE
<script type="text/javascript">
    parent.mainFrame.window.location = "changePassword.php?message=$badPass";
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
            $error = 1;
echo<<<ENERGIE
<script type="text/javascript">
    parent.mainFrame.window.location = "changePassword.php?message=$badCharinPass";
</script>
ENERGIE;
        }
    }
	// check passwords match
	if ($_POST['passwd'] != $_POST['passwd_again']) {
	$error = 1;
echo<<<ENERGIE
<script type="text/javascript">
    parent.mainFrame.window.location = "changePassword.php?message=$noMatchPass";
</script>
ENERGIE;
	}
	$invalidPassword;
	if(checkXMLPasswordSettings($_POST['passwd'], $user->db_name)) {
	$error = 1; ?>
<script type="text/javascript">
    parent.mainFrame.window.location = "changePassword.php?message=<?php echo $invalidPassword; ?>";
</script> <?php
	}
	// no HTML tags in username, website, location, password
	$_POST['passwd'] = strip_tags($_POST['passwd']);
	$_POST['passwd'] = md5($_POST['passwd']);

	if (!get_magic_quotes_gpc()) {
		$_POST['passwd'] = addslashes($_POST['passwd']);
	}

if(!$error){
	$user->setPassword($_POST['passwd']);
	$DO_userOrig =& DataObject::factory('users', $db_doc, $DO_user);
	$DO_user->password = $_POST['passwd'];
	$DO_user->update($DO_userOrig);
	
	// If the user was forced to change their password, clear the flag
	$user_settings = new Usrsettings($user->username, $user->db_name);
	if ($user_settings->get('change_password_on_login') == 'true')
	{
		$user_settings->removeKey('change_password_on_login');
	}
	//if the user is updated on schedule, renew next update time.
    if (getdbType() == 'mssql') {
		// mssql
		$sArr = array('min(cast(value as varchar(30)))');
	} else {
		// mysql & pgsql
		$sArr = array('min(value)');
	}
	$forcePassword = getTableInfo($db_doc, 'settings', $sArr ,array("k='forcePassword' AND department='".$user->db_name."'"), 'queryOne');
	$continueMessage = "";
    if($forcePassword > 0)
    {	
		//$sql = "SELECT date_add(CURDATE(), INTERVAL ".$forcePassword." DAY)";

		//$next_update = $db_doc->queryOne($sql);
		$date = new DateTime('NOW');
		$interval = 'P'.$forcePassword.'D';
		$date->add(new DateInterval($interval));
		$next_update = $date->format('Y-m-d');
		$user_settings->set('next_password_update', $next_update);
		$continueMessage = " Please Click Search To Continue.";
	}	
}
	$user->audit("password changed","User $username changed password");
        //if user has changed the password suceccfully, set the table entry in user_settings to say so

$message = "Password Successfully Changed!$continueMessage";

if($user->isDepAdmin()) {
echo<<<ENERGIE
<script type="text/javascript">
	parent.mainFrame.window.location = "passwordmgmt.php?message=$message"; 
</script>
ENERGIE;
} else {
echo<<<ENERGIE
<script type="text/javascript">
	parent.mainFrame.window.location = "changePassword.php?message=$message"; 
</script>
ENERGIE;
}

} else {	// if form hasn't been submitted
echo<<<FORM
<div class="mainDiv">
<div class="mainTitle">
<span>$chPass</span>
</div>
<form style='padding:0px' action="changePassword.php" method="post">
<table class="inputTable">
	<tr>
		<td class="label">
		<label for="oldpass">$currPass</label>
		</td>
		<td>
			<input type="password" id="oldpass" name="oldpass" maxlength="40"/>
		</td>
	</tr>
	<tr>
		<td class="label">
		<label for="passwd">$newPass</label>
		</td>
		<td>
			<input type="password" id="passwd" name="passwd" maxlength="50"/>
		</td>
	</tr>
	<tr>
		<td class="label">
		<label for="passwd_again">$confPass</label>
		</td>
		<td>
			<input type="password" id="passwd_again" name="passwd_again" maxlength="50"/>
		</td>
	</tr>
</table>
FORM;
if(!empty ($_GET['message'])) {
    echo "<div class=\"error\">{$_GET['message']}\n</div>";
} else {
    echo "<div class=\"error\">&nbsp;</div>";
}
echo <<<FORM
<div style="display: block; height: 24px;" id="submitDiv">
	<div style="float:left; padding-left: 5px; line-height: 18px;">
		<a href="#" onclick="ShowRestrictions(); return false;" title="Click here to view password validation restrcitions">
		View Password Rules&raquo;</a>
	</div>		
	<div style="float:right;">
		<input type="submit" name="submit" value="Save"/>
	</div>
	<div class="passwordSettings" id="passwordSettings"></div>
</div>
</form>
</div>
</body>
</html>
FORM;
}
	setSessionUser($user);
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
		// if the passwordRestriction is turned off then return no error, 
		// otherwise, read through everyone of the restrictions for one that
		// is set (continue).
		$id = $setting->getAttribute('id');		
		$value = $setting->getAttribute('value');
		switch($id)
		{
			case 'passwordRestriction':
				if($value == 0) {
					$error = false;
					break 2;
				}
				continue;
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
				if($value > 0 && (!preg_match("/[A-Za-z]+/", $pass)))
				{
					$invalidPassword = ("Passwords must include at least one alpha character");
					$error = true;
				}
				continue;
			case 'numeric_character':
			//check that passwords must have at least one number
				if($value > 0 && (!preg_match("/[0-9]+/", $pass)))
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
