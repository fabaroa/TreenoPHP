<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/settings.php';
include_once '../lib/mime.php';

if ($logged_in == 1 && strcmp($user -> username, "") != 0) {
	$page = $_GET['page'];
	$filesArray = $_SESSION['indexFileArray'];
	$nextFileLoc = $filesArray[$page - 1];
	if(file_exists($nextFileLoc)) { 
		$fileDirectory = dirname ($nextFileLoc);
		$fileName = basename ($nextFileLoc);
	}
	$file_type = getMimeType($nextFileLoc, $DEFS);

	$db_dept = getDbObject($user->db_name);
	$cab = $_SESSION['indexing']['cabinet'];
	$cabFields = getCabinetInfo($db_dept,$cab);
	$firstField = $cabFields[0];
	if($file_type == "image/tiff" && $file_type == "application/pdf") {
?>
<html>
<head>
<script>
	function onComplete() {
		parent.document.getElementById('field-<?php echo $firstField; ?>').focus();
	}
</script>
</head>
<body scroll="no" style="margin:0px" onload="onComplete()">
	<object width=100% height=100%
		data="indexingDisplay.php?page=<?php echo $page; ?>" type="<?php echo $file_type; ?>">
		<param name="src" value="indexingDisplay.php?page=<?php echo $page; ?>">
	</object>
</body>
</html>
<?php
	} else {
?>
<html>
<head>
<script>
	function onComplete() {
		parent.document.getElementById('field-<?php echo $firstField; ?>').focus();
		window.location = "indexingDisplay.php?page=<?php echo $page;?>"
	}
</script>
</head>
<body scroll="no" style="margin:0px" onload="onComplete()">
</body>
</html>
<?php
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
