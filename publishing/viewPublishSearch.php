<?php
// $Id: viewPublishSearch.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {
	$db_doc = getDbObject('docutron');
	if (isset ($_GET['type'])) {
		$type = $_GET['type'];
	} else {
		$type = '';
	}
	if (isset ($_GET['new'])) {
		$create = $_GET['new'];
	} else {
		$create = '';
	}
	$sArr = array('publish_search.id','name');
	if($type == "search") {
		if(!$create) {
			$tArr = array('publish_search','publish_search_list');
			$wArr = array(	'publish_search.ps_list_id = publish_search_list.ps_list_id',
							"type = 'folder_search'", 
							"publish_search_list.department = '".$user->db_name."'",
							'doc_id = 0');
			$oArr = array('name' => 'ASC');
			$pubSearchList = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc',$oArr);	
			$publishLabel = "Manage Auto-Publishing";
		} else {
			$publishLabel = "Auto-Publishing";
		}
	} else {
		if(!$create) {
			$tArr = array('publish_search','publish_search_list');
			$wArr = array(	'publish_search.ps_list_id = publish_search_list.ps_list_id',
							"type != 'folder_search'", 
							"publish_search_list.department = '".$user->db_name."'",
							'doc_id = 0');
			$oArr = array('name' => 'ASC');
			$pubSearchList = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc',$oArr);
			$publishLabel = "Manage Published Workflow/Upload";
		} else { 
			$publishLabel = "Publish Workflow/Upload";
		}
	}

	$db_dept = $user->getDbObject();
	$sArr = array('username');
	$userList = getTableInfo($db_dept,'access',$sArr,array(),'queryCol');
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Auto-Publishing</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="publishing.js"></script>
<style type="text/css">
td.label {
	font-weight: bold;
}
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
			<span><?php echo $publishLabel ?></span>
		</div>
		<table id="publishTable" class="inputTable">
			<tr>
				<td class="label">
					<label>Name</label>
				</td>
				<td class="input">
					<?php if($create): ?>
					<input type="text" id="publishName" name="publishName" value="" />
					<?php else: ?>
					<select id="editPubSearch" name="editPubSearch" onchange="editPublishSearch()">
						<option value="__default">Choose a Search</option>
						<?php foreach($pubSearchList AS $id => $name): ?>
						<option value="<?php echo $id; ?>"><?php echo $name; ?></option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
				</td>
			</tr>
			<?php if($type == "search"): ?>
			<tr>
				<td class="label">
					<label>Cabinet</label>
				</td>
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
					<input type="text" id="pubSearchTerm" name="pubSearchTerm" value="" />
				</td>
			</tr>
			<?php else: ?>
			<tr id="pubTypeTR">
				<td class="label">
					<label>Type</label>
				</td>
				<td class="input">
					<select id="pubUpload" name="pubUpload" onchange="chooseType()">
						<option value="__default">Choose a Type</option>
						<option value="workflow">Workflow</option>
						<option value="upload">Upload</option>
					</select>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align:center">
					<span id="errorMsg" class="error">&nbsp;</span>
				</td>
			</tr>
			<tr>
				<td style="text-align:right">
					<input type="checkbox" id="enable" name="enable" value="1" checked/>enabled
				</td>
				<td style="text-align:center">
					<input type="button" name="B1" value="Save" onclick="addPublishSearch()" />
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
