<?php
// $Id: odbcSetup.php 14224 2011-01-04 16:30:07Z acavedon $

require_once '../check_login.php';
if( $logged_in and $user->username and $user->isAdmin() ) {
	echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
 "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>ODBC Integration</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/help.js"></script>
<script type="text/javascript" src="../lib/odbcSetup.js"></script>
<script type="text/javascript">
var p = getXMLHTTP();
var myType = editConnDiv = whichConnSel = whichConnDiv = submitConnBtn = errDiv = myConnID = whichTypeSel = '';
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
<body class="centered" onload="registerVars()"><div class="mainDiv">
<div class="mainTitle"><span>ODBC Integration</span></div>

<div id="whichTypeDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel">
<label style="cursor:help" onclick="requestHelp(this,'odbcIntegrationFunction','english')" for="whichType">ODBC Integration Function</label>
</td>
<td class="systemSelect">
<select id="whichType" onchange="changeType(this)">
<option value="__default">Choose One</option>
<option value="Add">Add New Connector</option>
<option value="Edit">Edit Connector</option>
</select>
</td>
</tr>
</table></div>

<div id="whichConnDiv" class="hideDiv"><table class="systemSelectMenu"><tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcConnectorName','english')" for="whichConn">Choose Connector</label></td>
<td class="systemSelect">
<select id="whichConn" onchange="changeConn(this)">
<option value="__default">Choose One</option>
</select>
</td>
</tr>
</table></div>

<div id="editConnDiv" class="hideDiv"><table class="systemSelectMenu">
<tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcConnectorName','english')" for="connect_name">Connector Name</label></td>
<td class="systemSelect">
<input type="text" width="20" id="connect_name" />
</td>
</tr>
<tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcConnectorType','english')" for="connect_type">Connector Type</label></td>
<td class="systemSelect">
<select id="type">
<option value="odbtp">ODBTP</option>
<option value="mysql">MySQL</option>
<option value="oci8">Oracle</option>
<option value="pgsql">PostgreSQL</option>
<option value="sqlite">sqlite</option>
</select>
</td>
</tr>
<tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcDatabaseHost','english')" for="host">Database Host</label></td>
<td class="systemSelect">
<input type="text" width="20" id="host" />
</td>
</tr>
<tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcDatabaseDSN','english')" for="dsn">Database DSN</label></td>
<td class="systemSelect">
<input type="text" width="20" id="dsn" />
</td>
</tr>
<tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcDSNUsername','english')" for="username">DSN Username</label></td>
<td class="systemSelect">
<input type="text" width="20" id="username" />
</td>
</tr>
<tr>
<td class="systemLabel"><label style="cursor:help" onclick="requestHelp(this,'odbcDSNPassword','english')" for="password">DSN Password</label></td>
<td class="systemSelect">
<input type="password" width="20" id="password" />
</td>
</tr>
</table>
</div>

<div class="error" id="errDiv">&nbsp;</div>
<div class="submitBtn hideDiv" id="submitConnBtn">
<button onclick="testConn()">Test Connection</button>
<button onclick="submitConn()">Save</button>
</div>
</div>
</body>
</html>
HTML;
}
?>
