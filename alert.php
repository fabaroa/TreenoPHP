<?php

$message=$_GET['message'];

echo <<<ENERGIE

<html>
<head><title>Alert</title></head>
<body>
ENERGIE;

//close window when user clicks "OK"
if(isset($_POST['submit'])) {
	echo "<script>window.close();</script>";
	die();
}
echo <<<ENERGIE
<form name='alert' method="POST" action='alert.php'>
<center>$message<br>
<input type="submit" name="submit" value="OK"/>
</center>
</form>
</body>
</html>
ENERGIE;
?>
