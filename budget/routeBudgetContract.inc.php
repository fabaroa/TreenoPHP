<?PHP
include_once '../budget/routeBudget.inc.php';
include_once '../xmlrpc/xmlrpc.inc.php';
include_once '../lib/cabinets.php';
include_once '../lib/webServices.php';

class routeBudgetContract extends routeBudget {

	var $fileArr;
	var $xmlrpcObj;
	var $db_doc;



	function routeBudgetContract($myDepartment, $myCabinet, $path, $db_doc)
	{
$fd = fopen("/tmp/budget", "a+");
fwrite($fd, "In routeBudgetContract\n");
		$this->DOCUMENTNAME = "document1"; //document1 == Contracts
		$this->USERNAME = "admin";
		routeBudget::routeBudget($myDepartment, $myCabinet, $path);
		$this->fileArr = $this->getFileList($path);
fwrite($fd, "fileArr: ".print_r($this->fileArr, true));
		$this->xmlrpcObj =  new xmlrpc();
fwrite($fd, "after creating xmlrpc object\n");
fclose($fd);
		$this->db_doc = $db_doc;
	}

	function route()
	{
$fd = fopen("/tmp/budget", "a+");
fwrite($fd, "In route\n");
		foreach($this->fileArr AS $file) {
			//parses the filename into its separate parts
			$filenameArr = explode('.', $file);
			$filename = array_pop($filenameArr);
			$filename = array_pop($filenameArr);
			$filenameArr = explode('-', $filename);
			$fileType = $filenameArr[1];
			$filename = $filenameArr[0];
fwrite($fd, "filename: $filename\n");
fwrite($fd, "filetype: $fileType\n");
			//Determine action based on character after contract number
			$fileType = strtolower($fileType);
//TODO: TEMP contract number being used
//$filename = "3973800";
			switch($fileType) {
				case "w":
					echo "case w\n";
					$this->execOpenAndClosed($file, $filename, "WRITTEN");
					break;
				case "c":
					echo "case c\n";
					$this->execOpenAndClosed($file, $filename, "CLOSED");
					break;
				case "m":
					echo "case m\n";
					$this->execAddition($file, $filename, "MODIFY");
					break;
				case "x":
					echo "case x\n";
					$this->execAddition($file, $filename, "EXCHANGE");
					break;
				case "a":
					echo "case a\n";
					$this->execAddition($file, $filename, "ADDITION");
					break;
				case "v":
					echo "case v\n";
					$this->execOpenAndClosed($file, $filename, "VOID");
					break;
				default:
					echo "default case\n";
					$this->routeToDeleteFolder($file);
//					$this->routeToPersonalInbox($file, "Invalid file name");
			}
		}
fclose($fd);
	}

	//case "w"
	function execWritten($file, $contractNum) {
$fd = fopen("/tmp/budget", "a+");
fwrite($fd, "In execWritten\n");
		$doc_id = getTableInfo($this->db_dept, $this->cabinet, 
			array('doc_id'), 
			array('ID' => $contractNum, 'deleted' => (int)0), 
			'queryOne'
		);
fwrite($fd, "doc_id: $doc_id\n");

		if( $doc_id > 0 ) {
fwrite($fd, "doc_id is not null or not empty string\n");
			//Duplicate written file, need to move to delete folder for now
//			routeToDeleteFolder($file);
			$this->routeToPersonalInbox($file, "Folder already exists, not creating duplicate");
			return;
		}

		//$contractNum does not exist in docutron, add
		$contactInfo = $this->xmlrpcObj->getInfo('contactInfo', $contractNum, 'int');
fwrite($fd, print_r($contactInfo, true));
			
		if( count($contactInfo) == 3 ) { //the xml results is array(last, first, lic#)
			$insertArr = array($contractNum, date("Y-m-d"), $contactInfo[2]);
			$cabinetIndices = getCabinetInfo($this->db_dept, $this->cabinet);

			$doc_id = createFolderInCabinet($this->db_dept,$this->USERNAME,
				$this->department,$this->cabinet,$insertArr,$cabinetIndices);

			if($doc_id <= 0) {
				//die; create folder failed
				$this->routeToPersonalInbox($file, "Create folder failed");
				return;
			}

fwrite($fd, "new docid: $doc_id\n");
			$docIndices = $this->getDocIndices();
			$docIndices['f1'] = "WRITTEN";
			$subfolderID = createDocumentInfo($this->department, $this->cabinetID, $doc_id,
				$this->DOCUMENTNAME, $docIndices, $this->USERNAME, $this->db_doc);
fwrite($fd, "new subfolder: $subfolderID\n");
			$this->moveFileToFolder($this->path, $file, $doc_id, $subfolderID);
		} else {
echo "no contact info found\n";
			$this->routeToPersonalInbox($file, "Contact Info did not exist to create the folder");
		}
		
fclose($fd);
	}

	//case "w" and "c"
	function execOpenAndClosed($file, $contractNum, $type) {
		$doc_id = getTableInfo($this->db_dept, $this->cabinet, 
			array('doc_id'), 
			array('ID' => $contractNum, 'deleted' => (int)0), 
			'queryOne'
		);

		if($doc_id == null) {
			//$contractNum does not exist in docutron, add
			$contactInfo = $this->xmlrpcObj->getInfo('licenseInfo', $contractNum, 'int');
			
			if( count($contactInfo) == 2 ) { //the xml results is array(last, first, lic#)
				$insertArr = array($contractNum, date("Y-m-d"), $contactInfo[0]);
				$cabinetIndices = getCabinetInfo($this->db_dept, $this->cabinet);

				$doc_id = createFolderInCabinet($this->db_dept,$this->USERNAME,
					$this->department,$this->cabinet,$insertArr,$cabinetIndices);

				if($doc_id <= 0) {
					//die; create folder failed
					$this->routeToPersonalInbox($file, "Create folder failed");
					return;
				}
			} else {
echo "no contact info found\n";
				$this->routeToPersonalInbox($file, "Contact Info did not exist to create the folder");
				return;
			}
		}


		if($doc_id == null) {
			$this->routeToPersonalInbox($file, "Folder does not exist to place file");
		} elseif( $this->documentExists($doc_id, "$type") ) {
//			if($type == "WRITTEN" OR $type == "VOID") {
				$this->routeToDeleteFolder($file);	
//			} else {
//				$this->routeToPersonalInbox($file, "$type file already exists");
//			}
		} else {
			//xml stuff
			$docIndices = $this->getDocIndices();
			$docIndices['f1'] = "$type";
			$subfolderID = createDocumentInfo($this->department, $this->cabinetID, $doc_id,
				$this->DOCUMENTNAME, $docIndices, $this->USERNAME, $this->db_doc);
			$this->moveFileToFolder($this->path, $file, $doc_id, $subfolderID);
		}
	}

	//case "m" and "x" and "a"
	function execAddition($file, $contractNum, $type) {
		$doc_id = getTableInfo($this->db_dept, $this->cabinet, 
			array('doc_id'), 
			array('ID' => $contractNum, 'deleted' => (int)0), 
			'queryOne'
		);
		if($doc_id == null) {
			$this->routeToPersonalInbox($file, "Folder does not exist to place file");
		} else {
			//xml stuff
			$docIndices = $this->getDocIndices();
			$docIndices['f1'] = "$type";
			$subfolderID = createDocumentInfo($this->department, $this->cabinetID, $doc_id,
				$this->DOCUMENTNAME, $docIndices, $this->USERNAME, $this->db_doc);
			$this->moveFileToFolder($this->path, $file, $doc_id, $subfolderID);
		}
	}
}
?>

