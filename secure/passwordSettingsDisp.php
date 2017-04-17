<?php
// $Id: passwordSettingsDisp.php 14227 2011-01-04 16:32:07Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script type="text/javascript" src="../lib/help.js"></script>
	<script type="text/javascript" src="../lib/passwordSettings.js"></script>
	<script type="text/javascript">
	var p = getXMLHTTP();
	var passwordRestriction,requireChange,minLength,alpha,numeric,special,forcePassword,submitBtn,errDiv;
	passwordRestriction = requireChange = minLength = alpha = numeric = special = forcePassword = submitBtn = errDiv = ''; 
	</script>	
	
	<style type="text/css">
		.mDiv {
			width			: 550px;
			margin-right	: auto;
			margin-left		: auto;
			padding			: 1px;
			font-size		: 12px;				
		}
		.mDiv p label {
			width			: 250px;
			line-height		: 28px;
			float			: left;
		}
		
		.mDiv input {
			vertical-align	: middle;
			height			: 24px;
			line-height		: 30px;
		}
	
		.mDiv select {
			line-height		: 30px;
			margin-top		: 3px;
			vertical-align	: middle;
		}		
	</style>
</head>
<body onload="loadSettings();">
	<div class="mainDiv" style="width:500px">
		<div class="mainTitle">
			<span>Password Restrictions</span>
		</div>
		<form action="passwordSettingsDisp.php" method="post" id="passwordSettings" name="passwordSettings">
				<label>Turn Password Restrictions On: </label>
				<input type="checkbox" name="passwordRestriction" id="passwordRestriction" value="1" />
				<br><br>
				<label>Require All Users To Change Password: </label>
				<input type="checkbox" value="1" name="requireChange" id="requireChange"/>
				<br><br>
				<label>Minimum Password Length: </label>
				<select name="minLength" id="minLength">
					<?php printLength(); ?>
				</select>
				<br><br>
				<label>Require At Least One Of The Following: </label>
				<input type="checkbox" name="alpha_character" value="1" id="alpha_character" />Alpha
				<input type="checkbox" name="numeric_character" value="1" id="numeric_character"/>Number
				<input type="checkbox" name="special_character" value="1" id="special_character"/>Special Character
				<br><br>
				<label>Force New Password Every: </label>
					<select name="forcePassword" id="forcePassword">
						<option value="0">Never</option>
						<option value="30">30 Days</option>
						<option value="60">60 Days</option>
						<option value="90">90 Days</option>
					</select>
				<br><br>
				<input type="button" name="submitBtn" value="Save" id="submitBtn"
					onclick="submitPasswordSettings();" style="line-height: 30px; height: 30px" />
		</form>
		<div id="errDiv" class="error"></div>
	</div>
	</div>
</body>
</html>
<?php	
	setSessionUser($user);
} else {
	logUserOut();
}

function printLength()
{
	for($i = 6; $i <= 20; $i++)
		echo "<option value=\"$i\">$i</option>\n";
}
?>


