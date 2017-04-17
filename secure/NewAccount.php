<?php
// $Id: NewAccount.php 14222 2011-01-04 16:24:32Z acavedon $

include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../db/db_engine.php';
include_once '../lib/passwordSettingsFuncs.php';

function generatePasswordSettings($user)
{
  	$dept = $user->db_name;
	$xmlDoc = new DOMDocument ();
	$error = false;
	//if an xml string has been passed, use that to create the array
	//otherwise, get the array from the settings table.
	$xmlString = getPasswordSettingsXmlString($dept);
	$xmlDoc->loadXML($xmlString);
	$xmlArray = $xmlDoc->getElementsByTagName('Setting');
	$count = 0;
	
	echo '
	<script type="text/javascript">
		function setting(id, value)
		{
			this.id = id;
			this.value = value;
		}
	
		var settings = [';
	foreach($xmlArray as $setting)
	{
		$id = $setting->getAttribute('id');
		$value = $setting->getAttribute('value');
		
		echo "
		new setting('$id', '$value'),";
		$count++;
	}
	
		echo '];
	</script>';
}


if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {
	//variables that may need to be translated
	$docTitle = $trans['Guest Accounts'];
	$usernamed = $trans['Username'];
	$passwords = $trans['Password'];
	$confirmPass = $trans['Confirm Password'];
	$expires = $trans['Expires'];
	$signUpButton = $trans['Sign Up'];
	$missingField = $trans['Missing Field'];
	$noMatchPass = $trans['No Match Password'];
	$invalidEmail = "Invalid Email Address";

	$email = "Email Address";
	$createGuestAccts = (($_GET['guest']==1) ? $trans['Create Guest User'] : 'Create New User');
	$guest = $_GET['guest'];
	if (isset ($_GET['mess'])) {
		$message = $_GET['mess'];
	} else {
		$message = '';
	}
	if(isset($_GET['noCabinets'])) {
		$message = $_GET['noCabinets'].' '.$trans['Has Been Added']. '.<br/>' .
			$trans['noCabsMessage'];
	}

	$lite = (check_enable('lite',$user->db_name)) ? 1 : 0;
	
	
echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/settings2.js"></script>
ENERGIE;

$generatePaswordSettings = generatePasswordSettings($user);

echo<<<ENERGIE
<script>
	
	var lite = $lite;
	var p = getXMLHTTP();
	var errorDiv;
		
	function createNewUser() {
		errorDiv = document.getElementById('errorMsg');		
	
		if(checkMissingFields()) {
			removeElementsChildren(errorDiv);
			errorDiv.appendChild(document.createTextNode('$missingField'));
		} else if(checkMatchingPwd()) {
			removeElementsChildren(errorDiv);
			errorDiv.appendChild(document.createTextNode('$noMatchPass'));
		} else if(checkEmail()) {
			removeElementsChildren(errorDiv);
			errorDiv.appendChild(document.createTextNode('$invalidEmail'));
		} else if(checkPasswordSettings()) {
			//		
		} else {
			var guest = 0;
			var exp_time = 0;
			var admin = 0;

			admin = 0;

			if(el = $('expBox')) {
				guest = 1;
				exp_time = el.value;
			}
			
			xmlArr = {	"include" : "secure/userActions.php",
						"function" : "createUserCheck",
						"username" : $('unameBox').value ,
						"password" : $('pwBox').value,
						"admin" : admin,
						"guest" : guest,
						"exp_time" : exp_time };
			if($('emailBox')) {
				if($('emailBox').value) {
					xmlArr['email'] = $('emailBox').value;
				}
			}
			postXML(xmlArr);
		}
	}
	 
	function checkPasswordSettings() 
	{
		var isError = false;
		var arr = settings;

		for(i = 0; i < arr.length-1; i++)	
		{
			var id = arr[i].id;
			var value = arr[i].value;
			var pwd = document.getElementById('pwBox').value;
			if(id == 'passwordRestriction' && value == '0')
			{
				isError = false;
				break;
			}
			else 
			{
					
				switch(id)
				{
					case 'minLength':
					//check that the minimum safe password length
						if(pwd.length < value)
						{
							removeElementsChildren(errorDiv);
							errorDiv.appendChild(document.createTextNode("Passwords must be at least "+value+" characters in length"));
							isError = true;
						}
						break;
					case 'alpha_character':
					//check that passwords must include at least one letter	
						if(value > 0 && !(pwd.search(/[a-z]+/) > -1))
						{
							removeElementsChildren(errorDiv);
							errorDiv.appendChild(document.createTextNode("Passwords must include at least one alpha character ( aA-zZ )"));
							isError = true;
						}
						break;
					case 'numeric_character':
					//check that passwords must have at least one number
						if(value > 0 && !(pwd.search(/[0-9]+/) > -1))
						{
							removeElementsChildren(errorDiv);
							errorDiv.appendChild(document.createTextNode("Passwords must include at least one numeric character ( 0-9 )"));
							isError = true;
						}
						break;	
					case 'special_character':
					//check that passwords need a special character
						if(value > 0 && !(pwd.search(/(\W+)/) > -1))
						{
							removeElementsChildren(errorDiv);
							errorDiv.appendChild(document.createTextNode("Passwords must include at least one special character ( &,%,_,-,!,@,# )"));
							isError = true;
						}
						break;																				
				}
			}				
		}
		return isError;
	}
	

	function setMessage(XML) {
		var message = XML.getElementsByTagName('MESSAGE');
		if(message.length > 0) {
			var errorDiv = document.getElementById('errorMsg');
			removeElementsChildren(errorDiv);
			errorDiv.appendChild(document.createTextNode(message[0].firstChild.nodeValue));
		}
		var link = XML.getElementsByTagName('LINK');
		if(link.length > 0) {
			var newLink = link[0].firstChild.nodeValue;
			window.location = newLink;
		}
	}

	function checkMissingFields() {
		var inputFields = document.getElementsByTagName('input');		
		for(var i=0;i<inputFields.length;i++) {
			// All fields are required (uid, pass, email)
			if( (inputFields[i].type == 'text' || 
			    inputFields[i].type == 'password') && 
			    inputFields[i].value == '' ) {
				return true;	
			}
		}
		return false;
	}

	function checkMatchingPwd() {
		var pwd1 = document.getElementById('pwBox').value;
		var pwd2 = document.getElementById('pwBox2').value;
		if(pwd1 != pwd2) {
			return true;
		}
		return false;
	}
	
	function checkPwdFormat()
	{
	}

	function checkEmail() {
		if(!lite) {
			var email = document.getElementById('emailBox').value;
			if(email && email.indexOf('@') == -1) {
				return true;
			}
		}
		return false;
	}
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<style>
 #unameBox,#pwBox,#pwBox2,#emailBox {
  width: 150px;
}
</style>
<title>New Account</title>
</head>
<body class="centered" onload="document.getElementById('unameBox').focus()">
<div class="mainDiv">
<div class="mainTitle">
<span>$createGuestAccts</span>
</div>
<table class="inputTable">
<tr>
<td class="label">
<label for="unameBox">$usernamed</label>
</td>
<td>
<input type="text" id="unameBox" name="uname" maxlength="40" value="" />
     </td>
    </tr>
    <tr>
     <td class="label">
<label for="pwBox">$passwords</label>
     </td>
     <td>
      <input id="pwBox" type="password" name="passwd" maxlength="50" value="" />
     </td>
    </tr>
    <tr>
     <td class="label">
<label for="pwBox2">$confirmPass</label>
     </td>
     <td>
      <input type="password" id="pwBox2" name="passwd_again" maxlength="50" value="" />
     </td>
    </tr>
ENERGIE;
	if(!check_enable('lite', $user->db_name)) {
		echo <<<ENERGIE
	<tr>
	 <td class="label">
<label for="emailBox">$email</label>
     </td>
	 <td>
	  <input type="text" id="emailBox" name="email" value=""/>
	 </td>
   	</tr>
ENERGIE;
	}
		if ($guest == 1) {
			echo " <tr>\n";
			echo "  <td class=\"label\">\n";
			echo "   <label for=\"expBox\">$expires</label>\n";
			echo "  </td>\n";
			echo "  <td>\n";
			echo "   <select id=\"expBox\" name=\"hour\"> \n";
			for ($i = 1; $i <= 36; $i ++) {
				echo "\t<option value=\"$i\">$i</option>\n";
			}
			echo "  </select>\n";
			echo "  </td>\n";
			echo " </tr>\n";
		}
		echo "</table>\n";
		echo "<div style='padding-bottom:10px' id='submitDiv'>\n";
		echo<<<ENERGIE
	<div style="float:left; padding-left: 5px; height: 18px;">
		<a href="#" onclick="ShowRestrictions(); return false;" title="Click here to view password validation restrcitions">
			View Password Rules&raquo;</a>
	</div>		
	<div style="float: right">
      	 <input type="button" onclick='createNewUser()' name="submit" value="$signUpButton" /></div>
	 <div class="error" id='errorMsg' style="text-align: center; font-weight:bold">&nbsp;$message</div>
	 <div class="passwordSettings" id="passwordSettings"></div>
	</div>
  </div>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}

?>
