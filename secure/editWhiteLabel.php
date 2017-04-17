<?php
// $Id: editWhiteLabel.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isSuperUser()) {
	$db_doc = getDbObject ('docutron');
	$gblStt = new GblStt('client_files', $db_doc);
	$whiteLabel = $gblStt->get('whiteLabel');
	if(isSet($_POST['B1'])) {
		$whiteLabel = trim($_POST['whiteLabel']);	
		if($whiteLabel) {
			$gblStt->set('whiteLabel',$whiteLabel);
		}
	}
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>System White Label</title>
	<script>
	</script>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<style>
	</style>
</head>
<body>
	<div class="mainDiv">
		<div class='mainTitle'>
			<span>Edit White Label</span>
		</div>
		<div style="padding:5px">
			<form name="editWhiteLabel" 
				method="POST" 
				action="editWhiteLabel.php" 
				style="padding:0px"
			>
				<input type="text" 
					name="whiteLabel" 
					value="<?php echo $whiteLabel; ?>" 
				/>
				<input type="submit" 
					name="B1" 
					value="Save" 
				/>
			</form>
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
