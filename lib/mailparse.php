<?php

function parseEmailHeader( $path )
{
	$indiceArr = array (
		'from'		=> '',
		'subject'	=> '',
		'to'		=> '',
		'date'		=> ''
	);
	if(file_exists($path)) {
		$lines = file( $path );
		foreach( $lines as $line )
		{
			$line = trim( $line );
			if($line) {
				$tokens = explode( ':', $line );
				//check for To starting a line to get to
				if( strtolower($line{0})=='t' and 
					strtolower($line{1})=='o' )
				{
					//The first is the To field
					array_shift( $tokens );
					$indiceArr['to'] = trim(implode(':', $tokens));
				}
				if( strtolower($line{0})=='f' and 
					strtolower($line{1})=='r' and 
					strtolower($line{2})=='o' and 
					strtolower($line{3})=='m' )
				{
					//This is the from field
					array_shift( $tokens );
					$indiceArr['from'] = trim(implode(':', $tokens));
				}
				if( strtolower($line{0})=='s' and 
					strtolower($line{1})=='u' and 
					strtolower($line{2})=='b' and 
					strtolower($line{3})=='j' )
				{
					//This is the subject field
					array_shift( $tokens );
					$indiceArr['subject'] = trim(implode(':', $tokens));
				}
				if( strtolower($line{0})=='d' and 
					strtolower($line{1})=='a' and 
					strtolower($line{2})=='t' and 
					strtolower($line{3})=='e' )
				{
					//remove the date word
					array_shift( $tokens );
					$indiceArr['date'] = trim(implode( ":", $tokens ));
				}
			}
		}
	}
	return $indiceArr;
}
?>
