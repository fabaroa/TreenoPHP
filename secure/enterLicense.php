<?php
include_once '../lib/licenseFuncs.php';

$mac = getServerMAC();
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Treeno Licensing</title>
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript" src="../lib/behaviour.js"></script>
	<script type="text/javascript" src="../yui/yahoo.js"></script>
	<script type="text/javascript" src="../yui/event.js"></script>
	<script type="text/javascript" src="../yui/connection.js"></script>
	<script type="text/javascript" src="../yui/json2.js"></script>
	<script type="text/javascript">
		var boxID = 0;
		function setFocus() {
			$('lic').focus();			
		}

		function enterLicense() {
			while( $('licenseInfo').childNodes[0] ) {
				$('licenseInfo').removeChild($('licenseInfo').childNodes[0]);
			}
			var lic = $('lic').value;

			var json = { include : 'lib/licenseFuncs.php', 
						functionCall : 'enterLicense',
						license: lic };
			var jsonStr = YAHOO.lang.JSON.stringify(json,null);

			var callbacks = {
				success: suc,
				failure: fail
			};
			url = '../lib/jsonPostRequest.php';
			var transaction = YAHOO.util.Connect.asyncRequest('POST',url,callbacks,jsonStr);
		}

		function suc(o) {
			try {
				licInfo = YAHOO.lang.JSON.parse(o.responseText);
			} catch(e) {
				top.location = '../logout.php';
			}
			if(licInfo.valid) {
				top.location = '../logout.php';
			} else {
				$('licenseInfo').appendChild(document.createTextNode('Error importing license. Please enter a valid license key.'));
			}
		}

		function fail() {

		}

		Behaviour.addLoadEvent(
			function() {
				setFocus();
			}
		); 
	</script>
	<style type="text/css">
		body {
			font-family		: Tahoma, Verdana, sans-serif;
		}

		.pMac {
			width			: 400px;
			text-align		: center;
			margin-right	: auto;
			margin-left		: auto;
		}

		.mac {
			text-decoration	: underline;
			color			: blue
		}

		.keys input {
			width				: 50px;
			font-size			: 8pt;
			background-color	: lightyellow; 
		}

		.pKeys {
			width				: 400px;
			height				: 70px;
			border				: 1px solid blue;
			background-color	: lightgrey;
			margin-top			: 10px; 
			padding				: 1px;
			margin-right	: auto;
			margin-left		: auto;
		}

		.keys {
			text-align	: center;
		}

		.submission {
			margin-top	: 5px;
			text-align	: right;	
		}

		.mess {
			color			: red;
			margin-left		: auto;
			margin-right	: auto;
			width			: 300px;
			font-size		: 10pt;
			font-weight		: bold;
			text-align		: center;
		}

	</style>
</head>
<body>
	<div class="pMac">
		<span>MAC Address: </span>
		<span class="mac"><?php echo $mac; ?></span>
	</div>
	<div class="mess">Upon entering a license, all users will be logged out. Users currently logged in will lose any unsaved work.</div>
	<div class="pKeys">
		<div class="keys">
			<span>License Key</span>
		</div>
		<div class="keys">
			<input type="text" id="lic" name="lic" value="" style="width:350px"/>
		</div>
		<div class="submission">
			<input type="button" id="submission" name="B1" value="Save" onclick="enterLicense()" />
		</div>
	</div>
	<div class="mess" id="licenseInfo"></div>
</body>
</html>
