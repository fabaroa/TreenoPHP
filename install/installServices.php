<?php
function installServices() {
	echo "\nTurning on httpd, mysqld, and smb for run levels 2345\n";
	shell_exec("chkconfig --levels 2345 httpd on");
	shell_exec("chkconfig --levels 2345 mysqld on");
	shell_exec("chkconfig --levels 2345 smb on");

	echo "\nServices Installed\n\n";
}
?>