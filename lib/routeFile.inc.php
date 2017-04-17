<?php
class RouteFile {
	var $bcinfo;
	var $db_dept;
	var $db_doc;
	var $department;
	var $filename;
	var $fileString;
	var $username;
	var $deptList;
	var $DEFS;
	var $tab;
	
	function RouteFile( $bcinfo, $db_dept, $db_doc, $department, $filename, $fileString, $username, $DEFS ){
		$this->bcinfo = $bcinfo;
		$this->db_dept = $db_dept;
		$this->db_doc = $db_doc;
		$this->department = $department;
		$this->filename = $filename;
		$this->fileString = $fileString;
		$this->username = $username;
		$this->db_deptName = $department;
		$this->deptList = getTableInfo($this->db_doc, 'licenses',
			array('real_department'), array(), 'queryCol');
		$this->DEFS = $DEFS;
		$this->tab = NULL;
	}

	function audit ($userName, $info, $action, $deleteErr = true) {
		if(!$userName) {
			$userName = 'admin';
		}
		$insertArr = array (
			"username"	=> $userName,
			"datetime"	=> date('Y-m-d G:i:s'),
			"info"		=> $info,
			"action"	=> $action
		);
		$res = $this->db_dept->extended->autoExecute('audit',$insertArr);
		dbErr($res);
	}
		
