#!/usr/bin/php -q
<?php
/* strips white space from a file */
/* good for re indenting files */
$f = $argv[1];
if( $argv[1] == "" )
{
	die( "Usage: stripwhitespace.php <filename>\n" );
}
$file1 = file( $argv[1] );
$file2 = fopen( "__".$argv[1], "w+" );
for($i=0; $str = $file1[$i]; $i++ )
{
	$strtrimmed = trim($str );
echo $strtrimmed."\n";
	if( $strtrimmed!="" )
		fwrite( $file2, $strtrimmed."\n" );
}
`mv __$argv[1] $argv[1]`;
?>
