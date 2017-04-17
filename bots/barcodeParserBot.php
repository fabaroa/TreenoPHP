<?php 
include_once '../lib/settings.php';
include_once '../lib/PDF.php';

$barcodeParserObj = new barcodeParser();
$barcodeParserObj->getFilesFromInbox();
$barcodeParserObj->processFiles();

class imageSplitter {
	var $tempDir;
	var $mimeType;
	var $origType;
	var $splitFiles;
	var $pnmFiles;
	var $batchArray;
	var $barcodeArray;
	var $currBatch;
	var $firstPage;
	var $keepLooking;
	var $errFile;
	var $batchPathArr;

	function imageSplitter($firstPage=false,$keepLooking=true) {
		$this->tempDir = "";
		$this->mimeType = "";
		$this->origType = "";
		$this->splitFiles = array();
		$this->pnmFiles = array();
		$this->batchArray = array();
		$this->barcodeArray = array();
		$this->currBatch = -1;
		$this->firstPage = $firstPage; 
		$this->keepLooking = $keepLooking; 
		$this->errFile = "";
		$this->batchPathArr = array();
	}

	function splitPages($fpath) {
		//split file
		global $DEFS;
		$currTime = time();
		if(($currTime - 600) > $db_docInfo->time) {
			$db_docInfo->db = getDbObject('docutron');
			$db_docInfo->time = $currTime; 
		}
		$db_doc = $db_docInfo->db;

		$this->createTempDir();
		$this->mimeType = getMimeType ($fpath);
		$origType = '';
		if($this->mimeType == 'application/pdf') {
			
		} else {
			$this->origType = 'mtif';
			$cmd = 'tiffsplit '.escapeshellarg ($fpath) . ' ' . $this->tempDir . '/';
			@shell_exec ($cmd);

			$this->ifFilesExist();
			$this->ifValidFirstFile();
			$this->getSplitFiles();
			$this->createPNMsFromTiffs();
			$this->flipImages();
		}
		$this->lookForBarcode();

		$destDir = dirname($fpath);
		$destLoc = $destDir.'/'.getRandString();
		while (file_exists($destLoc)) {
			$destLoc = $destDir.'/'.getRandString();
		}

		for($i=0;$i<count($this->batchArray);$i++) {
			$splitType = $this->barcodeArray[$i]['split_type'];
			$compress = $this->barcodeArray[$i]['compress'];
			$deleteBarcode = $this->barcodeArray[$i]['delete_barcode']; 
			$myLoc = Indexing::makeUnique($destLoc);
			mkdir($myLoc);
			if(!file_exists($myLoc)) {
				return false;
			}
			touch($myLoc.'/.lock');

			if ($splitType == 'pdf' or ($splitType == 'asis' and $this->origType == 'pdf')) {
				for($j = 0; $j < count($this->batchArray[$i]); $j++) {
					$this->batchArray[$i][$j] = escapeshellarg($this->batchArray[$i][$j]);
				}
				$pbmStr = implode(' ', $this->batchArray[$i]);
				$myCmd = "cat $pbmStr > $myLoc/1.pbm";
				shell_exec($myCmd);
				if(!file_exists($myLoc.'/1.pbm') or
					filesize($myLoc.'/1.pbm') == 0) {

					return false;
				}
				$myCmd = "/usr/bin/pnmtops -nosetpage -nocenter -imageheight 14 " .
					"-imagewidth 8.5 $myLoc/1.pbm | ps2pdf -dEPSCrop - $myLoc/1.PDF";
				shell_exec($myCmd);
				if(!file_exists($myLoc.'/1.PDF') or
					filesize($myLoc.'/1.PDF') == 0) {
					return false;
				} else {
					unlink($myLoc.'/1.pbm');
				}
			} else {
				if($splitType == 'mtif' or ($splitType == 'asis' and $this->origType == 'mtif')) {
					if(!$compress and $this->origType == 'mtif') {
						$fArray = array ();
						foreach($this->batchArray[$i] as $myFile) {
							$fArray[] = escapeshellarg(dirname($myFile) . '/' . 
								basename($myFile, '.pnm'));
						}
						$myCmd = 'tiffcp ' . implode(' ', $fArray) . ' ' . $myLoc . '/1.TIF';
						shell_exec($myCmd);
						if(!file_exists($myLoc.'/1.TIF') or
							filesize($myLoc.'/1.TIF') == 0) {
							
							return false;
						}
					} else {
						$endTiffs = array ();
						for ($j = 0; $j < count($this->batchArray[$i]); $j ++) {
							$endName = dirname($this->batchArray[$i][$j]) . '/' . 
								($j + 1).'.TIFnew';
							$myCmd = 'pnmtotiff -g4 ' . escapeshellarg($this->batchArray[$i][$j]) . ' > ' .
								escapeshellarg($endName);
							shell_exec($myCmd);
							if(!file_exists($endName) or filesize($endName) == 0) {
								return false;
							}
							$endTiffs[] = $endName;
						}
						$myCmd = 'tiffcp ' . implode(' ', $endTiffs) . ' ' . $myLoc . '/1.TIF';
						shell_exec($myCmd);
						if(!file_exists($myLoc.'/1.TIF') or
							filesize($myLoc.'/1.TIF') == 0) {

							return false;
						}
					}
				} else {
					if(!$compress and $this->origType == 'mtif') {
						for ($j = 0; $j < count($this->batchArray[$i]); $j ++) {
							$startName = dirname($this->batchArray[$i][$j]).'/' . 
								basename($this->batchArray[$i][$j], '.pnm');

							$endName = ($j +1).'.TIF';
							if(!copy($startName, $myLoc.'/'.$endName)) {
								return false;
							}
						}
					} else {
						for ($j = 0; $j < count($this->batchArray[$i]); $j ++) {
							$endName = $myLoc .'/'.($j + 1).'.TIF';
							$myCmd = "pnmtotiff -g4 " .
								escapeshellarg($this->batchArray[$i][$j]) . ' > ' .
								escapeshellarg($endName);

							@shell_exec($myCmd);
							if(!file_exists($endName) or filesize($endName) == 0) {
								return false;
							}
						}
					}
				}
			}
			if (!file_exists ($tempDir.'/ERROR.txt')) {
				$this->writeToErrorFile($myLoc,"NO BARCODES FOUND\n");
			} else {
				copy ($tempDir.'/ERROR.txt', $myLoc.'/ERROR.txt');
			}

			if(is_array($this->confArr)) {
				$this->writeIndexAutoFile($myLoc,$this->barcodeArray[$i]['number']);
			} elseif (isset($this->barcodeArray[$i])) {
				$this->writeDATRWFile($myLoc,$this->barcodeArray[$i]['number']);
				rename($myLoc.'/INDEX.DATRW', $myLoc.'/INDEX.DAT');
			}

			chownDir($myLoc, 'apache', 'apache');
			$this->ifRTFile($myLoc,$fpath);
			unlink($myLoc.'/.lock');

			$this->batchPathArr[] = $myLoc;
		}
		if(file_exists($fpath . '.RT')) {
			unlink($fpath . '.RT');
		}
		delDir($this->tempDir);
		return true;
	}

