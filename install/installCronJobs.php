<?php
function installCronJobs() {
	include_once '../lib/settings.php' ;

	// Setting up default cron jobs
	echo "\nSetting up default cron jobs\n" ;
	shell_exec("crontab conf/install.cron");
}
?>