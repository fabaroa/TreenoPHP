<?php
chdir(dirname(__FILE__));

require_once '../lib/licenseFuncs.php';


if($argc == 1) {
	die();
}

if($argv[1] != '1') {
	die();
}

$key = $argv[2];

registerLicense($key);

?>