	function ifRTFile($path,$file) {
		if(file_exists($file . '.RT')) {
			if(file_exists($path.'/INDEX.DAT')) {
				if(file_get_contents($path.'/INDEX.DAT') == '0') {
					unlink ($path.'/INDEX.DAT');
					copy($file . '.RT', $path.'/INDEX.DAT');
				}
			} else {
				copy($file . '.RT', $path.'/INDEX.DAT');
			}
		}
	}

	function writeDATRWFile($path,$mess) {
		$fd = fopen($path.'/INDEX.DATRW', 'w+');
		fwrite($fd,$mess);
		fclose($fd);
	}

	function writeToErrorFile($path,$mess) {
		$fp = fopen($path.'/ERROR.txt','a+');
		fwrite($fp,$mess);
		fclose($fp);
	}

	function createTempDir() {
		global $DEFS;
		$tempDir = $DEFS['TMP_DIR'].'/'.getRandString();
		while (file_exists($tempDir)) {
			$tempDir = $DEFS['TMP_DIR'].'/'.getRandString();
		}
		mkdir($tempDir);
		$this->tempDir = $tempDir;
	}

	function getSplitFiles() {
		$allFiles = array ();
		$dh = opendir ($this->tempDir);
		while(false !== ($file = readdir($dh))) {
			if(is_file($this->tempDir.'/'.$file)) {
				$allFiles[] = $this->tempDir.'/'.$file;
			}
		}
		closedir ($dh);

		$this->splitFiles = $allFiles;
	}

