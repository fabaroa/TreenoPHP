<?php
define("CHECK_VALID", "yes");
include_once '../check_login.php';

$prevQuery = $_SERVER['QUERY_STRING'];
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>View File History</title>
</head>
<frameset cols="100%, *, *">
<frame name="vfhMainFrame" src="viewFileHistory.php?<?php echo $prevQuery ?>"
	frameborder="0" noresize="noresize" />
<frame src="../energie/blue_bar.php" name="vfhTransFrame" frameborder="0" 
	noresize="noresize" />
<frame src="../energie/blue_bar.php" name="vfhTransFrame2" frameborder="0" 
	noresize="noresize" />
</frameset>
</html>

