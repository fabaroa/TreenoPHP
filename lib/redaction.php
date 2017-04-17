<?php
require_once '../lib/utility.php';
require_once '../lib/settings.php';
require_once '../db/db_common.php';
require_once '../lib/redactionObj.php';


function checkAndSetRedact($db_doc, $myID, & $myFile) {
	lockTables($db_doc, array ('files_to_redact'));
	$myFile = getTableInfo ($db_doc, 'files_to_redact', array (),
		array ('id' => (int) $myID), 'queryRow');
	if ($myFile['locked'] == 1) {
		unlockTables($db_doc);
		return false;
	} else {
		updateTableInfo ($db_doc, 'files_to_redact',
			array ('locked' => 1), array ('id' => (int) $myID));
		unlockTables($db_doc);
		return true;
	}
}

function getReadyRedactID($db_doc, $cabinet, $fileID, $db_name) {
	$whereArr = array ('cabinet' => $cabinet, 'file_id' => (int) $fileID, 'department' => $db_name);
	$row = getTableInfo($db_doc, 'files_to_redact', array ('id'), $whereArr, 'queryOne');
	return $row;
}

function redactFile($db_dept, $db_doc, $dataDir, $cabinet, $fileID, $db_name) {
	$concatArr = array ("'$dataDir/'", "REPLACE(location, ' ', '/')", "'/'", "COALESCE(".
		dbConcat(array ($cabinet.'_files.subfolder', "'/'")).", '')");
	$getDataQuery = "SELECT ".dbConcat($concatArr).','.
		"{$cabinet}_files.filename,parent_id,xml_data FROM {$cabinet}_files, redactions, $cabinet WHERE ".
		"{$cabinet}_files.id = ".(int)$fileID." AND {$cabinet}_files.doc_id = ".
		"$cabinet.doc_id AND redaction_id = redactions.id";

	//This query gets 3 items, two concatenations and a column. In at least
	//PostgreSQL, if you fetch it with MDB2_FETCHMODE_ASSOC, it then replaces the
	//first concatenation with the second one. This is wrong. To get around this
	//issue, MDB2_FETCHMODE_ORDERED is needed. Concatenations should not ever be
	//fetched with MDB2_FETCHMODE_ASSOC because it cannot be assumed what the
	//column is called.
	$row = $db_dept->queryRow($getDataQuery, array(), MDB2_FETCHMODE_ORDERED);
	dbErr($row);
	list ($path, $fname, $parentID, $xmlData) = $row;
	$sArr = array('filename');
	$wArr = array('id' => (int)$parentID);
	$parentFilename = getTableInfo($db_dept,$cabinet.'_files',$sArr,$wArr,'queryOne');
	$parentPath = $path.$parentFilename;
	$newPath = $path.$fname;
	$parseRedact = new parseRedact();
	$parseRedact->parse($xmlData);
	$caHash = $parseRedact->newImage($parentPath, $newPath);
	$statArr = stat($newPath);
	$fSize = $statArr[7];

	$uArr = array (	'redaction' => 'COMPLETED', 
			'file_size' => (int) $fSize,
			'ca_hash' => $caHash);
	$wArr = array('id' => (int) $fileID);
	updateTableInfo ($db_dept, $cabinet.'_files',$uArr,$wArr);
	$updateArr = array ('quota_used' => 'quota_used+'.$fSize);
	$whereArr = array ('real_department' => $db_name);
	updateTableInfo($db_doc, 'licenses', $updateArr, $whereArr, 1);
}

function redactionDone($db_doc, $redactionID) {
	deleteTableInfo ($db_doc, 'files_to_redact',
		array ('id' => (int) $redactionID));
}

