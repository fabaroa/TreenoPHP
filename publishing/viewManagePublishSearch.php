<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'publishing.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {
	$db_doc = getDbObject('docutron');
	$publishLabel = "Remove Published Searches";

	$tArr = array('publish_search','publish_search_list');
	$sArr = array('publish_search.id','name','date_added','expiration','enabled');
	$wArr = array(	'publish_search.ps_list_id=publish_search_list.ps_list_id',
					"publish_search_list.department='$user->db_name'");
	$oArr = array('name' => 'ASC');
	$pubSearchUserList = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc',$oArr);
	$ct = 1;
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Remove Publish Searches</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<link rel="stylesheet" type="text/css" href="publishing.css" />
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="publishing.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
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
			<span><?php echo $publishLabel ?></span>
		</div>
		<div id="actionDiv">
			<input type="button" name="B1" value="Delete" onclick="removePublishSearch()" />
		</div>
		<div id="pubSearchDiv">
			<table id="pubUserManageTable" class="pubSearchTable" cellspacing="0" cellpadding="0" style="width:100%">
				<tr class="pubTableHead">
					<th>
						<input type="checkbox" 
							id="pubSearchAll" 
							name="pubSearchAll" 
							value="all" 
							onclick="selectAllPubSearch()"
						/>
					</th>
					<th style="width:50%">Search</th>
					<th>Date Added</th>
					<th>Expiration</th>
					<th>Enabled</th>
				</tr>
				<?php foreach($pubSearchUserList AS $id => $info): ?>
				<tr id="pubSearch-<?php echo $id; ?>">
					<td>
						<input type="checkbox" 
							id="check-<?php echo $ct; ?>" 
							name="check-<?php echo $ct++; ?>" 
							value="<?php echo $id; ?>" 
							class="checkbox"
						/>
					</td>
					<td style="text-align:left;text-indent:5px">
						<span><?php echo $info['name']; ?></span>
						<input type="hidden" 
							id="pubSearch-<?php echo $id; ?>" 
							name="pubSearch-<?php echo $id; ?>" 
							value="<?php echo $info['name']; ?>" />
					</td>
					<td>
						<span><?php echo $info['date_added']; ?></span>
					</td>
					<td>
						<span><?php echo $info['expiration']; ?></span>
					</td>
					<td>
						<span><?php echo $info['enabled']; ?></span>
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
