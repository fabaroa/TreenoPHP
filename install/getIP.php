<?php
$counter = 0;
for($i=1; $i < $argc; $i++) //loop to get multi-word company name
{
	//the first argument is to turn off
	if( ($i == 1) && (strcmp($argv[1], "Off") == 0) ) 
	{	
		$counter = 2;
		$i++;
		$name = $argv[$i];
	}
	//the first argument is to turn on crontab for company
	elseif( ($i == 1) && (strcmp($argv[1], "On") == 0) )
	{
		$i++;
		$name = $argv[$i];
	}
	else
		$name = $name." ".$argv[$i];
}

$name = urlencode($name) ;

$output = `crontab -l`;
$lines = explode( "\n", $output );
foreach( $lines as $line )
{	
	//if counter is not already set, and company name is listed in crontab -l, 
			//minimum offset is 11
	if( ($counter == 0) && (strpos($line, $name,11)) )
		$counter = 1;
	//if counter is set to turn off for company name, do not
			//include in array keeplines
	elseif( ($counter == 2) && (strpos($line, $name,11)) )
	{ /* Do nothing */ }
	//if value is not a comment, and not null, keep in array keeplines
	elseif( $line{0}!='#' && trim($line) != '')
		$keeplines[] = $line;
}

if($counter == 1) //do nothing besides print out message that it is on
{
	echo "Auto-Detection is already on\n";
}
elseif($counter == 2) //rewrite crontab where company name is turned off
{
	$fd = fopen( 'tmp.txt', 'w+' );
	if( $keeplines!='' )
		foreach( $keeplines as $line )
		{
			fwrite( $fd, $line."\n" );
		}
	fclose( $fd );
	`crontab -u root tmp.txt`;
	unlink( 'tmp.txt' );
}
else //add company name to the crontab list
{
	$randmin1 = rand(0,59);
	$randhour1 = rand(0,11);
	$randhour2 = $randhour1 + 12 ;
	$insert = "$randmin1 $randhour1,$randhour2 * * * `/usr/bin/php -q /var/www/html/bots/cronIP.php $name`\n";
	$fd = fopen( 'tmp.txt', 'w+' );
	fwrite( $fd, $insert );
	if( $keeplines!='' )
		foreach( $keeplines as $line )
		{
			fwrite( $fd, $line."\n" );
		}
	fclose( $fd );
	`crontab -u root tmp.txt`;
	unlink( 'tmp.txt' );
}
?>
