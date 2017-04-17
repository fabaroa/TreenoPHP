<?php
// $Id: dmsPasswordGen.php 14208 2011-01-04 15:50:30Z acavedon $
require_once '../check_login.php';
if( $logged_in and $user->username and $user->isAdmin() ) {
	echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
 "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>DMS Password Generator</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/help.js"></script>
<script type="text/javascript" src="../lib/dmsPasswordGen.js"></script>
<script type="text/javascript">
var p = getXMLHTTP();
var ptPassword = encPassword = '';
</script>
<style type="text/css" >
table.systemSelectMenu { width: 100%; }
td.systemLabel { text-align: right;	width: 50%; }
td.systemSelect { text-align: left; width: 50%; }
div.hideDiv { display: none; }
div.mainDiv { width: 500px; }
#infoDiv { font-weight: bold }
tr.myTblHead {font-weight: bold }
td.myHover { cursor: pointer }
div.submitBtn { text-align: right }
</style>

</head>
<body class="centered" onload="registerVars()">

<div class="mainDiv">

<div class="mainTitle"><span>Password Generator</span></div>

<div id="getEncodedPassword">
	<table class="systemSelectMenu">
		<tr><td colspan="2"><p>To generate a password, please enter a plain text password 
		in the space provided below and click <b>Generate</b>. Passwords must be 8 to 12 characters and include
		at least one capital letter and one number.</p></td></tr>  
		<tr>
		<td class="systemLabel">Plain Text Password:</td>
		<td class="systemSelect"><input type="password" name="ptPassword" id="ptPassword"/>
		
		</td>
		</tr>
		<tr><td colspan="2" style="text-align: center;">
		<button onclick="createGeneratedPassword();">Generate</button></td>
		</tr>
		<tr>
		<td class="systemLabel">Generated Password:</td>
		<td class="systemSelect"><input type="text" disabled="true" name="encPassword" id="encPassword"/></td>
		</tr>
	</table>
</div>
<div class="error" id="errDiv" style="padding: 3px;">&nbsp;</div>

</div>
</body>
</html>
HTML;
}
?>