function checkRedaction($db_dept, $db_doc, $cabinet, $fileID, $db_name, $dataDir) {
	global $DEFS;
	global $user;
	$whereArr = array ("id" => (int) $fileID, "display" => 1, "deleted" => 0);

	$fInfo = getTableInfo($db_dept, $cabinet."_files", array (), $whereArr, 'queryRow');
	if ($fInfo['redaction'] != '') {
		$retVal = $fInfo['filename'];
	} else {
		$retVal = '';
	}
	if ($fInfo['redaction'] == 'IN PROCESS') {
		$readyRedactID = getReadyRedactID($db_doc, $cabinet, $fileID, $db_name);
		$myFile = array ();
		if (checkAndSetRedact($db_doc, $readyRedactID, $myFile)) {
			//You have control over the file, redact
			redactFile($db_dept, $db_doc, $dataDir, $cabinet, $fileID, $db_name);
			redactionDone($db_doc, $readyRedactID);
		} else {
			//It is currently being redacted by a bot, another user, etc.
			//Loop and wait for file for max 5 seconds, keep going.
			//fma 6-10-2010 ticket #998871 change to 10 seconds
			//TODO: handling errors and stuff
			$i = 0;
			while ($i < 10) {
				sleep(1);
				$whereArr = array ("id" => (int) $fileID, "display" => 1, "deleted" => 0);

				$fInfo = getTableInfo($db_dept, $cabinet."_files", array (), $whereArr, 'queryRow');
				if ($fInfo['redaction'] == 'COMPLETED') {
					break;
				}
				$i ++;
				error_log("***************waiting to redact ".$i);
			}
			if ($i==5) error_log("**************never finished redacting");
		}
	}
	if(check_enable('centera',$user->db_name) and $retVal ) {
		$location = getTableInfo($db_dept, $cabinet, array('location'), 
			array('doc_id' => (int) $fInfo['doc_id']), 'queryOne');
		
		$filePath = $dataDir.'/'.str_replace(' ', '/', $location).'/' . 
			$fInfo['subfolder'].'/'.$fInfo['filename'];
		centget($DEFS['CENT_HOST'], $fInfo['ca_hash'], $fInfo['file_size'],$filePath,$user,$cabinet);
		if(file_exists($filePath . '.adminRedacted.ca_hash')) {
			$hashInfo = file_get_contents($filePath . '.adminRedacted.ca_hash');
			$hashArr = explode(',', $hashInfo);
			centget($DEFS['CENT_HOST'], $hashArr[0], $hashArr[1],$filePath.'.adminRedacted',$user,$cabinet);
		}
	}
	
	return $retVal;
}

