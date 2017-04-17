<?php
// $Id: viewUserFilterSearch.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin()) {
	$db_dept = $user->getDbObject();
	$sArr = array('username');
	$wArr = array();
	$uList = getTableInfo($db_dept,'access',$sArr,$wArr,'queryCol');	
	usort($uList, "strnatcasecmp");
	$filterLabel = "Folder Access";
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>User Filter Search</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../publishing/publishing.js"></script>
<script type="text/javascript">
	function addUserFilter() {
		var uname = $('userList').value;
		var cab = $('pubSearchCab').value;
		var index = $('pubSearchIndex').value;
		var search = $('searchTerm').value;
		
		removeElementsChildren($('errorMsg'));
		if(uname == "__default") {
			var sp = document.createElement('span');
			sp.appendChild(document.createTextNode("Please select a username"));
			$('errorMsg').appendChild(sp);
		} else if(cab == "__default") {
			var sp = document.createElement('span');
			sp.appendChild(document.createTextNode("Please select a cabinet"));
			$('errorMsg').appendChild(sp);
		} else if(index == "__default") {
			var sp = document.createElement('span');
			sp.appendChild(document.createTextNode("Please select a index"));
			$('errorMsg').appendChild(sp);
		} else if(search == "") {
			var sp = document.createElement('span');
			sp.appendChild(document.createTextNode("Please enter a search term"));
			$('errorMsg').appendChild(sp);
		} else {
			var xmlArr = { "include" : "secure/userActions.php",
						"function" : "xmlAddCabinetFilter",
						"username" : uname,
						"cabinet" : cab,
						"index1" : index,
						"search" : search };
			postXML(xmlArr);
		}
	}
</script>
<style type="text/css">
td.label, td.input {
	width: 30%;
}
table.inputTable {
	margin: 1em 0 1em 0;
	width: 100%;
	border-collapse: separate;
}
</style>
</head>
<body>
	<div class="mainDiv">
		<div class="mainTitle">
			<span><?php echo $filterLabel ?></span>
		</div>
		<table id="publishTable" class="inputTable">
			<tr>
				<td class="label">User</td>
				<td class="input">
					<select id="userList" name="userList">
						<option value="__default">Choose a Username</option>
						<?php foreach($uList AS $name): ?>
						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Cabinet</td>
				<td class="input">
					<select id="pubSearchCab" name="pubSearchCab" onchange="chooseCabinet()">
						<option value="__default">Choose a Cabinet</option>
						<?php foreach($user->cabArr AS $real => $arb): ?>
						<option value="<?php echo $real; ?>"><?php echo $arb; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label>Index</label>
				</td>
				<td class="input">
					<select id="pubSearchIndex" name="pubSearchIndex" disabled="disabled">
						<option value="__default">Choose One</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label>Search Term</label>
				</td>
				<td class="input">
					<input type="text" id="searchTerm" name="searchTerm" value="" />
				</td>
			</tr>
		</table>
		<div style="text-align:center">
			<input type="button" name="B1" value="Save" onclick="addUserFilter()" />
			<br>
			<span id="errorMsg" class="error">&nbsp;</span>
		</div>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
