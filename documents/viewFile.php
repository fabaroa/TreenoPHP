<?php 
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';
include_once '../lib/mime.php';

if ($logged_in == 1 && strcmp($user -> username, "") != 0) {
	if(isSet($_GET['fileID'])) {
		$cab		= $_GET['cab'];
		$file_id	= $_GET['fileID'];

		$uname = ($user->username) ? $user->username : $user->email;
		$dep = ($user->db_name) ? $user->db_name : $user->department;
		if($user->db_name) {
			$db_dept = $db_object;
			$doDisconnect = false;
		} else {
			$db_dept = getDbObject($user->department);
			$doDisconnect = true;
		}
		$wArr = array('id' => (int)$file_id);
		$fileInfo = getTableInfo($db_dept,$cab."_files",array(),$wArr,'queryRow');
		if(!$fileInfo['display']) {
			$wArr = array(	'parent_id' => (int)$fileInfo['parent_id'],
					'display' => 1 );
			$fileInfo = getTableInfo($db_dept,$cab."_files",array(),$wArr,'queryRow');
		}

		$sArr = array('location');
		$wArr = array('doc_id' =>(int)$fileInfo['doc_id']);
		$loc = getTableInfo($db_dept,$cab,$sArr,$wArr,'queryOne');
		$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
		$location .= "/".$fileInfo['subfolder']."/".$fileInfo['filename'];
		$file_type = getMimeType($location, $DEFS);
		$filename = $fileInfo['filename'];
		$filename = urlencode($filename);
		$file_id = $fileInfo['id'];

		$num = 0;
		$userStt = new Usrsettings($uname, $dep);
		$num = $userStt->get('viewRestrict');
		$portal = 0;
		if(isset($_GET['portal'])) {
			$portal = 1;
		}
?>
<html>
<head>
<script>
	function openOtherFile(download) {
		window.location = "../energie/readfile.php?"
					+ "fileID=<?php echo $file_id; ?>"
					+ "&cab=<?php echo $cab; ?>"
					+ "&portal=<?php echo $portal; ?>"
					+ "&download="+download
					+ "&tmp=0/<?php echo $filename; ?>";	
	}

	function openJpegFile(download) {
		window.location = "../energie/display2.php?"
					+ "fileID=<?php echo $file_id; ?>"
					+ "&cab=<?php echo $cab; ?>"
					+ "&portal=<?php echo $portal; ?>"
					+ "&download="+download
					+ "&tmp=0/<?php echo $filename; ?>";	
	}
</script>
</head>
<body scroll="no" style="margin:0px"
	<?php if($file_type == "image/jpeg"): ?>
		onload="openJpegFile(0)"
		>
	<?php elseif($file_type != "image/tiff"): ?>
		onload="openOtherFile(0)"
		>
	<?php else: ?>
>
	<object width=100% height=100%
		data="../energie/readfile.php?fileID=<?php echo $file_id; ?>&cab=<?php echo $cab; ?>&portal=<?php echo $portal; ?>" type="image/tiff">
		<param name="src" value="../energie/readfile.php?fileID=<?php echo $file_id; ?>&cab=<?php echo $cab; ?>&portal=<?php echo $portal; ?>">
		<param name="access" value="<?php echo $num; ?>">
	</object>
	<?php endif; ?>
</body>
</html>
<?php
		if($doDisconnect) {
			$db_dept->disconnect ();
		}
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
