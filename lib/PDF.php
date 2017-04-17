<?php
// $Id: PDF.php 14657 2012-02-06 13:48:38Z acavedon $
/*****************************************************************************/
include_once '../lib/settings.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/random.php';
include_once '../lib/indexing2.php';
include_once '../lib/mime.php';
include_once '../lib/versioning.php';
include_once '../lib/indexing.inc.php';
include_once '../lib/imageFuncs.php';
/*****************************************************************************/

function replaceTiff($PDF, $tmpFiles) {
	$newPDF = $PDF;
	$filename = strtok($PDF, " ");
	for ($i = 0; $i < sizeof($tmpFiles); $i ++) {
		while (!in_array("$filename-tmp", $tmpFiles))
			$filename = strtok(" ");

		$newPDF = str_replace($filename, "$filename-tmp", $newPDF);
		$filename = strtok(" ");
	}
	return ($newPDF);
}

function getImageInfo($myFile,&$maxSizeArr,&$letterSized) {
	global $DEFS;

	$imArr = getimagesize ($myFile);
	$width = $imArr[0];
	$height = $imArr[1];

	if($width > $height) {
		rotate90($myFile,$myFile,$DEFS);
		
		$imArr = getimagesize ($myFile);
		$width = $imArr[0];
		$height = $imArr[1];
	}

	$ratio = $height / $width;
	$mySize = $height * $width;
	if ( $mySize > $maxSizeArr['maxsize']) {
		if ($width > $height) {//landscape
			$maxSizeArr['height'] = $width;
			$maxSizeArr['width'] = $height;
		} else {//portrait
			$maxSizeArr['width'] = $width;
			$maxSizeArr['height'] = $height;
		}
		$maxSizeArr['maxsize'] = $mySize;
		$maxSizeArr['ratio'] = $ratio;
	}
	if ($letterSized) {
		if ($ratio < 0.7) {
			$letterSized = false;
		}
	}
}

function splitBWTiffs($myFile) {
	global $DEFS;
	$userOrig = false;
	$tmpBWArr = array();

	$tmpPath = getUniqueDirectory(dirname($myFile));
	allowWebWrite($tmpPath,$DEFS);
	$oldCwd = getcwd();
	chdir($tmpPath);
	$res = shell_exec($DEFS['TIFFSPLIT_EXE']." ".escapeshellarg($myFile));	

	$f = $myFile;
	$tmpFileArr = scandir($tmpPath);
	foreach($tmpFileArr AS $f) {
		if($f != "." && $f != "..") {
			$st = stat($f);
			if($st[7] > 10) {
				shell_exec($DEFS['TIFFTOPNM_EXE']." ".escapeshellarg($f)." > ".escapeshellarg($f.".pnm"));
				shell_exec($DEFS['PNMTOTIFF_EXE']." -flate ".escapeshellarg($f.".pnm")." > ".escapeshellarg($f));
				$tmpBWArr[] = escapeshellarg($tmpPath.$f);
			} else {
				$userOrig = true;
				break;
			}
		}
	}

	if($userOrig) {
		$tmpBWArr = array();

		shell_exec($DEFS['TIFFTOPNM_EXE']." ".escapeshellarg($myFile)." > ".escapeshellarg($myFile.".pnm"));
		shell_exec($DEFS['PNMTOTIFF_EXE']." -flate ".escapeshellarg($myFile.".pnm")." > ".escapeshellarg($myFile));
		$tmpBWArr[] = escapeshellarg($myFile);
	}
	chdir($oldCwd);
	return $tmpBWArr;
}

function splitGreyTiffs($myFile,$maxSizeArr,$letterSized=true) {
	global $DEFS;
	$tmpBWArr = array();

	$width = $maxSizeArr['width'];
	$height = $maxSizeArr['height'];
	$fname = substr($myFile,0,(strlen($myFile)-5));
	shell_exec($DEFS['TIFFTOPNM_EXE']." ".escapeshellarg($fname)." > ".escapeshellarg($fname.".pnm"));
	shell_exec($DEFS['PNMTOTIFF_EXE']." -flate ".escapeshellarg($fname.".pnm")." > ".escapeshellarg($fname));
	$tmpBWArr[] = escapeshellarg($fname);

	return $tmpBWArr;
}

