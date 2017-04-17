<?php
// $Id: systemSettings.php 14290 2011-03-21 17:32:17Z acavedon $

require_once '../check_login.php';

if($logged_in and $user->username) {
	echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
 "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Advanced Folder Settings</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />

<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
var myType = '', myUser = '', myGroup = '', myView = '', myCab = '', mySett = '';
var myArbCab, myArbGroup, myArbSett, systemSettings, submitBtn;
var whichUserDiv, whichGroupDiv, whichCabDiv, whichSettDiv, viewByDiv;
var whichUser, whichGroup, whichCab, whichSett, viewBy, errDiv, infoDiv;
var p = getXMLHTTP();
</script>
<script type="text/javascript" src="../lib/systemSettings.js"></script>

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
<div class="mainTitle"><span>Advanced Folder Settings</span></div>

<div id="whichTypeDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="whichType">Settings For</label></td>
<td class="systemSelect">
<select id="whichType" onchange="changeType(this)">
<option value="__default">Choose One</option>
<option value="System">System</option>
<option value="Groups">Groups</option>
<option value="Users">Users</option>
</select>
</td>
</tr></table></div>

<div id="whichUserDiv" class="hideDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="whichUser">User</label></td>
<td class="systemSelect">
<select id="whichUser" onchange="changeUser(this)">
<option value="__default">Choose One</option>
</select>
</td>
</tr></table></div>

<div id="whichGroupDiv" class="hideDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="whichGroup">Group</label></td>
<td class="systemSelect">
<select id="whichGroup" onchange="changeGroup(this)">
<option value="__default">Choose One</option>
</select>
</td>
</tr></table></div>

<div id="viewByDiv" class="hideDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="viewBy">View By</label></td>
<td class="systemSelect">
<select id="viewBy" onchange="changeView(this)">
<option value="__default">Choose One</option>
<option value="Global">Global</option>
<option value="Cabinet">Cabinet</option>
<option value="Setting">Setting</option>
</select>
</td>
</tr></table></div>

<div id="whichCabDiv" class="hideDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label for="whichCabinet">Cabinet</label></td>
<td class="systemSelect">
<select id="whichCab" onchange="changeCab(this)">
<option value="__default">Choose One</option>
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
<div id="systemSettings" class="inputForm hideDiv">
</div>

<div class="error" id="errDiv">&nbsp;</div>

<div id="submitBtn" class="hideDiv"><button onclick="submitSettings()">Save</button></div>
</div></body></html>
HTML;
}
?>
