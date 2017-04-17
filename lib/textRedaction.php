<?php
require_once '../check_login.php';
require_once '../lib/redactionObj.php';
require_once '../lib/mime.php';

if($logged_in and $user->username) {

	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$font = 'C:/WINDOWS/fonts/arial.ttf';
	} else {
		if(isSet($DEFS['TTFONT'])) {
			$font = $DEFS['TTFONT'];
		} else {
			$font = '/usr/X11R6/lib/X11/fonts/TTF/luxisr.ttf';
		}
	}
	$text='';
	if(isset($_GET['text']))
	{
		if(strlen($_GET['text']) > 0){ $text = $_GET['text']; }
		
		if($_GET['echo'] == 1) {
			$fontSize = strlen($_GET['fontSize']) ? $_GET['fontSize'] : 11; 
			header('Content-type: image/png');
			imagepng(getTextImage($text, $fontSize));
		}
			
	}
}

function getTextImage($text, $fontsize=11) 
{	
	global $font;
	
	$text = preg_replace("%[\r]+%", "%[\n]+%", $text);
	
	//create the image
	$im = imagecreatetruecolor(550, 550);
	imagetruecolortopalette($im, false, 256);
//	$vars = detectTextSize($text, $fontsize)
	$vars = imageWordWrapBBox ( $text, 549, $fontsize, $font );	
	$text = $vars[2];
//	print_r($text);
	$height = $vars[1];
	$width = $vars[0];
	$bbox = imagettfbbox($fontsize, 0, $font, $text);
	
	//make the image transparent
	$bgColor = imagecolorallocate($im, 230, 230, 230);
	$textcolor = imagecolorallocate($im, 0, 0, 0);
	imagecolortransparent($im, $bgColor);
	imagefilledrectangle($im, 0, 0, 549, 549, $bgColor);
	
	// This is our cordinates for X and Y
	$x = 0;
	$y = 15;

	imagettftext($im, $fontsize, 0, $x, $y, $textcolor, $font, $text);
	// Output the image
	return ($im);
}

function detectTextSize($text, $fontsize, $chunkLength=65)
{
	
	$strLength = strlen($text);
	$textLength = $strLength * $fontsize;
	$chars = array();
	$results = array();
	
	if($strLength > $chunkLength) {
		//split at the 52nd character and if it's not a space
		//add a dash.
		$x = 0;
	    $chars = str_split($text, $chunkLength);
	    
		$text = implode("\n", $chars);
	}
	
	$results = explode("\n", $text);
	
	$height = count($results) > 1 ? count($results) + 2: 2; 
	$width = $strLength * $fontsize;
	return array($width, (( $height )* 20), $text);
}
function imageWordWrapBBox ( $Text, $Width = 650, $FontSize = 10, $Font = './fonts/arial.ttf' )
{
    $Words = split ( ' ', $Text );
    $Lines = array ( );
    $Line  = '';

    foreach ( $Words as $Word )
    {
        $Box  = imagettfbbox ( $FontSize, 0, $Font, $Line . $Word );
        $Size = $Box[4] - $Box[0];
        if ( $Size > $Width )
        {
            $Lines[] = trim ( $Line );
            $Line    = '';
        }
        $Line .= $Word . ' ';
    }
    $Lines[] = trim ( $Line );

    $Dimensions = imagettfbbox ( $FontSize, 0, $Font, 'AJLMYabdfghjklpqry019`@$^&*(,' );
    $lineHeight = $Dimensions[1] - $Dimensions[5];
		$text = implode("\n", $Lines);

    return array ( $Width,$lineHeight, $text );
}

?>