function adjustFileArr($fArr,$tmpBWArr,$myFile) {
	$tmpFArr = array();
	foreach($fArr AS $f) {
		if($f == escapeshellarg($myFile)) {
			foreach($tmpBWArr AS $tmp) {
				$tmpFArr[] = $tmp;
			}
		} else {
			$tmpFArr[] = $f;
		}
	}
	return $tmpFArr;
}

/*
 * Main Workhorse for creating a single PDF from multiply selected PDFs
 * 
 * Returns:
 * 		0 = success
 * 		1 = failure 
 * 			(of pdftk tool, either not installed correctly or 
 * 			 tmp dir permissions problem)
 * Requires: 
 *     -that pdftk tool is installed in location designated by PDFTK_EXE
 *      in the DMS.DEFS file. 
 * 
 */

function createPDFFromPDFs($fileArr, $myDir, $userName=NULL,$endPath=NULL) {
	global $DEFS;

	// add all selected PDF files to a list
	$pdfList = "";
	foreach ($fileArr as $myFile) {
		$pdfList = $pdfList." ".escapeshellarg($myFile);
	}

	// create output filename
	if($userName) {
		$userTmpDir = $DEFS['TMP_DIR']."/docutron/$userName";
		chdir($userTmpDir);
		$endFile = $userTmpDir . "/$userName-files.pdf";
		if(is_file($endFile)) {
			unlink($endFile);
		}
	} else {
		$endFile = $endPath."/".date("Y-m-d_G-i-s").".pdf";
	}

	// concatinate pdf files into one (pdftk)
	$cmd = $DEFS['PDFTK_EXE'].$pdfList." cat output ".
			escapeshellarg($endFile);

	system($cmd, $ret);

	// cleanup
	delDir($myDir);

	return $ret;
	
}	// end of createPDFFromPDFs()

function createPDFFromTiffs($fileArr, $myDir, $userName=NULL,$endPath=NULL,$imgType="PDF") {
	global $DEFS;

	$bwArr = array();//black and white array
	$cArr = array();//color tiff array

	$fArr = array ();
	$letterSized = true;
	$maxSizeArr = array ('maxsize'=> 0);
	foreach ($fileArr as $myFile) {
		$myFile = $myDir."/".$myFile;
		$tfInfo = shell_exec($DEFS['TIFFINFO_EXE'].' '.escapeshellarg($myFile));
		$tfInfo = explode("\n",$tfInfo);
		if(	false !== strrpos($tfInfo[6],'YCbCr')) {
			$cArr[] = $myFile;
		} elseif(false !== strrpos($tfInfo[6],'min-is-black')) {
			$bwArr[] = $myFile."-grey";
		} else {			
			$bwArr[] = $myFile;
		}

		$fArr[] = escapeshellarg ($myFile);
		getImageInfo($myFile,$maxSizeArr,$letterSized);
	}

	$xDPY = ceil ($maxSizeArr['width'] / 8.5);
	if ($maxSizeArr['ratio'] < 0.7) {//Make calculations based on legal sized paper
		$yDPY = ceil ($maxSizeArr['height'] / 14.0);
	} else {//Make calculations based on letter sized paper
		$yDPY = ceil ($maxSizeArr['height'] / 11.0);
	}

	if ($letterSized) {
		$pSize = '-pletter';
	} else {
		$pSize = '-plegal';
	}

	if(count($bwArr)) {
		foreach($bwArr AS $myFile) {
			if(substr($myFile,-5) != "-grey") {
				$tmpBWArr = splitBWTiffs($myFile);
			} else {
				$tmpBWArr = splitGreyTiffs($myFile,$maxSizeArr,$letterSized);
			}
			$fArr = adjustFileArr($fArr,$tmpBWArr,$myFile);
		}
	}

	if($userName) {
		$userTmpDir = $DEFS['TMP_DIR']."/docutron/$userName";
		chdir($userTmpDir);
		$endFile = $userTmpDir . "/$userName-files.pdf";

		if(is_file($endFile)) {
			unlink($endFile);
		}
	} else {
		if($imgType == "PDF") {
			$endFile = $endPath."/".date("Y-m-d_G-i-s").".pdf";
		} else {
			$endFile = $endPath."/".date("Y-m-d_G-i-s").".tif";
		}
	}

	//tiffcp option handles the commas in the filename
	if($imgType == "PDF") {
		$bigTiff = $myDir.basename($myDir).'.tif';
		tiffcp ($fArr, dirname($bigTiff), basename($bigTiff), '-,=%');
		$cmd = $DEFS['TIFF2PDF_EXE']." -z $pSize -ro -x$xDPY -y$yDPY -o ".escapeshellarg ($endFile).
			' '.escapeshellarg($bigTiff);
		shell_exec ($cmd);
	} else {
		tiffcp ($fArr, $endPath, basename($endFile), '-,=%');
	}
	delDir($myDir);
}

