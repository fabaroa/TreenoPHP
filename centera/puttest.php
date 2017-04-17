<?php
include 'centera.php';
//create test directories
if( !is_dir( 'testput' ) ){
	mkdir( 'testput', '0777' );
}
if( !is_dir( 'testget' ) ){
	mkdir( 'testget', '0777' );
}
$fp = fopen( 'ca.txt', 'a+' );
for($i=0;$i<2;$i++){ 
	$number = mt_rand( 900000,999999 );
	`touch a/$number`;
	`echo "$number" > a/$number`;
	$fd = fopen( "a/$number", 'a+' );
	for( $j=0; $j<200000; $j++ ){
		fwrite( $fd, $number );
	}
	fclose( $fd );
	$ca = centput( "a/$number", "centera1.cascommunity.org", $user );
	echo "$ca\n";
	fwrite( $fp, $ca."\n" );
}
fclose( $fp );

?>
