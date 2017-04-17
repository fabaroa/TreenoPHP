#! /bin/tcsh
set samba_common_version = "samba-common-2.2.7a-7.9.0.i386.rpm"
set samba_client_version = "samba-client-2.2.7a-7.9.0.i386.rpm"
set samba_version = "samba-2.2.7a-7.9.0.i386.rpm"
set redhat_config_samba_version = "redhat-config-samba-1.0.4-1.noarch.rpm"

set php_mysql_version = "php-mysql-4.2.2-17.2.i386.rpm"

set apache_manual_version = "httpd-manual-2.0.40-21.i386.rpm"
set apache_version = "httpd-2.0.40-21.i386.rpm"
set redhat_config_apache_version = "redhat-config-httpd-1.0.1-18.noarch.rpm"

set mysql_server_version = "mysql-server-3.23.58-1.9.i386.rpm"
set mysql_version = "mysql-3.23.58-1.9.i386.rpm"

set php_imap_version = "php-imap-4.2.2-17.2.i386.rpm"
set php_version = "php-4.2.2-17.2.i386.rpm"
set php_ldap_version = "php-ldap-4.2.2-17.2.i386.rpm"

set netpbm_progs_version = "netpbm-progs-9.24-10.i386.rpm"
set netpbm_version = "netpbm-9.24-10.i386.rpm"
set mod_ssl_version = "mod_ssl-2.0.40-21.i386.rpm"
set ghostscript_fonts_version = "ghostscript-fonts-5.50-9.i386.rpm"
set ghostscript_version = "ghostscript-7.05-32.i386.rpm"

echo "Testing the installation of the packages: "
echo " "
echo "php, php-mysql, samba, mysql, apache"
echo " "

echo "Looking for installation of samba: "
rpm -qa > list.txt 
echo  " "
#set result=`rpm -e samba-common-2.'[2-9]'`
#if ($result > 0) then
#	echo "uninstalled samba-common"
#else
#	echo "couldn't uninstall samba-common"
#endif
echo " "

set result=`grep samba-common-2.'[2-9]' list.txt |wc -l`
if ($result > 0) then
	echo "Samba common installed"
else
	echo "Installing samba-common"
	echo "rpm -ihv " $samba_common_version
	rpm -ihv /mnt/cdrom/$samba_common_version
endif	
echo " "	
set result=`grep samba-client-2.'[2-9]' list.txt |wc -l`
if ($result > 0) then
	echo "Samba client installed"
else
	echo "Installing samba-client"
	echo "rpm -ihv " $samba_client_version 
	rpm -ihv /mnt/cdrom/$samba_client_version
endif
echo " "

set result=`grep samba-2.'[2-9]' list.txt |wc -l`
if ($result > 0) then
	echo "Samba installed"
else
	echo "Installing samba"
	echo "rpm -ihv " $samba_version
	rpm -ihv /mnt/cdrom/$samba_version
endif		
echo " "

set result=`grep redhat-config-samba-1.'[0-9]' list.txt |wc -l`
if ($result > 0) then
	echo "Redhat samba configuration installed"
else
	echo "Installing redhat-config-samba"
	echo "rpm -ihv " $redhat_config_samba_version
	rpm -ihv /mnt/cdrom/$redhat_config_samba_version
endif
echo " "

set result=`grep php-4.'[2-9]' list.txt|wc -l`
if ($result > 0) then
	echo "php installed...."
else
	echo "Installing php"
	echo "rpm -ihv " $php_version
	rpm -ihv /mnt/cdrom/$php_version
endif
echo " "

echo "Looking for installation of php-mysql: "
set result=`grep php-mysql-4.'[2-9]' list.txt|wc -l`;
if ($result > 0) then
	echo "php-mysql installed...."
else
        echo "Installing php-mysql"
	echo "rpm -ihv " $php_mysql_version
	rpm -ihv /mnt/cdrom/$php_mysql_version
endif
echo " "

echo  "Looking for installation of apache:"
set result=`grep httpd-2.'[0-9]' list.txt|wc -l`
if ($result > 0) then
	echo "Apache installed"
else
	echo "Installing Apache"
	echo "rpm -ihv " $apache_version
	rpm -ihv /mnt/cdrom/$apache_version
endif
echo  " "
set result=`grep httpd-manual-2.'[0-9]' list.txt|wc -l`
if ($result > 0) then
	echo "Apache manual installed"
else
	echo "Installing Apache-Manual"
	echo "rpm -ihv " $apache_manual_version
	rpm -ihv /mnt/cdrom/$apache_manual_version
endif
echo " "
set result=`grep redhat-config-httpd-1.'[0-9]' list.txt|wc -l`
if ($result > 0) then
	echo "RedHat Apache Configuration installed"
else
	echo "Installing RedHat Apache Configuration"
	echo "rpm -ihv " $redhat_config_apache_version
	rpm -ihv /mnt/cdrom/$redhat_config_apache_version
endif
echo  " "
	
echo  "Looking for installation of mysql: "
set result=`grep mysql-3.2'[3-9]' list.txt|wc -l`
if ($result > 0) then
	echo "mysql installed...."
else
	echo "Installing mysql"
	echo "rpm -ihv " $mysql_version
	rpm -ihv /mnt/cdrom/$mysql_version
endif
echo " "
set result=`grep mysql-server-3.2'[3-9]' list.txt|wc -l`
if ($result > 0) then
	echo "mysql-server installed...."
else
	echo "Installing mysql server"
	echo "rpm -ihv " $mysql_server_version
	rpm -ihv /mnt/cdrom/$mysql_server_version
endif
echo " "
set result=`grep php-imap-4.'[2-9]' list.txt|wc -l`
if ($result > 0) then
	echo "php-imap installed...."
else
	echo "Installing php-imap"
	echo "rpm -ihv " $php_imap_version
	rpm -ihv /mnt/cdrom/$php_imap_version
endif
echo " "
set result=`grep php-ldap-4.'[2-9]' list.txt|wc -l`
if ($result > 0) then
	echo "php-ldap installed...."
else
	echo "Installing php-ldap"
	echo "rpm -ihv " $php_ldap_version
	rpm -ihv /mnt/cdrom/$php_ldap_version
endif
echo " "
set result=`grep mod_ssl-2.'[0-9]' list.txt|wc -l`
if ($result > 0) then
	echo "mod_ssl installed...."
else
	echo "Installing mod_ssl"
	echo "rpm -ihv " $mod_ssl_version
	rpm -ihv /mnt/cdrom/$mod_ssl_version
endif
echo " "
set result=`grep netpbm-progs-9.'[2-9]''[4-9]' list.txt|wc -l`
if ($result > 0) then
	echo "netpbm-progs installed, hence netpbm installed...."
else
	echo "Installing netpbm, netpbm-progs"
	echo "rpm -ihv " $netpbm_version
	rpm -ihv /mnt/cdrom/$netpbm_version
	echo "rpm -ihv " $netpbm_progs_version
	rpm -ihv /mnt/cdrom/$netpbm_progs_version
endif
echo " "
set result=`grep ghostscript-fonts-5.'[5-9]''[0-9]' list.txt|wc -l`
if ($result > 0) then
	echo "ghostscript installed...."
else
	echo "Installing ghostscript, ghostscript-fonts"
	echo "rpm -ihv " $ghostscript_version
	rpm -ihv /mnt/cdrom/$ghostscript_version
	echo "rpm -ihv " $ghostscript_fonts_version
	rpm -ihv /mnt/cdrom/$ghostscript_fonts_version
endif
echo " "
echo "search completed"
rm -f list.txt
exit