function splitMultiPage($pdfFile, &$db_docInfo, &$dbObjects, $firstPage = false, $keepLooking = true)
{
	global $DEFS;
	$currTime = time();
	if(($currTime - 600) > $db_docInfo->time) {
		$db_docInfo->db = getDbObject('docutron');
		$db_docInfo->time = $currTime; 
	}
	$db_doc = $db_docInfo->db;
	$tempDir = $DEFS['TMP_DIR'].'/'.getRandString();
	while (file_exists($tempDir)) {
		$tempDir = $DEFS['TMP_DIR'].'/'.getRandString();
	}
	mkdir($tempDir);
	if(!file_exists($tempDir)) {
		error_log(  'file exists in the temp dir' );
		return false;
	}
	$myFiles = array ();
	$mimeType = getMimeType ($pdfFile, $DEFS);
	$origType = '';
	if ($mimeType == 'application/pdf') {
		$origType = 'pdf';
		$basePDF = basename($pdfFile, '.pdf');
		$pdfFile = dirname($pdfFile).'/'.escapeshellarg(basename($pdfFile));
 		$pdf2ps = $DEFS['PDF2PS_EXE'] . ' ' .$pdfFile. ' ' . 
 			escapeshellarg ("$tempDir/$basePDF.ps");
		shell_exec($pdf2ps);
		if(!file_exists($tempDir.'/'.$basePDF.'.ps') or
			filesize($tempDir.'/'.$basePDF.'.ps') == 0) {
			error_log( "couldn't convert pdf2ps or filesize is 0" );
			return false;
		}
 		$pstopnm = $DEFS['PSTOPNM_EXE'] . " -xborder 0 -yborder 0 -portrait " .
 			"-ymax 4200 -xmax 2552 -pbm " . escapeshellarg
 			("$tempDir/$basePDF.ps") . ' 2> ';
 		if (substr (PHP_OS, 0, 3) == 'WIN') {
 			$pstopnm .= 'NUL';
 		} else {
 			$pstopnm .= ' /dev/null';
 		}
 		shell_exec ($pstopnm);
		if(!file_exists($tempDir.'/'.$basePDF.'001.pbm') or
				filesize($tempDir.'/'.$basePDF.'001.pbm') == 0) {
			error_log( "couldn't convert pstopnm or filesize is 0" );
			return false;
		}
		unlink($tempDir.'/'.$basePDF.'.ps');
	} else {
		$origType = 'mtif';
		$cmd = $DEFS['TIFFSPLIT_EXE'] . ' '.escapeshellarg ($pdfFile) . ' ' .
			escapeshellarg ($tempDir . '/');
		@shell_exec ($cmd);
		//read in that directory
		$farr = listdir( $tempDir );
		if( sizeof($farr)==0 ){
			error_log( "no files in $tempDir from tiffsplit" );
			return false;
		}
		$st = stat( $tempDir."/".$farr[0] );
		if( $st['size']==0 ){
			error_log( "file size is zero for $tempDir/".$farr[0] );
			return false;
		}
		$dh = opendir ($tempDir);
		$allFiles = array ();
		$file = readdir ($dh);
		while ($file !== false) {
			if (is_file ($tempDir.'/'.$file)) {
				$allFiles[] = $tempDir.'/'.$file;
			}
			$file = readdir ($dh);
		}
		closedir ($dh);

			foreach($allFiles AS $tempDirFile) {
				$cmd = $DEFS['TIFFTOPNM_EXE'] . ' ' . escapeshellarg
					($tempDirFile) . ' > ' . escapeshellarg
					($tempDirFile.'.pnm');
				@shell_exec ($cmd);
				if(!file_exists($tempDirFile.'.pnm') or
					filesize($tempDirFile.'.pnm') == 0) {
				
					return false;
				}
			}
	}
	$batchArray = array ();
	$barcodeArray = array ();
	$currBatch = -1;
	
	$dh = opendir($tempDir);
	while (($file = readdir($dh)) !== false) {
		if (is_file($tempDir.'/'.$file)) {
			if(getMimeType($tempDir.'/'.$file, $DEFS) != 'image/tiff') {
				$cmd = $DEFS['PAMFILE_EXE'] . " ".escapeshellarg($tempDir.'/'.$file);
				$strParse = explode(' ', trim(shell_exec($cmd)));
				$width = $strParse[sizeof($strParse) - 3];
				$height = $strParse[sizeof($strParse) - 1];
				if ($width > $height) {
	 				$cmd = $DEFS['PAMFLIP_EXE'] . ' -r270 ' . escapeshellarg("$tempDir/$file") .
	 				   ' > ' . escapeshellarg ("$tempDir/$file.flipped");
	 				shell_exec($cmd);
					if(!file_exists($tempDir.'/'.$file.'.flipped') or
						filesize($tempDir.'/'.$file.'.flipped') == 0) {
						
						return false;
					}
					unlink($tempDir.'/'.$file);
					rename($tempDir.'/'.$file.'.flipped', $tempDir.'/'.$file);
				}
				$myFiles[] = $file;
			}
		}
	}
	usort($myFiles, 'strnatcasecmp');
	foreach ($myFiles as $myFile) {
		if ($keepLooking) {
			$barcode = getBarcode($tempDir.'/'.$myFile, $db_doc, $dbObjects);
			if ($firstPage) {
				$keepLooking = false;
			}
		} else {
			$barcode = array ();
		}
		if ($barcode) {
			$currBatch++;
			$batchArray[$currBatch] = array ();
			$barcode['split_type'] = empty($barcode['split_type']) ? 'stif' :
				$barcode['split_type'];
			$barcode['delete_barcode'] = $barcode['delete_barcode'] == '0' ? false : true;
			$barcode['compress'] = $barcode['compress'] == '0' ? false : true;
			$barcodeArray[$currBatch] = $barcode; 
			if(!$barcode['delete_barcode'] || (isset ($DEFS['KEEP_BCPAGE']) and $DEFS['KEEP_BCPAGE'])) {
				$batchArray[$currBatch][] = $tempDir.'/'.($myFile);
			}
		} else {
			if ($currBatch == -1) {
				if( isSet($DEFS['ERR_SPLIT_TYPE']) ) {
					$errSplitType = $DEFS['ERR_SPLIT_TYPE'];
				} else {
					$errSplitType = 'stif';
				}
				$barcode = array (
					'number'			=> 0,
					'split_type'		=> $errSplitType,
					'delete_barcode'	=> true,
					'compress'			=> true
				);
				$barcodeArray[0] = $barcode;
				$currBatch = 0;
			}
			$batchArray[$currBatch][] = $tempDir.'/'.($myFile);
		}
	}
	$destDir = dirname($pdfFile);
	$destLoc = $destDir.'/'.getRandString();
	while (file_exists($destLoc)) {
		$destLoc = $destDir.'/'.getRandString();
	}
	for ($i = 0; $i < count($batchArray); $i ++) {
		$splitType = $barcodeArray[$i]['split_type'];
		$compress = $barcodeArray[$i]['compress'];
		$myLoc = Indexing::makeUnique($destLoc);
		mkdir($myLoc);
		if(!file_exists($myLoc)) {
			return false;
		}
		touch($myLoc.'/.lock');

		if ($splitType == 'pdf' or ($splitType == 'asis' and $origType == 'pdf')) {
 			$newArr = array ();
 			foreach ($batchArray[$i] as $myFile) {
 				$newArr[] = escapeshellarg ($myFile);
 			}
 			if (substr (PHP_OS, 0, 3) == 'WIN') {
 				$pbmStr = implode('+', $newArr);
 				$pbmStr = str_replace ('/', '\\', $pbmStr);
 				$myCmd = "copy /b $pbmStr " . escapeshellarg ("$myLoc/1.pbm");
 			} else {
 				$pbmStr = implode(' ', $newArr);
 				$myCmd = "cat $pbmStr > " . escapeshellarg ("$myLoc/1.pbm");
 			}
 			shell_exec($myCmd);
 			$myCmd = $DEFS['PNMTOPS_EXE'] . " -nosetpage -nocenter -imageheight 14 " .
 				"-imagewidth 8.5 " . escapeshellarg("$myLoc/1.pbm") .
 				"| " . $DEFS['PS2PDF_EXE'] . " -dEPSCrop - " . escapeshellarg("$myLoc/1.PDF");
			shell_exec($myCmd);
			if(!file_exists($myLoc.'/1.PDF') or
				filesize($myLoc.'/1.PDF') == 0) {

				return false;
			} else {
				unlink($myLoc.'/1.pbm');
			}
		} else {
			if($splitType == 'mtif' or ($splitType == 'asis' and $origType == 'mtif')) {
				if(!$compress and $origType == 'mtif') {
					$fArray = array ();
					foreach($batchArray[$i] as $myFile) {
						$fArray[] = escapeshellarg(dirname($myFile) . '/' . 
							basename($myFile, '.pnm'));
					}
					tiffcp($fArray, $myLoc, '1.TIF');
					if(!file_exists($myLoc.'/1.TIF') or
						filesize($myLoc.'/1.TIF') == 0) {
						
						return false;
					}
				} else {
					$endTiffs = array ();
					for ($j = 0; $j < count($batchArray[$i]); $j ++) {
						$endName = dirname($batchArray[$i][$j]) . '/' . 
							($j + 1).'.TIFnew';
						$myCmd = $DEFS['PNMTOTIFF_EXE'] . ' -g4 ' . escapeshellarg($batchArray[$i][$j]) . ' > ' .
							escapeshellarg($endName);
						shell_exec($myCmd);
						if(!file_exists($endName) or filesize($endName) == 0) {
							return false;
						}
						if( isSet($DEFS['DROP_BLANKS']) AND $DEFS['DROP_BLANKS'] == '1') {
							if(filesize($endName) < $DEFS['DROP_THRESH']) {
								unlink($endName);
							} else {
								$endTiffs[] = $endName;
							}
						} else {
							$endTiffs[] = $endName;
						}
					}
					tiffcp($endTiffs, $myLoc, '1.TIF');
					if(!file_exists($myLoc.'/1.TIF') or
						filesize($myLoc.'/1.TIF') == 0) {

						return false;
					}
				}
			} else {
				if(!$compress and $origType == 'mtif') {
					for ($j = 0; $j < count($batchArray[$i]); $j ++) {
						$startName = dirname($batchArray[$i][$j]).'/' . 
							basename($batchArray[$i][$j], '.pnm');

						$endName = ($j +1).'.TIF';
						if(!copy($startName, $myLoc.'/'.$endName)) {
							return false;
						}
						if( isSet($DEFS['DROP_BLANKS']) AND $DEFS['DROP_BLANKS'] == '1') {
							if( filesize($myLoc.'/'.$endName) < $DEFS['DROP_THRESH'] ) {
								unlink($myLoc.'/'.$endName);
							}
						}
					}
				} else {
					for ($j = 0; $j < count($batchArray[$i]); $j ++) {
						$endName = $myLoc .'/'.($j + 1).'.TIF';
						$myCmd = $DEFS['PNMTOTIFF_EXE'] . " -g4 " .
							escapeshellarg($batchArray[$i][$j]) . ' > ' .
							escapeshellarg($endName);

						@shell_exec($myCmd);
						if(!file_exists($endName) or filesize($endName) == 0) {
							return false;
						}
						if( isSet($DEFS['DROP_BLANKS']) AND $DEFS['DROP_BLANKS'] == '1') {
							if( filesize($endName) < $DEFS['DROP_THRESH'] ) {
								unlink($endName);
							}
						}
					}
				}
			}
		}
		if (!file_exists ($tempDir.'/ERROR.txt')) {
			$fd = fopen ($myLoc.'/ERROR.txt', 'w+');
			fwrite ($fd, "NO BARCODES FOUND\n");
			fclose ($fd);
		} else {
			copy ($tempDir.'/ERROR.txt', $myLoc.'/ERROR.txt');
		}
		if (isset($barcodeArray[$i])) {
			$fd = fopen($myLoc.'/INDEX.DATRW', 'w+');
			fwrite($fd, $barcodeArray[$i]['number']);
			fclose($fd);
			rename($myLoc.'/INDEX.DATRW', $myLoc.'/INDEX.DAT');
		}
		if(file_exists($pdfFile . '.RT')) {
			if(file_exists($myLoc.'/INDEX.DAT')) {
				if(file_get_contents($myLoc.'/INDEX.DAT') == '0') {
					unlink ($myLoc.'/INDEX.DAT');
					copy($pdfFile . '.RT', $myLoc.'/INDEX.DAT');
				}
			} else {
				copy($pdfFile . '.RT', $myLoc.'/INDEX.DAT');
			}
		}
		unlink($myLoc.'/.lock');
	}
	if(file_exists($pdfFile . '.RT')) {
		unlink($pdfFile . '.RT');
	}
	delDir($tempDir);
	return true;
}

