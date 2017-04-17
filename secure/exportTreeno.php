<?php
// $Id: exportTreeno.php 14212 2011-01-04 16:10:23Z acavedon $
include_once '../check_login.php';
include_once '../classuser.inc';
if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<script type="text/javascript" src="../lib/settings2.js"></script>
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript" src="../yui/yahoo.js"></script>
	<script type="text/javascript" src="../yui/event.js"></script>
	<script type="text/javascript" src="../yui/connection.js"></script>
	<script type="text/javascript" src="../yui/json.js"></script>
	<script>
		var importBool = false;
		function suc(o) {
			try {
				fileInfo = JSON.parse(o.responseText);
				if(fileInfo.filename) {
					window.location = "../energie/displayExport.php?export=1&file=/"+fileInfo.filename;
				} else if(fileInfo.messages) {
					removeElementsChildren($('errMsg'));
					for(var i=0;i<fileInfo.messages.length;i++) {
						var sp = document.createElement('span');			
						sp.appendChild(document.createTextNode(fileInfo.messages[i]));
						$('errMsg').appendChild(sp);

						var br = document.createElement('BR');
						$('errMsg').appendChild(br);
					}
				}
			} catch(e) {
				top.location = '../logout.php';
			}
		}

		function fail() {

		}

		function exportTreeno() {
			var json = { include : 'lib/exportFuncs.php', 
						functionCall : 'exportSystem' };
			var jsonStr = JSON.stringify(json,null);

			var callbacks = {
				success: suc,
				failure: fail
			};
			url = '../lib/jsonPostRequest.php';
			var transaction = YAHOO.util.Connect.asyncRequest('POST',url,callbacks,jsonStr);
		}

		function getMessages() {
			var json = { include : 'lib/exportFuncs.php', 
						functionCall : 'getMessages'};
			var jsonStr = JSON.stringify(json,null);

			var callbacks = {
				success: suc,
				failure: fail
			};
			url = '../lib/jsonPostRequest.php';
			var transaction = YAHOO.util.Connect.asyncRequest('POST',url,callbacks,jsonStr);
		}
	</script>
	<style>
		.mDiv {
			width			: 550px;
			margin-right	: auto;
			margin-left		: auto;
			padding			: 1px;
		}
	</style>
</head>
<body>
	<div class="mainDiv">
		<div class="mainTitle">
			<span>Import/Export Department</span>
		</div>
		<div style="margin-top:20px">
			<form name="importForm" 
			   enctype="multipart/form-data" 
				target="importFrame" 
				action="../lib/importTreeno.php" 
				method="POST">
				<div class="inputForm">
				<table>
					<tr>
						<td><input type="file" id="finput" name="finput" /></td>
					</tr>
				</table>
				<table>
					<tr>
						<td><input type="submit" id="import" name="import" value="Import" /></td>
						<td><input type="button" id="export" name="export" value="Export" onclick="exportTreeno()" /></td>
					</tr>
				</table>
				</div>
			</form>
			<div id="errMsg" class="error"></div>
		</div>
		</fieldset>
	</div>
</body>
</html>
<?php	
	setSessionUser($user);
} else {
	logUserOut();
}
?>
