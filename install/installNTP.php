<?php

function installNTP() {
	if(file_exists('/etc/ntp.conf')) {
		if(md5_file('/etc/ntp.conf') !=md5_file($_ENV['PWD'].'/conf/ntp.conf')){
			rename('/etc/ntp.conf', '/etc/ntp.conf.old');
			$touch = true;
		} else {
			$touch = false;
		}
	} else {
		$touch = true;
	}
	if($touch) {
		copy('conf/ntp.conf', '/etc/ntp.conf');
		shell_exec("/sbin/chkconfig ntpd on");
		shell_exec("/usr/sbin/ntpdate 0.pool.ntp.org 2>&1 &> /dev/null");
		shell_exec("/sbin/service ntpd restart 2>&1 &> /dev/null");
	}
}

?>