//Due to command line argument list limit this function breaks down the number of arguments
//$tiffArr is the array of file paths including the filename
//$destPath is the destination path of the tiffcped file
//$destFile is the filename that tiffcp should write to
function tiffcp($tiffArr, $destPath, $destFile, $args = '') {
	global $DEFS;
	$endFile = $destPath.'/'.$destFile;
	$endTiffs = array_chunk($tiffArr, 50);
	for($chunk = 0; $chunk < count($endTiffs); $chunk++) {
		$chunkStr = implode(' ', $endTiffs[$chunk]);
		if( file_exists($endFile) ) {
			$myCmd = $DEFS['TIFFCP_EXE'] . ' ' . $args . ' ' . 
				$endFile . ' ' . $chunkStr . ' ' . $destPath .
			       	'/temp.TIF';
			shell_exec($myCmd);
			unlink($endFile);
			rename($destPath.'/temp.TIF', $endFile);
		} else {
			$myCmd = $DEFS['TIFFCP_EXE'] . ' ' . $args . ' ' .
				$chunkStr . ' ' . $endFile;
			shell_exec($myCmd);
		}
	}
}

function isBigPDF($fNameInfo) {
	global $DEFS;
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$nullStr = 'NUL';
	} else {
		$nullStr = '/dev/null';
	}
	$totalCount = 0;
	foreach ($fNameInfo as $myFile) {
		$myOutput = array ();
		exec ($DEFS['TIFFINFO_EXE'].' ' . escapeshellarg($myFile) . ' 2> ' .
			$nullStr, $myOutput);
		foreach ($myOutput as $myLine) {
			if (substr ($myLine, 0, 14) == 'TIFF Directory') {
				$totalCount++;
				if ($totalCount > 40) {
					return true;
				}
			}
		}
	}
	return false;
}

