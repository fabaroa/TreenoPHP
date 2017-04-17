<?php
require_once '../db/db_common.php';
require_once '../settings/settings.php';
require_once '../lib/mime.php';

$db_doc = getDbObject ('docutron');
$settings = new GblStt ('client_files', $db_doc);
$logo = $settings->get('systemLogo');
if( $_SERVER['SERVER_NAME']=='docmgmt.bizds.com' ){
		$logo = $DEFS['DOC_DIR'] . '/images/bizdata.bmp';
}elseif( $_SERVER['SERVER_NAME']=='saas.syndicit.com'){
		$logo = $DEFS['DOC_DIR'] . '/images/SyndicIT.jpg';
}elseif($logo){
		$logo = $DEFS['DATA_DIR'] . '/client_files/logos/' . $logo;
}
if (!$logo or !file_exists ($logo)) {
	$logo = $DEFS['DOC_DIR'] . '/images/logo_whitebg.gif';
}

$type = getMimeType($logo, $DEFS);

header ('Content-type: ' . $type);
readfile ($logo);

?>
