<?PHP
//This is the class structure used for the Canada crawler script
class newFolderObj {
	var $department;
	var $cabinet;
	var $cabinetID;
	var $indicesArr;
	var $docID;
	var $fullPath;
	var $fileObjArr;

	function newFolderObj($department, $cabinet, $cabinetID, $fullPath)
	{
		$this->department = $department;
		$this->cabinet = $cabinet;
		$this->cabinetID = $cabinetID;
		$this->fullPath = $fullPath;
		$this->fileObjArr = array();
	}

}

class fileObj {
	var $filename;
	var $filePath;
	var $extension;

	function fileObj($filename, $filePath, $extension)
	{
		$this->filename = $filename;
		$this->filePath = $filePath;
		$this->extension = $extension;
	}
}

?>
