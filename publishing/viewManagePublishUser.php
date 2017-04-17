<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'publishing.php';
include_once '../lib/settings.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {
	$db_doc = getDbObject('docutron');
	$publishLabel = "Manage Accounts";

	$sArr = array('id','email','upload','status');

	$wArr = array('department' => $user->db_name);
	if(isSet($DEFS['PORTAL_MDEPS']) && $DEFS['PORTAL_MDEPS'] == 1) {
		$wArr = array();
	}
	$oArr = array('email' => 'ASC');
	$pubSearchUserList = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'getAssoc',$oArr);
	$ct = 1;
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Manage Publish User</title>
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
			<input type="button" name="B1" value="Delete" onclick="managePublishUser('delete')" />
			<input type="button" name="B2" value="Suspend/Enable" onclick="managePublishUser('toggle')" />
			<input type="button" name="B3" value="Resend Password" onclick="managePublishUser('password')" />
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
					<th>User</th>
					<th>Upload</th>
					<th>Status</th>
				</tr>
				<?php foreach($pubSearchUserList AS $id => $info): ?>
				<tr id="pubUser-<?php echo $id; ?>">
					<td>
						<input type="checkbox" 
							id="check-<?php echo $ct; ?>" 
							name="check-<?php echo $ct++; ?>" 
							value="<?php echo $id; ?>" 
							class="checkbox"
						/>
					</td>
					<td>
						<span><?php echo $info['email']; ?></span>
						<input type="hidden" 
							id="email-<?php echo $id; ?>" 
							name="email-<?php echo $id; ?>" 
							value="<?php echo $info['email']; ?>" />
					</td>
					<td>
						<span><?php echo $info['upload']; ?></span>
					</td>
					<td>
						<span id="statusSpan-<?php echo $id; ?>"><?php echo $info['status']; ?></span>
						<input type="hidden" 
							id="status-<?php echo $id; ?>" 
							name="status-<?php echo $id; ?>" 
							value="<?php echo $info['status']; ?>" />
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
