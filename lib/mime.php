<?php
include_once '../centera/centera.php';
include_once 'filename.php';
include_once '../lib/settings.php';
require_once '../lib/imageFuncs.php';
/*
 * returns a files extension
 */
function getExtension($str) {
	$pos = strrchr($str, '.');
	if ($pos !== false) {
		$ext = substr($pos, 1);
	} else {
		$ext = '';
	}
	return $ext;
}

function getMimeType($f, $DEFS) {
if (file_exists($f)) {
	$ext = strtolower(getExtension($f));
	$f = escapeshellarg($f);
	if( $ext == "xls" || $ext == "csv" ) {
		return( "application/vnd.ms-excel" );
	} elseif( $ext == "ppt" ) {
		return( "application/vnd.ms-powerpoint" );
	} elseif( $ext == "vsd" ) {
		return( "application/vnd.visio" );
	} elseif( $ext == "msg" ) {
		return( "application/msoutlook" );
	} elseif ($ext == 'txt') {
		return ('text/plain');
	} elseif( $ext == 'wav' || $ext == 'mp3' || $ext == 'ogg') {
		return ('audio/x-wav');
	} elseif( $ext == 'avi' || $ext == 'mov' || $ext == 'mpeg') {
		return ('video/x-avi');
	} elseif( $ext == 'docx' ) {
		return( "application/vnd.openxmlformats-officedocument.wordprocessingml.document" );
	} elseif( $ext == 'pptx' ) {
		return( "application/vnd.openxmlformats-officedocument.presentationml.presentation" );
	} elseif( $ext == 'xlsx' ) {
		return( "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" );
	} elseif( $ext == 'html' or $ext == 'htm' ) {
		return( "text/html" );
	}
	
	$type = trim(shell_exec($DEFS['FILE_EXE'] . ' -bi '.$f));
	return $type;
} else {
	return false;
}
}

function downloadFile($path, $filename, $attach, $delete, $realFilename = '', $quickView=false) {
	global $DEFS;
 	if ($path) {
 		$finalPath = $path.'/'.$filename;
 	} else {
 		$finalPath = $filename;
 	}
 	$fsize = filesize($finalPath);
	if($realFilename) {
		$headerFName = strtolower($realFilename);
	} else {
		$headerFName = strtolower(str_replace("/", "-", $filename));
	}
	$headerFName = str_replace(";","",$headerFName);

	//This prevents the header from sending the file as an attachment
	$headerFName = str_replace("attachment", "_", $headerFName);	
	
	if ($attach) {
		$cDisp = 'attachment;';
	} else {
		$cDisp = 'inline;';
	}

	if( $quickView ){
				
		$type = getMimeType($finalPath, $DEFS);
		$tempname = array();
		$deleteinfo = false;
		if( $type=='application/pdf' ){
			$tempname = getJpegImagePath( $finalPath );
			$finalPath = $tempname;
			$type = getMimeType($finalPath, $DEFS );
			$fsize = filesize( $finalPath );
			$headerFName = $headerFName.'.jpg';
			$deleteinfo = true;
		}
			
		$headerArr = array (
			'Content-type: '.$type,
			'Content-Length: '.$fsize,
			'Cache-Control:',
			'Pragma:',
			'Content-Disposition: '.$cDisp.' filename="'.$headerFName.'"', 
		);
	
		ini_set('zlib.output_compression', 'Off');
		foreach($headerArr as $myHeader) {
			header($myHeader);
		}
	 	readfile($finalPath);
		if( $deleteinfo ){
			unlink( $tempname );
		}
	} else {
		$type = getMimeType($finalPath, $DEFS);
		$headerArr = array (
			'Content-type: '.$type,
			'Content-Length: '.$fsize,
			'Cache-Control:',
			'Pragma:',
			'Content-Disposition: '.$cDisp.' filename="'.$headerFName.'"', 
		);
	
		ini_set('zlib.output_compression', 'Off');
		foreach($headerArr as $myHeader) {
			header($myHeader);
		}
	 	readfile($finalPath);
	}
}
?>
