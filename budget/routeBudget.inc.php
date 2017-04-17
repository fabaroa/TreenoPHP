<?PHP
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';
include_once '../lib/random.php';
include_once '../lib/fileFuncs.php';
class routeBudget {
	var $department;
	var $cabinet;
	var $path;
	var $db_dept;
	var $db_doc;
	var $departmentID;
	var $cabinetID;
	var $cabinetIndicesArr;
	var $DELETE_PATH;
	var $DATA_DIR;
	var $USERNAME;
	var $DOCUMENTNAME;

	function routeBudget($myDepartment, $myCabinet, $myPath)
	{
		global $DEFS;
		$this->department = $myDepartment;
		$this->cabinet = $myCabinet;
		$this->path = $myPath;
		$this->DATA_DIR = $DEFS['DATA_DIR'];
		$this->DELETE_PATH = $this->DATA_DIR."/deleteContracts";
		$this->db_dept = getDBObject($this->department);
		$this->db_doc = getDBObject('docutron');
		$match = array ();
		preg_match('/[0-9].*/', $myDepartment, $match);
		if($match) {
			$this->departmentID = $match[0];
		} else {
			$this->departmentID = 0;
		}
		$this->cabinetID = getTableInfo($this->db_dept, 'departments', 
			array('departmentid'), 
			array('real_name' => $this->cabinet), 
			'queryOne'
		);
		$this->cabinetIndicesArr = getCabinetInfo($this->db_dept, $this->cabinet);
	}

	//Moves the selected file to the given location
	//Parameters:
	//	$newFilename: Pass a value if a new filename is desired at the destination
	function moveFileToFolder( $path, $filename, $doc_id, $tabID=null, $newFilename=null )
	{
		global $DEFS;
		$fullPath = $path.'/'.$filename;
		$TMPDIR = $DEFS['TMP_DIR'];
		if( file_exists($fullPath) ) {
			$tempLoc = getUniqueDirectory($TMPDIR);
			if($newFilename != null) {
				$filename = $newFilename;
			}
			$filename = $this->stripEndingNumbers($filename);
			rename($fullPath, $tempLoc.'/'.$filename);

			$fd = fopen($tempLoc."/INDEX.DAT", "w+");
			fwrite($fd, $this->departmentID." ".$this->cabinetID." ".$doc_id." ".$tabID);
			fclose($fd);

			//Set permissions for the folder
			chmod($tempLoc, 0777);
			allowWebWrite($tempLoc,$DEFS);
			$randDir = substr($tempLoc, strrpos($tempLoc, "/"));
			//Move the temp folder to the scan dir
			rename($tempLoc, $this->DATA_DIR."/Scan/$randDir");
		} else {
			$this->errorLog("Failed to move $fullPath to cabinetID: ".$this->cabinetID." docID: $doc_id\n");
		}
	}

	//Removes extra information at the end of the filename that interferes with the file format
	// ie abc.pdf-12323141 should be abc.pdf
	function stripEndingNumbers( $filename )
	{
		$filenameArr = explode('.', $filename);
		$fext = array_pop($filenameArr);
		$file = array_pop($filenameArr);

		$hypPos = strpos($fext, "-");
		if( $hypPos !== false ) {
			$fext = substr($fext, 0, strpos($fext, "-"));
		}
		$fullName = $file.".".$fext;
		return $fullName;
	}

	//If file cannot be processed, route to personal inbox
	function routeToPersonalInbox($filename, $message)
	{
		global $DEFS;
		$inboxDir = $this->DATA_DIR.'/client_files/personalInbox/admin';
		$dateTime = date('Y-m-d-h-i-s', time());
		$newDir = Indexing::makeUnique($inboxDir.'/'.$filename."-$dateTime");
		rename($this->path.'/'.$filename, $newDir);
		chmod($newDir, '0755');
		allowWebWrite($tempLoc,$DEFS);
		$this->errorLog("$filename sent to the admin inbox, $message\n");
	}

	// POSSIBLY OBSOLETE
	// This function is called when contract already exists in Docutron
	//	and needs to be moved out of the scan contract directory
	function routeToDeleteFolder($filename)
	{
		$newDir = Indexing::makeUnique($this->DELETE_PATH.'/'.$filename);
		rename($this->path.'/'.$filename, $newDir);
		$this->errorLog("Duplicate $filename sent to the trash ($newDir). See the administrator to restore the file");
	}

	//Returns a list of files in the given directory
	function getFileList($path)
	{
		$filesArr = array();
		if( $handle = opendir($path) ) {
			while( false !== ($file = readdir($handle)) ) {
				if( $file != "." AND $file != ".." AND is_file($path."/".$file)
					AND $file != "BANCORMS_IDScanOCR.log" AND $file != "Thumbs.db") {
					if( (filemtime($path."/".$file) + 21600) < time() ) {
						$filesArr[] = $file;
					}
				}
			}
		}
		return $filesArr;
	}

	//Returns the list of document indices for the document type
	//	The indices are returned with an empty string value
	function getDocIndices() {
		$documentTypes = getDocumentTypeList($this->department, $this->cabinetID, $this->USERNAME);
		foreach($documentTypes AS $docInfo) {
			if( strcmp($docInfo['realName'], $this->DOCUMENTNAME) == 0 ) {
				$docIndices = $docInfo['indices'];
				foreach(array_keys ($docIndices) as $index) {
					//Set the doc index as empty string
					$docIndices[$index] = "";
				}
				return $docIndices;
			}
		}
		return array ();
	}

	//TODO: Potential flaw if the other documents contain the message
	function documentExists($doc_id, $message) {
$fd = fopen("/tmp/budget", "a+");
fwrite($fd, "In documentExists\n");
		$subfolderIDs = getTableInfo($this->db_dept, $this->cabinet."_files", 
			array('id'), 
			array("doc_id=$doc_id", "filename IS NULL", "deleted=0"),
			'queryCol'
		);
fwrite($fd, "subfolderIDs: ".print_r($subfolderIDs, true));
		$documentList = getDocumentList($this->department, $this->cabinetID, $doc_id, $this->USERNAME);
fwrite($fd, "documentList: ".print_r($documentList, true));
		foreach($documentList AS $documentID => $documentFields) {
			if( in_array($documentID, $subfolderIDs) ) {
				if( strpos($documentFields, $message) !== false ) {
					return true;
				}
			}
		}
fclose($fd);
		return false;
	}

	function stop()
	{
		$this->db_dept->disconnect();
		$this->db_doc->disconnect();
	}

	function errorLog($message)
	{
		error_log($message);
		$insertArr = array(
			"username"	=>	"admin",
			"datetime"	=>	date("Y-m-d G:i:s"),
			"info"		=>	$message,
			"action"	=>	"Route Error"
		);
		$res = $this->db_dept->extended->autoExecute('audit', $insertArr);
		dbErr($res);
	}
}
?>