function getBarcode($pbmFile, &$db_doc, &$dbObjects) {
	global $DEFS;
	$regex = '/code="(.*)" crc=/';
	$regexError = '/error="(.*)"/';
	$code128Regex = '/type="128"/';
	$cmd = $DEFS['GOCR_EXE'] . " " . escapeshellarg ($pbmFile) . " 2>";
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$cmd .= ' NUL';
	} else {
		$cmd .= ' /dev/null';
	}
	$ocrData = shell_exec($cmd);
	if( isset ($DEFS['OCRDEBUG']) and $DEFS['OCRDEBUG']=='1' )
		error_log( $pbmFile." ".$ocrData );
	$errors = array ();
	$matches = array ();
	preg_match($regexError, $ocrData, $errors);
	$error = 0.0;
	$errFile = dirname($pbmFile).'/ERROR.txt';
	$fd = fopen ($errFile, 'a+');
	if ($errors and isset ($errors[1])) {
		$error = (float) $errors[1];
		$thresh = (float) $DEFS['OCR_THRESH'];
		fwrite ($fd, "Intermediate File: $pbmFile\n");
		fwrite ($fd, 'Barcode Error: '.$error.", Threshold: $thresh\n");
		if ($error > $thresh) {
			fwrite ($fd, "Barcode error is too high to accept.\n\n");
			fclose ($fd);
			return array();
		}
	}
	preg_match($code128Regex, $ocrData, $matches);
	if (!$matches or !isset ($matches[0])) {
		fwrite($fd, "Barcode type mismatch\n");
		fclose ($fd);
		return array();
	}

	preg_match($regex, $ocrData, $matches);
	if ($matches and isset ($matches[1])) {

		//Checks for invalid characters	in the barcode
		$string = $matches[1];
		for($i=0;$i< strlen($string);$i++) {
			$asciiChr = ord($string{$i});
			if( !($asciiChr > 47 && $asciiChr < 58) //0-9
				&& !($asciiChr > 64 && $asciiChr < 91) //A-Z
				&& !($asciiChr > 96 && $asciiChr < 123) //a-z
				&& ($asciiChr != 32) && ($asciiChr != 9) ) { //space and tab

				fwrite($fd, "Invalid barcode: $string\n");
                fclose($fd);
				return array();
            }
        }

		fwrite ($fd, "Barcode accepted.\n\n");
		fclose ($fd);

		//look for barcode in barcode_reconciliation
		//if there, then get split_type, compress and delete barcode settings
		//if not, look in barcode lookup
		//if there, get db object for department that barcode history is in
		//look in barcode_history for that department
		//if there, then get split_type, compress, and delete barcode settings
		//else set 3 settings to default

		$recRow = getTableInfo($db_doc, 'barcode_reconciliation', array(), array('id' => (int) $string), 'queryRow');

		if(!$recRow) {
			$department = getTableInfo($db_doc, 'barcode_lookup', array('department'), array('id' => (int) $string), 'queryOne');
			if($department) {
				$currTime = time();
				$needNew = false;
				if(isset($dbObjects[$department])) {
					if(($currTime - 600) > $dbObjects[$department]->time) {
						$needNew = true;
					}
				} else {
					$needNew = true;
				}
				
				if($needNew) {
					$dbObjects[$department] = new stdClass();
					$dbObjects[$department]->db = getDbObject($department);
					$dbObjects[$department]->time = $currTime;
				}
				
				$db_dept = $dbObjects[$department]->db;
				$recRow = getTableInfo($db_dept, 'barcode_history', array(), array('barcode_rec_id' => (int) $string), 'queryRow');
			}
		}
		if($recRow) {
			return array (
				'split_type'		=> $recRow['split_type'],
				'compress'			=> $recRow['compress'],
				'delete_barcode'	=> $recRow['delete_barcode'],
				'number'			=> $string
			);
		} else {
			return array (
				'split_type'        => 'stif',
				'compress'          => '1',
				'delete_barcode'    => '1',
				'number'            => $string
			);
		}

//		return array();
	}
	fclose ($fd);
	return array();
}
?>