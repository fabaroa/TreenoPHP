<?php
// $Id: viewAddPublishUser.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'publishing.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {
	$_SERVER['docutron'] = 1;
	$db_doc = getDbObject('docutron');
	$publishLabel = "Publish to External User";

	$mess = "";
	if (isset ($_GET['new'])) {
		$create = $_GET['new'];
	} else {
		$create = '';
	}
	if(!$create) {
		$sArr = array('id','email');
		$wArr = array('status' => 'active'); 
		
		if(!isSet($DEFS['PORTAL_MDEPS']) || $DEFS['PORTAL_MDEPS'] != 1) {
			$wArr['department'] = $user->db_name;
		}
		$oArr = array('email' => 'ASC');
		$pubSearchUserList = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'getAssoc',$oArr);
		if(!count($pubSearchUserList)) {
			$mess = "There are no publishing users";
		}
	}
	
	$pubSearchList = getPublishSearchDisplay($user);
	$ct = 1;
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Publish to External User</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<link rel="stylesheet" type="text/css" href="publishing.css" />
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="publishing.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<style type="text/css">
	#userDiv {
		margin: 1em 0 1em 0;
	}

	#checkbox {
	}
</style>
</head>
<body>
	<div class="mainDiv" style="width:650px">
		<div class="mainTitle">
			<span><?php echo $publishLabel ?></span>
		</div>
		<?php if(!$mess): ?>
		<div id="userDiv">
			<span>Email</span>
			<?php if($create): ?>
			<input type="text" id="pubUser" name="pubUser" value="" />
			<?php else: ?>
			<select id="editPubUser" name="editPubUser" onchange="editPublishUser()">
			<option value="__default">Choose a Search</option>
			<?php foreach($pubSearchUserList AS $id => $email): ?>
			<option value="<?php echo $id; ?>"><?php echo $email; ?></option>
			<?php endforeach; ?>
			</select>
			<?php endif; ?>
		</div>
		<div class="pubSearchDiv">
			<table class="pubSearchTable" cellspacing="0" cellpadding="0">
				<tr class="pubTableHead">
					<th><input type="checkbox" id="pubSearchAll" name="pubSearchAll" value="" onclick="selectAllPubSearch()" /></th>
					<th>Name</th>
					<th>Type</th>
					<th>Search</th>
				</tr>
				<?php foreach($pubSearchList AS $id => $info): ?>
					<tr id="<?php echo "search-".$id; ?>">
						<td>
							<input type="checkbox" 
								id="<?php echo "check-".$ct; ?>" 
								name="<?php echo "check-".$ct++; ?>" 
								value="<?php echo $id; ?>" 
								class="checkbox"
							/>
						</td>
						<td><?php echo $info['name']; ?></td>
						<td><?php echo $info['type']; ?></td>
						<td><?php echo $info['search']; ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php endif; ?>
		<div style="text-align:center;height:20px;padding:5px">
			<span id="errorMsg" class="error"><?php echo " ".$mess; ?></span>
		</div>
		<?php if(!$mess): ?>
		<div style="height:50px;position:relative;top:10px">
			<div style="text-align:left;float:left; width:20%;position:relative;left:10%">
				<input type="checkbox" id="upload" name="upload" value="1" />
				<span>Upload Files</span>
			</div>
			<div style="text-align:left;float:left; width:20%;position:relative;left:10%">
				<input type="checkbox" id="publish" name="publish" value="2"/>
				<span>Publish Files</span>
			</div>
			<div style="float:right;width:20%">
				<input type="button" name="B1" value="Save" onclick="addPublishUser()" />
			</div>
		<div>
		<?php endif; ?>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
