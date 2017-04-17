<html>
<head>
</head>
<body bgcolor="#FFFFFF">
<table align='center'>
 <tr>
 </tr>
</table>
<br><br>
<?php 
 define ('__TRACE_ENABLED__', false);
 define ('__DEBUG_ENABLED__', false);
 require("barcode.php");		   
 require("c128aobject.php");
$keys = array_keys( $_GET );
switch( strtolower($keys[0]) ) 
{
	case 'clientid':
		$type = "1";
		break;
	case 'policyid':
		$type = "2";
		break;
	case 'lossid':
		$type = "3";
		break;
	case 'memoid':
		$type = "4";
		break;
	case 'marketingid':
		$type = "5";
		break;
	case 'inbox':
		$type = "6";
		break;
	case 'createbc':
		break;
	default:
		die("not found");
}

$barcode = $_GET[$keys[0]];
if( $type!="" )
{
	$barcode= $type." ".$barcode;
}
$barcode = strtoupper( $barcode );
$output = "png";
$type = "C128A";
$style = 164;
$drawtext = 'on';
if (!isset($output))  $output   = "png"; 
if (!isset($barcode)) $barcode  = "0123456789";
if (!isset($type))    $type     = "I25";
if (!isset($width))   $width    = "800";
if (!isset($height))  $height   = "120";
if (!isset($xres))    $xres     = "2";
if (!isset($font))    $font     = "4";
	$obj = new C128AObject(250, 120, $style, $barcode);
echo "<center><table border=1><tr><td>$keys[0]</td><td>".$_GET[$keys[0]]."</td></tr></table></center>\n";
for( $i=0;$i<10;$i++ )
	echo "<br>";
if ($obj) 
{
	if ($obj->DrawObject($xres)) 
	{
		echo "<table align='center'><tr><td><img src='./image.php?code=";
		echo $barcode."&style=".$style."&type=".$type."&width=".$width;
		echo "&height=".$height."&xres=".$xres."&font=".$font."'></td></tr></table>";
	} 
	else
	{ 
		echo "<table align='center'><tr><td><font color='#FF0000'>".($obj->GetError());
		echo "</font></td></tr></table>";
	}
}

?>
<br>
</body>
</html>
