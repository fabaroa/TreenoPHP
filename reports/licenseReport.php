<?php require_once '../check_login.php'; ?>

<?php

if($logged_in and $user->username):
	$dbName = $user->db_name; 
		
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
var dbName = '<?php echo $dbName; ?>';
function registerFuncs() {
	var dateIcon = getEl('dateIcon');
	dateIcon.input = getEl('dateInput');
	dateIcon.onclick = dispCurrMonth;
}

function genReport() {
	var now = new Date();
	var repPlace = getEl('repPlace');
	var date = getEl('dateInput').value;
	if(date != "") {
		clearDiv(repPlace);
		var myImg = newEl('img');
		myImg.src = "../reports/licenseGraph.php?dept=" + dbName + "&date=" + date + "&t=" + now.getTime();
		repPlace.appendChild(myImg);
	} 
}
</script>
<body class="centered" onload="registerFuncs()">
<div>
<label for="dateInput">Enter Date To Report (YYYY-MM-DD):<label>
<input type="text" id="dateInput" />
<img src="../images/edit_16.gif" id="dateIcon" />
</div>
<div>
<button onclick="genReport()">Generate Report</button>
</div>
<div id="repPlace">
</div>
</body>
</head>
</html>

<?php endif; ?>