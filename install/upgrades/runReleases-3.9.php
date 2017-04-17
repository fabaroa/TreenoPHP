<?php

$start = 0;
if(isSet($argv[1])) {
	$start = $argv[1];
}

do{ 
	$file = "release-3.9.$start.php";
	echo shell_exec("php $file");
	$start++;
} while(is_file($file));

?>
