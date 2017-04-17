<?php
include_once '../db/db_common.php';
include_once '../lib/inbox.php';
include_once '../lib/utility.php';

class delegate {
	//Name of the user's personal inbox
	var $username;
	//Holds the db object of the $db_name
	var $db_object;
	//Disable sentry to stop delegateObj from
	//	populating the arrays
	var $enable;
	//Delegate list of the user, holds both
	//	$delegateFolder & $delegateFiles
	var $delegateList;
	//TODO: Remove this class variable
	//Array of the full path with filename
	var $filePaths; 
	var $path;
	var $start;
	var $count;

	//Takes a username and the db_object  as the parameter
	//	Params may be null if not coming from the personal inbox
	function delegate($path=null,$username=null, $db_obj=null, $enable=1,$start=0,$count=0) {
		if($username == null || $db_obj == null || !$enable) {
			$this->enable = 0;
		} else {
			$this->enable = 1;
		}
		$this->username = $username;
		$this->db_object = $db_obj;
		$this->path = $path;

		//Initialize to nothing
		$this->delegateList = array();
		$this->filePaths = array();

		$this->start = ($start - 1) * $count;
		$this->count = $count;
		//Build the delegate object from the db
		$this->buildDelegateList();
	}

	//Call build delegate object if enabled
	function buildDelegateList() {
		if($this->enable) {
			$this->_buildDelegateList();
		}
	}

	//Private function of class
	//Build the delegate object
	function _buildDelegateList() {
		$db_object = $this->db_object;
		$sArr = array('inbox_delegation.list_id', 'folder');
		$wArr = array("(delegate_username='".$this->username."' OR delegate_owner='".$this->username."')",
					"inbox_delegation.list_id=inbox_delegation_file_list.list_id");
		$tArr = array("inbox_delegation","inbox_delegation_file_list");
		$oArr = array('folder' => 'ASC');
		$list = getTableInfo($db_object,$tArr,$sArr,$wArr,'getAssoc',$oArr,$this->start,$this->count);

		if(count($list)) {
			$sArr = array('inbox_delegation.*','folder','filename','inbox_delegation_file_list.id AS file_id');
			$wArr = array("inbox_delegation.list_id IN(".implode(",",array_keys($list)).")",
						"inbox_delegation.list_id=inbox_delegation_file_list.list_id");
			$tArr = array("inbox_delegation","inbox_delegation_file_list");
			$oArr = array('folder' => 'ASC', 'filename' => 'ASC');
			$list = getTableInfo($db_object,$tArr,$sArr,$wArr,'queryAll',$oArr);
		}

		$delegateList = array();
		$filePaths = array();
		$list = $this->displaySort($list);
		foreach($list AS $row) {
			$id = $row['list_id'];
			$filename = $row['filename'];
			$folder = $row['folder']; 
			$owner = $row['delegate_owner']; 
			$path = $this->path.$owner."/";
			if($folder) {
				$filePaths[] = $path.$folder;
				$path .= $folder."/";
			}
			if( $this->_filesExist($path, $filename) ) {
				$filePaths[] = $path.$filename;
//				$stats = getFileStats( array($filename), $path );
				$stats = array('urlName'=>rawurlencode($filename), 'name'=>$filename );
				$fileInfo = array_merge($row, $stats);
				$delegateList[$id][] = $fileInfo;
			} else { //Remove from inbox_delegation
				$this->_removeDelegateItem($id);
			}
		}
		//Set class variable with new values
		$this->delegateList = $delegateList;
		$this->filePaths = $filePaths;
	}

	//Helper function for displaySort
	//	sorts an array by the given column name in the assoc array
	function delSort($list, $column) {
		$tempArr = array();
		foreach($list as $row) {
			$col = $row[$column];
			$tempArr[$col] = $row;
		}
		uksort($tempArr, "strnatcasecmp");

		$retArr = array();
		foreach($tempArr As $col => $row) {
			$retArr[] = $row;
		}
		return $retArr;
	}

	//sorts the given list of delegated items to be folder first, files second
	//	in strnatcasecmp format
	function displaySort($list) {
		$retArr = array();
		$folderArr = array();
		$fileArr = array();
		foreach($list AS $row) {
			$folder = $row['folder'];
			if( strcmp($folder, '') == 0 ) {
				$fileArr[] = $row;
			} else {
				$folderArr[$folder][] = $row;
			}
		}

		foreach($folderArr AS $folder => $folderRow) {
			$resultArr = $this->delSort($folderRow, 'filename');
			$retArr = array_merge($retArr, $resultArr);
		}
		$resultArr = $this->delSort($fileArr, 'filename');
		$retArr = array_merge($retArr, $resultArr);

		return $retArr;
	}

