<?php 
include_once '../lib/settings.php';

$monitorArr = array(	$DEFS['DATA_DIR']."/IDPUsernames/amyv"		=> '0 0 9',
						$DEFS['DATA_DIR']."/IDPUsernames/cindyn"	=> '0 0 10',
						$DEFS['DATA_DIR']."/IDPUsernames/dianef"	=> '0 0 8',
						$DEFS['DATA_DIR']."/IDPUsernames/jessiep" 	=> '0 0 11',
						$DEFS['DATA_DIR']."/IDPUsernames/joanneh"	=> '0 0 4',
						$DEFS['DATA_DIR']."/IDPUsernames/katef"		=> '0 0 19',
						$DEFS['DATA_DIR']."/IDPUsernames/shawnee"	=> '0 0 7',
						$DEFS['DATA_DIR']."/IDPUsernames/tasha"		=> '0 0 6',
						$DEFS['DATA_DIR']."/IDPUsernames/vanessa"	=> '0 0 5',
						$DEFS['DATA_DIR']."/IDPUsernames/cantiello"	=> '0 0 18' );

$dest = $DEFS['DATA_DIR']."/splitPDF";
foreach($monitorArr AS $dir => $barcode) {
	if(is_dir($dir)) {
		$handle = opendir($dir);
		while(false !== ($file = readdir($handle))) {
			if(is_file($dir."/".$file)) {
				if((filectime($dir."/".$file) + 60) < time()) {
					createRTFile($dir."/".$file,$barcode);
					rename($dir."/".$file.".RT", $dest."/".$file.".RT"); 
					rename($dir."/".$file, $dest."/".$file); 
				}
			}
			clearstatcache();
		}
	}
}

function createRTFile($filePath,$barcode) {
	$fp = fopen($filePath.".RT","w+");
	fwrite($fp,$barcode);
	fclose($fp);
}
?>
