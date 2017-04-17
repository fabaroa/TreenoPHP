<?php
$name = $argv[1];
file_get_contents("http://mail.docutronsystems.com/ipaddress.php?" .
		"name=$name");
?>
