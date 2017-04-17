<?php
include 'centera.php';
$filename = $argv[1];
$ca = centput( "$filename", "centera1.cascommunity.org",$user );
$fp = fopen( 'ca.txt', 'a+' );
fwrite( $fp, $ca."\n" );
fclose( $fp );
?>
