<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/inbox.php';
include_once '../lib/quota.php';
include_once '../centera/centera.php';
include_once '../modules/modules.php';
include_once '../lib/licenseFuncs.php';

if($logged_in == 1 && strcmp($user->username, "") != 0) {
	$URL = '';
	$destName = "";
	$errorMsg = "";

	if(!isValidLicense($db_doc)) {
		$errorMsg = 'Invalid License Cannot upload files';
	} elseif(isset($_FILES['finput']) && strcmp($_FILES['finput']['name'], '') != 0) {
		$ext = getExtension($_FILES['finput']['name']);

		if(isSet($DEFS['MAX_UPLOAD_FILESIZE_EXCEPTIONS'])) {
			$extArr = explode(",",$DEFS['MAX_UPLOAD_FILESIZE_EXCEPTIONS']);
		}
		
		$destPath = $_SESSION['uploadTmpDest'];
		//Do not allow someone to upload a php file for security reasons.
		if(strcmp(strtolower($ext), "php") != 0) {
			if(isSet($extArr)) {
				if(!in_array(strtolower($ext),$extArr)) {
					if(isSet($DEFS['MAX_UPLOAD_FILESIZE'])) {
						$maxSize = $DEFS['MAX_UPLOAD_FILESIZE'];
						if(strtolower(substr($maxSize,-1)) == "k") {
							$max = substr($maxSize,0,-1) * pow(1024,1);	
						} else if(strtolower(substr($maxSize,-1)) == "m") {
							$max = substr($maxSize,0,-1) * pow(1024,2);	
						} else if(strtolower(substr($maxSize,-1)) == "g") {
							$max = substr($maxSize,0,-1) * pow(1024,3);	
						}
					}
				}
			}

			if(!isSet($max) || $max >= $_FILES['finput']['size']) {
				$orgName = $_FILES['finput']['name'];
		
				//Replace bad characters with a '_'.
				$destName = preg_replace('/[^a-z0-9_\-\.]/i', '_', $orgName);
				$oldDestName = $destName;

				$ct = 1;
				while(file_exists("$destPath/$destName")) {
					$tmpName = substr($oldDestName,0,-(strlen($ext)+1));
					$destName = "$tmpName-$ct.$ext";
					$ct++;
				}
				
				if(checkQuota($db_doc, $_FILES['finput']['size'],$user->db_name)) {
					if(!is_dir($destPath)) {
						mkdir($destPath);
					}
					if(move_uploaded_file($_FILES['finput']['tmp_name'], "$destPath/$destName")) {
						allowWebWrite ("$destPath/$destName", $DEFS);
						$errorMsg = 'File successfully Uploaded';
					} else {
						$destName = "";
						$errorMsg = 'Error uploading file...Please try again';
					}
				} else {
					$destName = "";
					$errorMsg = 'Quota Failed';	
				}
			} else {
				$destName = "";
				$errorMsg = 'File too large to upload';	
			}
		} else {
			$errorMsg = 'Cannot upload php files';
		}
	} else {
		$errorMsg = 'No file was selected to upload';
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Upload to Inbox</title>
	<script>
		var uploadMess = '<?php echo $errorMsg; ?>';
		var fname = '<?php echo $destName; ?>';
		function stopProgressBar() {
			parent.toggleIsFinished(uploadMess,fname);
		}
	</script>
</head>
<body onload="stopProgressBar()">
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
