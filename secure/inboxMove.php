<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/inbox.php';

if (($logged_in == 1 && strcmp($user->username, "") != 0)) {
	if(isset($_GET['movefiles'])) {
		moveToInbox($user);				
	} elseif(isset($_GET['rename'])) {
		renameFolder($user); 
	} elseif(isset($_GET['folderRename'])) {
		renameFolder2($user); 
	} elseif(isset($_GET['inboxView'])) {
		toggleInboxView($user,$_GET['inboxView']);
	} elseif(isset($_GET['searchPanelView'])) {
		toggleSearchPanelView($user,$_GET['searchPanelView']);
	} elseif(isset($_GET['openNew'])) {
		toggleOpenNewWindow ($user);
	} elseif(isset($_GET['fileType'])) {
		$xmlStr = file_get_contents ('php://input');
		$xml = simplexml_load_string ($xmlStr);
		$username = $xml->USERNAME[0];
		$folder = $xml->FOLDER[0];
		$filename = $xml->FILENAME[0];
		$type = $xml->FTYPE[0];

		$path = $DEFS['DATA_DIR']."/".$user->db_name;		
		if($type == 1) {
			$path .= "/personalInbox/$username";
		} else {
			$path .= "/inbox";
		}
		if($folder) {
			$path .= "/$folder";
		}
		$path .= "/$filename";

		$mime = getMimeType($path,$DEFS);
		echo $mime;
	}
} else {
	logUserOut();
}
?>
