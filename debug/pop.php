#!/usr/bin/php -q
<?php
//to use this program copy it to the /var/www/client_files/indexing directory
//get 4 tif images( 1.TIF, 2.TIF, 3.TIF, 4.TIF ) and place them in the same directory as this file
// pass cabinet name and the number of folders you want.
// example : ./pop.php Clients 200 
//the above example will create 200 folders 1-200 with 4 tiff images in them
//this program also rotates which tiff images will be the 
$cabname = $argv[1];//pass cabinet name
for( $i=1; $i<=$argv[2]; $i++ )
{
	`mkdir $cabname/$i`;
	for( $ii=1; $ii < 5; $ii ++ )
	{
		
		`cp 1.TIF $cabname/$i/$ii.TIF`;
	}
$fp = fopen("$cabname/$i/INDEX.DAT", 'w+');
	fwrite($fp, "IO2r;01;0000000001;0000000004;");
    fwrite($fp, getRandString(1).";".getRandString().";".getRandString().";".getRandString());
fclose($fp);
`chmod -R 777 $cabname/$i`; 
}

function getrandstring($length = 14) 
{
	if($length==1 )
		$pool="abc";
	else
	{
		$pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$pool .= "abcdefghijklmnopqrstuvwxyz";
	}
	for($index = 0; $index < $length; $index++)
		$sid .= substr($pool,(rand(0,60)%(strlen($pool))), 1);
	return($sid);
}
?>
