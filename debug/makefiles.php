#!/usr/bin/php -q
<?php
	// This just makes lots of files in the current directory
	// First argument is the number of files
	// The files start with testfile

	$num = $argv[1] ;
	$i = 0 ;
	while($i <= $num){
		`touch testfile$i` ;
		$i++ ;
	}
?>
