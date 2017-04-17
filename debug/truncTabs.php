#!/usr/bin/php -q
<?php
die ();
$cab = $argv[1];//pass cabinet name
$pass = "Doc-4tron";
$link = mysql_connect("localhost","root","$pass");
mysql_select_db("client_files");
$query1 = "truncate table ".$cab; 
$query2 = "truncate table ".$cab."_files";
$query3 = "truncate table ".$cab."_indexing_table;";
$results = mysql_query($query1) or die("could not: $query1");
$results = mysql_query($query2) or die("could not: $query2");
$results = mysql_query($query3) or die("could not: $query3");
mysql_close($link);
echo "Tables Truncated sucessfully\n";
?>
