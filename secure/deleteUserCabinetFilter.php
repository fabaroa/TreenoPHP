<?php
// $Id: deleteUserCabinetFilter.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin()) {
	$db_dept = $user->getDbObject();
	$filterLabel = "Remove Access Restrictions";

	$sArr = array();
	$wArr = array();	
	$oArr = array('id' => 'ASC');
	$filterList = getTableInfo($db_dept,"cabinet_filters",$sArr,$wArr,'queryAll',$oArr);
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Remove Access Restrictions</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<link rel="stylesheet" type="text/css" href="../publishing/publishing.css" />
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="../publishing/publishing.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript">
	function selectAllFilters() {
		var toggle = $('pubSearchAll').checked;
		var chkBoxes = document.getElementsByClassName('checkbox');
		for(i=0;i<chkBoxes.length;i++) {
			chkBoxes[i].checked = toggle;
		}	
	}

	function removeCabinetFilters() {
		var xmlArr = { "include" : "secure/userActions.php",
						"function" : "xmlRemoveCabinetFilter" };

		var rowIndexList = new Array();
		var chkBoxes = document.getElementsByClassName('checkbox');
		var j = 1;
		for(i=0;i<chkBoxes.length;i++) {
			if(chkBoxes[i].value != "all") {
				if(chkBoxes[i].checked) {
					xmlArr["searchID-"+j] = chkBoxes[i].value;
					rowIndexList.push(chkBoxes[i].value);
					j++;
				}
			}
		}
		postXML(xmlArr);

		for(i=0;i<rowIndexList.length;i++) {
			var rowIndex = $('filter-'+rowIndexList[i]).sectionRowIndex;
			$('cabinetFilters').deleteRow(rowIndex);
		}
	}
</script>
<style type="text/css">
	#actionDiv {
		margin: 1em 0 1em 0;
		width : 90%;
		margin-left: auto;
		margin-right: auto;
	}

	#pubSearchDiv {
		margin: 1em 0 1em 0;
		width : 90%;
		margin-left: auto;
		margin-right: auto;
	}
</style>
</head>
<body>
	<div class="mainDiv" style="width:650px">
		<div class="mainTitle">
			<span><?php echo $filterLabel ?></span>
		</div>
		<div id="actionDiv">
			<input type="button" name="B1" value="Delete" onclick="removeCabinetFilters()" />
		</div>
		<div id="pubSearchDiv">
			<table id="cabinetFilters" class="pubSearchTable" cellspacing="0" cellpadding="0" style="width:100%">
				<tr class="pubTableHead">
					<th>
						<input type="checkbox" 
							id="pubSearchAll" 
							name="pubSearchAll" 
							value="all" 
							onclick="selectAllFilters()"
						/>
					</th>
					<th>ID</th>
					<th>Username</th>
					<th>Cabinet</th>
					<th>Index</th>
					<th>Search</th>
				</tr>
				<?php foreach($filterList AS $info): ?>
				<tr id="filter-<?php echo $info['id']; ?>">
					<td>
						<input type="checkbox" 
							value="<?php echo $info['id']; ?>" 
							class="checkbox"
						/>
					</td>
					<td>
						<span><?php echo $info['id']; ?></span>
					</td>
					<td>
						<span><?php echo $info['username']; ?></span>
					</td>
					<td>
						<span><?php echo $info['cabinet']; ?></span>
					</td>
					<td>
						<span><?php echo $info['index1']; ?></span>
					</td>
					<td>
						<span><?php echo $info['search']; ?></span>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<div id="errorMsg" class="error" style="height:25px"></div>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
