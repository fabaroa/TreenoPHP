<?php
include 'centera.php';
$farr = file( 'ca.txt' );
foreach( $farr as $ca ){
	echo centget( "b", "centera1.cascommunity.org", trim($ca), $user )."\n";

}