	/**
	 * Given a cabinetID and docID, route the file directly to
	 * the exact folder, 'main' tab.
	 * 
	 * If all fails, route to personal inbox of admin in 'client_files'.
	 * 
	 * @param int $cabinetID ID of cabinet from departments table.
	 * @param int $docID ID of folder from cabinet_files table.
	 */
	function routeToFolder($departmentID, $cabinetID, $docID, $fileString, $filename) {
		$this->setRealDepartmentName($departmentID);
		if($this->department) {
			$cabinet = Barcode::getRealCabinetName($this->db_dept, $cabinetID);
			if($cabinet) {
				if ($this->username) {
					$userName = $this->username;
				} else {
					$userName = 'admin';
				}
				$indices = getCabinetInfo ($this->db_dept, $cabinet);
				$row = getTableInfo ($this->db_dept, $cabinet, $indices, array
						('doc_id' => $docID), 'queryRow');
				if($row){	
					$folderStr = implode (' - ', array_values($row));
					//This audits for the folder route.
					$this->audit ($userName, 'File: '.$this->filename.
							', folder: '.$folderStr.', cabinet: '.$cabinet.
							', docID: '.$docID.', Barcode: '.$this->bcinfo,
							'File Routed');
					$this->indexAway($cabinet, $docID);
					return 'good';
				} else {
					$this->routeToPersonalInbox();
					return 'bad doc_id not found';
				}
			} else {
				$this->routeToPersonalInbox();
				return 'bad cabinet not found';
			}
		} else {
			$this->routeToPersonalInbox();
			return 'bad department not found';
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
		//error_log("routeFile.inc.php routeToPersonalInbox() departmentID: ".$departmentID.", userID: ".$userID.", good: ".$good);
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
 		if (!$this->db_dept) {
 			$this->db_dept = getDbObject ($this->department);
 			$this->db_deptName = $this->department;
 			$this->gblStt = new GblStt($this->department, $this->db_doc);
 		} elseif ($this->db_deptName != $this->department) {
 			$this->db_dept->disconnect ();
 			$this->db_dept = getDbObject ($this->department);
 			$this->db_deptName = $this->department;
 			$this->gblStt = new GblStt($this->department, $this->db_doc);
 		}
		$audit = 'File: '.$this->filename;
		if( $error ) {
			$audit .= ', destination: Personal Inbox, user: admin';
		} elseif($userID === 0) {
			$audit .= ', destination: Public Inbox';
		} else {
			$audit .= ', destination: Personal Inbox, user: '.$this->username;
		}
		if (isset ($this->barcode)) {
			$audit .= ', Barcode: '.$this->bcinfo;
		}

		$this->audit ($this->username, $audit, 'File Routed', $good);
		if (!empty($this->DEFS['EMAIL']) and !$good) {
			error_log ($audit, 1, $this->DEFS['EMAIL'], 
					'From: support@treenosoftware.com.com');
		}
		$ct = 1;
		$filename = $this->filename;
		while(file_exists($inboxDir.'/'.$filename)){
			$fpieces = explode(".",$this->filename);
			$fpieces[sizeof($fpieces)-2] .= "-".$ct;
			$filename = implode(".",$fpieces);
			$ct++;
		}
		$destLoc = $inboxDir.'/'.$filename;
		$fp = fopen( $destLoc, 'w+' );
		fwrite( $fp, $this->fileString );
		fclose( $fp );
		allowWebWrite ($destLoc, $this->DEFS);
	}
	/**
	 * Helper function used by $this->routeToSubfolder() and
	 * $this->routeToFolder().
	 * 
	 * This calls the external function copyFiles().
	 * 
	 * @param string $cabinet cabinet name
	 * @param int $docID ID from cabinet_files table.
	 */
	function indexAway($cabinet, $docID) {
		$filesize = strlen($this->fileString);
		if(checkQuota($this->db_doc, $filesize, $this->department)) {
			$updateArr = array('quota_used' => 'quota_used+'.$filesize);
			$whereArr = array('real_department' => $this->department);
			updateTableInfo($this->db_doc, 'licenses', $updateArr, $whereArr, 
				1);
			$locStr = getTableInfo($this->db_dept, $cabinet, array('location'),
				array('doc_id' => $docID), 'queryOne');
			$location = $this->DEFS['DATA_DIR'].'/'.
				str_replace(' ', '/', $locStr);
			return writeFileFromString($location, 
								$docID, 
								$this->db_dept,
								$this->username, 
								$cabinet, 
								$this->department,
								$this->DEFS,
								$this->fileString,
								$this->filename,
								$this->tab);
			
		} else {
			return $this->routeToPersonalInbox();
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
			if (!$this->db_dept) {
				$this->db_dept = getDbObject ($this->department);
				$this->db_deptName = $this->department;
				$this->gblStt = new GblStt($this->department, $this->db_doc);
			} elseif ($this->db_deptName != $this->department) {
				$this->db_dept->disconnect ();
				$this->db_dept = getDbObject ($this->department);
				$this->db_deptName = $this->department;
				$this->gblStt = new GblStt($this->department, $this->db_doc);
			}
		} else {
			$this->department = null;
		}
	}
	
	function routeBatch() {
		if(substr_count($this->bcinfo, ' ') == 0) {
			$barcodeStr = $this->getRealBarcode();		
		} else {
			$barcodeStr = $this->bcinfo;
		}
		//error_log("routeFile.inc.php routeBatch() barcodeStr: ".$barcodeStr);
		$barcodeArr = explode(' ', $barcodeStr);
		$barcodeCt = count($barcodeArr);

		if($barcodeCt > 1) {
			if($barcodeArr[0] == 'W') {
				return 'bad routeToNewWorkflow not implemented';
				$this->routeToNewWorkflow((int) $barcodeArr[1],
					(int) $barcodeArr[2], (int) $barcodeArr[3],
					(int) $barcodeArr[4]);
				
			} elseif($barcodeArr[0] == 'WF') {
				return 'bad routeToExistingWorkflow not implemented';
				$this->routeToExistingWorkflow((int) $barcodeArr[1],
					(int) $barcodeArr[2], (int) $barcodeArr[3],
					(int) $barcodeArr[4], (int) $barcodeArr[5]);
								
			} elseif(is_numeric($barcodeArr[0]) and 
				is_numeric($barcodeArr[1])) {
					
				if($barcodeArr[1] == 0) {
					return $this->routeToPersonalInbox((int) $barcodeArr[0],
						(int) $barcodeArr[2], true);
						
				} else {
					if($barcodeCt == 2) {
						$this->routeToCabinetIndexing((int) $barcodeArr[0],
							(int) $barcodeArr[1]);
							
					} elseif($barcodeCt == 3) {
						return 'bad routeToFolder not implemented';
						$this->routeToFolder((int) $barcodeArr[0],
							(int) $barcodeArr[1], (int) $barcodeArr[2]);
							
					} else {
						return $this->routeToSubfolder((int) $barcodeArr[0],
							(int) $barcodeArr[1], (int) $barcodeArr[2],
							(int) $barcodeArr[3]);
							
					}
				}
			} else {
				$this->routeToPersonalInbox();
				return 'bad barcode cannot determine barcode type';	
			}
		} else {
			$this->routeToPersonalInbox();
			return 'bad barcode count is < 1 which means there is no barcode given';
		}
	}
	/**
	 * Method to get the real barcode from the database, given a reconciliation
	 * ID.
	 * 
	 * Do not pass a non-reconciliation ID (such as an already converted barcode
	 * value or a barcode that did not go through reconciliation to this method.
	 * 
	 * @return string Converted barcode that can be decoded into department,
	 * cabinet, etc.  
	 */
	function getRealBarcode() {
		$barcodeID = (int) $this->barcode;
		if($barcodeID == 0) {
			return 0;
		}
		$barcodeInfo = getTableInfo($this->db_doc, 'barcode_reconciliation',
			array(), array('id' => $barcodeID), 'queryRow');

		if($barcodeInfo) {
			$barcodeLookup = array (
				'id'			=> $barcodeID,
				'department'	=> $barcodeInfo['department']
			);
			$res = $this->db_doc->extended->autoExecute('barcode_lookup', $barcodeLookup);
			dbErr($res,0);
			$this->department = $barcodeInfo['department'];
			if (!$this->db_dept) {
				$this->db_dept = getDbObject ($this->department);
				$this->db_deptName = $this->department;
				$this->gblStt = new GblStt($this->department, $this->db_doc);
			} elseif ($this->db_deptName != $this->department) {
				$this->db_dept->disconnect ();
				$this->db_dept = getDbObject ($this->department);
				$this->db_deptName = $this->department;
				$this->gblStt = new GblStt($this->department, $this->db_doc);
			}
			$this->username = $barcodeInfo['username'];
			$this->insertIntoBarcodeHistory($barcodeID, $barcodeInfo);
			deleteTableInfo($this->db_doc, 'barcode_reconciliation',
				array ('id' => $barcodeID));
				
			$realBarcode = $barcodeInfo['barcode_info'];
		} else {
			$department = getTableInfo($this->db_doc, 'barcode_lookup',
				array ('department'), array ('id' => $barcodeID), 'queryOne');
				
			if($department) {
				$this->department = $department;
				if (!$this->db_dept) {
					$this->db_dept = getDbObject ($this->department);
					$this->db_deptName = $this->department;
					$this->gblStt = new GblStt($this->department, $this->db_doc);
				} elseif ($this->db_deptName != $this->department) {
					$this->db_dept->disconnect ();
					$this->db_dept = getDbObject ($this->department);
					$this->db_deptName = $this->department;
					$this->gblStt = new GblStt($this->department, $this->db_doc);
				}
				$barcodeInfo = getTableInfo($this->db_dept, 'barcode_history',
					array (), array ('barcode_rec_id' => $barcodeID), 'queryRow');
					
				$this->username = $barcodeInfo['username'];
				$this->insertIntoBarcodeHistory($barcodeID, $barcodeInfo);
				$realBarcode = $barcodeInfo['barcode_info'];		
			} else {
				$realBarcode = '';
			}
		}
		return $realBarcode;
	}
	
	/**
	 * Given a cabinetID, docID, and subfolderID, route the batch directly to
	 * the exact tab.
	 * 
	 * If all fails, route to personal inbox of admin in 'client_files'.
	 * 
	 * @param int $cabinetID ID of cabinet from departments table.
	 * @param int $docID ID of folder from cabinet_files table.
	 * @param int $subfolderID ID of tab in the exact folder.
	 */
	function routeToSubfolder($departmentID, $cabinetID, $docID, $subfolderID) {
		$this->setRealDepartmentName($departmentID);
		if($this->department) {
			$cabinet = Barcode::getRealCabinetName($this->db_dept, $cabinetID);
			if($cabinet) {
				$tab = getTableInfo($this->db_dept, $cabinet.'_files',
					array('subfolder'), array('id' => $subfolderID), 'queryOne');
				if($tab){	
					$this->tab = $tab;
					$indices = getCabinetInfo ($this->db_dept, $cabinet);
					$row = getTableInfo ($this->db_dept, $cabinet, $indices, array
							('doc_id' => $docID), 'queryRow');
					if($row){
						$folderStr = implode (' - ', array_values($row));
						if ($this->username) {
							$userName = $this->username;
						} else {
							$userName = 'admin';
						}
						$this->audit ($userName, 'File Name: '.basename($this->filename).
								', Folder: '.$folderStr.', Cabinet: '.$cabinet.
								', docID: '.$docID.', Subfolder: '.$tab.', Barcode: '.
								$this->bcinfo, 'File Routed');
						$this->indexAway($cabinet, $docID);
						return "good";
					} else {
						$this->routeToPersonalInbox();
						return "bad folder $docID does not exist";	
					}
				} else {
					$this->routeToPersonalInbox();
					return "bad $tab does not exist";
				}
			} else {
				$this->routeToPersonalInbox();
				return "bad $cabinet does not exist";
			}
		} else {
			$this->routeToPersonalInbox();
			return "bad {$this->department} does not exist";
		}
	}


}
?>
