<?php
include_once '../lib/fileFuncs.php';
function getJpegImagePath( $path ){
	global $DEFS;
	$tempname = tempnam( $DEFS['TMP_DIR'], 'quickview' );
	rename( $tempname, $tempname.'.jpg' );
	$tempname = $tempname.'.jpg';
	@shell_exec ( $DEFS['CONVERT_EXE']." $path".'[0]'." $tempname" );
	return $tempname;
}

function rotate90($filepath,$newfilepath,$DEFS) {
	$dir = dirname($newfilepath);
	$tempFilePath = $dir."/1.pnm";
	$tempFilePath2 = $dir."/2.pnm";
	$cmd = $DEFS['TIFFTOPNM_EXE'] . ' ' . escapeshellarg ($filepath) . ' > ' . escapeshellarg($tempFilePath); 
	shell_exec ($cmd);
	$cmd = $DEFS['PAMFLIP_EXE'] .  ' -r90 ' . escapeshellarg($tempFilePath) . ' > ' . escapeshellarg($tempFilePath2);
	shell_exec ($cmd);

	$searchTerm = "Bits/Sample: 1";
	if( tiffInfo($filepath, $searchTerm) ) {
		$cmd = $DEFS['PNMTOTIFF_EXE'] . ' -g4 ' . escapeshellarg($tempFilePath2) . ' > ' . escapeshellarg ($newfilepath);
	} else {
		$cmd = $DEFS['PNMTOTIFF_EXE'] . ' -flate -quiet ' . escapeshellarg($tempFilePath2) . ' > ' . escapeshellarg ($newfilepath);
	}
	shell_exec($cmd);	

	allowWebWrite( $newfilepath, $DEFS );
	unlink($tempFilePath);
	unlink($tempFilePath2);
}

function flip($filepath,$newfilepath,$DEFS) {
	$dir = dirname($newfilepath);
	$tempFilePath = $dir."/1.pnm";
	$tempFilePath2 = $dir."/2.pnm";
	$cmd = $DEFS['TIFFTOPNM_EXE'] . ' ' . escapeshellarg ($filepath) . ' > ' . escapeshellarg($tempFilePath); 
	shell_exec ($cmd);
	$cmd = $DEFS['PAMFLIP_EXE'] .  ' -tb ' . escapeshellarg($tempFilePath) . ' > ' . escapeshellarg($tempFilePath2);
	shell_exec ($cmd);

	$searchTerm = "Bits/Sample: 1";
	if( tiffInfo($filepath, $searchTerm) ) {
		$cmd = $DEFS['PNMTOTIFF_EXE'] . ' -g4 ' . escapeshellarg($tempFilePath2) . ' > ' . escapeshellarg ($newfilepath);
	} else {
		$cmd = $DEFS['PNMTOTIFF_EXE'] . ' -flate -quiet ' . escapeshellarg($tempFilePath2) . ' > ' . escapeshellarg ($newfilepath);
	}
	shell_exec ($cmd);

	allowWebWrite( $newfilepath, $DEFS );
	unlink($tempFilePath);
	unlink($tempFilePath2);
}

//Checks the tiffinfo of the file to see if the $searchTerm exists
function tiffInfo($filePath, $searchTerm) {
	global $DEFS;
	$tfInfo = shell_exec($DEFS['TIFFINFO_EXE'] . ' '.escapeshellarg($filePath));
	$tfInfo = explode("\n", $tfInfo);

	if( in_array($searchTerm, $tfInfo) ) {
		return true;
	}
	return false;
}

function createThumbnail($fileLoc, $thumbLoc, $db_doc, $department, $fileSize = NULL) {
	global $DEFS;
	$ext = getExtension($fileLoc);
	$ext = strtolower($ext);
	if(!file_exists(dirname($thumbLoc))) {
		makeAllDir(dirname($thumbLoc));
	}
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$redirect = '2> NUL';
	} else {
		$redirect = '2> /dev/null';
	}
	$fileSize = is_null($fileSize) ? '65x80' : $fileSize;
	$cmd = $DEFS['CONVERT_EXE'] . " -thumbnail ".$fileSize." -colorspace RGB ".escapeshellarg($fileLoc). 
		"[0] ".escapeshellarg($thumbLoc);
	shell_exec($cmd);
	if ($ext == 'jpg' or $ext == 'jpeg') {
		$drawOverlay = true;
		$iconPath = $DEFS['DOC_DIR']."/images/smallJpeg.jpg";
	} elseif ($ext == 'pdf') {
		$drawOverlay = true;
		$iconPath = $DEFS['DOC_DIR']."/images/smallpdf.gif";
	} else {
		$drawOverlay = false;
		$iconPath = '';
	}
	if ($drawOverlay) {
		$escIcon = escapeshellarg($iconPath);
		if(substr(PHP_OS, 0, 3) == 'WIN') {
			$escIcon = addslashes($escIcon);
		}
		$cmd = $DEFS['CONVERT_EXE'] . " -gravity SouthEast -draw \"image Over 0,0 0,0 ".
			$escIcon ."\" " .
			escapeshellarg($thumbLoc) .' '.
			escapeshellarg($thumbLoc) . ' ' . $redirect;
		shell_exec($cmd);
	}

	if(file_exists($thumbLoc)) {
		$finfo = stat($thumbLoc);
		$updateArr = array('quota_used'=>'quota_used+'.$finfo[7]);
		$whereArr = array('real_department'=> $department);
		updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
	}
	allowWebWrite($thumbLoc, $DEFS);
}
?>
