<?PHP
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';
include_once '../lib/webServices.php';
include_once '../lib/documentObj.inc.php';

class folderObj {
	var $db_dept;
	var $department;
	var $cabinet;
	var $cabinetID;
	var $doc_id;
	var $location;
	var $documentObjArr;
	var $mainFilesArr;

	function folderObj($department, $cabinetID, $doc_id, $db_dept=null)
	{
		global $DEFS;
		if( $db_dept == null ) {
			$db_dept = getDbObject($department);
		}
		$this->db_dept = $db_dept;
		$this->department = $department;
		$this->cabinetID = $cabinetID;
		$this->doc_id = $doc_id;
		$this->cabinet = getTableInfo($db_dept, 'departments', array('real_name'), array('departmentid' => $cabinetID), 'queryOne');
		$this->location = $DEFS['DATA_DIR']."/".str_replace(" ","/",getFolderLocation($db_dept,$this->cabinet,$doc_id));
		$this->buildFilesList($this->db_dept, $this->department, $this->cabinet, $this->doc_id);
	}

	function buildFilesList($db_dept, $department, $cabinet, $doc_id)
	{
		$documentsArr = array();
		$mainFilesArr = array();
		$subfolderList = getTableInfo($db_dept, $cabinet."_files", array('id'), array("doc_id=$doc_id", "filename IS NULL"), 'queryCol');
		foreach($subfolderList AS $subfolderID) {
			$documentsArr[] = new documentObj($department, $cabinet, $doc_id, $subfolderID, $db_dept);
		}
		$this->documentObjArr = $documentsArr;

		$mainFiles = getTableInfo($db_dept, $cabinet."_files", array(), array("doc_id=$doc_id", "subfolder IS NULL", "filename IS NOT NULL"));
		while($row = $mainFiles->fetchRow()) {
			$mainFilesArr[] = $row;
		}
		$this->mainFilesArr = $mainFilesArr;
	}
}
?>