	function createPNMsFromTiffs() {
		foreach($this->splitFiles AS $tempDirFile) {
			$cmd = 'tifftopnm ' . escapeshellarg
				($tempDirFile) . ' > ' . escapeshellarg
				($tempDirFile.'.pnm');
			@shell_exec ($cmd);
			if(!file_exists($tempDirFile.'.pnm') or filesize($tempDirFile.'.pnm') == 0) {
				return false;
			}
		}
	}

	function flipImages() {
		$myFiles = array();
		$dh = opendir($this->tempDir);
		while (($file = readdir($dh)) !== false) {
			if (is_file($this->tempDir.'/'.$file)) {
				if(getMimeType($this->tempDir.'/'.$file) != 'image/tiff') {
					$cmd = "pamfile ".escapeshellarg($this->tempDir.'/'.$file);
					$strParse = explode(' ', trim(shell_exec($cmd)));
					$width = $strParse[sizeof($strParse) - 3];
					$height = $strParse[sizeof($strParse) - 1];
					if ($width > $height) {
						shell_exec("pnmflip -r270 $this->tempDir/$file > $this->tempDir/$file.flipped");
						if(!file_exists($this->tempDir.'/'.$file.'.flipped') or
							filesize($this->tempDir.'/'.$file.'.flipped') == 0) {
							
							return false;
						}
						unlink($this->tempDir.'/'.$file);
						rename($this->tempDir.'/'.$file.'.flipped', $this->tempDir.'/'.$file);
					}
					$myFiles[] = $file;
				}
			}
		}
		usort($myFiles, 'strnatcasecmp');
		$this->pnmFiles = $myFiles;
	}

	function lookForBarcode() {
		$db_doc = getDbObject('docutron');
		foreach ($this->pnmFiles as $myFile) {
			if ($this->keepLooking) {
				$bcObj = new barcodeReader('gocr','39',$db_doc);
				$bcObj->readBarcode($this->tempDir.'/'.$myFile);
				$barcode = $bcObj->processOCRData();
				if ($this->firstPage) {
					$this->keepLooking = false;
				}
			} else {
				$barcode = array ();
			}

			if ($barcode) {
				$this->currBatch++;
				$this->batchArray[$this->currBatch] = array();
				$barcode['split_type'] = (empty($barcode['split_type'])) ? 'stif' : $barcode['split_type'];
				$barcode['delete_barcode'] = ($barcode['delete_barcode'] == '0') ? false : true;
				$barcode['compress'] = ($barcode['compress'] == '0') ? false : true;
				$this->barcodeArray[$this->currBatch] = $barcode; 
				if(!$barcode['delete_barcode'] || (isset ($DEFS['KEEP_BCPAGE']) and $DEFS['KEEP_BCPAGE'])) {
					$this->batchArray[$this->currBatch][] = $this->tempDir.'/'.($myFile);
				}
			} else {
				if ($this->currBatch == -1) {
					$barcode = array (	'number'			=> 0,
										'split_type'		=> 'stif',
										'delete_barcode'	=> true,
										'compress'			=> true );
					$this->barcodeArray[0] = $barcode;
					$this->currBatch = 0;
				}
				$this->batchArray[$this->currBatch][] = $this->tempDir.'/'.($myFile);
			}
		}
	}

