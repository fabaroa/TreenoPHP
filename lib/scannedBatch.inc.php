<?php
/**
 * A representation of a batch. Figure out where it needs to go based on a
 * barcode, and route it there directly. 
 * 
 * @package ScannedBatch
 */
class ScannedBatch {
	/**
	 * Full path to batch of files
	 * @var string
	 */
	var $batchLoc;
	
	/**
	 * Object for the RouteDocuments object
	 */
	var $rd;
	
	/**
	 * Department to which batch is to be routed, and where the barcode is
	 * is printed from. If null, it is 'client_files'.
	 * @var string
	 */
	var $department;
	
	/**
	 * Object for the docutron database.
	 * @var object PEAR::DB
	 */
	var $db_doc;
	
	/**
	 * Username who printed the barcode. If null, it is 'admin'.
	 * @var string
	 */
	var $username;

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
	 * Is the Batch (if going to an inbox) staying in a folder?
	 * @var int
	 */
	 var $inFolder;
	
	/**
	 * Constructor
	 * 
	 * @param string $batchLoc Full path to batch of files
	 * @param object $db_doc Object for the docutron database
	 * @param array $deptList A string list of all departments in Docutron
	 * installation.
	 * @param array $DEFS An associative list of stuff from DMS.DEFS
	 */
	function ScannedBatch($batchLoc, &$db_doc, &$deptList, &$DEFS, $rd,
			$inFolder = true) {
		$this->batchLoc = $batchLoc;
		$this->db_dept = null;
		$this->db_doc =& $db_doc;
		$this->deptList =& $deptList;
		$this->DEFS =& $DEFS;
		$this->rd = $rd;
		$this->inFolder = $inFolder;
	}
	
	function audit ($userName, $info, $action, $deleteErr = true) {
		if(!$userName) {
			$userName = 'admin';
		}
		if ($deleteErr) {
			$errStr = $this->getErrStr();
			if ($errStr) {
				$info .= ", ".$errStr;
			}
		}
		$insertArr = array (
			"username"	=> $userName,
			"datetime"	=> date('Y-m-d G:i:s'),
			"info"		=> $info,
			"action"	=> $action
		);
		$res = $this->rd->db_dept->extended->autoExecute('audit',$insertArr);
		dbErr($res);
	}
		
	/**
	 * Delete batch directory after indexed
	 */
	function deleteBatch() {
		delDir($this->batchLoc);
	}
	
	/**
	 * Given a cabinetID and docID, route the batch directly to
	 * the exact folder, 'main' tab.
	 * 
	 * If all fails, route to personal inbox of admin in 'client_files'.
	 * 
	 * @param int $cabinetID ID of cabinet from departments table.
	 * @param int $docID ID of folder from cabinet_files table.
	 */
	function routeToFolder($departmentID, $cabinetID, $docID) {
		$this->setRealDepartmentName($departmentID);
		if($this->department) {
			$cabinet = Barcode::getRealCabinetName($this->rd->db_dept, $cabinetID);
			if($cabinet) {
				if ($this->username) {
					$userName = $this->username;
				} else {
					$userName = 'admin';
				}
				$indices = getCabinetInfo ($this->rd->db_dept, $cabinet);
				$row = getTableInfo ($this->rd->db_dept, $cabinet, $indices, array
						('doc_id' => $docID), 'queryRow');
				if($row){	
					$folderStr = implode (' - ', array_values($row));
					//This audits for the folder route.
					$this->audit ($userName, 'Batch: '.basename($this->batchLoc).
							', folder: '.$folderStr.', cabinet: '.$cabinet.
							', docID: '.$docID.', Barcode: '.$this->barcode,
							'Batch Routed');
					$this->indexAway($cabinet, $docID);
				} else {
					$this->routeToPersonalInbox();
				}
			} else {
				$this->routeToPersonalInbox();
			}
		} else {
			$this->routeToPersonalInbox();
		}
	}
	
