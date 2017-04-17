<?php 
include_once '../check_login.php'; 
include_once '../classuser.inc';

if($logged_in == 1 && strcmp($user->username,"") != 0) {
	$db_dept = getDbObject($user->db_name);

	$sArr = array('DISTINCT(defs_name)');
	$workflowList = getTableInfo($db_dept,'wf_defs',$sArr,array(),'queryCol');
	usort($workflowList,"strnatcasecmp");
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
		dateIcon1.input = getEl('dateInputStart');
		dateIcon1.onclick = dispCurrMonth;
	}

	function genReport() {
		var date1 = getEl('dateInputStart').value;
		if(getEl('total').checked){
			var sort_by = getEl('total').value;
		} else{
			var sort_by = getEl('mintime_value').value;
		}

		var wf_name = getEl('wf_name').value;
		window.location = "wf_report.php?start_date=" + date1 +"&sort_by="+sort_by + "&wf_name=" + wf_name;
	}
	</script>
</head>
<body class="centered" onload="registerFuncs()">
	<div>
		<label for="dateInputStart">Enter Start Date(YYYY-MM-DD):<label>
		<input type="text" id="dateInputStart" />
		<img src="../images/edit_16.gif" id="dateIcon1" />
	</div>
	<div>
		<table style="margin-right:auto;margin-left:auto">
			<tr>
				<td>
					<span>sort by:</span>
				</td>
				<td>
					<input id="total" 
						type="radio" 
						name="sort_by" 
						value="total" 
						checked="checked" 
					/>
					<span>Total Time</span>
					<input id="mintime_value" 
						type="radio" 
						name="sort_by" 
						value="mintime_value" 
					/>
					<span>Start Date</span>
				</td>
			</tr>
		</table>
	</div>
	<div>
		<select id="wf_name" name="wf_name">
		<?php foreach($workflowList AS $wfname): ?>
			<option value="<?php echo $wfname; ?>"><?php echo str_replace("_"," ",$wfname); ?></option>
		<?php endforeach; ?>
		</select>
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