	function ifFilesExist() {
		//read in that directory
		$farr = listdir( $this->tempDir );
		if( sizeof($farr)==0 ){
			error_log( "no files in $this->tempDir from tiffsplit" );
			return false;
		}
	}

	function ifValidFirstFile() {
		$st = stat( $this->tempDir."/".$farr[0] );
		if( $st['size']==0 ){
			error_log( "file size is zero for $this->tempDir/".$farr[0] );
			return false;
		}
	}
}

class barcodeParser extends imageSplitter {
	var $confPath;
	var $confArr;
	var $fileArr;

	function barcodeParser() {
		imageSplitter::imageSplitter();
		$this->confPath = "/etc/opt/docutron/barcodeParser.conf";
		$this->confArr = parse_ini_file($this->confPath,true);
		$this->fileArr = array();
	}

	function getFilesFromInbox() {
		global $DEFS;
		$path = $DEFS['DATA_DIR'];
		foreach($this->confArr AS $dept => $info) {
			$username = $info['userinbox'];
			$cab = $info['cabinet'];

			$inboxPath = $path."/".$dept."/personalInbox/".$username;	
			$hd = opendir($inboxPath);
			$fList = array();
			while(false !== ($file = readdir($hd))) {
				if(is_file($inboxPath."/".$file)) {
					$fList[]['path'] = $inboxPath."/".$file;
					$fList[count($fList)-1]['department'] = $dept;
				}
			}
		}
		$this->fileArr = $fList;
	}

	function processFiles() {
		global $DEFS;
		foreach($this->fileArr AS $fileInfo) {
			$this->splitPages($fileInfo['path']);	

			$dept = $fileInfo['department'];
			$cab = $this->confArr[$dept]['cabinet'];
			$bPath = $DEFS['DATA_DIR']."/$dept/indexing/$cab";
			foreach($this->batchPathArr AS $batch) {
				$dirArr = explode("/",$batch);
				rename($batch,$bPath."/".$dirArr[count($dirArr)-1]);
echo $batch."----";
echo $bPath."/".$dirArr[count($dirArr)-1]."\n";
			}
			$this->batchPathArr = array();
		}
	}

	function writeIndexAutoFile($path,$bc) {
		$fp = fopen($path.'/INDEX.AUTO','w+');
		fwrite($fp,'"'.$bc.'";"'.date('Y-m-d').'"');
		fclose($fp);
	}
}

class barcodeReader {
	var $ocr_engine;
	var $ocrData;
	var $regex;
	var $regexError;
	var $codeRegex;
	var $errors;
	var $matches;
	var $db_doc;
	var $dbObjects;
	var $errFile;
	var $bcType;

	function barcodeReader($ocr_engine="gocr",$bctype=128,$db_doc=NULL) {
		$this->ocr_engine = $ocr_engine;
		$this->ocrData = "";
		$this->regex = '/code="(.*)" crc=/';
		$this->regexError = '/error="(.*)"/';
		$this->codeRegex = '/type="'.$bctype.'"/';
		$this->bcType = $bctype;
		$this->errors = array();
		$this->matches = array();
		$this->db_doc = (!$db_doc) ? getDbObject('docutron') : $db_doc;
		$this->dbObjects = array();
		$this->errFile = "";
	}

	function readBarcode($pbmFile) {
		global $DEFS;
		$this->errFile = dirname($pbmFile);
		$cmd = $this->ocr_engine." " . escapeshellarg ($pbmFile) . " 2> /dev/null";
		$this->ocrData = shell_exec($cmd);

		if( isset ($DEFS['OCRDEBUG']) and $DEFS['OCRDEBUG']=='1' ) {
			error_log( $pbmFile." ".$this->ocrData );
		}
	}

