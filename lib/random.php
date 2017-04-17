<?php
function getUniqueDirectory($path) 
{
	$dir = $path . '/' . getRandString(12);
	while (is_dir($dir)) {
		$dir = $path . '/' . getRandString(12);
	}

	if( !is_dir( $path ) )
	{
		mkdir( $path );
	}
	$result = @mkdir($dir);
	if (!$result) {
		return false;
	}

	return $dir.'/';
}

function getRand($length)
{

    srand((double)microtime()*1000000);
     
    $vowels = array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U" );
    $cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "u", "v", "w", "tr",
    "cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl");
    $num_vowels = count($vowels);
    $num_cons = count($cons);
     
    for($i = 0; $i < $length; $i++)
    {
        $password .= $cons[rand(0, $num_cons - 1)] . $vowels[rand(0, $num_vowels - 1)];
    }
     
    return substr($password, 0, $length);
}

function getRandString ($size = 14) {
	$str = '';
    for ($i=0; $i < $size; $i++) {
        $str .= chr (mt_rand (97, 122));
    }
    return $str;
}

/*
		$time = explode (' ', microtime ());
        usleep (($time[0] * 1000000) % 100);
function getRandString($length = 14) {
		 $pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		 $pool .= "abcdefghijklmnopqrstuvwxyz";
		$sid = "";
		for($index = 0; $index < $length; $index++)
			$sid .= substr($pool,(rand(0,60)%(strlen($pool))), 1);
	 return($sid);
}

 */
?>
