<?php

//this file will remove all the files in files_to_remove.txt

include_once '../lib/settings.php';
$lines=file("files_to_remove.txt");
$prefix=$DEFS['DATA_DIR'].'/';

if(sizeof($lines) > 0){
	echo "\nRemoving:\n";
	foreach ($lines as $file) {
		echo "   $file" ;
		$str=$prefix.$file;
		unlink($str);
	}
}
else{
	echo "\nNo files to remove\n";
}

	echo "\n";
?>
