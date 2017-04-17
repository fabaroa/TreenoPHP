<?php 
include_once '../check_login.php'; 

if($logged_in == 1 && strcmp($user->username,"") != 0) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>License Report</title>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<link rel="stylesheet" type="text/css" href="../lib/calendar.css" />
	<style type="text/css">
		#dateIcon {
			vertical-align: middle;
			cursor: pointer;
		}
	</style>
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script type="text/javascript" src="../lib/calendar.js"></script>
	<script type="text/javascript">
	function registerFuncs() {
		var dateIcon1 = getEl('dateIcon1');
		var dateIcon2 = getEl('dateIcon2');
		dateIcon1.input = getEl('dateInputEnd');
		dateIcon2.input = getEl('dateInputStart');
		dateIcon1.onclick = dispCurrMonth;
		dateIcon2.onclick = dispCurrMonth;
	}

	function genReport() {
		var date1 = getEl('dateInputStart').value;
		var date2 = getEl('dateInputEnd').value;
		if(date1 != "") {
			window.location = "miAuditReport.php?start_date=" + date1+ "&end_date=" + date2;
		} 
	}
	</script>
</head>
<body class="centered" onload="registerFuncs()">
	<div>
		<label for="dateInputStart">Enter Start Date(YYYY-MM-DD):</label>
		<input type="text" id="dateInputStart" />
		<img src="../images/edit_16.gif" id="dateIcon2" />
	</div>
	<div>
		<label for="dateInputEnd">Enter Finish Date(YYYY-MM-DD):</label>
		<input type="text" id="dateInputEnd" />
		<img src="../images/edit_16.gif" id="dateIcon1" />
	</div>
	<div>
		<button onclick="genReport()">Generate Report</button>
	</div>
</body>
</html>
<?php 
	setSessionUser($user);
} else {
	logUserOut();
}