	function _sortDelegateList() {
		uksort($this->delegateList,'strnatcasecmp');
		foreach($this->delegateList AS $folder => $folderArr) {
			uksort($this->delegateList[$folder],'strnatcasecmp');
			foreach($folderArr AS $owner => $fileArr) {
					uksort($this->delegateList[$folder][$owner],'strnatcasecmp');
					$this->delegateList[$folder][$owner] = array_values($this->delegateList[$folder][$owner]);
			}
		}

		$min = ($this->start - 1) * $this->count;
		$max = $min + $this->count;
		$ct = 1;
		$pageList = array();

		foreach($this->delegateList AS $folder => $folderArr) {
			if($folder != "zzz_empty") {
				if($ct > $min && $ct <= $max) {
					$pageList[$folder] = $folderArr;
				}
				$ct++;
			} else {
				foreach($folderArr AS $owner => $fileArr) {
					foreach($fileArr AS $info) {
						if($ct > $min && $ct <= $max) {
							$pageList[$folder][$owner][] = $info;
							$ct++;
						} else {
							$ct++;
						}
					}
				}
			}
		}
		$this->delegateList = $pageList;

	}

	function countDelegateList() {
		$sArr = array('COUNT(id)');
		$wArr = array("delegate_username = '".$this->username."' OR delegate_owner = '".$this->username."'");
		$ct = getTableInfo($this->db_object,"inbox_delegation",$sArr,$wArr,'queryOne');

		return $ct;
	}

	//Takes the given path to see if it is in the dekegate list
	//Returns true if found or false if not
	function findInDelegateList($fullPath) {
		if( in_array($fullPath, $this->filePaths) ) {
			return true;
		}
		return false;
	}

	//add folder/file to delegate list
	function addToDelegateList($del_user, $del_owner,$folder,$fileArr, $status, $comments) {
		$db_object = $this->db_object;

		lockTables($this->db_object,array('inbox_delegation'));
		$list_id = $this->getNextListID();

		$insertFiles = array(
			'delegate_username' => $del_user,
			'delegate_owner'    => $del_owner,
			'list_id'           => (int)$list_id,
			'status'            => $status,
			'comments'          => $comments,
			'dtime'             => date('Y-m-d G:i:s')
		);
		$res = $db_object->extended->autoExecute('inbox_delegation', $insertFiles);
		dbErr($res);
		unlockTables($this->db_object);

		$insertFile = array('list_id' => (int)$list_id);
		if($folder) {
			$insertFile['folder'] = $folder;
		}
							
		foreach($fileArr AS $file) {
			$insertFile['filename'] = $file;
			$res = $db_object->extended->autoExecute('inbox_delegation_file_list', $insertFile);
			dbErr($res);
		}

		//$sArr = array('MAX(id)');
		//$delegateID = getTableInfo($this->db_object,'inbox_delegation',$sArr,array(),'queryOne');
		$this->_auditDelegation($list_id,'File Delegated');

		//Rebuild delegate list after adding new delegates
		//$this->buildDelegateList();
	}

	function getNextListID() {
		$sArr = array('MAX(list_id)+1');
		$max = getTableInfo($this->db_object,'inbox_delegation',$sArr,array(),'queryOne');

		if(!$max) {
			$max = 1;
		}
		return $max;
	}

	//Filing away from the inbox
	function moveFromInbox($user, &$db_raw, $filesArr) {
		$filesSelected = $this->getPathAndFileByIDs($filesArr);
		$orderType = getOrderSett($user->db_name,$_GET['cab'], $db_raw);
		if(!$orderType) {
			$filesSelected = array_reverse($filesSelected,true);
		}
		$mess = "";
		foreach($filesSelected AS $delegateID => $fileArr) {
			$path = $this->path.$fileArr['path']."/";
			$mess = moveFromInbox($path, $user, $db_raw, $fileArr['files'], $this->db_object);
			if(strcmp("Files successfully moved", $mess) == 0) {
				$this->_auditDelegation($delegateID,'Delegated File Moved');
				$this->_removeDelegateItem($delegateID);
			//If success message does not match return error
			} else {
				break;
			}
		}
		return $mess;
	}

