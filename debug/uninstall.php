#!/usr/bin/php -q
<?php
include_once '../db/db_engine.php';

$uninstall_dir = "uninstall_docutron";
$starting_dir = "{$DEFS['DATA_DIR']}";
$db_username = "root";
$db_password = "Doc-4tron";
$t = date("D-M-j-G-i-s-Y");
$file = "/tmp/tmp-$t.txt";


//if uninstall_docutron directory does not exist
if( !is_dir("$starting_dir/$uninstall_dir") ) 
	`mkdir $starting_dir/$uninstall_dir`;

//if there is a client_files directory
if( is_dir("$starting_dir/client_files") )
	`mv $starting_dir/client_files $starting_dir/$uninstall_dir/client_files`;

//if there is a html directory
if( is_dir("$starting_dir/html") )
	`mv $starting_dir/html $starting_dir/$uninstall_dir/html`;

//make empty html directory
`mkdir $starting_dir/html`;

//reads all the databases into a file called all_databases.sql
`mysqldump --all-databases > $starting_dir/$uninstall_dir/all_databases.sql`;


`mysqlshow -u $db_username --password="$db_password" docutron > $file`;
//if $file > 0, then docutron db exists
if( filesize("$file" )!=0 )
	`mysqladmin -u $db_username --password=$db_password -f drop docutron`;
unlink($file);

`mysqlshow -u $db_username --password="$db_password" client_files > $file`;
if( filesize("$file") !=0 )
	`mysqladmin -u $db_username --password="$db_password" -f drop client_files`;
unlink($file);

//change password to blank
`mysqladmin -u $db_username --password=$db_password password ''`;




?>
