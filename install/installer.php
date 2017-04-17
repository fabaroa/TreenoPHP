<?php
require_once 'installInitialize.php';
require_once 'installHTML.php';
require_once 'installDB.php';
require_once 'installServices.php';
require_once 'installConfiguration.php';
require_once 'installCronJobs.php';
require_once 'installPrinter.php';
require_once 'soapInstall.php';
if(isset($argv[1])) {
	$db_type = $argv[1];
} elseif (substr (PHP_VERSION, 0, 1) == '4') {
	$db_type = 'pgsql';
} else {
	$db_type = 'pgsql';
}
installInitialize($db_type);
include '../lib/settings.php';
installHTML();
installConfiguration();
installDB(true, $DEFS);
installServices();
installCronJobs();
installPrinter();
installSoapPackages();
?>
