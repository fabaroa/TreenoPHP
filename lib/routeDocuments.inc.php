<?php
/**
 * Driver for routeDocuments3
 * 
 * @package RouteDocuments
 */
class RouteDocuments {
	
	/**
	 * Object for the 'docutron' database.
	 * @var object PEAR::DB
	 */
	var $db_doc;
	
	/**
	 * A list of all departments in Docutron installation.
	 * @var array string
	 */
	var $deptList;
	
	/**
	 * Stuff from DMS.DEFS
	 * @var array string
	 */
	var $DEFS;
	
	/**
	 * Last update of department list
	 * @var int
	 */
	var $last;

	var $db_dept;
	var $db_deptName;
	var $gblStt;
	
	/**
	 * Constructor
	 * 
	 * @param array $DEFS An associative list of stuff from DMS.DEFS
	 */
	function RouteDocuments(&$DEFS) {
		$this->db_doc = getDbObject('docutron');
		
		$this->DEFS =& $DEFS;
	}

	function checkBarcodeVal($myStr) {
		$myStr = str_replace(' ', '', $myStr);
		if(is_numeric($myStr)) {
			return true;
		} elseif (substr($myStr, 0, 2) == 'WF' && is_numeric(substr($myStr, 2))) {
			return true;
		} elseif (substr($myStr, 0, 1) == 'W' && is_numeric(substr($myStr, 1))) {
			return true;
		} elseif (substr($myStr, 0, 1) == 'V' && is_numeric(substr($myStr, 1))) {
			return true;
		}		 
		return false;
	}

	function checkUserName($userName) {
		return preg_match('/^[A-Z0-9_-]/i', $userName);
	}
	
	/**
	 * Get array of ScannedBatch located in given directory in Scan directory.
	 * 
	 * If the directory contains an INDEX.DAT, the array will have one element
	 * in it. If the directory contains a INDEX.TMP, the array will have as 
	 * many elements as there are lines in the INDEX.MDAT. If the directory
	 * does not contain these files but contains an INDEX.TMP, the directory
	 * is skipped and the return value is an empty array, because INDEX.TMP
	 * means that the batch from the Kodak software has not finished
	 * transferring. If there is a '.lock' file, the directory is skipped as
	 * well, because another Docutron process is accessing it.
	 * 
	 * @param $myDirs Array of String Directory inside the Scan directory
	 * @return array Array of ScannedBatch
	 */
	function &getBatches($dirName) {
		$time = time();
		if($time - $this->last > 600) {
			$this->deptList = getTableInfo($this->db_doc, 'licenses',
				array('real_department'), array(), 'queryCol');
			$this->last = $time;
		}

		$batches = array ();
		if(is_dir($dirName)) {
				
			$datFile = $dirName.'/INDEX.DAT';
			$mDatFile = $dirName.'/INDEX.MDAT';
			$tmpFile = $dirName.'/INDEX.TMP';
			$lockFile = $dirName.'/.lock';
			if(file_exists($datFile) && !file_exists($lockFile)) {
				$fileArr = file ($datFile);
				$barcodeArr = explode(';', trim($fileArr[0]));
				$barcodeStr = str_replace('"', '',
					$barcodeArr[count($barcodeArr) - 1]);

				if(!$this->checkBarcodeVal($barcodeStr)) {
					$barcodeStr = '';
				}
				//error_log("getBatches() barcodeStr: ".$barcodeStr);
				$userName = '';
				if(isset($fileArr[1])) {
					$userName = trim($fileArr[1]);
				}
				if(!$this->checkUserName($userName)) {
					$userName = '';
				}

				$department = '';
				if(isset($fileArr[2])) {
					$department = trim($fileArr[2]);
				}
				if(substr($department, 0, 12) != 'client_files') {
					$department = '';
				}
				$deptAdmin = '';
				if(isset($fileArr[3])) {
					$deptAdmin = trim($fileArr[3]);
				}
				if(!$this->checkUserName($deptAdmin)) {
					$deptAdmin = '';
				}

				$inFolder = true;
				if(isset($fileArr[4])) {
					$inF = trim($fileArr[4]);
					if($inF == '0') {
						$inFolder = false;
					}
				}
					
				$batches[] = new BarcodeBatch($dirName,
					$barcodeStr, $this->db_doc, $this->deptList, $this->DEFS,
					$this, $userName, $department, $deptAdmin, $inFolder);
					
			} elseif(file_exists($mDatFile) && !file_exists($lockFile)) {
				$dh = safeOpenDir($dirName);
				$myEntry = readdir($dh);
				$dirArr = array ();
				while($myEntry !== false) {
					if(is_dir($dirName.'/'.$myEntry) and 
						$myEntry != '.' and $myEntry != '..') {
							
						$dirArr[] = $dirName.'/'.$myEntry;
					}
					$myEntry = readdir($dh);
				}
				closedir($dh);
				usort($dirArr, 'strnatcasecmp');
				$barcodeList = file($mDatFile);
				for($i = 0; $i < count($dirArr); $i++) {
					$barcodeArr = explode(';', trim($barcodeList[$i]));
					$barcodeStr = str_replace('"', '', $barcodeArr[count($barcodeArr) - 1]);
					$batches[] = new BarcodeBatch($dirArr[$i], $barcodeStr,
						$this->db_doc, $this->deptList, $this->DEFS, $this, 'admin - INDEX.MDAT');
																
				}
				unlink($mDatFile);
			} elseif(!file_exists($tmpFile) and !file_exists($lockFile)) {
				clearstatcache();
				$dirStats = stat($dirName);
				if($dirStats['ctime'] < (time() - 60)) {
					$hasFiles = false;
					$dh = safeOpenDir($dirName);
					$myEntry = readdir($dh);
					while($myEntry !== false) {
						if($myEntry != '.' and $myEntry != '..') {
							$hasFiles = true;
							break;
						}
						$myEntry = readdir($dh);
					}
					closedir($dh);
					if($hasFiles) {
		 				$badBatch = new ScannedBatch(
		 					$dirName, $this->db_doc,
		 					$this->deptList, $this->DEFS, $this);
						
						$badBatch->routeToPersonalInbox();
					} else {
						@rmdir($dirName);
					}
				}
			}
		} elseif(is_file($dirName)) {
			clearstatcache();
			$fileStats = stat($dirName);
			if($fileStats['ctime'] < (time() - 10)) {
				$fileType = getMimeType($dirName, $this->DEFS);
				if($fileType == 'application/pdf' or $fileType == 'image/tiff') {
					$destLoc = Indexing::makeUnique ($this->DEFS['DATA_DIR'] .
							'/splitPDF/' . basename($dirName));
					@rename($dirName, $destLoc);
				} elseif (getExtension ($dirName) == 'DAT') {
				 	unlink ($dirName);
				} else {
					$destLoc = Indexing::makeUnique ($this->DEFS['DATA_DIR'] .
							'/client_files/personalInbox/admin/' . basename($dirName));
					@rename($dirName, $destLoc);
				}
			}
		}
		return $batches;
	}
}
?>
