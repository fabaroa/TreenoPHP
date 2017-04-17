<?PHP
include_once '../budget/routeBudget.inc.php';
include_once '../lib/webServices.php';

class routeBudgetLicense extends routeBudget {
	var $fileArr;
	var $DOCUMENTNAME;
	var $USERNAME;
	var $db_doc;
	
	function routeBudgetLicense($myDepartment, $myCabinet, $path, $db_doc)
	{
		$this->DOCUMENTNAME = "document2"; //document2 == Licensing
		$this->USERNAME = "admin";
		routeBudget::routeBudget($myDepartment, $myCabinet, $path);
		$this->fileArr = $this->getFileList($path);
		$this->db_doc = $db_doc;
	}

	function route()
	{
print_r($this->fileArr);
		foreach($this->fileArr AS $file) {
			$filenameArr = explode('.', $file);
			$filename = array_pop($filenameArr);
			$filename = array_pop($filenameArr);
			$filename = $this->stripDupNumbers($filename);

			$doc_id = getTableInfo($this->db_dept, $this->cabinet, 
				array('doc_id'), 
				array('License_Number' => $filename), 
				'queryOne',
				array('doc_id' => 'DESC')
			);
echo "doc_id: $doc_id\n";
			if($doc_id == NULL) {
				$this->routeToPersonalInbox($file, "License Number could not find a matching contract");
			} else {

				$docIndices = $this->getDocIndices();
				$docIndices['f1'] = "LICENSE";
				$subfolderID = createDocumentInfo($this->department, $this->cabinetID, $doc_id,
					$this->DOCUMENTNAME, $docIndices, $this->USERNAME, $this->db_doc);
echo "subfolderID: $subfolderID\n";
				$this->moveFileToFolder($this->path, $file, $doc_id, $subfolderID, $filename);
			}
		}
	}

	//Strips the numbers off the end of the filename
	//ie. 123 (1).bmp should be 123.bmp
	function stripDupNumbers($filename)
	{
		$filename = preg_replace("/\s\([0-9]\)/", "", $filename);
		if($filename == "") {
			$filename = "ServerGeneratedFilename";
		}
		return $filename;
	}
}
?>
