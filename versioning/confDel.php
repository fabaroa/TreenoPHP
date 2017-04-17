<?php
define("CHECK_VALID", "yes");
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/versioning.php';
include_once '../lib/cabinets.php';

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Delete And Rollback Configure</title>
</head>
<body>
<?php
$cabinetID = $_GET['cabinetID'];
$db_object = $user->getDbObject();
$cabinetName = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinetID), 'queryOne');
$cabSecurity = $user->checkSecurity($cabinetName);
$lastURL = $_SESSION['lastURL'];

if($logged_in == 1 && strcmp($user->username, "") != 0 && $cabSecurity) {
	if(isset($_POST['yesdelete'])) {
		$delID = $_POST['delID'];
		$delVer = $_POST['delVer'];
		$parentAudit = $_POST['pAudit'];
		$delArgs = "cabinetID=$cabinetID&fileID=$delID";
		if(strcmp($_POST['myaction'], 'rollback') == 0) {
			echo "<script type=\"text/javascript\">\n";
			echo "parent.vfhTransFrame.location = \"rollBack.php?$delArgs\"\n";
			$user->audit('file rolled back', "$parentAudit, versions greater than $delVer have been removed");
			echo "</script>\n";
		} else {
			echo "<script type=\"text/javascript\">\n";
			echo "parent.vfhTransFrame.location = \"deleteVersion.php?$delArgs\"\n";
			$user->audit('file removed', "$parentAudit, Version: $delVer");
			echo "</script>\n";
		}
	} else {
		echo "<script type=\"text/javascript\">\n";
		echo "parent.vfhMainFrame.location = \"$lastURL\";\n";
		echo "</script>\n";
	}

	setSessionUser($user);
} else {
	echo "<script type=\"text/javascript\">\n";
	echo "document.onload = top.window.location = \"../logout.php\";\n";
	echo "</script>\n";
}
?>
</body>
</html>