	//Update the database to changes made to delegate items
	function updateDelegateItem($delegateTable,$delegateID,$updateArr,$whereArr) {
		$db_obj = $this->db_object;
		updateTableInfo($db_obj,$delegateTable,$updateArr,$whereArr);
		$this->_auditDelegation($delegateID, 'Delegated File Updated');
	}

	function deleteFromInboxDelegation($delegateIDArr) {
		foreach($delegateIDArr AS $delegateID) {
			$this->_auditDelegation($delegateID,'Delegated File Deleted');
			$this->_removeDelegateItem($delegateID);
		}
		$this->buildDelegateList();
	}

	function _auditDelegation($delegateID,$action) {
		$cols = array('delegate_id',
				'delegate_username',
				'delegate_owner',
				'folder',
				'filename',
				'status',
				'comments',
				'date_delegated',
				'date_completed',
				'action');
		$sArr = array('inbox_delegation.id',
				'delegate_username',
				'delegate_owner',
				'folder',
				'filename',
				'status',
				'comments',
				'dtime',
				dbConcat(array("'".date('Y-m-d G:i:s')."'")),
				dbConcat(array("'".$action."'")));
		$wArr = array('inbox_delegation.list_id ='.(int)$delegateID,
					'inbox_delegation.list_id=inbox_delegation_file_list.list_id');
		$tArr = array('inbox_delegation','inbox_delegation_file_list');
		insertFromSelect($this->db_object,'inbox_delegation_history',$cols,$tArr,$sArr,$wArr);
	}
	
	//TODO: rebuilding the delegatelist each time is inefficient
	//Removes the delegate item from the database based on delegateID
	function _removeDelegateItem($delegateID) {
		$wArr = array('list_id' => (int)$delegateID);
		deleteTableInfo($this->db_object,'inbox_delegation',$wArr);
		deleteTableInfo($this->db_object,'inbox_delegation_file_list',$wArr);
	}

	//Test that files actually exist at delegated path
	//Test that folders have files
	//Remove from delegate list if not true
	function _filesExist($path, $filename) {
		$fullPath = $path."/".$filename;
		//Fail test if not file or dir
		if( !file_exists($fullPath) )
			return false;
		
		if( is_file($fullPath) )
			return true;
		//If not file, has to be dir
		$handle = opendir($path."/".$filename);
		while(false !== ($file = readdir($handle))) {
			if($file != "." && $file != "..")
				return true;
		}
		//Return false if there are no files in dir
		return false;
	}

	//Need a security check for deleting, etc.
	function _securityCheck() {

	}

	//Sorts the delegate List, folders, files for display purposes
	function sortDelegateList(/*$sortField, $sortDir*/) {
		$tempFolders = array();
		$tempFiles = array();
		foreach($this->delegateList AS $delItem) {
			$file = $delItem['filename'];
			$fullPath = $delItem['folder']."/".$file;
			if( is_file($fullPath) ) {
				$tempFiles[] = $delItem;
			} elseif( $file != "." AND $file != ".." ) {
				$tempFolders[] = $delItem;
			}
		}
		//sortFileList($tempFolders, $sortField, $sortDir);
		//sortFileList($tempFiles, $sortField, $sortDir);
		usort($tempFolders,"strnatcasecmp");
		$this->delegateList = array_merge($tempFolders, $tempFiles);
	}

/* GET FUNCTIONS */
	function getDelegateList(/*$sortField, $sortDir*/) {
		//$this->sortDelegateList(/*$sortField, $sortDir*/);
		return $this->delegateList;
	}

	//WARNING: may become out of sync if user edit folders
	//	Probably have to remove this function
	function getFilePaths() {
		return $this->filePaths;
	}

	//Returns the paths and filenames in an array[$delgateID]
	//	array[delegateID] = array(folder, file) 
	function getPathAndFileByIDs($delegateIDArr) {
		$retArr = array();
		foreach($this->delegateList AS $fileArr) {
			foreach($fileArr AS $file) {
				if( in_array($file['list_id'], $delegateIDArr) ) {
					$retArr[$file['list_id']]['path'] = $file['delegate_owner']."/".$file['folder'];
					$retArr[$file['list_id']]['files'][] = $file['filename'];
				}
			}
		}
		return $retArr;
	}

	//Given an inbox delegation table id
	//	Returns the folder path and file name
	//	Generally used for folder viewing
	function getFullPathByID($delegateID) {
		$path = "";
		foreach($this->delegateList AS $delItem) {
			if($delItem['id'] == $delegateID) {
				$path = $delItem['folder']."/".$delItem['filename'];
				break;
			}
		}
		return $path;
	}
}
?>