	/**
	 * Route the batch directly to the personal inbox.
	 * 
	 * If neither parameter is specified, it goes to the 'admin' inbox in 
	 * department 'client_files'.
	 * 
	 * @param int $departmentID ID of department. If null, it goes to
	 * 'client_files'. 
	 * @param int $userID ID of user from users table. If null, it goes to
	 * 'admin' user.
	 */
	function routeToPersonalInbox($departmentID = null, $userID = null,
			$good = false) {
		error_log("routeToPersonalInbox() departmentID: ".$departmentID.", userID: ".$userID.", good: ".$good);
		if($userID) {
			$this->username = Barcode::getUserName($this->db_doc, $userID);
		}
		if(!$this->username) {
			$this->username = 'admin';
		}
		if($departmentID) {
			$this->setRealDepartmentName($departmentID);
		}
		if(!$this->department) {
			$this->department = 'client_files';
		}
		$inboxDir = $this->DEFS['DATA_DIR'].'/'.$this->department;
		if($userID === 0) {
		   $inboxDir .= '/inbox';
		} else {
		   $inboxDir .= '/personalInbox/'.$this->username;
		}
	
		$error = false;
		if(!file_exists($inboxDir)) {
			$inboxDir = $this->DEFS['DATA_DIR'];
			$inboxDir .= '/client_files/personalInbox/admin';
			$error = true;
		} else {
		}
 		if (!$this->rd->db_dept) {
 			$this->rd->db_dept = getDbObject ($this->department);
 			$this->rd->db_deptName = $this->department;
 			$this->rd->gblStt = new GblStt($this->department, $this->rd->db_doc);
 		} elseif ($this->rd->db_deptName != $this->department) {
 			$this->rd->db_dept->disconnect ();
 			$this->rd->db_dept = getDbObject ($this->department);
 			$this->rd->db_deptName = $this->department;
 			$this->rd->gblStt = new GblStt($this->department, $this->rd->db_doc);
 		}
		$audit = 'Batch: '.basename ($this->batchLoc);
		if( $error ) {
			$audit .= ', destination: Personal Inbox, user: admin';
		} elseif($userID === 0) {
			$audit .= ', destination: Public Inbox';
		} else {
			$audit .= ', destination: Personal Inbox, user: '.$this->username;
		}
		if (isset ($this->barcode)) {
			$audit .= ', Barcode: '.$this->barcode;
		}

		$this->audit ('admin', $audit, 'Batch Routed', $good);
		if (!empty($this->DEFS['EMAIL']) and !$good) {
			error_log ($audit, 1, $this->DEFS['EMAIL'], 
					'From: support@docutronsystems.com');
		}

		$type = $this->rd->gblStt->get('fileFormat');
		if(check_enable("lite",$this->department)) {
			$type = "pdf";
		}

		if($type) {
			$filesList = glob($this->batchLoc."/*");
			$tmpDir = getUniqueDirectory($this->batchLoc); 
			$tiffList = array();
			foreach($filesList AS $file) {
				if(is_file($file)) {
					if(getMimeType($file, $this->DEFS) == 'image/tiff') {
						rename($file,$tmpDir.basename($file));
						$tiffList[] = $tmpDir.basename($file);
					}
				}
			}
			usort($tiffList,"strnatcasecmp");
			if(count($tiffList)) {
				if(!$type || $type == "pdf") {
					createPDFFromTiffs($tiffList,$tmpDir,NULL,$this->batchLoc);
					$this->inFolder = false;
				} else {
					createPDFFromTiffs($tiffList,$tmpDir,NULL,$this->batchLoc,"MTIFF");
					$this->inFolder = false;
				}
			} else {
				delDir($tmpDir);
			}
		}

		if(!$this->inFolder) {
			$dh = opendir($this->batchLoc);
			$myEntry = readdir($dh);
			$files = array ();
			while($myEntry !== false) {
				if($myEntry != '.' && $myEntry != '..' && 
						$myEntry != 'INDEX.DAT') {
					$files[] = $myEntry;
				}
				$myEntry = readdir($dh);
			}
			if(sizeof($files) == 1) {
				$destLoc = $inboxDir.'/'.$files[0];
				$startLoc = $this->batchLoc.'/'.$files[0];
			} else {
				$this->inFolder = true;
			}
		}
		if($this->inFolder) {
			$dateTime = date('Y-m-d-h-i-s', time());
			$destLoc = $inboxDir.'/'.basename($this->batchLoc)."-$dateTime";
			$startLoc = $this->batchLoc;
		}
		$newDir = Indexing::makeUnique($destLoc);
		
		@rename($startLoc, $newDir);
		allowWebWrite ($newDir, $this->DEFS);
		if(file_exists($newDir.'/INDEX.DAT')) {
			unlink($newDir.'/INDEX.DAT');
		}
		if(file_exists($this->batchLoc.'/INDEX.DAT')) {
			unlink($this->batchLoc.'/INDEX.DAT');
			rmdir($this->batchLoc);
		}
	}
	