	function checkBarcodeThreshold() {
		global $DEFS;

		preg_match($this->regexError, $this->ocrData, $this->errors);
		$error = 0.0;
		if ($this->errors and isset ($this->errors[1])) {
			$error = (float) $this->errors[1];
			$thresh = (float) $DEFS['OCR_THRESH'];
			$this->writeToErrorFile("Intermediate File: $pbmFile\n");
			$this->writeToErrorFile('Barcode Error: '.$error.", Threshold: $thresh\n");
			if ($error > $thresh) {
				$this->writeToErrorFile("Barcode error is too high to accept.\n\n");
				return array();
			}
		}
	}

	function checkBarcodeType() {
		preg_match($this->codeRegex, $this->ocrData, $this->matches);
		if (!$this->matches or !isset ($this->matches[0])) {
			$this->writeToErrorFile("Barcode type mismatch\n");
			return array();
		}
	}

	function checkInvalidBarcode() {
		$string = $this->matches[1];
		for($i=0;$i< strlen($string);$i++) {
			$asciiChr = ord($string{$i});
			if( !($asciiChr > 47 && $asciiChr < 58) //0-9
				&& !($asciiChr > 64 && $asciiChr < 91) //A-Z
				&& !($asciiChr > 96 && $asciiChr < 123) //a-z
				&& ($asciiChr != 32) && ($asciiChr != 9) ) { //space and tab

				$this->writeToErrorFile("Invalid barcode: $string\n");
				return array();
			}
		}
		$this->writeToErrorFile("Barcode accepted.\n\n");
	}

	function processOCRData()  {
		$this->checkBarcodeThreshold();
		$this->checkBarcodeType();

		preg_match($this->regex, $this->ocrData, $this->matches);
		if ($this->matches and isset ($this->matches[1])) {
			//Checks for invalid characters	in the barcode
			$this->checkInvalidBarcode();

			//look for barcode in barcode_reconciliation
			//if there, then get split_type, compress and delete barcode settings
			//if not, look in barcode lookup
			//if there, get db object for department that barcode history is in
			//look in barcode_history for that department
			//if there, then get split_type, compress, and delete barcode settings
			//else set 3 settings to default
			if($this->bcType == "39" && $recRow = $this->findBarcode()) {
				return array (	'split_type'		=> $recRow['split_type'],
								'compress'			=> $recRow['compress'],
								'delete_barcode'	=> $recRow['delete_barcode'],
								'number'			=> $this->matches[1] );
			} else {
				return array (	'split_type'        => 'stif',
								'compress'          => '1',
								'delete_barcode'    => ($this->bcType == "39") ? '0' : '1',
								'number'            => $this->matches[1] );
			}
		}
		return array();
	}

	function findBarcode() {
		$wArr = array('id' => (int)$this->matches[1]);
		$recRow = getTableInfo($this->db_doc,'barcode_reconciliation',array(),$wArr,'queryRow');
		if(!$recRow) {
			$sArr = array('department');
			$wArr = array('id' => (int)$this->matches[1]);
			$department = getTableInfo($this->db_doc,'barcode_lookup',$sArr,$wArr,'queryOne');
			if($department) {
				$currTime = time();
				$needNew = false;
				if(isset($this->dbObjects[$department])) {
					if(($currTime - 600) > $this->dbObjects[$department]->time) {
						$needNew = true;
					}
				} else {
					$needNew = true;
				}
				
				if($needNew) {
					$this->dbObjects[$department] = new stdClass();
					$this->dbObjects[$department]->db = getDbObject($department);
					$this->dbObjects[$department]->time = $currTime;
				}
				
				$db_dept = $this->dbObjects[$department]->db;
				$wArr = array('barcode_rec_id' => (int)$this->matches[1]);
				$recRow = getTableInfo($db_dept,'barcode_history',array(),$wArr,'queryRow');
			}
		}
		return $recRow;
	}

	function writeToErrorFile($mess) {
		$fp = fopen($this->errFile.'/ERROR.txt','a+');
		fwrite($fp,$mess);
		fclose($fp);
	}
}
?>
