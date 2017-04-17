#!/usr/bin/php
<?php
	include '../lib/settings.php' ;
	
	$time = $DEFS['INSTALLTIME'] ;

	// If not a force, prompt for roll back
	if($argv[1] != "-f"){
		echo " Would you like to roll back config files to $time\n".
			"  [Y/n]?> " ;
		$stdin = fopen("php://stdin", "r") ;
		$ans = str_replace("\n", "", fgets($stdin)) ;
		while($ans != "Y" && $ans != "n"){
			echo "  [Y/n]?> " ;
			$ans = str_replace("\n", "", fgets($stdin)) ;
		}
	}
	else{ // If force a yes and a roll back
		$ans = "Y" ;
	}

	if($ans == "Y"){
		echo "DELETING HARD DRIVE\n" ;
	}
				
?>
