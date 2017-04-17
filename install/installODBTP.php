<?php
//First install odbtp from tar.gz
echo shell_exec('touch /usr/local/share/odbtp.conf');
echo "\nInstalling/Upgrading PECL::odbtp...\n";
echo shell_exec('echo "" | pear install odbtp');
copy('conf/odbtp.ini', '/etc/php.d/odbtp.ini');
echo "\nInstalling/Upgrading PEAR::DB_odbtp...\n";
echo shell_exec('pear install DB_odbtp');
?>
