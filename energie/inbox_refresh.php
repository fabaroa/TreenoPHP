<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../modules/modules.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
	$OK = $_GET['OK'];
	if(check_enable('advancedInbox',$user->db_name)) {
		$OK = 2;
	}
?>
<html>
<head>
	<script type="text/javascript" src="../lib/prototype.js"></script> 
	<script type="text/javascript" src="../lib/settings2.js"></script>
	<script type="text/javascript" src="../lib/windowTitle.js"></script>
	<script>
		parent.topMenuFrame.removeBackButton();
		setTitle(1, "Inbox");
		function refresh2() {
			<?php if($OK == 1): ?>
			parent.searchPanel.window.location = "../secure/inboxSelect1.php";
			parent.mainFrame.window.location = "../secure/inbox1.php?new=1&type=1";
			parent.bottomFrame.window.location = "bottom_white.php";
			<?php else: ?>
			parent.document.getElementById('afterMenu').setAttribute('cols','0,*');
			parent.document.getElementById('mainFrameSet').setAttribute('cols', '*,0');
			parent.document.getElementById('folderViewSet').setAttribute('rows', '*,0');
			parent.mainFrame.window.location = "../inbox/inboxView.html";
			<?php endif; ?>
		}
	</script>
</head>
<body onload="refresh2()">
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
