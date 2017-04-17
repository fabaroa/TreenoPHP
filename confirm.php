<?php

/*simulates a confirmation box*/

$confirm_action=$_GET['confirm_action'];
$cancel_action=$_GET['cancel_action'];
$message=$_GET['message'];

echo <<<ENERGIE

<html>
<head><title>Confirm</title></head>
<body>
ENERGIE;

//action for yes clicked
if(isset($_POST['yes'])) {

	echo "<script>opener.window.location='$confirm_action';
		window.close();</script>";
	die();
}
if(isset($_POST['no'])) {
	
	echo "<script>opener.window.location='$cancel_action';
		window.close();</script>";
	die();
}
echo<<<ENERGIE
<form name="confirm" method="POST" action="confirm.php?confirm_action=$confirm_action&cancel_action=$cancel_action">
<center>$message<br>
<input type="submit" name="yes" value="Yes"/>&nbsp;<input type="submit" name="no" value="No"/>
</center>
</form>
</body>
</html>
ENERGIE;
?>
