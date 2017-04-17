<?php
// $Id: uploadLogo.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/fileFuncs.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isSuperUser()) {
	$db_doc = getDbObject ('docutron');
	$gblStt = new GblStt('client_files', $db_doc);
	$defLogo = $gblStt->get('systemLogo');
	if(isSet($_POST['B1'])) {
		$fileUploadName = str_replace("'","",$_FILES['f1']['name']);
		$fileUploadName = str_replace("`","",$fileUploadName);
		$dest = $DEFS['DATA_DIR']."/client_files/logos";
		if(!is_dir($dest)) {
			mkdir($dest,0755);
			allowWebWrite($dest,$DEFS);
		}
		$dest .= "/".$fileUploadName;
		$source = $_FILES['f1']['tmp_name'];
		move_uploaded_file($source,$dest);
	} elseif(isset($_POST['B2'])) {
		$logo = $_POST['logo'];
		$gblStt->set('systemLogo',$logo);
		$defLogo = $logo;
		$path = $DEFS['DATA_DIR']."/client_files/logos/".$logo;
		//--Change to use dynamic php script--
		//$dest = $DEFS['DOC_DIR']."/images/".$logo;
		//copy($path,$dest);
	}
	
	$logoList = array();
	$path = $DEFS['DATA_DIR']."/client_files/logos";
	if(is_dir($path)) {
		$hd = openDir($path);
		while(false !== ($file = readdir($hd))) {
			if(is_file($path."/".$file)) {
				$logoList[] = $file;
			}
		}
	}
	
	
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>System Logo</title>
	<script>
	</script>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<style>
	</style>
</head>
<body>
	<div class="mainDiv">
		<div class='mainTitle'>
			<span>Upload System Logo</span>
		</div>
		<div style="padding-top:5px;padding-bottom:5px">
			<form name="uploadLogo"
				method="POST"
				enctype="multipart/form-data"
				action="uploadLogo.php"
				style="padding:0px">
				<input id='fileUpload' type='file' name='f1' size='40' style="height:20px;font-size:9pt" />
				<input type="submit" name="B1" value="Upload" style="height:20px;font-size:9pt" />
			</form>
		</div>
		<div style="padding:3px">
			<form name="setDefault" method="POST" action="uploadLogo.php">
				<fieldset>
					<legend>Previously Uploaded Files</legend>
					<table style="width:95%;text-align:left;margin-right:auto;margin-left:auto">
					<?php for($i=0;$i<count($logoList);$i++): ?>
						<tr>
							<td style="width:50%">
								<input type="radio" name="logo" value="<?php echo $logoList[$i]; ?>" 
								<?php if($defLogo == $logoList[$i]): ?>
									checked="checked"
								<?php endif; ?>
								/>
								<span><?php echo $logoList[$i]; ?></span>
							</td>
							<td style="width:50%">
							<?php if(($i+1) <= count($logoList) - 1): ?>
								<input type="radio" name="logo" value="<?php echo $logoList[($i+1)]; ?>"
								<?php if($defLogo == $logoList[($i+1)]): ?>
									checked="checked"
								<?php endif; ?>
								/>
								<span><?php echo $logoList[($i+1)]; ?></span>
							<?php else: ?>
								&nbsp;
							<?php endif; ?>
							</td>
						</tr>
					<?php $i++; ?>
					<?php endfor; ?>
					</table>
					<div style="text-align:right">
						<input type="submit" name="B2" value="Save" />
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
