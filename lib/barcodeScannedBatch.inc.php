<?php
require_once '../lib/scannedBatch.inc.php';
require_once '../lib/versioning.php';
class BarcodeBatch extends ScannedBatch {
	/**
	 * Exact string of what is displayed under barcode
	 * @var string
	 */
	var $barcode;
	
	function BarcodeBatch($batchLoc, $barcode, &$db_doc, &$deptList, &$DEFS,
			$rd, $userName = '', $department = '', $deptAdmin = '',
			$inFolder = true) {
		ScannedBatch::ScannedBatch($batchLoc, $db_doc, $deptList, $DEFS, $rd,
			$inFolder);
		$this->barcode = $barcode;
		$this->username = $userName;
		if(!$userName) {
			$this->username = $deptAdmin;
		}
		$this->department = $department;
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
		error_log("test ats:".$this->department);
		if ($this->department=="client_files156")
		{
			$barcodeInfo = getTableInfo($this->db_doc, 'barcode_reconciliation_ATS',
				array(), array('id' => $barcodeID), 'queryRow');
			$ATSFlag=1;
		}
		if (!isset($barcodeInfo) || !$barcodeInfo)
		{
			$barcodeInfo = getTableInfo($this->db_doc, 'barcode_reconciliation',
				array(), array('id' => $barcodeID), 'queryRow');
			$ATSFlag=0;
		}

		if($barcodeInfo) {
			$barcodeLookup = array (
				'id'			=> $barcodeID,
				'department'	=> $barcodeInfo['department']
			);
			if ($ATSFlag)
			{
				$res = $this->db_doc->extended->autoExecute('barcode_lookup_ATS', $barcodeLookup);
			} else {
				$res = $this->db_doc->extended->autoExecute('barcode_lookup', $barcodeLookup);
			}
			dbErr($res,0);
			$this->department = $barcodeInfo['department'];
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
			$this->username = $barcodeInfo['username'];
			$this->insertIntoBarcodeHistory($barcodeID, $barcodeInfo);
			if ($ATSFlag)
			{
				deleteTableInfo($this->db_doc, 'barcode_reconciliation_ATS',
					array ('id' => $barcodeID));
			} else {
				deleteTableInfo($this->db_doc, 'barcode_reconciliation',
					array ('id' => $barcodeID));
			}
				
			$realBarcode = $barcodeInfo['barcode_info'];
		} else {
			if ($this->department=="client_files156")
			{
				$department = getTableInfo($this->db_doc, 'barcode_lookup_ATS',
					array ('department'), array ('id' => $barcodeID), 'queryOne');
			}
			if (!isset($department) || !$department)
			{
				$department = getTableInfo($this->db_doc, 'barcode_lookup',
					array ('department'), array ('id' => $barcodeID), 'queryOne');
			}
				
			if($department) {
				$this->department = $department;
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
				$barcodeInfo = getTableInfo($this->rd->db_dept, 'barcode_history',
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
	 * Given an array which is a row from the reconcilation table or history
	 * table and an ID, insert a row in the history table with that same
	 * information.
	 * 
	 * @param int $barcodeID reconciliation ID from barcode
	 * @param array $barcodeInfo information from reconciliation table/history
	 * table.
	 * barcode_field cannot be null
	 */
	function insertIntoBarcodeHistory($barcodeID, $barcodeInfo) {
		if( $barcodeInfo['barcode_field']=='' ){
			$barcodeInfo['barcode_field']='-';
		}
		$barcodeHistory = array (
			'barcode_rec_id'	=> $barcodeID,
			'barcode_info'		=> $barcodeInfo['barcode_info'],
			'username'			=> $barcodeInfo['username'],
			'cab'				=> $barcodeInfo['cab'],
			'barcode_field'		=> $barcodeInfo['barcode_field'],
			'date_printed'		=> $barcodeInfo['date_printed'],
			'split_type' 		=> $barcodeInfo['split_type'],
			'compress'	 		=> $barcodeInfo['compress'],
			'delete_barcode'	=> $barcodeInfo['delete_barcode'],
			'date_processed'	=> date('Y-m-d G:i:s'),
			'description'		=> 'Scanned'
		);
		$res = $this->rd->db_dept->extended->autoExecute('barcode_history', $barcodeHistory);
		dbErr($res);
	}

	/**
	 * Route a batch to a folder in a cabinet and assign workflow to the new 
	 * folder.
	 * 
	 * If all fails, route to personal inbox of admin in 'client_files'.
	 * 
	 * @param int $departmentID ID of department (can be parsed to real name)
	 * @param int $cabinetID ID of cabinet from departments table
	 * @param int $workflowID ID of workflow from wf_defs table
	 * @param int $userID ID of user from users table
	 */
	function routeToNewWorkflow($departmentID, $cabinetID, $workflowID,
		$userID) {
			
		$this->setRealDepartmentName($departmentID);
		if($this->department) {
			$cabinet = Barcode::getRealCabinetName($this->rd->db_dept, $cabinetID);
			if($cabinet) {
				$DO_user = DataObject::factory('users', $this->db_doc);
				$DO_user->get($userID);
				if($DO_user->username) {
					if($this->checkWorkflowID($workflowID)) {
						$docID = $this->createEmptyFolder($cabinet,
							$DO_user->username);
						$workflowName = getTableInfo ($this->rd->db_dept, 'wf_defs',
								array ('defs_name'), array ('id' => (int)
									$workflowID), 'queryOne');
						$this->audit ($DO_user->username, 'Batch: '.
							basename($this->batchLoc).', cabinet: '.$cabinet.
							', docID: '.$docID.', Workflow: '.$workflowName.
							', Barcode: '.$this->barcode,
							'Batch Routed To Workflow');
						$this->indexAway($cabinet, $docID);
						$this->assignWorkflow($cabinet, $docID, $workflowID,
							$DO_user->username);
						
					} else {
						$this->routeToPersonalInbox();
					}
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
	 * Route a batch to a folder in a cabinet and assign workflow to the
	 * existing folder.
	 * 
	 * If all fails, route to personal inbox of admin in 'client_files'.
	 * 
	 * @param int $departmentID ID of department (can be parsed to real name)
	 * @param int $cabinetID ID of cabinet from departments table
	 * @param int $workflowID ID of workflow from wf_defs table
	 * @param int $userID ID of user from users table
	 */
	function routeToExistingWorkflow($departmentID, $cabinetID, $docID,
		$workflowID, $userID) {
			
		$this->setRealDepartmentName($departmentID);
		if($this->department) {
			$cabinet = Barcode::getRealCabinetName($this->rd->db_dept, $cabinetID);
			if($cabinet) {
				$DO_user = DataObject::factory('users', $this->db_doc);
				$DO_user->get($userID);
				if($DO_user->username) {
					if($this->checkWorkflowID($workflowID)) {
						$indices = getCabinetInfo ($this->rd->db_dept, $cabinet);
						$row = getTableInfo ($this->rd->db_dept, $cabinet,
								$indices, array ('doc_id' => $docID), 'queryRow');
						$folderStr = implode (' - ', array_values($row));
						$workflowName = getTableInfo ($this->rd->db_dept, 'wf_defs',
								array ('defs_name'), array ('id' => (int)
									$workflowID), 'queryOne');
						$this->audit ($DO_user->username, 'Batch: '.
							basename($this->batchLoc).', cabinet: '.$cabinet.
							', folder: '.$folderStr.', docID: '.$docID.
							', Workflow: '.$workflowName.', Barcode: '.
							$this->barcode, 'Batch Routed To Workflow');
						$this->indexAway($cabinet, $docID);
						$this->assignWorkflow($cabinet, $docID, $workflowID,
							$DO_user->username);
						
					} else {
						$this->routeToPersonalInbox();
					}
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
	 * Assign given workflow to folder.
	 * 
	 * @param string $cabinet valid cabinet name
	 * @param int $docID valid folder ID
	 * @param string $workflowID ID of valid workflow
	 * @param string $userName valid username from users table
	 */
	function assignWorkflow($cabinet, $docID, $workflowID, $userName) {
		$wfDocID = addToWorkflow($this->rd->db_dept, $workflowID, $docID, 0,
			$cabinet, $userName);
		$cabDispName = getTableInfo($this->rd->db_dept, 'departments', array('departmentname'),
			array('real_name' => $cabinet), 'queryOne');
		$stateNodeObj = new stateNode($this->rd->db_dept, $this->department,
			$userName, $wfDocID, $workflowID, $cabinet, $cabDispName, $docID, $this->rd->db_doc);
		$stateNodeObj->notify();
		if ($stateNodeObj->message) {
			$myMsg = $stateNodeObj->message;
		} else {
			$myMsg = 'Workflow started on docID: ' . $docID . ' in cabinet: ' . $cabinet;
		}
		$this->audit ($userName, 'Workflow', $myMsg, false);
	}
	
	/**
	 * Helper method to create an empty folder in a cabinet.
	 * 
	 * This is for workflow.
	 * 
	 * @param string $cabinet Cabinet where the folder should be created
	 * @param string $userName username who creates folder, for auditing
	 * purposes
	 * @return int folder ID for empty folder
	 */
	function createEmptyFolder($cabinet, $userName) {
		$cabinetIndices = getCabinetInfo($this->rd->db_dept, $cabinet);
		$indexArr = array ();
		for($i = 0; $i < count($cabinetIndices); $i++) {
			$indexArr[] = '';
		}
		$tempTable = '';
		$docID = createFolderInCabinet($this->rd->db_dept, $this->rd->gblStt,
				$this->db_doc, $userName, $this->department, $cabinet,
				$indexArr, $cabinetIndices, $tempTable);
		
		return $docID;
	}
	
	/**
	 * Helper method to make sure workflow exists and is its own parent_id.
	 * 
	 * @param int $workflowID ID from wf_defs table
	 * @return boolean
	 */
	function checkWorkflowID($workflowID) {
		$parentID = getTableInfo($this->rd->db_dept, 'wf_defs', array('parent_id'), 
			array('id' => $workflowID), 'queryOne');
		
		if($parentID == $workflowID) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Given a departmentID and a cabinetID, route the batch to the indexing
	 * directory for the correct department and cabinet.
	 * 
	 * If all fails, route to personal inbox of admin in 'client_files'.
	 * 
	 * @param int $departmentID ID of department (can be parsed to real name)
	 * @param int $cabinetID ID of cabinet from departments table
	 */
	function routeToCabinetIndexing($departmentID, $cabinetID) {
		$this->setRealDepartmentName($departmentID);
		if($this->department) {
			$cabinet = Barcode::getRealCabinetName($this->rd->db_dept, $cabinetID);
			if($cabinet) {
				$destDir = $this->DEFS['DATA_DIR'].'/';
				$destDir .= $this->department.'/indexing/'.$cabinet.'/';
				$newDir = Indexing::makeUnique ($destDir.
						basename( $this->batchLoc));
				//This audits for the indexing directory route.
				if ($this->username) {
					$userName = $this->username;
				} else {
					$userName = 'admin';
				}
				$errStr = $this->getErrStr();
				$this->audit ($userName, 'Batch: '.basename($this->batchLoc).
						'cabinet: '.$cabinet.', Barcode: '.$this->barcode,
						'Batch Routed To Indexing Directory', $errStr);

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

				@rename($this->batchLoc, $newDir);
				allowWebWrite ($newDir, $this->DEFS);	
				if(file_exists($newDir.'/INDEX.DAT')) {
					unlink($newDir.'/INDEX.DAT');
				}
			} else {
				$this->routeToPersonalInbox();
			}
		} else {
			$this->routeToPersonalInbox();
		}
	}

	function getErrStr () {
		$errStr = '';
		if (file_exists($this->batchLoc.'/ERROR.txt')) {
			$errStr = str_replace ("\n", ", ", file_get_contents
					($this->batchLoc.'/ERROR.txt'));
			unlink ($this->batchLoc.'/ERROR.txt');
		}
		return $errStr;

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
			$cabinet = Barcode::getRealCabinetName($this->rd->db_dept, $cabinetID);
			if($cabinet) {
				$allFiles = array ();				
				$dh = safeOpenDir($this->batchLoc);
				$myEntry = readdir($dh);
				while($myEntry !== false) {
					if(is_file($this->batchLoc.'/'.$myEntry) and
						$myEntry != 'INDEX.DAT' and $myEntry != '.' and
						$myEntry != '..' and $myEntry != 'ERROR.txt') {
							
						$allFiles[] = $myEntry;
					}
					$myEntry = readdir($dh);			
				}
				closedir($dh);
				$tab = getTableInfo($this->rd->db_dept, $cabinet.'_files',
					array('subfolder'), array('id' => $subfolderID), 'queryOne');
				if($tab){	
					if (!file_exists($this->batchLoc.'/'.$tab)) {
						$e = mkdir($this->batchLoc.'/'.$tab);
					}
					foreach($allFiles as $myFile) {
						@rename($this->batchLoc.'/'.$myFile,
							$this->batchLoc.'/'.$tab.'/'.$myFile);
							
					}
					$indices = getCabinetInfo ($this->rd->db_dept, $cabinet);
					$row = getTableInfo ($this->rd->db_dept, $cabinet, $indices, array
							('doc_id' => $docID), 'queryRow');
					if($row){
						$folderStr = implode (' - ', array_values($row));
						if ($this->username) {
							$userName = $this->username;
						} else {
							$userName = 'admin';
						}
						$this->audit ($userName, 'Batch: '.basename($this->batchLoc).
								', Folder: '.$folderStr.', Cabinet: '.$cabinet.
								', docID: '.$docID.', Subfolder: '.$tab.', Barcode: '.
								$this->barcode, 'Batch Routed');
						$this->indexAwaySub($cabinet, $docID);
					} else {
						$this->routeToPersonalInbox();
					}
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

	function routeVersionFile($departmentID, $cabinetID, $docID, $subfolderID = '') 
	{
		$this->setRealDepartmentName($departmentID);
		$user = new user();

		if ($this->username) {
			$userName = $this->username;
		} else {
			$userName = 'admin';
		}
		
		$user->username = $userName;
		
		if($this->department) 
		{
			$cabinet = Barcode::getRealCabinetName($this->rd->db_dept, $cabinetID);
			if($cabinet) {
				$allFiles = array ();				
				$dh = safeOpenDir($this->batchLoc);
				$myEntry = readdir($dh);
				$fileID = $fileName = '';
				while($myEntry !== false) {
					if(is_file($this->batchLoc.'/'.$myEntry) and
						$myEntry != 'INDEX.DAT' and $myEntry != '.' and
						$myEntry != '..' and $myEntry != 'ERROR.txt') {
							
						$allFiles[] = $myEntry;
						$fileName = $myEntry;
					}
					$myEntry = readdir($dh);			
				}
				closedir($dh);
				//original file path
				$filePath = $this->batchLoc.'/'.$fileName;
				if(is_numeric($subfolderID) && $fileName !='') {
					$tab = getTableInfo($this->rd->db_dept, $cabinet.'_files',
						array('subfolder'), array('id' => $subfolderID), 'queryOne');
					if($tab){	
						if (!file_exists($this->batchLoc.'/'.$tab)) {
							mkdir($this->batchLoc.'/'.$tab);
						}
						foreach($allFiles as $myFile) {
							@rename($this->batchLoc.'/'.$myFile,
								$this->batchLoc.'/'.$tab.'/'.$myFile);		
						}
						//if there is a tab.
						$filePath = $this->batchLoc.'/'.$tab.'/'.$fileName;
						$fileID = getTableInfo($this->rd->db_dept, $cabinet.'_files',
							array('id'), array("subfolder='$tab'", 'filename is not null', 
							"doc_id='$docID'"), 'queryOne');
						//	error_log("Found file $fileID to version dept=".$departmentID."* cabinet=".$cabinet."* filePath=".$filePath."* tab=".$tab."* subfolderID=".$subfolderID."* batchLoc".$this->batchLoc);
					}
				}else {
					$fileID = getTableInfo($this->rd->db_dept, $cabinet.'_files',
							array('id'), array('filename is not null', "doc_id='$docID'"), 'queryOne');
				}
				if(!is_numeric($fileID) || $fileID < 1 || $tab=="Disposition_Report")
				{
					if(is_numeric($subfolderID)) {
						$this->routeToSubfolder($departmentID, $cabinetID, $docID, $subfolderID);
						//error_log("route to subfolder");
					}
					else {
						$this->routeToFolder($departmentID, $cabinetID, $docID);
						//error_log("route to folder");
					}
					 //error_log("No File");
				} else {
					//error_log("$docID :: $fileID");
					//find the first file in this subfolder, automatically assumes
					//you are trying to version only the first file.  Any other files
					//will be ignored.
					$parentID = getParentID($cabinet, $fileID, $this->rd->db_dept);
					$fileID = getRecentID($cabinet, $parentID, $this->rd->db_dept);
					$who = whoLocked($cabinet, $parentID, $this->rd->db_dept);
					
					$who = strlen($who) ? $who : $userName;
					$fileArr = getCheckInDetails ($cabinet, 
						$parentID, $this->rd->db_dept, $who, 
						$fileName);
					//error_log(print_r($fileArr, true));
					@rename($filePath, $fileArr['path']);
					@unlink($this->batchLoc.'/INDEX.DAT');
					@unlink($this->batchLoc);
					$return = checkInVersion ($this->rd->db_dept, $fileArr, 
						$cabinet, $parentID, $user, $this->db_doc);
					//error_log($return);
				}
				return true;
			} else {
				$this->routeToPersonalInbox();
				//error_log("No CABINET");
			}	
		} else {
			$this->routeToPersonalInbox();
			//error_log("No DEPT");
		}
		
	}

	/**
	 * Route batch to where it is supposed to go.
	 * 
	 * If it cannot be determined where it should go, put it in the personal
	 * inbox of admin in 'client_files'.
	 */
	function routeBatch() {
		if(substr_count($this->barcode, ' ') == 0) {
			$barcodeStr = $this->getRealBarcode();		
		} else {
			$barcodeStr = $this->barcode;
		}
		$barcodeArr = explode(' ', $barcodeStr);
		$barcodeCt = count($barcodeArr);

		if($barcodeCt > 1) {
			if($barcodeArr[0] == 'W') {
				$this->routeToNewWorkflow((int) $barcodeArr[1],
					(int) $barcodeArr[2], (int) $barcodeArr[3],
					(int) $barcodeArr[4]);
				
			} elseif($barcodeArr[0] == 'WF') {
				$this->routeToExistingWorkflow((int) $barcodeArr[1],
					(int) $barcodeArr[2], (int) $barcodeArr[3],
					(int) $barcodeArr[4], (int) $barcodeArr[5]);
			
								
			} elseif($barcodeArr[0] == 'V') {
				$this->routeVersionFile((int) $barcodeArr[1],
							(int) $barcodeArr[2], (int) $barcodeArr[3],
							(isset($barcodeArr[4]) ? (int) $barcodeArr[4] : ''));
					
			} elseif(is_numeric($barcodeArr[0]) && is_numeric($barcodeArr[1])) {
          $deptID = isset($barcodeArr[0]) ? (int) $barcodeArr[0] : '';
          if($deptID == '168' && isset($barcodeArr[3]) && is_numeric($barcodeArr[3]))
 	        {
            $this->routeVersionFile((int) $barcodeArr[0],(int) $barcodeArr[1], (int) $barcodeArr[2],(isset($barcodeArr[3]) ? (int) $barcodeArr[3] : ''));
          } else if($barcodeArr[1] == 0) {
						$this->routeToPersonalInbox((int) $barcodeArr[0],
							(int) $barcodeArr[2], true);
							
					} else {
						if($barcodeCt == 2) {
							$this->routeToCabinetIndexing((int) $barcodeArr[0],
								(int) $barcodeArr[1]);
								
						} elseif($barcodeCt == 3) {
							$this->routeToFolder((int) $barcodeArr[0],
								(int) $barcodeArr[1], (int) $barcodeArr[2]);
								
						} else {
							$this->routeToSubfolder((int) $barcodeArr[0],
								(int) $barcodeArr[1], (int) $barcodeArr[2],
								(int) $barcodeArr[3]);
								
						}
					}
			} else {
				$this->routeToPersonalInbox();
			}
		} else {
			$this->routeToPersonalInbox();
		}
	}
}
?>
