<?php
// This gets values from the DMS.DEFS file by reading in each line
// and settings the values as such
$incDefs = '/etc/opt/docutron/DMS.DEFS';

$lines = file( $incDefs );
foreach( $lines as $line )
{
	if( $line{0}!='#' )	
	{
		$t = explode( "=", trim($line) );
		$DEFS[trim($t[0])] = trim($t[1]);
	}
}
?>
