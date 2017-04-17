<?php
	$output = `crontab -l`;
	$lines = explode( "\n", $output );
	foreach( $lines as $line )
	{
		if( $line{0}!='#' && trim($line) != '')
			$keeplines[] = $line;
	}

	$insert .= "* * * * * /usr/bin/php -q /var/www/html/bots/upForIndexing.php &> /dev/null\n";
	$fd = fopen( 'tmp.txt', 'w+' );
	fwrite( $fd, $insert );
	if( $keeplines!='' )
	{
		foreach( $keeplines as $line )
		{
			fwrite( $fd, $line."\n" );
		}
	}
	fclose( $fd );
	`crontab -u root tmp.txt`;
	unlink( 'tmp.txt' );

?>
