<?php
// This gets values from the DMS.DEFS file by reading in each line
// and settings the values as such

if(isset($_SESSION) and isset($_SESSION['DEFS'])) {
	$DEFS = $_SESSION['DEFS'];
} else {
	$relDMS = realpath(dirname(__FILE__) . '/../../config/DMS.DEFS');
	$incDefs = '';
	if( file_exists( '/etc/opt/treeno/DMS.DEFS' ) ){
		$incDefs = '/etc/opt/treeno/DMS.DEFS';
	} elseif( file_exists( '/etc/opt/docutron/DMS.DEFS' ) ){
		$incDefs = '/etc/opt/docutron/DMS.DEFS';
	} elseif(file_exists('/etc/docutron/DMS.DEFS')) {
		$incDefs = '/etc/docutron/DMS.DEFS';
	} elseif (file_exists('c:/docutron/DMS.DEFS')) {
		$incDefs = 'c:/docutron/DMS.DEFS';
	} elseif (file_exists($relDMS)) {
		$incDefs = $relDMS; 
	}
	if($incDefs) {
		$lines = file($incDefs);
		foreach($lines as $line) {
			if($line{0} != '#') {
				if( strpos($line, "LOGO") !== false ) {
					$t = substr( $line, 0, strpos($line, "="));
					$t = trim($t);
					$value = substr( $line, strpos($line, "=") + 1);
					$value = trim($value);
					$DEFS[$t] = eval("return ".$value);
				}
				else
			  {
					if(substr(PHP_VERSION, 0, 1) >= 5 and substr(PHP_VERSION, 2, 3) >= 3)
					{
						$t = preg_split('%=%', trim($line));//preg functions allow any non-alphanumeric character as regex delimiters
					}
					else
					{
						$t = explode('=', trim($line),2);
					}
					
					if (isset ($t[1])) {
						$DEFS[trim($t[0])] = trim($t[1]);
					}
				}
			}
		}
		if(isset($DEFS['USE_SECURE_PASSWORDS']) && ($DEFS['USE_SECURE_PASSWORDS'] == '1'))
		{
			$DEFS['DB_PASS'] = base64_decode($DEFS['DB_PASS']);
		}
		else{
			$DEFS['USE_SECURE_PASSWORDS'] = '0';
		}
		$_SESSION['DEFS'] = $DEFS;
	}
}

?>
