<?php
include_once '../modules/modules.php';
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';
include_once '../lib/allSettings.php';

if( $logged_in == 1 && strcmp($user->username,"")!=0 ) {
	$gblStt = new GblStt($user->db_name, $db_doc);
	$defPage = $gblStt->get ('defaultPage');
	$defPageInfo = getDefaultPageInfo ();
	if (isset ($defPageInfo[$defPage])) {
		$myURL = $defPageInfo[$defPage]['url'];
	} else {
		$myURL = '';
	}
?>
<html>
<head>
	<script type="text/javascript" src="../lib/prototype.js"></script> 
	<script type="text/javascript" src="../lib/windowTitle.js"></script>
	<script>
		var myURL = '<?php echo $myURL ?>';
		setTitle(1, "Settings");
		parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
		parent.document.getElementById('folderViewSet').setAttribute('rows', '100%,*');
		parent.document.getElementById('afterMenu').setAttribute('cols','260,*');
		parent.document.getElementById('searchPanel').setAttribute('scrolling', 'yes');
		parent.sideFrame.window.location = '../energie/left_blue_search.php';
		if (myURL == '') {
			document.onload = parent.mainFrame.window.location = "../modules/userInfo.php";
		} else {
			document.onload = parent.mainFrame.window.location = myURL;
		}
		document.onload = parent.searchPanel.window.location = "leftAdmin.php";
		parent.topMenuFrame.removeBackButton();
		parent.topMenuFrame.removeVersButton();
	</script>
</head>
<body>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
