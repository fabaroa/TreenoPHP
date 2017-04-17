<?php
//NOT WINDOWS-SAFE
function addScannerUser() {
	$passwdLines = file('/etc/passwd');
	$scannerExists = false;
	$i = 0;
	$password = 'docutron';
	while($scannerExists === false and $i < count($passwdLines)) {
		if(strpos($passwdLines[$i], 'scanner:') !== false) {
			$scannerExists = true;
		}
		$i++;
	}
	if(!$scannerExists) {
		echo "Adding User 'scanner' with password 'docutron'\n";
		$addScannerCmd = "useradd -g apache -d /var/www scanner";
		shell_exec($addScannerCmd);
		shell_exec("echo $password | passwd --stdin scanner");
		$fd = fopen( "/var/www/.bashrc", 'a+' );
		fwrite( $fd, "umask 0000\n" );
		fclose( $fd );
	} else {
		echo "User 'scanner' already exists, not adding\n";
	}
}
?>
