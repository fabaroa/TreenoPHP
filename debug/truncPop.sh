#!/bin/sh
CAB=$1;
NUM=$2;
/usr/bin/php -q truncTabs.php ${CAB}
cd /var/www/client_files/indexing/${CAB}/; rm -rf *; cd ..; /usr/bin/php -q pop.php ${CAB} ${NUM}