function createStamp($department, $username, $timestamp, $time, $width, $height, $topImg = '', $xScale = 0, $yScale = 0) {
	global $DEFS;
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$font = 'C:/WINDOWS/fonts/arial.ttf';
	} else {
		if(isSet($DEFS['TTFONT'])) {
			$font = $DEFS['TTFONT'];
		} else {
			$font = '/usr/X11R6/lib/X11/fonts/TTF/luxisr.ttf';
		}
	}
	if ($topImg == 'APPROVED' or $topImg == 'DENIED') {
		$typeTxt = $topImg;
		$i = 0;
		$ret = array (0, 0, 0, 0, 0, 0, 0, 0);
		$oldWidth = 0;
		$fontProps = array ();
		while ($ret[4] < $width and (- $ret[7] + $ret[3]) < ($height / 2)) {
			$fontProps[] = $ret;
			$i ++;
			$ret = imagettfbbox($i, 0, $font, $typeTxt);
		}
		array_pop($fontProps);
		$largeFont = array_pop($fontProps);
		$centerDiff = ($width - $largeFont[4]) / 2;
		$largeFontSize = $i -2;
		$smallFontSize = $largeFontSize / 3;
	} else {
		$fileName = $DEFS['DATA_DIR'].'/'.$department.'/stamps/'.$topImg;
		$imgOnDisk = imagecreatefromstring(file_get_contents($fileName));
		$typeTxt = '';
		if ($timestamp) {
			$i = 0;
			$ret = array (0, 0, 0, 0, 0, 0, 0, 0);
			$fontProps = array ();
			$testTxt = 'APPROVED';
			while ($ret[4] < $width and (- $ret[7] + $ret[3]) < ($height / 2)) {
				$fontProps[] = $ret;
				$i ++;
				$ret = imagettfbbox($i, 0, $font, $testTxt);
			}
			array_pop($fontProps);
			$largeFont = array_pop($fontProps);
			$centerDiff = ($width - $largeFont[4]) / 2;
			$largeFontSize = $i -2;
			$smallFontSize = $largeFontSize / 3;
		}
	}

	if ($typeTxt or $timestamp) {
		$img = imagecreatetruecolor($width, $height);
		imagetruecolortopalette($img, false, 256);
		$bgColor = imagecolorallocate($img, 230, 230, 230);
		imagecolortransparent($img, $bgColor);

		$red = 128;
		$green = 0;
		$blue = 0;
		if(isSet($DEFS['STAMPCOLOR'])) {
			$rgbInfo = explode(",",$DEFS['STAMPCOLOR']);	
			$red = $rgbInfo[0];
			$green = $rgbInfo[1];
			$blue = $rgbInfo[2];
		}
		$textColor = imagecolorallocate($img, $red, $green, $blue);
		imagefilledrectangle($img, 0, 0, $width -1, $height -1, $bgColor);
		$typeTxtY = - $largeFont[7] + $largeFont[3] + 5;
		if ($typeTxt) {
			imagettftext($img, $largeFontSize, 0, $centerDiff, $typeTxtY, $textColor, $font, $typeTxt);
		} else {
			$topWidth = imagesx($imgOnDisk);
			$topHeight = imagesy($imgOnDisk);
			if ($topWidth > $width) {
				$newWidth = $width;
				$newHeight = $topHeight * $newWidth / $topWidth;
				if ($newHeight > $height / 2) {
					$oldNewHeight = $newHeight;
					$newHeight = $height / 2;
					$newWidth = $newWidth * $newHeight / $oldNewHeight;
				}
			}
			elseif ($topHeight > $height / 2) {
				$newHeight = $height / 2;
				$newWidth = $topWidth * $newHeight / $topHeight;
				if ($newWidth > $width) {
					$oldNewWidth = $newWidth;
					$newWidth = $width;
					$newHeight = $newHeight * $newWidth / $oldNewWidth;
				}
			}
			elseif ($xScale and $yScale) {
				$newWidth = ceil($xScale * $topWidth);
				$newHeight = ceil($yScale * $topHeight);
			} else {
				$newWidth = $topWidth;
				$newHeight = $topHeight;
			}
			$centerDiffX = ($width - $newWidth) / 2;
			$centerDiffY = ($height / 2 - $newHeight) / 2;
			imagecopyresampled($img, $imgOnDisk, $centerDiffX, $centerDiffY, 0, 0, $newWidth, $newHeight, $topWidth, $topHeight);
		}
		$byTxt = 'By: '.$username;
		$byFont = imagettfbbox($smallFontSize, 0, $font, $byTxt);
		$centerDiff = ($width - $byFont[4]) / 2;
		$userTxtY = $height / 2 + (- $byFont[7] + $byFont[3]) + $smallFontSize / 2;
		imagettftext($img, $smallFontSize, 0, $centerDiff, $userTxtY, $textColor, $font, $byTxt);
		if($timestamp) {
			$timeStr = strftime("%Y-%m-%d %H:%M:%S", $time);
			$byFont = imagettfbbox($smallFontSize, 0, $font, $timeStr);
			$centerDiff = ($width - $byFont[4]) / 2;
			$timeTxtY = $userTxtY + (- $byFont[7] + $byFont[3]) + $smallFontSize / 2;
			imagettftext($img, $smallFontSize, 0, $centerDiff, $timeTxtY, $textColor, $font, $timeStr);
		}
	} else {
		$img = $imgOnDisk;
	}
	return $img;
}
?>
