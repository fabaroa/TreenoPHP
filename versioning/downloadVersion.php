<?php

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/versioning.php';
include_once '../lib/cabinets.php';

if($logged_in == 1 && strcmp($user->username, "") != 0) {
	preg_match('/[0-9].*/', $user->db_name, $match);
	if($match) {
		$dbID = $match[0];
	} else {
		$dbID = '';
	}
	$cabinetID = $_GET['cabinetID'];
	$db_object = $user->getDbObject();
	$cabinetName = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinetID), 'queryOne');
	if(isset($_GET['parentID'])) {
		$parentID = $_GET['parentID'];
		$fileID = getRecentID($cabinetName, $parentID, $db_object);
	} else 
		$fileID = $_GET['fileID'];
	
	$fileRow = getTableInfo($db_object, $cabinetName.'_files', array(), array('id' => (int) $fileID), 'queryRow');
	$whereArr = array('doc_id'=>(int)$fileRow['doc_id']);
	$result = getTableInfo($db_object,$cabinetName,array(),$whereArr);
	$row = $result->fetchRow();
    $path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/".$row['location']);
    if(isset($fileRow['subfolder']) and $fileRow['subfolder']) {
       $path = $path."/".$fileRow['subfolder'];		
    }
	if ($user->checkSetting ('prefixCheckOut', $cabinetName)) {
		$dispName = "[$dbID-$cabinetID-$fileID]{$fileRow['filename']}";
	} else {
		$dispName = $fileRow['parent_filename'];
	}
	if(isset($DEFS['CENTERA_MODULE']) and $DEFS['CENTERA_MODULE'] == '1') {
		centget($DEFS['CENT_HOST'], $fileRow['ca_hash'],
			$fileRow['file_size'], $path.'/'.$fileRow['filename'],$user);
	}
	downloadFile($path, $fileRow['filename'], true, false, $dispName);
} else {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
	"http://www.w3.org/TR/xhtml1.1/strict.dtd">
<html>
    <body>
	    <script type="text/javascript">
            document.onload = top.window.location = "../logout.php";
        </script>
    </body>
</html>
<?php
}

	setSessionUser($user);

?>
