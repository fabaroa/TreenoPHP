<?php
include_once '../check_login.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/imageFuncs.php';
include_once '../lib/cabinets.php';
include_once '../lib/mime.php';
include_once '../lib/quota.php';
include_once '../lib/redaction.php';
include_once '../modules/modules.php';
include_once '../centera/centera.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$thumbArr = $_SESSION['thumbnailArr'];
	$fileID = $_GET['fileID'];
	$cabinet = $_GET['cab'];
	if (isset ($thumbArr[$fileID])) {
		$fileLoc = $thumbArr[$fileID]['fileLoc'];
		$thumbLoc = $thumbArr[$fileID]['thumbLoc'];
		if ($user->checkSecurity($cabinet)) {
			if(check_enable('redaction', $user->db_name)) {
				if(checkRedaction($db_object, $db_doc, $cabinet, $fileID, $user->db_name, $DEFS['DATA_DIR']) != '') {
					if($user->checkSetting('viewNonRedact', $cabinet)) {
						$fileLoc .= '.adminRedacted';
						$thumbLoc .= '.adminRedacted.jpeg';
					}
				}
			}
			if (!file_exists($thumbLoc)) {
				if(check_enable('centera', $user->db_name) and !empty ($DEFS['CENT_HOST'])) {
					lockTables( $db_object, array( 'audit' ) );
					centget($DEFS['CENT_HOST'], $thumbArr[$fileID]['ca_hash'],$thumbArr[$fileID]['file_size'], $fileLoc,$user,$cabinet);
					unlockTables( $db_object );
				}
				createThumbnail($fileLoc, $thumbLoc, $db_doc, $user->db_name);
			}
		}
		$ext = getExtension($fileLoc);
		$ext = strtolower($ext);
		if (file_exists($thumbLoc)) {
			$finfo = stat($thumbLoc);
			if ($finfo[7] == 0) {
				$user->audit("error with thumbnail", $thumbLoc);
				if ($ext=="pdf") {
					$thumbLoc = $DEFS['DOC_DIR']."/images/smallpdf.gif";
				} else {
					$thumbLoc = $DEFS['DOC_DIR']."/images/thumb.jpg";
				}
			}
			downloadFile("", $thumbLoc, false, false);
		} else {
			$user->audit("error with thumbnail", $thumbLoc);
			if ($ext=="pdf") {
				$thumbLoc = $DEFS['DOC_DIR']."/images/smallpdf.gif";
			} else {
				$thumbLoc = $DEFS['DOC_DIR']."/images/thumb.jpg";
			}
			downloadFile("", $thumbLoc, false, false);
		}
	} else {
		$thumbLoc = $DEFS['DOC_DIR']."/images/thumb.jpg";
		downloadFile("", $thumbLoc, false, false);
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