	/**
	 * Helper function used by $this->routeToSubfolder() and
	 * 
	 * 
	 * This calls the external function copyFiles().
	 * 
	 * @param string $cabinet cabinet name
	 * @param int $docID ID from cabinet_files table.
	 */
	function indexAway($cabinet, $docID) {
		$dirSize = duDir($this->batchLoc);

		if(checkQuota($this->db_doc, $dirSize, $this->department)) {
			$updateArr = array('quota_used' => 'quota_used+'.$dirSize);
			$whereArr = array('real_department' => $this->department);
			updateTableInfo($this->db_doc, 'licenses', $updateArr, $whereArr, 
				1);
			$locStr = getTableInfo($this->rd->db_dept, $cabinet, array('location'),
				array('doc_id' => $docID), 'queryOne');
			$location = $this->DEFS['DATA_DIR'].'/'.
				str_replace(' ', '/', $locStr);
			if (file_exists($this->batchLoc.'/ERROR.txt')) {
				unlink ($this->batchLoc.'/ERROR.txt');
			}

			$type = $this->rd->gblStt->get('fileFormat');
			if(check_enable("lite",$this->department)) {
				$type = "pdf";
			}

			if($type) {
				$filesList = glob($this->batchLoc."/*");
				$tmpDir = getUniqueDirectory($this->batchLoc); 
				$tiffList = array();
				foreach($filesList AS $file) {
					if(is_file($file)) {
						if(getMimeType($file, $this->DEFS) == 'image/tiff') {
							rename($file,$tmpDir.basename($file));
							$tiffList[] = $tmpDir.basename($file);
						}
					}
				}
				usort($tiffList,"strnatcasecmp");
				if(count($tiffList)) {
					if(!$type || $type == "pdf") {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$this->batchLoc);
						$this->inFolder = false;
					} else {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$this->batchLoc,"MTIFF");
						$this->inFolder = false;
					}
				} else {
					delDir($tmpDir);
				}
			}

			copyFiles($this->batchLoc, $location, $docID, $this->rd->db_dept,
				$this->username, $cabinet, $this->department, 'On',
				$this->rd->gblStt, $this->DEFS);
			$this->deleteBatch();
		} else {
			$fd = fopen($this->batchLoc.'/ERROR.txt', 'a+');
			fwrite($fd, "QUOTA CHECK FAILED\n");
			fclose($fd);
			$this->routeToPersonalInbox();
		}
	}
	/**
	 * Helper function used by 
	 * $this->routeToFolder().
	 * 
	 * This calls the external function copyFiles().
	 * 
	 * @param string $cabinet cabinet name
	 * @param int $docID ID from cabinet_files table.
	 */
	function indexAwaySub($cabinet, $docID) {
		$dirSize = duDir($this->batchLoc);

		if(checkQuota($this->db_doc, $dirSize, $this->department)) {
			$updateArr = array('quota_used' => 'quota_used+'.$dirSize);
			$whereArr = array('real_department' => $this->department);
			updateTableInfo($this->db_doc, 'licenses', $updateArr, $whereArr, 
				1);
			$locStr = getTableInfo($this->rd->db_dept, $cabinet, array('location'),
				array('doc_id' => $docID), 'queryOne');
			$location = $this->DEFS['DATA_DIR'].'/'.
				str_replace(' ', '/', $locStr);
			if (file_exists($this->batchLoc.'/ERROR.txt')) {
				unlink ($this->batchLoc.'/ERROR.txt');
			}

			$type = $this->rd->gblStt->get('fileFormat');
			if(check_enable("lite",$this->department)) {
				$type = "pdf";
			}

			if($type) {
				$filesList = glob($this->batchLoc."/*");
				$tmpDir = getUniqueDirectory($this->batchLoc); 
				$tiffList = array();
				foreach($filesList AS $file) {
					if(is_file($file)) {
						if(getMimeType($file, $this->DEFS) == 'image/tiff') {
							rename($file,$tmpDir.basename($file));
							$tiffList[] = $tmpDir.basename($file);
						}
					}
				}
				usort($tiffList,"strnatcasecmp");
				if(count($tiffList)) {
					if(!$type || $type == "pdf") {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$this->batchLoc);
						$this->inFolder = false;
					} else {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$this->batchLoc,"MTIFF");
						$this->inFolder = false;
					}
				} else {
					delDir($tmpDir);
				}
			}

			copyFilesSub($this->batchLoc, $location, $docID, $this->rd->db_dept,
				$this->username, $cabinet, $this->department, 'On',
				$this->rd->gblStt, $this->DEFS);
			$this->deleteBatch();
		} else {
			$fd = fopen($this->batchLoc.'/ERROR.txt', 'a+');
			fwrite($fd, "QUOTA CHECK FAILED\n");
			fclose($fd);
			$this->routeToPersonalInbox();
		}
	}
	
	/**
	 * Set the real department name from the ID.
	 * 
	 * @param int $departmentID ID of department.
	 */
	function setRealDepartmentName($departmentID) {
		$dept = Barcode::getRealDepartmentName($departmentID);
		if(in_array($dept, $this->deptList)) {
			$this->department = $dept;
			if (!$this->rd->db_dept) {
				$this->rd->db_dept = getDbObject ($this->department);
				$this->rd->db_deptName = $this->department;
				$this->rd->gblStt = new GblStt($this->department, $this->rd->db_doc);
			} elseif ($this->rd->db_deptName != $this->department) {
				$this->rd->db_dept->disconnect ();
				$this->rd->db_dept = getDbObject ($this->department);
				$this->rd->db_deptName = $this->department;
				$this->rd->gblStt = new GblStt($this->department, $this->rd->db_doc);
			}
		} else {
			$this->department = null;
		}
	}
	
	function routeBatch() {
	}
}
?>
