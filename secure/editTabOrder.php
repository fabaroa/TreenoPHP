<?php 
// $Id: editTabOrder.php 14210 2011-01-04 15:53:11Z acavedon $

require_once '../check_login.php'; 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Edit Tab Order</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/settings.js"></script>
<style type="text/css">
#subTitle {
	font-size: 11pt;
	font-weight: bold;
}
</style>
<script type="text/javascript">
function setTO() {
	var errMsg = document.getElementById('errMsg');
	var myVal;
	if(document.getElementById('radioASC').checked == true) {
		myVal = 'ASC';
	} else if(document.getElementById('radioDESC').checked == true) {
		myVal = 'DESC';
	} else {
		myVal = 'NONE';
	}
	var response = execFunc('setTabOrdering', myVal);
	if(!response) {
		errMsg.firstChild.nodeValue = 'Tab Ordering Sucessfully Updated';
	} else {
		errMsg.firstChild.nodeValue = 'Tab Ordering Could Not Be Updated';
	}
	
}
</script>
</head>
<?php if($logged_in and $user->username and $user->isDepAdmin()): 
	$db_doc = getDbObject('docutron');
	$settings = new GblStt($user->db_name, $db_doc);
	$order = $settings->get('tab_ordering');
?>
	<body class="centered">
	<div class="mainDiv">
		<div class="mainTitle"><span>Edit Tab Ordering</span></div>
		<div class="inputForm">Ordering For Tabs (Alphabetically)</div>
		<table class="inputTable">
		<tr style="text-align:center">
			<td class="label" width="50px">
				<label for="radioASC">Ascending</label>
			</td>
			<td>
				<?php if($order == 'ASC'): ?>
					<input type="radio" name="tabOrdering" id="radioASC" checked="checked" value="ASC" />
				<?php else: ?>
					<input type="radio" name="tabOrdering" id="radioASC" value="ASC" />
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td class="label">
				<label for="radioDESC">Descending</label>
			</td>
			<td>
				<?php if($order == 'DESC'): ?>
					<input type="radio" name="tabOrdering" id="radioDESC" checked="checked" value="DESC" />
				<?php else: ?>
					<input type="radio" name="tabOrdering" id="radioDESC" value="DESC" />
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td class="label">
				<label for="radioNONE">None</label>
			</td>
			<td>
				<?php if($order != 'ASC' && $order != 'DESC'): ?>
					<input type="radio" name="tabOrdering" id="radioNONE" checked="checked" value="NONE" />
				<?php else: ?>
					<input type="radio" name="tabOrdering" id="radioNONE" value="NONE" />
				<?php endif; ?>
			</td>
		</tr>
		</table>
		<div>
			<input type="submit" value="Save" onclick="setTO()" />
		</div>
		<div class="error" id="errMsg">&nbsp;</div>
	</div>
	</body>
<?php else: ?>
	<body>
	<script type="text/javascript">
		top.window.location.href = '../logout.php';
	</script>
	</body>
<?php endif; ?>
</html>

