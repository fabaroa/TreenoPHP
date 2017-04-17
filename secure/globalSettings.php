<?php
// $Id: globalSettings.php 14293 2011-03-21 17:34:32Z acavedon $
require_once '../check_login.php';

if($logged_in and $user->username) {
	echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
 "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Global Settings</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />

<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
var submitBtn = globalSettings = infoDiv = errDiv = whichSett = whichSettDiv = '';
var myType = mySett = '';
var p = getXMLHTTP();
</script>
<script type="text/javascript" src="../lib/globalSettings.js"></script>

<style type="text/css">
table.systemSelectMenu { width: 100%; }
td.systemLabel { text-align: right;	width: 50%; }
td.systemSelect { text-align: left; width: 50%; }
div.hideDiv { display: none; }
div.mainDiv { width: 500px; }
#infoDiv { font-weight: bold }
tr.myTblHead {font-weight: bold }
td.myHover { cursor: pointer }
#submitBtn { text-align: right }
</style>

</head>
<body class="centered" onload="registerVars()"><div class="mainDiv">
<div class="mainTitle"><span>Global Settings</span></div>

<div id="whichTypeDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="whichType">Settings For</label></td>
<td class="systemSelect">
<select id="whichType" onchange="changeType(this)">
<option value="__default">Choose One</option>
<option value="System">System</option>
<!--<option value="Groups">Groups</option>-->
<option value="Users">Users</option>
</select>
</td>
</tr></table></div>

<div id="whichSettDiv" class="hideDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="whichSetting">Setting</label></td>
<td class="systemSelect">
<select id="whichSett" onchange="changeSett(this)">
<option value="__default">Choose One</option>
</select>
</td>
</tr></table></div>
<p id="infoDiv" class="hideDiv"></p>
<div id="globalSettings" class="inputForm hideDiv">&nbsp;</div>

<div class="error" id="errDiv">&nbsp;</div>

<div id="submitBtn" class="hideDiv"><button onclick="submitSettings()">Save</button></div>
</div></body></html>
HTML;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
