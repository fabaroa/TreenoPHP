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
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<title>Delete Version</title>
<script type="text/javascript" src="versioning.js"></script>
</head>
<body>
<?php



if($logged_in == 1 && strcmp($user->username, "") != 0) {
	$db_object = $user->getDbObject();
	$cabinetID = $_GET['cabinetID'];
	$cabinetName = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinetID), 'queryOne');
	$fileID = $_GET['fileID'];
	$parentID = getParentID($cabinetName, $fileID, $db_object);
	if(!isLocked($cabinetName, $fileID, $db_object)) {
		if(numberOfVersions($cabinetName, $parentID, $db_object) != 1) 
		{
			$updateArr = array();
			$updateArr['deleted'] = 1;
			$updateArr['display'] = 0;
			$whereArr = array();
			$whereArr['id'] = (int)$fileID;
			updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
			$recentID = getRecentID($cabinetName, $parentID, $db_object);
			$updateArr = array();
			$updateArr['display'] = 1;
			$whereArr = array();
			$whereArr['id'] = (int)$recentID;
			updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
			
			if($parentID == $fileID) {
				$updateArr = array();
				$updateArr['parent_id'] = (int)$newParentID;
				$whereArr = array();
				$whereArr['parent_id'] = (int)$fileID;
				updateTableInfo($db_object,$cabinetName."_files",$updateArr,$whereArr);
				$newParentID = getOldestVersion($cabinetName, $fileID, $db_object);
			}
			$mfArgs = $_SESSION['lastURL'];
			$atArgs = $_SESSION['allThumbsURL'];
			echo "<script type=\"text/javascript\">reloadMainFrame('$mfArgs', '$atArgs');</script>\n";
		} 
	} 
} else {
?>
<script type="text/javascript">
document.onload = top.window.location = "../logout.php";
</script>
<?php
}

	setSessionUser($user);
?>
</body>
</html>
