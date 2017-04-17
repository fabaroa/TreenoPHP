#!/usr/bin/php -q
<?php
//remove the following line to use this file
die();
//string for backup directory
if( !is_dir( "{$DEFS['DATA_DIR']}/back/"))
	mkdir( "{$DEFS['DATA_DIR']}/back/");
$dest = "{$DEFS['DATA_DIR']}/back/html-".date("Y-m-d-H-i-s");
echo "$dest\n";
`mkdir $dest`;
if( !is_dir( $dest ) )
	die("could not make directory");
//mv /var/www/html into $dest
`mv {$DEFS['DOC_DIR']} $dest`;
//mv corresponding client_files into that directory
`mv {$DEFS['DATA_DIR']}/client_files $dest`;
//dump databases into $dest
`mysqldump --all-databases > $dest/allDBs.sql`;
//drop dbs
`mysqladmin -f drop docutron`;
`mysqladmin -f drop client_files`;

?>
